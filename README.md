# Sites PL Resende · plresende.pt e sites.plresende.pt

Presença online da PL Resende – Tecnologias de Informação, Lda., Rua da Alegria 1035, 4000-048 Porto (NIPC 508 239 052). Este repositório tem o código do subdomínio **sites.plresende.pt** (campanhas Site novo e Loja online) e é também o sítio onde fica documentado o **site principal plresende.pt**, que é feito no Lovable e não tem código aqui.

Este ficheiro é o contexto dos sites: como foram feitos, como estão organizados e tudo o que já fazem. As regras de trabalho para o Claude estão em [`CLAUDE.md`](CLAUDE.md).

## Índice

1. [Como foi desenvolvido](#1-como-foi-desenvolvido)
2. [Arquitetura e publicação](#2-arquitetura-e-publicação)
3. [Estrutura de pastas](#3-estrutura-de-pastas)
4. [Site principal plresende.pt (Lovable)](#4-site-principal-plresendept-lovable)
5. [Campanha Site novo (sites.plresende.pt)](#5-campanha-site-novo-sitesplresendept)
6. [Campanha Loja online (sites.plresende.pt/lojas/)](#6-campanha-loja-online-sitesplresendeptlojas)
7. [Questionário e envio de email (`enviar.php`)](#7-questionário-e-envio-de-email-enviarphp)
8. [Preços e condições](#8-preços-e-condições)
9. [Como publicar uma alteração](#9-como-publicar-uma-alteração)
10. [Como criar o site de um novo serviço](#10-como-criar-o-site-de-um-novo-serviço)
11. [Fora do repositório](#11-fora-do-repositório)
12. [Pontos em aberto](#12-pontos-em-aberto)

---

## 1. Como foi desenvolvido

**Site principal (plresende.pt).** Construído pelo Paulo no **Lovable**, um construtor de sites com IA (endereço de trabalho plresende.lovable.app), para substituir o site antigo em WordPress/Elementor. Os textos seguem o documento «PL Resende: contexto para propostas comerciais» (4/11/2026). As alterações fazem-se no Lovable com prompts; o da campanha Black Friday está em `campanha-website-alojamento/prompt-lovable-banner.md` (fora do repositório).

**sites.plresende.pt.** Feito de raiz com o Claude, em conversas e PRs, com o mesmo «método PC Infor» usado em www.pcinfor.pt: landing page de venda, PDF A4 de 2 folhas e mensagens de WhatsApp, email e redes sociais. É HTML, CSS e JavaScript escritos à mão, sem base de dados nem plugins, mais um ficheiro PHP para o questionário enviar email.

Cronologia:

| Data | O que entrou |
|---|---|
| 14/11/2026 | Primeira versão da campanha website e alojamento (criação oferecida, 29 €/mês). Análise da concorrência DotCompany. Passa a campanha Black Friday, com pedidos até 30/11/2026. |
| 14 a 17/11 | Estudo de preços por permanência (Pilar Digital, Siteron, Boxless, Studio351, Amen, Pipeline Media). Regra do Paulo: sem mensalidade nem opção sem fidelização; só plano anual e plano de 2 anos. |
| 17/11 | Modelo final igual à proposta José Modesto: criação 699 € → 349,50 € (−50 %), 19 €/mês anual, 17 €/mês a 2 anos, assistente 24 h, 1 revisão e 1 newsletter mensais. Regras do domínio e do site nas condições gerais. Questionário de 2 passos inspirado no da Pipeline Media. Escolha do subdomínio `sites` e da regra «um serviço, um subdomínio, um repositório». |
| 17/11, 03h20 | Primeiro commit e primeiro Deploy no cPanel (Git Version Control). AutoSSL ativo (certificado válido até 15/02/2027, renova sozinho). |
| 17/11, 03h41 a 05h00 | 9 commits para pôr o email do formulário a funcionar e melhorá-lo: confirmação ao cliente e registo de envios, alternativa por SMTP local e diagnóstico, mensagem final no ecrã, envio autenticado e cópia ao cliente, remetente igual à conta autenticada, AUTH LOGIN, email ao cliente em HTML com logótipo e assinatura, e mudança automática da campanha a 1 de dezembro. |
| 20/11 | Campanha Loja online em `/lojas/`, com a PC Infor como montra, análise de mercado (Shopify, Wix, WooCommerce, agências) e produtos ilimitados ([PR #1](https://github.com/pauloavresende-ai/plresende-sites/pull/1), por aprovar). |
| 21/11 | Esta documentação (README e CLAUDE.md). |

## 2. Arquitetura e publicação

| Peça | Endereço | Tecnologia | Alojamento |
|---|---|---|---|
| Site principal | plresende.pt | Lovable (aplicação web; imagens e vídeo em Cloudflare R2) | Lovable |
| Campanha Site novo | sites.plresende.pt | HTML/CSS/JS + PHP, este repositório | cPanel da PL Resende, `public_html/sites` |
| Campanha Loja online | sites.plresende.pt/lojas/ | Igual, pasta `lojas/` | Mesmo cPanel, `public_html/sites/lojas` |
| Montra PC Infor | www.pcinfor.pt | HTML/CSS estático, repositório `pcinfor-site` | GitHub Pages |

- **Porque cPanel e não GitHub Pages:** o GitHub Pages não corre PHP, por isso o questionário não conseguiria enviar email (só por WhatsApp ou por um serviço externo que fica com os dados). No cPanel, as respostas chegam por email ao info@plresende.com e os dados ficam no servidor da PL Resende.
- **GitHub + cPanel juntos:** o código fica aqui; o cPanel vai buscá-lo com **Git™ Version Control** (repositório em `~/repositories/plresende-sites`) e o `.cpanel.yml` copia os ficheiros para `public_html/sites/`.
- **Domínios no cPanel:** o domínio principal da conta é plresende.com; plresende.pt e outros são domínios adicionais. O DNS e o email estão no mesmo cPanel, por isso o registo `sites` foi criado sozinho. O site principal no Lovable não é afetado.
- Letra **DM Sans** (Google Fonts); laranja `#c44e1b`, cores em variáveis no topo do `<style>` de cada página. Cada página é um único ficheiro HTML com o CSS e o JS lá dentro.
- Links do WhatsApp para `wa.me/351963401241` com mensagem já escrita.

## 3. Estrutura de pastas

```
index.html          landing page da campanha Site novo (com questionário)
enviar.php          recebe os questionários, envia o email à PL Resende e a confirmação ao cliente
logo-email.png      logótipo usado na assinatura dos emails
avatar-email.png    avatar usado na assinatura dos emails
.cpanel.yml         tarefas do Deploy no cPanel (copia para public_html/sites/)
README.md           este ficheiro
CLAUDE.md           regras de trabalho para o Claude
lojas/index.html    landing page da campanha Loja online (no PR #1)
lojas/img/          capturas da loja www.pcinfor.pt (no PR #1)
```

Fora da pasta pública, no cPanel: `~/envios-sites.log` (registo de envios) e `~/sites-smtp.php` (credenciais SMTP, opcional). Nunca pôr credenciais no repositório.

## 4. Site principal plresende.pt (Lovable)

### Páginas

- `/` página inicial: faixa e destaque da campanha em vigor; vídeo de apresentação (animação do logótipo); **Sobre Nós** («O seu negócio, preparado para avançar.»); **Serviços** (8 cartões com «Conhecer serviço» e «Pedir informações»); **Descobrir a solução**; **Onde estamos** (morada, telefone, email, Google Maps) com o formulário de contacto; rodapé.
- `/servicos/software-de-gestao`, `/servicos/gestao-e-manutencao`, `/servicos/assistencia-ti`, `/servicos/aluguer-de-impressao` (Impressão e Arquivo Digital), `/servicos/solucoes-de-aluguer`, `/servicos/web-e-cloud`, `/servicos/automacao-com-ia`, `/servicos/videovigilancia-e-controlo-de-acessos`: todas no mesmo molde (descrição, 3 ou 4 pilares, «Quer mais informação?», botões «Pedir informações» e «Enviar email», contactos).
- `/assistencia` pedir assistência (noindex), `/clientes` área de cliente, `/politica-de-privacidade` (atualizada a 5/11/2026).
- `/en`, `/fr`, `/es` versões em inglês, francês e espanhol (serviços em `/en/services/<slug>`).

### Funcionalidades

- **Assistente virtual com IA** («Falar com assistente»): botão flutuante com avatar em todas as páginas, 24 h. Responde só com base nos conteúdos do site; as respostas não são propostas vinculativas (a equipa confirma preços, prazos e condições). Pode pedir o NIF; o histórico fica na área de cliente.
- **Ditado por voz** no assistente: depois de autorizar o microfone, o áudio é transcrito por IA e só o texto revisto fica guardado.
- **Descobrir a solução** («Qual é o próximo passo da sua empresa?»): o visitante descreve a necessidade e carrega em «Ver recomendação»; o site sugere o serviço ou ponto de partida. Avisa para não escrever palavras-passe.
- **Formulário de contacto** («Fale connosco ou peça informação»): serviço pretendido (8 serviços ou «Outro assunto»), nome, email, telefone, descrição e consentimento RGPD; aviso interno por email à equipa.
- **Pedir assistência**: «Já é cliente?». Cliente entra na conta e pede em `/clientes/assistencia`; quem não é segue como assistência pontual. O sistema verifica na ficha de cliente se há pack de horas ativo; sem pack, é intervenção pontual.
- **Área de cliente**: conta com email e palavra-passe cifrada, dados separados por cliente; dados de faturação, histórico de conversas e pedidos de assistência. Do lado da PL Resende: fichas de clientes e fotografias internas anexadas pelos técnicos. *(A área autenticada não foi vista por dentro; descrição a partir da Política de Privacidade.)*
- **4 idiomas** com seletor PT/EN/FR/ES.
- Botões flutuantes «Ligar», «Contactar» e «Falar com assistente».
- Metadados para Google e redes sociais, Google Maps, rodapé com Política de Privacidade, Área de cliente e Livro de Reclamações Online.
- **Privacidade e segurança**: HTTPS, só cookies estritamente necessários (mais os do Google Maps), proteção contra abuso, IP cifrado ou resumido, conservação de potenciais clientes até 24 meses e de clientes pelo prazo legal.
- **Campanha Black Friday** (prompt do Lovable): barra no topo com «Pedir o meu site →», cartão de destaque na página inicial e botão «Ver campanha Black Friday» em Web e Cloud, todos para sites.plresende.pt com `?utm_source=plresende.pt&utm_campaign=blackfriday2026`. A 1/12/2026 os textos passam a «Campanha até 31 de dezembro».

### Os oito serviços (agrupados por gerir, manter, equipar, evoluir, proteger)

| Grupo | Serviço | Em resumo |
|---|---|---|
| Gerir | Software de Gestão | Faturação, gestão comercial, stock, contabilidade e CRM (Sage, XD Software), com acompanhamento na escolha e na utilização. |
| Manter | Gestão e Manutenção | Computadores, redes e servidores; orientação sobre manter ou renovar. |
| Manter | Assistência TI | Diagnóstico, apoio remoto e assistência presencial com agendamento. |
| Equipar | Impressão e Arquivo Digital | Impressoras A4/A3, gestão da impressão e arquivo digital em hardware e cloud. |
| Equipar | Soluções de Aluguer | Alternativa à compra, não só para impressoras; condições claras na proposta. |
| Evoluir | Web e Cloud | Alojamento, domínios e email profissional; criação do site. |
| Evoluir | Automação com IA | Processo → automação → validação humana, à medida dos sistemas da empresa. |
| Proteger | Videovigilância e Controlo de Acessos | Câmaras, deteção de intrusão e controlo de acessos, respeitando a privacidade. |

## 5. Campanha Site novo (sites.plresende.pt)

Página `index.html`, por ordem (âncoras entre parênteses):

1. **Faixa Black Friday** e cabeçalho com menu (O que inclui, Como funciona, Alojamento, Preço, Pedir site) e telefone.
2. **Hero** (`#inicio`, `#preco-hero`): «Site novo para a sua empresa. Criação a metade do preço.», 699 € riscado e 349,50 € + IVA, plano 19 €/mês, botões «Pedir o meu site» e WhatsApp, e uma maqueta de um site de cliente (telemóvel, tablet e PC) com o assistente a responder às 23h40.
3. **Porquê agora**: metade do preço, tudo tratado por nós, perto de si desde 2007.
4. **O que está incluído** (`#incluido`): site próprio, assistente 24 h, trabalhos e pedidos, domínio e email; 1 newsletter mensal, 1 revisão mensal, RGPD e Livro de Reclamações.
5. **Antes e depois**: tabela com Google, email, domínio, atendimento, obrigações legais e investimento.
6. **Como funciona** (`#como-funciona`): fala connosco, envia textos e fotos, site publicado em 7 dias úteis.
7. **Alojamento** (`#alojamento`): servidores em Portugal, 30 GB SSD, email ilimitado, SSL, webmail/IMAP/POP3, backups diários, anti-spam e antivírus, tráfego ilimitado, Imunify360, assistência 24/7.
8. **Preço** (`#preco`): tabela com criação, incluídos, plano anual e plano 2 anos.
9. **Questionário** (`#comecar`, `#pedido`, `#done`): ver secção 7.
10. **Perguntas frequentes**: domínio, mudança de site e email, porquê plano anual, validade do desconto.
11. **Chamada final e contactos**: WhatsApp 96 340 1241, telefone 22 201 2425, info@plresende.com, morada.
12. **Condições gerais** (`#condicoes`, bloco que se abre) e rodapé legal.

**Mudança automática de datas:** um script no fim da página compara a data do browser com 1/12/2026 e, a partir daí, troca «Black Friday · até 30 de novembro» por «Campanha até 31 de dezembro» e todas as datas 30/11 por 31/12. Não esconde nada depois de 31/12: a campanha só sai quando o Paulo pedir.

## 6. Campanha Loja online (sites.plresende.pt/lojas/)

*Está no [PR #1](https://github.com/pauloavresende-ai/plresende-sites/pull/1), ainda por aprovar e publicar.*

- Mensagem: «A sua loja online a vender 24 horas. Criação a metade do preço.», com a **PC Infor como caso real** (capturas em `lojas/img/`, 40+ equipamentos, 130+ packs, 3 formas de pagamento).
- Secções: Porquê esta loja; Caso real PC Infor; Como se vende (Escolhe, Paga, É avisado, Prepara, Recebe); O que está incluído; Comparação com plataformas por subscrição; Preço; Questionário; Perguntas frequentes; Contactos; Condições gerais.
- O que se vende: loja com a marca do cliente, **produtos ilimitados** (uma página por produto), pagamentos Stripe (MB WAY, Multibanco, cartão) com NIF e morada, levantamento ou envio, emails automáticos pelo Make (recebida, pronta, enviada), folha Google de encomendas, dúvidas por WhatsApp, páginas legais, domínio e email, assinatura de email, 5 atualizações por mês.
- Limites: um produto ou pack por pagamento (sem carrinho); o plano grátis do Make só aguenta poucas dezenas de encomendas por mês; a loja do cliente deve ficar no cPanel e não no GitHub Pages (as regras do GitHub não permitem lojas). Publicação em 10 dias úteis.
- Usa o mesmo `enviar.php`, com o campo escondido `campanha` a dizer «loja»; o email chega com o assunto «Pedido de loja online».

## 7. Questionário e envio de email (`enviar.php`)

**Na página (2 passos):**
1. Contactos: nome*, telemóvel/WhatsApp*, email, empresa*, NIF, onde trabalha.
2. Negócio: atividade* (lista), principais serviços ou produtos, descrição*, domínio (sim/não/não sei e qual), site ou redes atuais, logótipo, fotografias, plano (anual/2 anos/não sei), forma de contacto preferida, observações, consentimento RGPD*. Na loja: também número de produtos, se já vende online e como quer entregar.

O JavaScript valida os obrigatórios (`check`), muda de passo (`go`), monta o resumo (`summary`) e faz `fetch('enviar.php')`. Se a resposta for `{"ok":true}`, mostra «Pedido recebido. Obrigado!»; se falhar, mostra o botão **«Enviar respostas pelo WhatsApp»** com tudo já escrito.

**No servidor:**
- Só aceita POST; o campo escondido `website` é a armadilha anti-spam (se vier preenchido, responde «ok» e não envia).
- Limpa e corta cada campo; monta o email para **info@plresende.com**, enviado de **info@plresende.com** (conta do mesmo cPanel), com `Reply-To` no email do cliente.
- Envia ao cliente um **email de confirmação em HTML** (com versão em texto) com cópia das respostas, logótipo, avatar e a assinatura geral PL Resende.
- Tenta três vias, por ordem: `mail()` do PHP; SMTP local em 127.0.0.1 (porta 25 ou 587); SMTP autenticado (AUTH PLAIN e, se falhar, AUTH LOGIN) com as credenciais de `~/sites-smtp.php`.
- Regista cada envio e o motivo de cada falha em `~/envios-sites.log`.
- **Diagnóstico:** abrir `https://sites.plresende.pt/enviar.php?diag=1` mostra a versão do PHP, se `mail()` está ligado, se há SMTP local, se as credenciais existem e se o registo pode ser escrito.

## 8. Preços e condições

Valores sem IVA. O site principal não publica preços (só a campanha).

| | Site novo | Loja online |
|---|---|---|
| Criação | 349,50 € (699 € −50 %) | 595 € (1.190 € −50 %) |
| Plano anual | 228 €/ano (19 €/mês) | 348 €/ano (29 €/mês) |
| Plano 2 anos | 204 €/ano (17 €/mês) | 300 €/ano (25 €/mês) |
| Incluído no plano | 1 revisão + 1 newsletter por mês | 5 atualizações por mês |
| Publicação | 7 dias úteis | 10 dias úteis (com Stripe ativo) |

Condições gerais (iguais nas duas): pedidos até 30/11/2026 (depois 31/12/2026); permanência de 12 ou 24 meses paga no início de cada ano; domínio registado em nome da empresa do cliente, com código de transferência se sair; site ou loja (design, código, estrutura) da PL Resende, disponibilizado como serviço enquanto o plano estiver ativo; textos, fotos, logótipo (e na loja os dados de clientes e encomendas) continuam do cliente; renovação por igual período; sem renovação, é desativado no fim do período pago. Na loja, as comissões do Stripe são cobradas ao cliente na conta dele.

Preços de assistência (internos, não publicados): hora avulsa 55 €, deslocação no Grande Porto 40 €, packs de 5/10/20/30 h a 250/475/900/1.275 €. Detalhe em `contexto/pl-resende-faturacao-servicos.md` (fora do repositório).

## 9. Como publicar uma alteração

1. O Claude trabalha num ramo `claude/...`, faz commit e abre PR para `main`.
2. O Paulo aprova e faz merge do PR.
3. No cPanel: **Git™ Version Control › Manage › Pull or Deploy › Update from Remote** e depois **Deploy HEAD Commit**.
4. Abrir https://sites.plresende.pt (e /lojas/) e, se o formulário mudou, fazer um pedido de teste e confirmar que chega ao info@plresende.com.

Ficheiros novos só são publicados se forem acrescentados ao `.cpanel.yml`.

## 10. Como criar o site de um novo serviço

Regra: **um serviço = um subdomínio = um repositório**, com uma palavra curta, sem acentos nem hífenes. O próximo previsto é o aluguer de impressoras:

| Serviço | Subdomínio | Repositório | Pasta no cPanel |
|---|---|---|---|
| Presença digital e lojas | sites.plresende.pt | plresende-sites | public_html/sites |
| Aluguer de impressoras | impressao.plresende.pt | plresende-impressao | public_html/impressao |

Passos: criar o repositório público e vazio no GitHub; no cPanel criar o subdomínio com raiz em `public_html/<nome>` (desmarcar «Share document root»); Git Version Control › Create com `https://github.com/pauloavresende-ai/<repo>.git` e caminho `repositories/<repo>`; Deploy; AutoSSL; teste do formulário. Copiar o `enviar.php` deste repositório e ajustar o nome do registo e o assunto.

## 11. Fora do repositório

- **Lovable**: o código e os conteúdos do site principal plresende.pt.
- **cPanel**: `~/envios-sites.log`, `~/sites-smtp.php`, email info@plresende.com, DNS, SSL.
- **Pasta do projeto** (`/mnt/project-files/`):
  - `contexto/pl-resende-contexto-site.md`: o conteúdo completo dos sites, pronto a usar como contexto.
  - `contexto/pl-resende-contexto.md`, `contexto/pl-resende-contexto-propostas.md`, `contexto/pl-resende-faturacao-servicos.md`.
  - `campanha-website-alojamento/`: PDF A4, mensagens de envio, análises de concorrência, preços por permanência, guia cPanel + GitHub, prompt do Lovable.
  - `campanha-loja-online/`: PDF A4, mensagens de envio, análise de mercado de lojas online.

## 12. Pontos em aberto

- Aprovar e publicar o PR #1 (Loja online) e confirmar os preços da loja.
- Na versão inglesa do plresende.pt ainda há rótulos em português («Onde estamos», «Fale connosco ou peça informação», «Política de Privacidade»).
- O plresende.pt não mostra horário nem redes sociais.
- As campanhas terminam a 31/12/2026: depois, rever preços e textos aqui, no Lovable e nos PDFs.
- Rever se a PC Infor deve sair do GitHub Pages para o cPanel (regras do GitHub sobre lojas).
