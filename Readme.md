
# Implementação
1. Validar interfaces/IP forwarding
2. Fazer o CLIENT usar o FIREWALL como rota para a DMZ
3. Testar comunicação sem bloqueios
4. Criar política DROP
5. Liberar ESTABLISHED,RELATED
6. Liberar Cliente → Nginx :80
7. Liberar Cliente → Nginx :443
8. Bloquear demais acessos
9. Testar curl/nmap
10. Adicionar logs
11. Documentar matriz de regras

# Passo 1: Dar um build para a criação do Dockerfile
# Passo 2: Rodar o comando docker exec farmview-firewall ip -4 addr
-- Nesse caso, o container criado para ser o firewall, foi dado o nome de farmview-firewall
--Apos esse comando, verificamos os IPS correspondente, definido no docker-compose: 172.30.0.2 e 172.31.0.2

Docker exec: Executa um comando dentro do container que ja esta em execução
farmview-firewall: Refere-se ao nome do container que estamos criando
ip -4 addr: Vamos verificar os endereço IPV4 que estao no gerenciamento da interface

# Passo 3 Rodar o comando docker exec farmview-firewall cat /proc/sys/net/ipv4/ip_forward

-- Vai exibir o conteudo de um arquivo que esta dentro das configurações do kernel que fica responsável pelo os encaminhamentos dos pacotes ipv4, espera que o resultado seja: 1, assim, identificamos que o resultado deu certo, encaminhamento dos pacotes foram habilitados com sucesso.

# Passo 4 Fazer o cliente utilziar o firewall como rota para DMZ    

-- Utilizando o comando: docker exec -it farmview-cliente sh
Onde nada mais que vamos entrar do container farmview-cliente (Sendo o container representante do usuário final) de forma interativa no terminal "-it", sh, servindo para abrir o terminal.

Dentro do container, utilizamos o comando: ip route para verificarmos o retorno dos ip: "default via 172.30.0.1 dev eth0 
172.30.0.0/24 dev eth0 proto kernel scope link src 172.30.0.10", Sendo o IP 172.30.0.0 o gateway padrão criado pelo Docker, após isso, criamos a rota DMZ, executando o comando: ip route add 172.31.0.0/24 via 172.30.0.2. 

Ip route serve para gerenciar as rotas da rede, em seguida, o add 172.31.0.0/24 adiciona uma rede de destino, que no caso seria o DMZ passando como via o ip do firewall. Ou seja, quando o usuário quiser acessar a rede 172.31.0.0/24 seus pacotes devem ser enviado para o firewall 172.30.0.2.

Para teste da configuração acima, vamos usar a linha de comando: ip route get 172.31.0.3, obtendo essa linha como resposta: *172.31.0.3 via 172.30.0.2 dev eth0 src 172.30.0.10 uid 0* Cofirmando que o usuário escolhe rota do firewall para chegar no nginx


# Passo 5 Teste de comunicação entre CLiente -> Firewall -> NGINX

docker exec farmview-firewall iptables -L FORWARD -n -v

Iremos verificar as listas de regras dentro desse container, exibindo os endereços IP, mostrando informações adicionais.

Retorno: *Chain FORWARD (policy ACCEPT 0 packets, 0 bytes)
 pkts bytes target     prot opt in     out     source               destination.*  Isso deixa claro que ainda não há nenhuma política de restrição

 Agora iremos entrar no modo interativo para fazer a requisição via curl: docker exec -it farmview-cliente sh

 A primeiro momento, foi retornado um erro:  # curl -I --max-time 5 http://172.31.0.3
"curl: (28) Connection timed out after 5001 milliseconds"

Onde, foi recebido um timeout de 5 segundos por não encontrar obter nenhuma resposta.

Vamos conferir oq eu pode ter dado errado, "docker exec farmview-client ip rout get 172.31.0.3", consultando qual caminho o cliente utilizaria para chegar no container NGINX, o resultado obtido com essa consulta foi de: 172.31.0.3 via 172.30.0.2 dev eth0 src 172.30.0.10 uid 0, significando que o próximo salto seria o firewall "172.30.0.2" e o ip de origem "172.30.0.10" uid 0 mostra que foi o usuário root que executou o comando para verificação.

