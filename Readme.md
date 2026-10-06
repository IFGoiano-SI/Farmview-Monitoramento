# Criação do conjunto de regras 
-- CHAIN personalizada
    "Sudo iptables -N FARMVIEW-ENTRADA"

SUDO: executa com permissão adminstrativa
Iptables: Ferramenta que configura as regras
-N: Cria uma cadeia
FARMVIEW-ENTRADA: Nome escolhido para representar


# Elaboração do trafégo de rede wifi

Sudo iptables -I DOCKER-USER 1 \ -i wlp63s0 -d 172.21.0.0/16 -j FARMVIEW-ENTRADA

-I DOCKER USER: Vai inserir na primeira posição
-i: Interface pela qual o pacote entrou
-d: Endereço ou rede de destino
-j: Ação para encaminhar para a cadeira FARMVIEW-ENTRADA


# Permissão do usuário a acessar o NGINX na porta 80/TCP

--  sudo iptables -I FARMVIEW-ENTRADA 1 -p tcp -d 172.21.0.3 --dport 80 -j RETURN

Foi defenido uma rede privada para a cadeia FARMVIEW-ENTRADA.

Vai inserir na primeira posição da cadeia FARMVIEW-ENTRADAM, considerando o protocolo TCP, selecionando o nginx, com o argumento "-d 172.21.0.3", selecionando a porta HTTP em --dport 80, a ação retorna ao docker apos sucesso.



# Permissão do usuário a acessar o NGIXN na porta 443/HTTPS

-- sudo iptables -I FARMVIEW-ENTRADA 2 -p tcp -d 172.21.0.3 --dport 443 -j RETURN

Segue a mesma logica dos argumentos acima, mudando somente para https (443).


# Bloquear o usuário, sendo necessário acessar o sistema passando pelo NGINX

-- sudo iptables -A FARMVIEW-ENTRADA -j DROP

Aplica no final da cadeia a regra, impedindo novas requisições vinda do wifi, ao cair nela, o firewall da um DROP, descarta o pacote sem dar uma resposta.


# Bloquear o usuário a acessar o MySQL na porta 3306

-- sudo iptables -I DOCKER-USER 2 -i wlp63s0 -d 172.20.0.0/16 -p tcp --dport 3306 -j DROP

Agora, será aplicada na segunda posição do DOCKER-USER a aplicação no trafégo de rede, vinda do wifi "-i wlp63s0" selecionando o IP do banco de dados, pegando o tcp vido da requisição 3306, na qual é o banco MySQL

# Permitir que somente o NGINX acesse o container app na porta 80
-- sudo iptables -N FARMVIEW-APP

Criação da cadeia FARMVIEW-APP


-- sudo iptables -A FARMVIEW-APP -m conntrack --ctstate ESTABLISHED,RELATED -j RETURN
 Permitir dar continuidade das conexões

