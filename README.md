# Peças Prime — Marketplace de autopeças (PHP + MySQL para XAMPP)

Site completo estilo Mercado Livre, pronto para rodar no XAMPP.

## Instalação (5 passos)

1. Copie a pasta `pecas-prime` para dentro de `C:\xampp\htdocs\` (no Windows).
2. Abra o **XAMPP Control Panel** e inicie **Apache** e **MySQL**.
3. Acesse **http://localhost/phpmyadmin**, clique em "Importar", selecione o arquivo `install.sql` desta pasta e clique em "Executar". Isso cria o banco `pecas_prime` com produtos de exemplo.
4. Abra **http://localhost/pecas-prime** no navegador.
5. Pronto! Para entrar no painel do vendedor: **admin@pecasprime.com** / senha **admin123**.

## O que o site tem

- Home com ofertas, destaques e mais vendidos
- Catálogo com busca, filtros (categoria, marca, preço, avaliação) e ordenação
- Página de produto com especificações, perguntas e respostas, avaliações
- Carrinho, frete grátis acima de R$ 199, cupons (PRIME10, BEMVINDO15, FREIO20)
- Checkout com Pix, cartão e boleto
- Cadastro e login de clientes, histórico de pedidos
- Painel do vendedor: estatísticas, cadastro/edição/exclusão de produtos, gestão de pedidos (status) e respostas às perguntas

## Estrutura

- `index.php`, `products.php`, `product.php`, `cart.php`, `checkout.php`, `login.php`, `register.php`, `my-orders.php`
- `admin/` — painel do vendedor
- `includes/` — cabeçalho e rodapé
- `img.php` — gera as imagens dos produtos automaticamente (funciona offline)
- `install.sql` — banco de dados + dados de exemplo
- `config.php` — configuração do banco (padrão XAMPP: root sem senha)

## Personalizar

- Cores no topo de `assets/css/style.css` (`--red`, `--dark`)
- Conexão do banco em `config.php`
