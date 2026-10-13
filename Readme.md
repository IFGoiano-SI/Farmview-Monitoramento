
**INSTITUTO FEDERAL GOIANO - CAMPUS CERES**

**Data:** 15 de Outubro de 2026  
**Docente:** Roitier Gonçalves Campos  
**Discente:** Felipe Ferreira Gomes  
**Disciplina:** Segurança da Informação


# Contextualização do problema

A Farmview é um projeto acadêmico desenvolvido para realizar o monitoramento de cultivos de hortaliças em ambiente controlados, afim de facilitar pesquisas de estudantes na instituição de ensino IF Goiano - Campus Ceres. O projeto consiste com um sistema web desenvolvido em PHP integrado com um protótipo ioT que utiliza raspberry pi 4 para receber as requisições http e comunicar com os sensores alocados. O sistema controla sensores como: luz de led, ventoinha, sensores de umidade do solo e uma câmera para controle em tempo real, além dessas funcionalidades, é possível realizar gravação da sala em que o protótipo está alocado, realizar anotações referentes à pesquisa, ver seus ultimos plantio, histórico de videos, obtendo a opção de realizar o download do mesmo. Durante o desenvolvimento desse software, identificou-se uma vulnerabilidade de segurança relacionada ao servidor executado no Raspberry Pi 4. Ao iniciar o servidor, qualquer computador conectado à mesma rede poderia, utilizando o endereço IP e os comandos adequados, realizar requisições HTTP diretamente ao dispositivo, possibilitando a manipulação dos sensores e atuadores sem passar pela interface web do FarmView.

Uma possível solução seria realizar o isolamento físico do Raspberry Pi 4 por meio de uma conexão Ethernet dedicada, separando sua comunicação da rede principal, desde que as configurações de rede impedissem acessos externos não autorizados. Entretanto, considerando a necessidade de aplicar conceitos de Segurança da Informação, surgiu a proposta de simular uma infraestrutura de rede segmentada utilizando Docker Compose e implementar regras de firewall com `iptables`, visando restringir o acesso direto aos serviços internos, permitir apenas as comunicações necessárias e registrar tentativas de acesso não autorizado.


# Criação dos containers

O ambiente de simulação do FarmView foi estruturado utilizando Docker Compose, contendo sete containers distribuídos em redes distintas para representar a aplicação web, o banco de dados, o firewall, o cliente e o servidor HTTP simulado do Raspberry Pi 4.


| **Container**          | **Função** |
|------------------------|--------------------------------------------------------------|
| `farmview-db`          | Armazena os dados da aplicação utilizando MySQL.             |
| `farmview-app`         | Executa a aplicação PHP do FarmView.                         |
| `farmview-nginx`       | Servidor web responsável por receber requisições HTTP/HTTPS. |
| `farmview-phpmyadmin`  | Permite o gerenciamento do banco de dados.                   |
| `farmview-firewall`    | Controla e filtra o tráfego entre a rede cliente e a DMZ.    |
| `farmview-client`      | Simula o computador de um usuário na rede.                   |
| `farmview-flask`       | Simula o servidor HTTP do Raspberry Pi na porta 5000.        |
|                                                                                       |



### 1. Container do Banco de Dados MySQL

Responsável pelo armazenamento das informações do sistema FarmView. Utiliza o MySQL 8.0 e está conectado à rede interna `database`.

```yaml
db:
  image: mysql:8.0
  container_name: farmview-db
  restart: unless-stopped

  environment:
    MYSQL_ROOT_PASSWORD: root
    MYSQL_DATABASE: CEBIOHUB

  command:
    - --sql-mode=NO_ENGINE_SUBSTITUTION
    - --character-set-server=utf8mb4
    - --collation-server=utf8mb4_general_ci

  volumes:
    - db_data:/var/lib/mysql
    - ./docker/mysql/init:/docker-entrypoint-initdb.d:ro

  healthcheck:
    test: ["CMD", "mysqladmin", "ping", "-h", "localhost", "-uroot", "-proot"]
    interval: 5s
    timeout: 5s
    retries: 10

  networks:
    - database
```

### 2. Container da Aplicação Web PHP (FarmView)

Executa a aplicação PHP do FarmView, permitindo a comunicação com o banco de dados e contendo as configurações de conexão com o Raspberry Pi 4.

