# sites.plresende.pt

Landing page da campanha Presença Digital da PL Resende (Black Friday: criação do site a 349,50 € + IVA), com questionário para o cliente.

- `index.html`: página e questionário de 2 passos.
- `enviar.php`: envia as respostas por email para info@plresende.com (precisa de PHP, por isso o site corre no cPanel e não no GitHub Pages).
- `.cpanel.yml`: copia os ficheiros para `public_html/sites/` quando se faz "Deploy" no cPanel.

Regra: um repositório e um subdomínio por serviço (`plresende-sites` → sites.plresende.pt; `plresende-impressao` → impressao.plresende.pt).
