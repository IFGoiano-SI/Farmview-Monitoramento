#!/bin/sh
set -e

echo "===================================="
echo "     FIREWALL FARMVIEW INICIADO"
echo "===================================="

echo "IP Forward:"
cat /proc/sys/net/ipv4/ip_forward

echo ""
echo "Interfaces de rede:"
ip -4 addr

echo ""
echo "Rotas:"
ip route

# Identificar a interface conectada à DMZ
INTERFACE_DMZ=$(ip -4 route show 172.31.0.0/24 | awk '{print $3}')

if [ -z "$INTERFACE_DMZ" ]; then
    echo "ERRO: Interface da DMZ não encontrada!"
    exit 1
fi

echo "Interface da DMZ: $INTERFACE_DMZ"

# Configurar política de bloqueio antes das permissões
iptables -P FORWARD DROP

# Limpar regras anteriores da cadeia FORWARD
iptables -F FORWARD

# Limpar regras NAT anteriores da cadeia POSTROUTING
iptables -t nat -F POSTROUTING

echo ""
echo "Configurando NAT para a DMZ..."

iptables -t nat -A POSTROUTING -s 172.30.0.0/24 -d 172.31.0.0/24 -o "$INTERFACE_DMZ" -j MASQUERADE

echo ""
echo "Configurando regras de segurança..."

# REGRA 1 - Bloquear pacotes inválidos
iptables -A FORWARD -m conntrack --ctstate INVALID -j DROP

# REGRA 2 - Bloquear acesso direto ao MySQL
iptables -A FORWARD -p tcp --dport 3306 -j DROP

# REGRA 3 - Registrar tentativas de acesso ao Flask
iptables -A FORWARD -s 172.30.0.10 -d 172.31.0.4 -p tcp --dport 5000 -m limit --limit 2/min --limit-burst 3 -j LOG --log-prefix "FLASK_BLOQUEADO: " --log-level 4

# REGRA 4 - Bloquear acesso do cliente ao Flask
iptables -A FORWARD -s 172.30.0.10 -d 172.31.0.4 -p tcp --dport 5000 -j DROP

# REGRA 5 - Permitir conexões estabelecidas
iptables -A FORWARD -m conntrack --ctstate ESTABLISHED,RELATED -j ACCEPT

# REGRA 6 - Permitir HTTP para o Nginx
iptables -A FORWARD -s 172.30.0.10 -d 172.31.0.3 -p tcp --dport 80 -m conntrack --ctstate NEW -j ACCEPT

# REGRA 7 - Permitir HTTPS para o Nginx
iptables -A FORWARD -s 172.30.0.10 -d 172.31.0.3 -p tcp --dport 443 -m conntrack --ctstate NEW -j ACCEPT

# REGRA 8 - Registrar demais tentativas não autorizadas
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