```yaml
app:
  build: .
  container_name: farmview-app
  restart: unless-stopped

  depends_on:
    db:
      condition: service_healthy

  environment:
    DB_HOST: db
    DB_PORT: "3306"
    DB_NAME: CEBIOHUB
    DB_USER: root
    DB_PASS: root

    RASPBERRY_PI: "192.168.1.18"
    RASPBERRY_PORT: "5000"

  volumes:
    - .:/var/www/html
    - uploads:/var/www/html/Public/uploadImages

  networks:
    - web
    - database
```

### 3. Container do Servidor Web Nginx

Recebe as requisições HTTP e HTTPS e encaminha as solicitações para a aplicação PHP. Está conectado às redes `dmz` e `web`.

```yaml
nginx:
  image: nginx:1.27-alpine
  container_name: farmview-nginx
  restart: unless-stopped

  depends_on:
    - app

  ports:
    - "80:80"
    - "443:443"

  volumes:
    - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf:ro

  networks:
    dmz:
      ipv4_address: 172.31.0.3
    web:
```

### 4. Container do phpMyAdmin

Disponibiliza uma interface gráfica para gerenciamento do banco de dados MySQL, utilizando a rede interna `database`.

```yaml
phpmyadmin:
  image: phpmyadmin:5.2
  container_name: farmview-phpmyadmin
  restart: unless-stopped

  depends_on:
    db:
      condition: service_healthy

  environment:
    PMA_HOST: db
    PMA_PORT: "3306"
    UPLOAD_LIMIT: 64M

  networks:
    - database
```

### 5. Container do Firewall

Responsável por controlar o tráfego entre a rede cliente e a DMZ, utilizando regras `iptables`.
O container possui a capacidade `NET_ADMIN`, permitindo a configuração das regras de firewall, e o encaminhamento IPv4 habilitado.

```yaml
firewall:
  build: ./docker/firewall
  container_name: farmview-firewall
  restart: unless-stopped

  cap_add:
    - NET_ADMIN

  sysctls:
    - net.ipv4.ip_forward=1

  networks:
    rede_cliente:
      ipv4_address: 172.30.0.2

    dmz:
      ipv4_address: 172.31.0.2
```

### 6. Container do Cliente

Simula um computador conectado à rede cliente, permitindo realizar testes de conectividade e verificar as restrições aplicadas pelo firewall.
São instaladas as ferramentas `curl`, `nmap` e `iproute2`. Também é configurada uma rota para alcançar a DMZ por meio do firewall.

```yaml
client:
  image: alpine:latest
  container_name: farmview-client
  restart: unless-stopped

  cap_add:
    - NET_ADMIN

  command: >
    sh -c "apk add --no-cache curl nmap iproute2 &&
       ip route replace 172.31.0.0/24 via 172.30.0.2 &&
       tail -f /dev/null"

  networks:
    rede_cliente:
      ipv4_address: 172.30.0.10
```

### 7. Container do Servidor HTTP Simulado (Raspberry Pi)

Representa o serviço HTTP executado no Raspberry Pi 4.
Para a simulação, utiliza a imagem Python 3.12 Alpine e executa um servidor HTTP simples na porta `5000`, permitindo testar as regras de bloqueio do firewall.

```yaml
flask:
  image: python:3.12-alpine
  container_name: farmview-flask
  restart: unless-stopped

  command:
    sh -c "python -m http.server 5000 --bind 0.0.0.0"

  networks:
    dmz:
      ipv4_address: 172.31.0.4
```

### 8. Configuração das Redes Docker

Foram configuradas quatro redes para segmentar a infraestrutura e organizar a comunicação entre os containers.

```yaml
networks:
  rede_cliente:
    driver: bridge
    ipam:
      config:
        - subnet: 172.30.0.0/24

  dmz:
    driver: bridge
    ipam:
      config:
        - subnet: 172.31.0.0/24

  web:
    driver: bridge

  database:
    driver: bridge
    internal: true
```

### 9. Configuração dos Volumes

Os volumes permitem manter os dados do MySQL e as imagens enviadas pelos usuários mesmo após a recriação dos containers.

```yaml
volumes:
  db_data:
  uploads:
```

Após a elaboração dos containers, segue o passo a passo para a criação do mesmo e em seguida as configurações do firewall.

# Passo 1: Buildar o container