Também foi testado o comando: "docker exec farmview-cliente" ping -c 3 172.30.0.2, retornando 
PING 172.30.0.2 (172.30.0.2): 56 data bytes
64 bytes from 172.30.0.2: seq=0 ttl=64 time=0.124 ms
64 bytes from 172.30.0.2: seq=1 ttl=64 time=0.149 ms
64 bytes from 172.30.0.2: seq=2 ttl=64 time=0.086 ms
Logo, o cliente consegue alcançar o container do firewall

Posteriomente a isso, foi testado o comando docker exec farmview-client ip rout get 172.31.0.3 para verificar se o cliente continua utilizando o firewall como rota DMz, a resposta obita foi: 172.31.0.3 via 172.30.0.1 dev eth0 src 172.30.0.10, que significa que o cliente esta tentanddo acessar o NGINX pelo gateway padrão do Docker ao enves do firewall.

Para corrigir o problema, foi necessário configurar novamente a rota do cliente por meio do comando `docker exec farmview-client ip route replace 172.31.0.0/24 via 172.30.0.2`, definindo que as requisições destinadas à rede DMZ deveriam passar pelo firewall. Após isso, foi utilizado o comando `ip route` para verificar a alteração, confirmando que o cliente estava utilizando o endereço `172.30.0.2` como próximo salto.

Com a rota corrigida, foi realizado um novo teste de comunicação com o Nginx utilizando o comando `docker exec farmview-client curl -I --max-time 5 http://172.31.0.3`. Entretanto, a requisição continuou apresentando timeout, indicando que, mesmo com o roteamento configurado, a comunicação ainda não estava sendo concluída.

Diante disso, foram realizados testes diretamente no firewall para verificar sua comunicação com o Nginx. Os comandos `ping` e `curl` apresentaram resultados positivos, confirmando que o firewall conseguia alcançar o servidor. Também foi verificado se o encaminhamento de pacotes estava habilitado, utilizando o comando `cat /proc/sys/net/ipv4/ip_forward`, que retornou o valor `1`.

Para investigar melhor o problema, foi utilizada a ferramenta `tcpdump`, permitindo acompanhar os pacotes que passavam pelas interfaces de rede do firewall. Durante os testes, foi possível identificar que os pacotes enviados pelo cliente chegavam à interface `eth0` e eram encaminhados pela `eth1` em direção ao Nginx. Entretanto, não foram identificadas respostas correspondentes, levantando a possibilidade de um problema no caminho de retorno dos pacotes.

Para solucionar essa situação, foi implementada uma regra NAT com `MASQUERADE`, utilizando o comando:

`docker exec farmview-firewall iptables -t nat -A POSTROUTING -s 172.30.0.0/24 -d 172.31.0.0/24 -o eth1 -j MASQUERADE`

Essa regra faz com que os pacotes encaminhados para a DMZ utilizem o endereço do firewall (`172.31.0.2`) como origem, permitindo que o Nginx envie suas respostas de volta ao firewall, que as direciona ao cliente.

Após essa configuração, foi realizado novamente o teste de comunicação com o Nginx, obtendo o retorno `HTTP/1.1 302 Found`, indicando que a requisição foi recebida e que a aplicação estava redirecionando o acesso para a página de login. Dessa forma, foi possível corrigir o problema e estabelecer a comunicação entre o cliente e o Nginx através do firewall.

Logo após corrigir a comunicação entre o cliente e o nginx, foi necessário adicionar o comando `ip rout replace 172.31.0.0/24 via 172.30.0.2` no container client. Essa alteração tem a finalidade de configurar automaticamente a rota para a rede DMZ através do firewall, evitando a necessidade de executar o comando de forma manual sempre que o container for recriado, em seguida, o container foi restartado.

# Passo 6 -> Automatizar as regras do NAT
 
 No arquivo criado dentro da pasta docker/firewall entrypoint.sh, foi adicionado as regras: 
 
echo ""
echo "Configurando NAT para a DMZ..."

INTERFACE_DMZ=$(ip -4 route show 172.31.0.0/24 | awk '{print $3}')

echo "Interface da DMZ: $INTERFACE_DMZ"

