# CLAUDE.md · sites PL Resende

Contexto completo (como foi feito, arquitetura, páginas, funcionalidades, preços, formulário, site principal no Lovable): ler o [`README.md`](README.md) antes de mexer em qualquer coisa.

## Essencial

- Este repositório é o **sites.plresende.pt** (campanhas Site novo e Loja online). O site principal **plresende.pt** é feito no **Lovable** e não tem código aqui: alterações nele fazem-se com um prompt para o Paulo colar no Lovable.
- Páginas em HTML, CSS e JavaScript escritos à mão, um ficheiro por página, mais `enviar.php`. Corre no **cPanel** da PL Resende (não no GitHub Pages), porque o questionário precisa de PHP para enviar email.
- Falar e escrever sempre em **português de Portugal**. Frases simples, sem jargão; tratar o cliente por «você» implícito.
- Trabalhar num ramo, abrir PR para `main`. Depois do merge, o Paulo faz **Update from Remote** e **Deploy HEAD Commit** no cPanel; dizer-lhe sempre isso no fim.
- Ficheiro novo que tenha de ir para o servidor: acrescentar ao `.cpanel.yml`.
- Regra: **um serviço = um subdomínio = um repositório** (próximo: `plresende-impressao` → impressao.plresende.pt).

## Regras de texto e de preço

- Valores sempre **sem IVA**, com «+ IVA» ao lado.
- Site novo: criação 699 € → **349,50 €**; plano anual **19 €/mês** (228 €/ano); 2 anos **17 €/mês** (204 €/ano). Loja online: 1.190 € → **595 €**; **29 €/mês** (348 €/ano); 2 anos **25 €/mês** (300 €/ano). Não há plano mensal nem sem fidelização.
- O domínio fica sempre em nome da empresa do cliente; o site é da PL Resende, prestado como serviço. Isto vai nas **condições gerais**, não em destaque comercial.
- Contactos: 22 201 2425, WhatsApp 96 340 1241, info@plresende.com, Rua da Alegria, 1035, 4000-048 Porto.
- Sem superlativos nem números que não se possam provar («taxa de satisfação de 100 %», «líderes»).
- Ao mudar preços ou datas, mudar também os PDFs e as mensagens em `/mnt/project-files/campanha-*` e o documento `contexto/pl-resende-contexto-site.md`.

## Ao mexer nas páginas

- Manter as cores em variáveis no topo do `<style>` (laranja `#c44e1b`) e a letra DM Sans.
- Verificar em computador e telemóvel (320–480 px) sem scroll horizontal.
- Não tirar o script das datas (1/12/2026: «Black Friday» → «Campanha até 31 de dezembro»).
- No formulário, manter o campo anti-spam `website`, o campo `campanha` e a alternativa por WhatsApp quando o envio falha.
- Nunca pôr credenciais no repositório: o SMTP autenticado lê `~/sites-smtp.php`, fora da pasta pública.
- Para testar o email no servidor: `https://sites.plresende.pt/enviar.php?diag=1` e o ficheiro `~/envios-sites.log`.