`docker compose -f docker-composeFirewall.yml up -d build`
Esse comando realiza a construção das imagens dos serviços farmview-app e farmview-firewall, conforme as configurações definidas no arquivo docker-composeFirewall.yml.
`docker compose -f docker-composeFirewall.yml ps`
Este faz a listagem das imagens e containers criados.

NAME                  IMAGE                COMMAND                  SERVICE      CREATED        STATUS                 PORTS
farmview-app          cebiohub-app         "docker-php-entrypoi…"   app          5 days ago     Up 2 hours             80/tcp
farmview-client       alpine:latest        "sh -c 'apk add --no…"   client       2 days ago     Up 2 hours             
farmview-db           mysql:8.0            "docker-entrypoint.s…"   db           6 days ago     Up 2 hours (healthy)   3306/tcp, 33060/tcp
farmview-firewall     cebiohub-firewall    "/entrypoint.sh"         firewall     36 hours ago   Up 2 hours             
farmview-flask        python:3.12-alpine   "sh -c 'python -m ht…"   flask        37 hours ago   Up 2 hours             
farmview-nginx        nginx:1.27-alpine    "/docker-entrypoint.…"   nginx        5 days ago     Up 2 hours             0.0.0.0:80->80/tcp, [::]:80->80/tcp, 0.0.0.0:443->443/tcp, [::]:443->443/tcp
farmview-phpmyadmin   phpmyadmin:5.2       "/docker-entrypoint.…"   phpmyadmin   5 days ago     Up 2 hours             80/tcp


# Passo 2: Verificação dos Endereços IP e Interfaces de Rede do Firewal
Após a inicialização dos containers, executa-se o comando abaixo para verificar os endereços IPv4 atribuídos às interfaces de rede do container `farmview-firewall`.

`docker exec farmview-firewall ip -4 add`

O comando permite identificar as interfaces de rede do firewall, associadas aos endereços `172.30.0.2` (rede cliente) e `172.31.0.2` (DMZ).

**Docker exec**: Executa um comando dentro do container que ja está em execução. <br>
**farmview-firewall**: Refere-se ao nome do container criado. <br>
**ip -4 addr:** Verifica os endereço IPV4 que estão no gerenciamento da interface. <br>

# Passo 3: Verificação do encaminhamento dos Pacotes IPv4 no firewall

Nesta etapa, verifica-se se o encaminhamento de pacotes IPv4 está habilitado no container farmview-firewall. Essa funcionalidade permite que o firewall encaminhe pacotes entre a rede cliente (172.30.0.0/24) e a DMZ (172.31.0.0/24), respeitando as regras de segurança configuradas no iptables.

Execute:
`docker exec farmview-firewall cat /proc/sys/net/ipv4/ip_forward`

Resultado obtido: 1

O valor 1 indica que o encaminhamento de pacotes IPv4 está habilitado no kernel do container. Já o valor 0 indica que essa funcionalidade está desabilitada. Dessa forma, confirma-se que o firewall está configurado para realizar o encaminhamento de pacotes entre as redes, desde que o tráfego seja permitido pelas regras estabelecidas.

# Passo 4: Configuração da Rota do cliente para acesso à DMZ por meio do firewall

Nesta etapa, configura-se o roteamento do container farmview-client, que representa o computador de um usuário final. O objetivo é garantir que as requisições destinadas à rede DMZ (172.31.0.0/24) sejam encaminhadas ao container farmview-firewall, responsável por analisar e controlar o tráfego de acordo com as regras de segurança estabelecidas.

De forma inicial, é necessário executa o comando:
`docker exec -it farmview-cliente sh`

O comando docker exec permite executar comandos dentro de um container em funcionamento. O parâmetro -it habilita uma sessão interativa no terminal, enquanto sh inicia o interpretador de comandos do Alpine Linux.

Verificação a tabela de roteamento
`ip route`

Resultado obtido: 

`default via 172.30.0.1 dev eth0 172.30.0.0/24 dev eth0 proto kernel scope link src 172.30.0.10`. 

O endereço 172.30.0.1 representa o gateway padrão da rede Docker rede_cliente, enquanto 172.30.0.10 correspondente ao endereço IP do container
Nesse momento, a tabela de roteamento apresenta a rota padrão e a rede diretamente conectada. Entretanto, para garantir que as requisições destinadas à DMZ passem pelo firewall, é necessário configurar uma rota específica.