iptables -t nat -C POSTROUTING -s 172.30.0.0/24 -d 172.31.0.0/24 -o "$INTERFACE_DMZ" -j MASQUERADE 2>/dev/null || \
iptables -t nat -A POSTROUTING -s 172.30.0.0/24 -d 172.31.0.0/24 -o "$INTERFACE_DMZ" -j MASQUERADE

echo "Regras NAT:"
iptables -t nat -L POSTROUTING -n -v

exec tail -f /dev/null

servindo como, ao acessar a tabela NAT, verifica se a regra ja existe: `-C POSTROUTING`, verificando se a regra ja existe, caso não exista, o comando `-A POSTROUTING` é acionado, -s 172.30.0.0/25 identifica a rede de origem, verificando onde o cliente esta, -d 172.31.0.0/24 identificando a rede de destino, sendo ela a DMZ, -o eth1 aplicando a regra aos pacotes que saem pela interface conectada a DMZ e -j MASQUERADE para realizar a tradução do endereço de origem, portanto, por ser um arquivo que faz parte da imagem, é recriado a imagem do firewall

# Passo 7 - Criar as regras para liberação

*Permissao 1*
Permitir conexões ja estabelecida e seus pacotes de resposta:
`docker exec farmview-firewall iptables -A FORWARD -m conntrack --ctstate ESTABLISHED,RELATED -j ACCEPT`

-A FORWARD: Adicionar regra à cadeia que fica responsável pelo encaminhamento dos pacotes

-m conntrack: Ativa o módulo de acompanhamento de conexão

-ctstate ESTABLISHED,RELATED: Identifica os pacotes de conexões estabelecida e relacionadas

Essa regra permite que o cliente solicite uma pagina ao NGINX, sendo o cliente 172.30.0.10 e o NGINX 172.31.0.3


*Permissao 2*
Permitir HTTP do cliente para o NGINX - Porta 80
`docker exec farmview-firewall iptables -A FORWARD -s 172.30.0.10 -d 172.31.0.3 -p tcp --dport 80 -m conntrack --ctstate NEW -j ACCEPT`

Permite que o cliente inicie conexões HTTP com o servidor NGINX

-s 172.30.0.10: Origem da requisição, cliente
-d 172.31.0.3: Destino da requisição, NGINX
-p tcp: Utiliza o protocolo TCP
--dport 80: Porta de destino HTTP
--cstate NEW: Permite iniciar novas conexões
-j ACCEPT: Autoriza conexão

*Permissão 3*

Permitir HTTPS do cliente para o NGINX
`docker exec farmview-firewall iptables -A FORWARD -s 172.30.0.10 -d 172.31.0.3 -p tcp --dport 443 -m conntrack --ctstate NEW -j ACCEPT`

Possui a mesma permissão da *permissao 2*, mas libera a porta 443, utilizada pelo HTTPS: Comunicação protegida por TLS

# Passo 8: Ativar o DROP nas requisições de acesso

*Permissao 4*

Descarte de todo pacote que não corresponde as permissões criadas
`docker exec farmview-firewall iptables -P FORWARD DROP`

Para validar a permissão 4, foi realizado o seguinte teste: `docker exec farmview-client curl -I --max-time 5 http://172.31.0.3`

O resultado obtido:  
% Total    % Received % Xferd  Average Speed  Time    Time    Time   Current
                                 Dload  Upload  Total   Spent   Left   Speed
  0      0   0      0   0      0      0      0                              0HTTP/1.1 302 Found
  0      0   0      0   0      0      0      0                              0
Server: nginx/1.27.5
Date: Sat, 10 Oct 2026 20:32:33 GMT
Content-Type: text/html; charset=UTF-8
Connection: keep-alive
X-Powered-By: PHP/8.2.34
Set-Cookie: PHPSESSID=aknrprvoo2f5bmp0qaheg88os4; path=/
Expires: Thu, 19 Nov 1981 08:52:00 GMT
Cache-Control: no-store, no-cache, must-revalidate
Pragma: no-cache
Location: http://172.31.0.3/login

- explicaçao: HTTP/1.1 302 Found indica que o servidor recebeu a requisição e respondeu para a página de login, conforme esperado, ou seja, a comunicação da porta 80 não foi bloqueada.


Verificação de uma porta não autorizada é bloqueada
`docker exec farmview-client nmap -Pn -p 80,8080 172.31.0.3                       `