Para adicionar a rota para a DMZ:
`ip route add 172.31.0.0/24 via 172.30.0.2`

O comando ip route permite consultar e gerenciar a tabela de roteamento do sistema operacional. Nesse caso, add adiciona uma nova rota, 172.31.0.0/24 representa a rede de destino (DMZ) e via 172.30.0.2 define o endereço IP do firewall como próximo salto. Dessa forma, sempre que o cliente tentar acessar um dispositivo pertencente à rede 172.31.0.0/24, os pacotes serão encaminhados ao firewall, que decidirá se a comunicação será permitida ou bloqueada.

Para verificar se o roteamento foi configurado corretamente:
`ip rout get 172.31.0.3`

Resultado obtido:
`172.31.0.3 via 172.30.0.2 dev eth0 src 172.30.0.10 uid 0`

O resultado demonstra que, para alcançar o servidor Nginx (172.31.0.3), o cliente utilizará o firewall (172.30.0.2) como próximo salto, enviando os pacotes pela interface eth0, com origem no endereço 172.30.0.10. Portanto, confirma-se que a rota de saída do cliente em direção à DMZ está configurada corretamente, permitindo que o tráfego seja submetido às regras de filtragem do firewall.

# Passo 5: Teste de comunicação entre CLiente -> Firewall -> NGINX

Para verificar as regras encaminhamento de pacotes configuradas no container farmview-firewal:
`docker exec farmview-firewall iptables -L FORWARD -n -v`

Utilizando esse comando, é possível visualizar as regras existente na cadeia FORWARD, exibindo os endereços IPs de origem e destino, as ações aplicadas e informações adicionais, como a quantidade de pacotes e bytes correspondentes a cada regra.

Resultado obtido:
*Chain FORWARD (policy ACCEPT 0 packets, 0 bytes)
 pkts bytes target     prot opt in     out     source               destination.* 

O resultado demonstra que, naquele momento, a política padrão da cadeia FORWARD estava definida como ACCEPT e não havia regras específicas de filtragem listadas. Dessa forma, o firewall ainda não possuía restrições configuradas nessa cadeia.

-- Primeiro teste de comunicação com o NGINX
Em seguida, foi acessado o terminal interativo do container farmview-client, responsável por representar o computador de um usuário final:

`docker exec -it farmview-cliente sh`

Dentro do container, foi realizado a requisição HTTP ao servidor Nginx, localizado no endereço 172.31.0.3, utilizando o seguinte comando:
`curl -I --max-time 5 http://172.31.0.3`. Porem resultando em um erro: `curl: (28) Connection timed out after 5001 milliseconds`.
Esse resultado indica que a requisição não recebeu uma resposta dentro do limite de cinco segundos estabelecido pelo parâmetro --max-time 5. Portanto, foi necessário investigar o motivo pelo qual a comunicação entre o cliente e o Nginx não estava sendo concluída.

-- Verificação da rota utilizada pelo cliente
Para identificar o caminho utilizado pelo cliente até o servidor:
`docker exec farmview-client ip route get 172.31.0.3`

Resultado obtido:
`172.31.0.3 via 172.30.0.2 dev eth0 src 172.30.0.10 uid 0`

Esse retorno indica que o cliente estava utilizando o endereço 172.30.0.2, pertencente ao firewall, como próximo salto para alcançar o Nginx. O endereço 172.30.0.10 representa o IP de origem do cliente, enquanto uid 0 identifica o usuário root utilizado na consulta.

Para um teste de conectividade entre o cliente e o firewall: 
`docker exec farmview-cliente ping -c 3 172.30.0.2`, 

Retornando:
PING 172.30.0.2 (172.30.0.2): 56 data bytes
64 bytes from 172.30.0.2: seq=0 ttl=64 time=0.124 ms
64 bytes from 172.30.0.2: seq=1 ttl=64 time=0.149 ms
64 bytes from 172.30.0.2: seq=2 ttl=64 time=0.086 ms
Logo, o cliente consegue alcançar o container do firewall.

Posteriomente a isso, foi testado o comando:
`docker exec farmview-client ip rout get 172.31.0.3` para verificar se o cliente continua utilizando o firewall como rota DMZ.
Resposta obita: 
`172.31.0.3 via 172.30.0.1 dev eth0 src 172.30.0.10` Que significa que o cliente esta tentanddo acessar o NGINX pelo gateway padrão do Docker ao envés do firewall.

Para corrigir o problema, foi necessário configurar novamente a rota do cliente por meio do comando `docker exec farmview-client ip route replace 172.31.0.0/24 via 172.30.0.2`, definindo que as requisições destinadas à rede DMZ deveriam passar pelo firewall. Após isso, foi utilizado o comando `ip route` para verificar a alteração, confirmando que o cliente estava utilizando o endereço `172.30.0.2` como próximo salto.

Com a rota corrigida, foi realizado um novo teste de comunicação com o Nginx utilizando o comando `docker exec farmview-client curl -I --max-time 5 http://172.31.0.3`. Entretanto, a requisição continuou apresentando timeout, indicando que, mesmo com o roteamento configurado, a comunicação ainda não estava sendo concluída.

Diante disso, foram realizados testes diretamente no firewall para verificar sua comunicação com o Nginx. Os comandos `ping` e `curl` apresentaram resultados positivos, confirmando que o firewall conseguia alcançar o servidor. Também foi verificado se o encaminhamento de pacotes estava habilitado, utilizando o comando `cat /proc/sys/net/ipv4/ip_forward`, que retornou o valor `1`.

Para investigar melhor o problema, foi utilizada a ferramenta `tcpdump`, permitindo acompanhar os pacotes que passavam pelas interfaces de rede do firewall. Durante os testes, foi possível identificar que os pacotes enviados pelo cliente chegavam à interface `eth0` e eram encaminhados pela `eth1` em direção ao Nginx. Entretanto, não foram identificadas respostas correspondentes, levantando a possibilidade de um problema no caminho de retorno dos pacotes.

Para solucionar essa situação, foi implementada uma regra NAT com `MASQUERADE`, utilizando o comando:

`docker exec farmview-firewall iptables -t nat -A POSTROUTING -s 172.30.0.0/24 -d 172.31.0.0/24 -o eth1 -j MASQUERADE`

Essa regra faz com que os pacotes encaminhados para a DMZ utilizem o endereço do firewall (`172.31.0.2`) como origem, permitindo que o Nginx envie suas respostas de volta ao firewall, que as direciona ao cliente. Após essa configuração, foi realizado novamente o teste de comunicação com o Nginx, obtendo o retorno `HTTP/1.1 302 Found`, indicando que a requisição foi recebida e que a aplicação estava redirecionando o acesso para a página de login. Dessa forma, foi possível corrigir o problema e estabelecer a comunicação entre o cliente e o Nginx através do firewall.

Logo após corrigir a comunicação entre o cliente e o nginx, foi necessário adicionar o comando `ip rout replace 172.31.0.0/24 via 172.30.0.2` no container client. Essa alteração tem a finalidade de configurar automaticamente a rota para a rede DMZ através do firewall, evitando a necessidade de executar o comando de forma manual sempre que o container for recriado, em seguida, o container foi restartado.

# Passo 6: Automatizar as regras do NAT

#!/bin/sh
set -e

echo "====================================" <br>
echo "     FIREWALL FARMVIEW INICIADO" <br>
echo "====================================" <br>

echo "IP Forward:"
cat /proc/sys/net/ipv4/ip_forward

echo ""
echo "Interfaces de rede:"
ip -4 addr

echo ""
echo "Rotas:"
ip route

<!-- Identificar a interface conectada à DMZ -->
INTERFACE_DMZ=$(ip -4 route show 172.31.0.0/24 | awk '{print $3}')

if [ -z "$INTERFACE_DMZ" ]; then
    echo "ERRO: Interface da DMZ não encontrada!"
    exit 1
fi

echo "Interface da DMZ: $INTERFACE_DMZ"

<!-- Configurar política de bloqueio antes das permissões -->
iptables -P FORWARD DROP

<!-- Limpar regras anteriores da cadeia FORWARD -->
iptables -F FORWARD

<!-- Limpar regras NAT anteriores da cadeia POSTROUTING -->
iptables -t nat -F POSTROUTING

echo ""
echo "Configurando NAT para a DMZ..."

iptables -t nat -A POSTROUTING -s 172.30.0.0/24 -d 172.31.0.0/24 -o "$INTERFACE_DMZ" -j MASQUERADE