Resultado obtido: 
Starting Nmap 7.99 ( https://nmap.org ) at 2026-10-10 20:35 +0000
Nmap scan report for 172.31.0.3
Host is up (0.00026s latency).

PORT     STATE    SERVICE
80/tcp   open     http
8080/tcp filtered http-proxy

Nmap done: 1 IP address (1 host up) scanned in 1.81 seconds

Teste utilizado com o NMAP, verificando o estado dos dispositivos na rede, onde teve o objetivo de verificar a comunicação HTTP pela porta 80 e restringindo o acesso à porta 8080 que não foi autorizada nas regras.


# Passo 9: Log com limitação de frequência

*Permissão 5*
`docker exec farmview-firewall iptables -A FORWARD -m limit --limit 2/min --limit-burst 3 -j LOG --log-prefix "FARMVIEW_DROP: " --log-level 4`

-A FORWARD: Adiciona a regra ao final da cadeia FORWARD
-m limit: Ativa o módulo de limitação de frequência
--limit 2/min: Limitação dos registros a uma média de 2 por minutos
--limit-burst 3: Permite a uma quantidade de até 3 registros iniciais
-j LOG: Registra informações sobres os pacotes
--log-prefix "FARMVIEW_DROP": Adiciona uma identificação aos registros
--log-level 4: Define o nível de registros com warning





Validação da *Permissão 5*
`docker exec farmview-firewall iptables -L FORWARD -n -v --line-numbers`

Resultado obtido:
Chain FORWARD (policy DROP 4 packets, 176 bytes)
num   pkts bytes target     prot opt in     out     source               destination         
1       40  3868 ACCEPT     all  --  *      *       0.0.0.0/0            0.0.0.0/0            ctstate RELATED,ESTABLISHED
2        6   328 ACCEPT     tcp  --  *      *       172.30.0.10          172.31.0.3           tcp dpt:80 ctstate NEW
3        0     0 ACCEPT     tcp  --  *      *       172.30.0.10          172.31.0.3           tcp dpt:443 ctstate NEW
4        0     0 LOG        all  --  *      *       0.0.0.0/0            0.0.0.0/0            limit: avg 2/min burst 3 LOG flags 0 level 4 prefix "FARMVIEW_DROP: "

Simualação de uma tentativa de acesso não autorizado

`docker exec farmview-client nmap -Pn -p 8080 172.31.0.3`

nmap: Executa uma varredura de rede
-Pn: Não realiza a descoberta inicial do host por ping
-p 8080: Verifica somente a porta 8080
172.31.0.3: Endereço do servidor NGINX

Resultado obtido: 
Starting Nmap 7.99 ( https://nmap.org ) at 2026-10-10 20:54 +0000
Nmap scan report for 172.31.0.3
Host is up.

PORT     STATE    SERVICE
8080/tcp filtered http-proxy

Nmap done: 1 IP address (1 host up) scanned in 2.62 seconds

em seguida: `docker exec farmview-firewall iptables -L FORWARD -n -v --line-numbers`

Resultado obtido:
Chain FORWARD (policy DROP 6 packets, 264 bytes)
num   pkts bytes target     prot opt in     out     source               destination         
1       40  3868 ACCEPT     all  --  *      *       0.0.0.0/0            0.0.0.0/0            ctstate RELATED,ESTABLISHED
2        6   328 ACCEPT     tcp  --  *      *       172.30.0.10          172.31.0.3           tcp dpt:80 ctstate NEW
3        0     0 ACCEPT     tcp  --  *      *       172.30.0.10          172.31.0.3           tcp dpt:443 ctstate NEW
4        2    88 LOG        all  --  *      *       0.0.0.0/0            0.0.0.0/0            limit: avg 2/min burst 3 LOG flags 0 level 4 prefix "FARMVIEW_DROP: "

É notório que a regra foi aplicada com sucesso. O NMAP tentou estabelecer uma conexação com a porta 8080, os pacotes não corresponderam às regras de liberação, em seguida, foram para à regra LOG, permaneceram pela cadeia e foram todos descartados pela política DROP

*Antes:*
Chain FORWARD (policy DROP 4 packets, 176 bytes)
num   pkts bytes target     prot opt in     out     source               destination    

*Depois:*
Chain FORWARD (policy DROP 6 packets, 264 bytes)
num   pkts bytes target     prot opt in     out     source               destination   


# Passo 10 - DROP para conexões inválidas

*Permissão 6*
`docker exec farmview-firewall iptables -I FORWARD 1 -m conntrack --ctstate INVALID -j DROP`

-I FORWARD: Vai inserir a regra na primeira posição na cadeia FORWARD
-m conntrack: Utiliza o rastreamento de conexões
--cstate INVALId: Identifica os pacotes classificados como inválidos
-j DROP descarta esses pacotes

Essa regra ajuda a descartar tráfego que não pode ser associado corretamente a uma conexão válida.


Resultado após consulta de regras na cadeia

Chain FORWARD (policy DROP 6 packets, 264 bytes)
num   pkts bytes target     prot opt in     out     source               destination         
1        0     0 DROP       all  --  *      *       0.0.0.0/0            0.0.0.0/0            ctstate INVALID
2       40  3868 ACCEPT     all  --  *      *       0.0.0.0/0            0.0.0.0/0            ctstate RELATED,ESTABLISHED
3        6   328 ACCEPT     tcp  --  *      *       172.30.0.10          172.31.0.3           tcp dpt:80 ctstate NEW
4        0     0 ACCEPT     tcp  --  *      *       172.30.0.10          172.31.0.3           tcp dpt:443 ctstate NEW
5        2    88 LOG        all  --  *      *       0.0.0.0/0            0.0.0.0/0            limit: avg 2/min burst 3 LOG flags 0 level 4 prefix "FARMVIEW_DROP: "

Comando para teste `docker exec farmview-client curl -I --max-time 5 http://172.31.0.3`

Resposta obtida:
 % Total    % Received % Xferd  Average Speed  Time    Time    Time   Current
                                 Dload  Upload  Total   Spent   Left   Speed
  0      0   0      0   0      0      0      0                              0HTTP/1.1 302 Found
  0      0   0      0   0      0      0      0                              0
Server: nginx/1.27.5
Date: Sat, 10 Oct 2026 21:52:46 GMT
Content-Type: text/html; charset=UTF-8
Connection: keep-alive
X-Powered-By: PHP/8.2.34
Set-Cookie: PHPSESSID=0sebbldqgurfivicg3qoq4386s; path=/
Expires: Thu, 19 Nov 1981 08:52:00 GMT
Cache-Control: no-store, no-cache, must-revalidate
Pragma: no-cache
Location: http://172.31.0.3/login


Apresentou a resposta: HTTP/1.1 302 Found Significando que essa regra imposta não interrompeu a cominicação HTTP normal


# Passo 11 - Bloquear acesso ao MySQL (Porta 3306)

*Permissão 7* 

Impedir que faça acesso direto ao banco de dados
`docker exec farmview-firewall iptables -I FORWARD 2 -p tcp --dport 3306 -j DROP`

-I FORWARD 2: Inserir a regra na segunda posição da cadeia FORWARD
-p tcp: Utiliza o protocolo TCP
--dport 3306: Porta de destino o MySQL
-j DROP: Descarte nos pacotes que tentarem acesso

Essa regra vai descartar as tentativas de conexões TCP à porta 3306 que atravessem o firewall. Deixando apenas que o sistema faça essa requisição

Chain FORWARD (policy DROP 6 packets, 264 bytes)
num   pkts bytes target     prot opt in     out     source               destination         
1        0     0 DROP       all  --  *      *       0.0.0.0/0            0.0.0.0/0            ctstate INVALID
2        0     0 DROP       tcp  --  *      *       0.0.0.0/0            0.0.0.0/0            tcp dpt:3306
3       49  4793 ACCEPT     all  --  *      *       0.0.0.0/0            0.0.0.0/0            ctstate RELATED,ESTABLISHED
4        7   388 ACCEPT     tcp  --  *      *       172.30.0.10          172.31.0.3           tcp dpt:80 ctstate NEW
5        0     0 ACCEPT     tcp  --  *      *       172.30.0.10          172.31.0.3           tcp dpt:443 ctstate NEW
6        2    88 LOG        all  --  *      *       0.0.0.0/0            0.0.0.0/0            limit: avg 2/min burst 3 LOG flags 0 level 4 prefix "FARMVIEW_DROP: "


Teste de validação: `docker exec farmview-client nmap -Pn -p 3306 172.31.0.3`

Resultado obtido:
Starting Nmap 7.99 ( https://nmap.org ) at 2026-10-10 22:06 +0000
Nmap scan report for 172.31.0.3
Host is up.

PORT     STATE    SERVICE
3306/tcp filtered mysql

Nmap done: 1 IP address (1 host up) scanned in 2.59 seconds

Nesse caso, tem-se o resultado: 3306/tcp filtered mysql, porém que o endereço 172.31.0.3 está sendo filtrado, mas não pode-se afirmar que ainda esteja protegido por esse ip ser do NGINX.

Para verificarmos os containers em execução e as portas publicadas, é utilizado o comando: 
`docker ps --format "table {{.Names}}\t{{.Ports}}"`

Resultado obtido:
NAMES                 PORTS
farmview-firewall     
farmview-client       
farmview-nginx        0.0.0.0:80->80/tcp, [::]:80->80/tcp, 0.0.0.0:443->443/tcp, [::]:443->443/tcp
farmview-app          80/tcp
farmview-phpmyadmin   80/tcp
farmview-db           3306/tcp, 33060/tcp
cebiohub_nginx        0.0.0.0:8089->80/tcp, [::]:8089->80/tcp
cebiohub_phpmyadmin   0.0.0.0:8082->80/tcp, [::]:8082->80/tcp
cebiohub_app          9000/tcp
cebiohub_mysql        33060/tcp, 0.0.0.0:3307->3306/tcp, [::]:3307->3306/tcp

Após isso, para identificar as redes e os endereços IP de cada container:
`docker inspect -f '{{.Name}} {{range .NetworkSettings.Networks}}{{.IPAddress}} {{end}}' $(docker ps -q)`

Resultado obtido:

/farmview-firewall 172.31.0.2 172.30.0.2 
/farmview-client 172.30.0.10 
/farmview-nginx 172.31.0.3 172.21.0.3 
/farmview-app 172.20.0.2 172.21.0.2 
/farmview-phpmyadmin 172.20.0.3 
/farmview-db 172.20.0.4 
/cebiohub_nginx 172.18.0.4 
/cebiohub_phpmyadmin 172.18.0.3 
/cebiohub_app 172.18.0.5 
/cebiohub_mysql 172.18.0.2 

Mostra quie o banco de dados não está na DMZ, e sim dentro de uma rede interna separada.

MySQL 3306 -> 172.20.0.0./24
Firewall -> 172.31.0.0/24

Em seguida dos testes de conexão com o banco MySQL, viu-se a necessidade de fazer o controle de acesso também das portas 5000, sendo ela o flask, na qual é a parte onde fica o servidor do raspberry pi 4 e o sistema conversa com ele e a porta 3309, porta onde a aplicação faz a conexão com o banco CEBIOHUB. Ao realizar o teste de tentar "ouvir" as portas, não obteve resposta, pois não havia nenhuma ligação com os mesmo, deste modo, se viu necessário em criar o container para simular o flask.

# Passo 12 - BLoquear acesso ao flask na porta 5000

*Permissão 8*
`docker exec farmview-firewall iptables -A FORWARD -s 172.30.0.10 -d 172.31.0.4 -p tcp --dport 5000 -j DROP`

-A FORWARD: Adicionando a regra na cadeia FORWARD
-s 172.30.0.10: Identifica o cliente que está tentando acessar o serviço
-d 172.31.0.4: Identifica o serviço flask que esta sendo simulado (Container do raspberry pi 4)
-p tcp: Seleciona o protocolo TCP
--dport 5000: Seleciona a porta de destino
-j DROP: Descarta os pacotes que estao tentando acessar

Teste de aplicação: 
`docker exec farmview-client nmap -Pn -p 5000 172.31.0.4`

Resultado obtido:

Starting Nmap 7.99 ( https://nmap.org ) at 2026-10-11 14:48 +0000
Nmap scan report for 172.31.0.4
Host is up.

PORT     STATE    SERVICE
5000/tcp filtered upnp

Nmap done: 1 IP address (1 host up) scanned in 2.59 seconds


O cliente é impedido de acessar diretamente o container do flask e é direcionado para a pagina de login do software.