echo ""
echo "Configurando regras de segurança..."

<!-- REGRA 1 - Bloquear pacotes inválidos -->
iptables -A FORWARD -m conntrack --ctstate INVALID -j DROP

<!-- REGRA 2 - Bloquear acesso direto ao MySQL -->
iptables -A FORWARD -p tcp --dport 3306 -j DROP

<!-- REGRA 3 - Registrar tentativas de acesso ao Flask -->
iptables -A FORWARD -s 172.30.0.10 -d 172.31.0.4 -p tcp --dport 5000 -m limit --limit 2/min --limit-burst 3 -j LOG --log-prefix "FLASK_BLOQUEADO: " --log-level 4

<!-- REGRA 4 - Bloquear acesso do cliente ao Flask -->
iptables -A FORWARD -s 172.30.0.10 -d 172.31.0.4 -p tcp --dport 5000 -j DROP

<!-- REGRA 5 - Permitir conexões estabelecidas -->
iptables -A FORWARD -m conntrack --ctstate ESTABLISHED,RELATED -j ACCEPT

<!-- REGRA 6 - Permitir HTTP para o Nginx -->
iptables -A FORWARD -s 172.30.0.10 -d 172.31.0.3 -p tcp --dport 80 -m conntrack --ctstate NEW -j ACCEPT

<!-- REGRA 7 - Permitir HTTPS para o Nginx -->
iptables -A FORWARD -s 172.30.0.10 -d 172.31.0.3 -p tcp --dport 443 -m conntrack --ctstate NEW -j ACCEPT

<!-- REGRA 8 - Registrar demais tentativas não autorizadas -->
iptables -A FORWARD -m limit --limit 2/min --limit-burst 3 -j LOG --log-prefix "FARMVIEW_DROP: " --log-level 4

echo ""
echo "Regras de filtragem:"
iptables -L FORWARD -n -v --line-numbers  

echo ""
echo "Regras NAT:"
iptables -t nat -L POSTROUTING -n -v

echo ""
echo "Firewall configurado com sucesso!"

exec tail -f /dev/null


# Passo 7 - Criar as regras para liberação no firewall.

**Permissão 1** <br>
Permitir conexões ja estabelecida e seus pacotes de resposta:
`docker exec farmview-firewall iptables -A FORWARD -m conntrack --ctstate ESTABLISHED,RELATED -j ACCEPT`

-A FORWARD: Adicionar regra à cadeia que fica responsável pelo encaminhamento dos pacotes. <br>
-m conntrack: Ativa o módulo de acompanhamento de conexão. <br>
-ctstate ESTABLISHED,RELATED: Identifica os pacotes de conexões estabelecida e relacionadas. <br>

Essa regra permite que o cliente solicite uma pagina ao NGINX, sendo o cliente `172.30.0.10` e o NGINX `172.31.0.3`

**Permissão 2** <br>
Permitir HTTP do cliente para o NGINX - Porta 80
`docker exec farmview-firewall iptables -A FORWARD -s 172.30.0.10 -d 172.31.0.3 -p tcp --dport 80 -m conntrack --ctstate NEW -j ACCEPT`

Permite que o cliente inicie conexões HTTP com o servidor NGINX

-s 172.30.0.10: Origem da requisição, cliente. <br>
-d 172.31.0.3: Destino da requisição, NGINX. <br>
-p tcp: Utiliza o protocolo TCP. <br>
--dport 80: Porta de destino HTTP. <br>
--cstate NEW: Permite iniciar novas conexões. <br>
-j ACCEPT: Autoriza conexão. <br>

**Permissão 3**

Permitir HTTPS do cliente para o NGINX:
`docker exec farmview-firewall iptables -A FORWARD -s 172.30.0.10 -d 172.31.0.3 -p tcp --dport 443 -m conntrack --ctstate NEW -j ACCEPT`

Possui a mesma permissão da **permissão 2**, mas libera a porta 443, utilizada pelo HTTPS: Comunicação protegida por TLS.


# Passo 8: Ativar o DROP nas requisições de acesso.

**Permissão 4**

Descarte de todo pacote que não corresponde as permissões criadas
`docker exec farmview-firewall iptables -P FORWARD DROP`

Para validar a **permissão 4**, foi realizado o seguinte teste: 
`docker exec farmview-client curl -I --max-time 5 http://172.31.0.3`

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

**Explicação:** HTTP/1.1 302 Found indica que o servidor recebeu a requisição e respondeu para a página de login, conforme esperado, ou seja, a comunicação da porta 80 não foi bloqueada.


Verificação de uma porta não autorizada é bloqueada:
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

**Permissão 5**
`docker exec farmview-firewall iptables -A FORWARD -m limit --limit 2/min --limit-burst 3 -j LOG --log-prefix "FARMVIEW_DROP: " --log-level 4`

-A FORWARD: Adiciona a regra ao final da cadeia FORWARD.<br>
-m limit: Ativa o módulo de limitação de frequência. <br>
--limit 2/min: Limitação dos registros a uma média de 2 por minutos. <br>
--limit-burst 3: Permite a uma quantidade de até 3 registros iniciais. <br>
-j LOG: Registra informações sobres os pacotes. <br>
--log-prefix "FARMVIEW_DROP": Adiciona uma identificação aos registros. <br>
--log-level 4: Define o nível de registros com warning. <br>


Validação da **Permissão 5** <br>
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

**Antes:**
Chain FORWARD (policy DROP 4 packets, 176 bytes)
num   pkts bytes target     prot opt in     out     source               destination.   

**Depois:**
Chain FORWARD (policy DROP 6 packets, 264 bytes)
num   pkts bytes target     prot opt in     out     source               destination.

# Passo 10: Regras de DROP para conexões inválidas

**Permissão 6**
`docker exec farmview-firewall iptables -I FORWARD 1 -m conntrack --ctstate INVALID -j DROP`

-I FORWARD: Vai inserir a regra na primeira posição na cadeia FORWARD. <br>
-m conntrack: Utiliza o rastreamento de conexões. <br>
--cstate INVALId: Identifica os pacotes classificados como inválidos. <br>
-j DROP descarta esses pacotes. <br>

Essa regra ajuda a descartar tráfego que não pode ser associado corretamente a uma conexão válida.

Resultado após consulta de regras na cadeia.

Chain FORWARD (policy DROP 6 packets, 264 bytes)
num   pkts bytes target     prot opt in     out     source               destination         
1        0     0 DROP       all  --  *      *       0.0.0.0/0            0.0.0.0/0            ctstate INVALID
2       40  3868 ACCEPT     all  --  *      *       0.0.0.0/0            0.0.0.0/0            ctstate RELATED,ESTABLISHED
3        6   328 ACCEPT     tcp  --  *      *       172.30.0.10          172.31.0.3           tcp dpt:80 ctstate NEW
4        0     0 ACCEPT     tcp  --  *      *       172.30.0.10          172.31.0.3           tcp dpt:443 ctstate NEW
5        2    88 LOG        all  --  *      *       0.0.0.0/0            0.0.0.0/0            limit: avg 2/min burst 3 LOG flags 0 level 4 prefix "FARMVIEW_DROP: "

Comando para teste `docker exec farmview-client curl -I --max-time 5 http://172.31.0.3`.

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

Apresentou a resposta: HTTP/1.1 302 Found Significando que essa regra imposta não interrompeu a cominicação HTTP normal.


# Passo 11 - Bloquear acesso ao MySQL (Porta 3306)

**Permissão 7**

Impedir que faça acesso direto ao banco de dados
`docker exec farmview-firewall iptables -I FORWARD 2 -p tcp --dport 3306 -j DROP`

-I FORWARD 2: Inserir a regra na segunda posição da cadeia FORWARD. <br>
-p tcp: Utiliza o protocolo TCP. <br>
--dport 3306: Porta de destino o MySQL. <br>
-j DROP: Descarte nos pacotes que tentarem acesso. <br>

Essa regra vai descartar as tentativas de conexões TCP à porta 3306 que atravessem o firewall. Deixando apenas que o sistema faça essa requisição.

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

Nesse caso, tem-se o resultado: 3306/tcp filtered mysql, porém o endereço 172.31.0.3 está sendo filtrado, mas não pode-se afirmar que ainda esteja protegido por esse ip ser do NGINX.

Para verificar os containers em execução e as portas publicadas, é utilizado o comando: 
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

Mostra que o banco de dados não está na DMZ, e sim dentro de uma rede interna separada.

MySQL 3306 -> 172.20.0.0./24
Firewall -> 172.31.0.0/24

# Passo 12 - BLoquear acesso ao flask na porta 5000

**Permissão 8**
`docker exec farmview-firewall iptables -A FORWARD -s 172.30.0.10 -d 172.31.0.4 -p tcp --dport 5000 -j DROP`

-A FORWARD: Adicionando a regra na cadeia FORWARD. <br>
-s 172.30.0.10: Identifica o cliente que está tentando acessar o serviço. <br>
-d 172.31.0.4: Identifica o serviço flask que esta sendo simulado (Container do raspberry pi 4). <br>
-p tcp: Seleciona o protocolo TCP. <br>
--dport 5000: Seleciona a porta de destino. <br>
-j DROP: Descarta os pacotes que estao tentando acessar. <br>

Teste de aplicação: 
`docker exec farmview-client nmap -Pn -p 5000 172.31.0.4`

Resultado obtido:

Starting Nmap 7.99 ( https://nmap.org ) at 2026-10-11 14:48 +0000
Nmap scan report for 172.31.0.4
Host is up.

PORT     STATE    SERVICE
5000/tcp filtered upnp

Nmap done: 1 IP address (1 host up) scanned in 2.59 seconds
O acesso direto do cliente ao servidor HTTP simulado na porta 5000 foi bloqueado. O acesso à aplicação permanece disponível pelo Nginx na porta 80, on


# Tabela de regras

| **Regra**                               | **Origem → Destino**| **Porta** | **Ação** |
|-----------------------------------------|---------------------|-----------|----------|
| 1 - Pacotes inválidos                   | Qualquer → Qualquer | Todas     | `DROP`   |
| 2 - Bloqueio MySQL                      | Qualquer → Qualquer | TCP 3306  | `DROP`   |
| 3 - Registro de acesso ao Flask         | Cliente → Flask     | TCP 5000  | `LOG`    |
| 4 - Bloqueio Flask                      | Cliente → Flask     | TCP 5000  | `DROP`   |
| 5 - Conexões estabelecidas              | Qualquer → Qualquer | Todas     | `ACCEPT` |
| 6 - Permitir HTTP                       | Cliente → Nginx     | TCP 80    | `ACCEPT` |
| 7 - Permitir HTTPS                      | Cliente → Nginx     | TCP 443   | `ACCEPT` |
| 8 - Registro de acessos não autorizados | Qualquer → Qualquer | Todas     | `LOG`    |
| Política padrão                         | Qualquer → Qualquer | Todas     | `DROP`   |




### Conclusão

Com o desenvolvimento deste trabalho, foi possível implementar e simular uma infraestrutura de rede segmentada para o sistema FarmView, utilizando Docker Compose e regras de firewall com `iptables`, com o objetivo de restringir acessos não autorizados aos serviços da aplicação. Durante a implementação, foram configuradas redes distintas para o cliente, a DMZ, a aplicação web e o banco de dados. Também foram aplicadas regras de liberação para os serviços HTTP e HTTPS, bloqueio de pacotes inválidos, restrição de acesso direto ao servidor HTTP simulado do Raspberry Pi e registro de tentativas de acesso não autorizado.

Os testes realizados com as ferramentas `curl`, `nmap` e `tcpdump` permitiram verificar o funcionamento das configurações e identificar problemas relacionados ao roteamento e ao retorno dos pacotes, solucionados por meio da implementação de uma regra NAT com `MASQUERADE`. Como resultados, foi possível estabelecer a comunicação HTTP entre o cliente e o Nginx, obtendo a resposta `HTTP/1.1 302 Found`, e verificar o bloqueio do acesso direto ao servidor HTTP simulado na porta 5000, identificado pelo `nmap` como `filtered`.

Além disso, as configurações foram automatizadas por meio do script `entrypoint.sh`, permitindo que as regras sejam aplicadas durante a inicialização do container do firewall.
Dessa forma, o trabalho demonstrou, em um ambiente de simulação, a importância da segmentação de redes, do controle de acesso e da aplicação de políticas de segurança para proteger serviços utilizados em sistemas integrados com dispositivos IoT. Como melhoria futura, pretende-se avaliar a aplicação dessas medidas na infraestrutura física do FarmView, utilizando o Raspberry Pi 4 real.