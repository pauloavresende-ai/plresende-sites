<?php
// Recebe o questionário de sites.plresende.pt e envia-o por email à PL Resende.
// Devolve JSON {"ok":true} ou {"ok":false}. A página mostra a alternativa por WhatsApp se falhar.
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$DESTINO   = 'info@plresende.com';          // quem recebe os pedidos
$REMETENTE = 'info@plresende.com';          // conta de email que existe neste cPanel
$LOG       = dirname(__DIR__, 2) . '/envios-sites.log';   // fora da pasta pública

function registar($txt) { global $LOG; @file_put_contents($LOG, date('Y-m-d H:i:s') . ' | ' . $txt . "\n", FILE_APPEND); }

// Diagnóstico rápido: abrir enviar.php?diag=1 no browser
if (isset($_GET['diag'])) {
  echo json_encode([
    'php' => PHP_VERSION,
    'mail' => function_exists('mail') && !in_array('mail', array_map('trim', explode(',', (string)ini_get('disable_functions')))),
    'mbstring' => function_exists('mb_substr'),
    'smtp_local' => (bool)@fsockopen('127.0.0.1', 25, $en, $es, 3),
    'smtp_587' => (bool)@fsockopen('127.0.0.1', 587, $en, $es, 3),
    'credenciais' => is_array(@include dirname(__DIR__, 2) . '/sites-smtp.php'),
    'log' => is_writable(dirname($LOG)),
  ]);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo '{"ok":false}'; exit; }
if (!empty($_POST['website'])) { echo '{"ok":true}'; exit; } // anti-spam (campo escondido)

function cortar($v, $max) { return function_exists('mb_substr') ? mb_substr($v, 0, $max, 'UTF-8') : substr($v, 0, $max); }
function campo($k, $max = 1500) {
  $v = isset($_POST[$k]) ? trim((string)$_POST[$k]) : '';
  $v = str_replace(["\r", "\0"], '', $v);
  return cortar($v, $max);
}

$nome = campo('nome', 120); $tel = campo('telefone', 40); $empresa = campo('empresa', 160);
$atividade = campo('atividade', 160); $descricao = campo('descricao'); $rgpd = campo('rgpd', 5);
if ($nome === '' || strlen(preg_replace('/\D/', '', $tel)) < 9 || $empresa === '' || $atividade === '' || $descricao === '' || $rgpd !== 'Sim') {
  registar("RECUSADO (campos em falta) | {$empresa}");
  http_response_code(422); echo '{"ok":false}'; exit;
}

$loja = stripos(campo('campanha', 40), 'loja') !== false;
$tipo = $loja ? 'loja online' : 'site';

$email = campo('email', 160);
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) { $email = ''; }

$rotulos = [
  'campanha'=>'Campanha','nome'=>'Nome','telefone'=>'Telemóvel','email'=>'Email','empresa'=>'Empresa','nif'=>'NIF','zona'=>'Zona',
  'atividade'=>'Atividade','produtos'=>'O que vende','num_produtos'=>'N.º de produtos','vende_online'=>'Vende online hoje','entrega'=>'Entrega','servicos'=>'Serviços','descricao'=>'Descrição','dominio_tem'=>'Já tem domínio',
  'dominio'=>'Domínio','site_atual'=>'Site / redes','logotipo'=>'Logótipo','fotos'=>'Fotografias',
  'plano'=>'Plano','contacto'=>'Contacto preferido','observacoes'=>'Observações','rgpd'=>'Consentimento RGPD'
];
$linhas = [];
foreach ($rotulos as $k => $r) { $v = campo($k); if ($v !== '') { $linhas[] = $r . ': ' . $v; } }
$linhas[] = '';
$linhas[] = 'Recebido em ' . date('d/m/Y H:i') . ' · IP ' . ($_SERVER['REMOTE_ADDR'] ?? '');

function assunto($t) { return '=?UTF-8?B?' . base64_encode($t) . '?='; }

// Credenciais da conta de email, num ficheiro FORA da pasta pública:
// /home/UTILIZADOR/sites-smtp.php  com  <?php return ['user'=>'info@plresende.com','pass'=>'PALAVRA-PASSE'];
$CRED = @include dirname(__DIR__, 2) . '/sites-smtp.php';
if (!is_array($CRED)) { $CRED = null; }
if ($CRED && !empty($CRED['user'])) { $REMETENTE = $CRED['user']; }   // envia em nome da conta autenticada

// Envio por SMTP no servidor de email do próprio cPanel (porta 587 com autenticação; 25 sem autenticação como último recurso)
function smtp_local($de, $para, $assunto, $corpo, $cab, $comCred = true) {
  global $CRED;
  $cred = $comCred ? $CRED : null;
  $porta = $cred ? 587 : 25;
  $s = @fsockopen('127.0.0.1', $porta, $en, $es, 10);
  if (!$s) { return "sem ligação SMTP {$porta}: " . $es; }
  stream_set_timeout($s, 15);
  $ler = function () use ($s) { $r = ''; while (($l = fgets($s, 515)) !== false) { $r .= $l; if (isset($l[3]) && $l[3] === ' ') break; } return $r; };
  $cmd = function ($c, $esperado) use ($s, $ler) { fwrite($s, $c . "\r\n"); $r = $ler(); return strpos($r, $esperado) === 0 ? true : trim($r); };
  $ler();
  $ehlo = $cmd('EHLO sites.plresende.pt', '250');
  if ($ehlo !== true) { fclose($s); return $ehlo; }
  if ($cred) {
    $tls = false;
    $r = $cmd('STARTTLS', '220');
    if ($r === true) { $tls = true;
      stream_context_set_option($s, 'ssl', 'verify_peer', false);
      stream_context_set_option($s, 'ssl', 'verify_peer_name', false);
      if (!@stream_socket_enable_crypto($s, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) { fclose($s); return 'falhou TLS'; }
      $cmd('EHLO sites.plresende.pt', '250');
    }
    $u = trim((string)$cred['user']); $pw = (string)$cred['pass'];
    $r = $cmd('AUTH PLAIN ' . base64_encode("\0" . $u . "\0" . $pw), '235');
    if ($r !== true) {   // tenta AUTH LOGIN
      $r2 = $cmd('AUTH LOGIN', '334');
      if ($r2 === true) { $cmd(base64_encode($u), '334'); $r2 = $cmd(base64_encode($pw), '235'); }
      if ($r2 !== true) { fclose($s); return 'autenticação recusada [' . $u . ', ' . strlen($pw) . ' caracteres, tls=' . ($tls ? 'sim' : 'não') . ']: ' . $r; }
    }
  }
  foreach ([["MAIL FROM:<{$de}>", '250'], ["RCPT TO:<{$para}>", '25'], ['DATA', '354']] as $p) {
    $r = $cmd($p[0], $p[1]); if ($r !== true) { fclose($s); return $r; }
  }
  $msg = "To: {$para}\r\nSubject: {$assunto}\r\nDate: " . date('r') . "\r\n" . $cab . "\r\n" . str_replace(["\r\n", "\n"], "\r\n", $corpo);
  $msg = preg_replace('/^\./m', '..', $msg);
  $r = $cmd($msg . "\r\n.", '250'); $cmd('QUIT', '221'); fclose($s);
  return $r;
}

function enviar($para, $assunto, $corpo, $cab, $de, &$via) {
  $ok = function_exists('mail') ? @mail($para, $assunto, $corpo, $cab, '-f' . $de) : false;
  if ($ok) { $via = 'mail'; return true; }
  global $CRED;
  $r = smtp_local($de, $para, $assunto, $corpo, $cab);
  if ($r === true) { $via = $CRED ? 'smtp autenticado' : 'smtp'; return true; }
  if ($CRED) {
    $r2 = smtp_local($de, $para, $assunto, $corpo, $cab, false);
    if ($r2 === true) { $via = 'smtp (sem autenticação; 587 falhou: ' . $r . ')'; return true; }
    $r .= ' / 25: ' . $r2;
  }
  $via = 'smtp: ' . $r; return false;
}

$cab  = "From: PL Resende Sites <{$REMETENTE}>\r\n";
if ($email !== '') { $cab .= "Reply-To: {$email}\r\n"; }
$cab .= "MIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n";

$via = '';
$ok = enviar($DESTINO, assunto('Pedido de ' . $tipo . ' · ' . $empresa), implode("\n", $linhas), $cab, $REMETENTE, $via);

// Confirmação automática para o cliente (se deixou email)
$viaCli = '-';
if ($ok && $email !== '') {
  $copia = implode("\n", array_slice($linhas, 0, -2));
  $h = function ($t) { return htmlspecialchars($t, ENT_QUOTES, 'UTF-8'); };
  $linhasHtml = '';
  foreach (array_slice($linhas, 0, -2) as $l) {
    if ($l === '') continue;
    $par = explode(': ', $l, 2);
    $linhasHtml .= '<tr><td style="padding:6px 12px 6px 0;color:#6b6460;vertical-align:top;white-space:nowrap">' . $h($par[0]) . "</td>\n"
                 . '<td style="padding:6px 0;color:#1f1a17">' . nl2br($h($par[1] ?? '')) . "</td></tr>\n";
  }
  $htmlCli = '<!doctype html><html lang="pt"><head><meta charset="utf-8"></head>' . "\n"
  . '<body style="margin:0;padding:0;background:#f4f1ee;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.5;color:#1f1a17">' . "\n"
  . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f1ee"><tr><td align="center" style="padding:24px 12px">' . "\n"
  . '<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:12px;overflow:hidden">' . "\n"
  . '<tr><td style="height:6px;background:#c44e1b"></td></tr>' . "\n"
  . '<tr><td style="padding:28px 32px 8px">' . "\n"
  . '<p style="margin:0 0 14px">Olá ' . $h($nome) . ',</p>' . "\n"
  . '<p style="margin:0 0 14px">Recebemos o seu pedido de ' . $tipo . ' para <b>' . $h($empresa) . '</b>. Vamos analisar as suas respostas e entramos em contacto no dia útil seguinte.</p>' . "\n"
  . '<p style="margin:22px 0 8px;font-weight:bold;color:#c44e1b">Cópia das suas respostas</p>' . "\n"
  . '<table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;font-size:14px;border-top:1px solid #eee">' . "\n" . $linhasHtml . "</table>\n"
  . '<p style="margin:22px 0 0">Se precisar de alguma coisa, responda a este email ou fale connosco pelo WhatsApp.</p>' . "\n"
  . '<p style="margin:14px 0 0">Com os melhores cumprimentos,</p>' . "\n"
  . '</td></tr>' . "\n"
  . '<tr><td style="padding:8px 32px 0">' . "\n"
  . '<table role="presentation" cellpadding="0" cellspacing="0" style="width:100%"><tr>' . "\n"
  . '<td style="width:116px;padding:8px 16px 16px 0;vertical-align:middle;border-right:3px solid #d2691e"><img src="https://sites.plresende.pt/avatar-email.png" width="100" height="100" alt="Apoio ao Cliente PL Resende" style="display:block;border:0;border-radius:50%"></td>' . "\n"
  . '<td style="padding:8px 0 16px 18px;vertical-align:top;font-size:13px;line-height:1.7;color:#333">' . "\n"
  . '<div style="font-size:20px;font-weight:bold;color:#1f1f1f;line-height:1.2">Apoio ao Cliente</div>' . "\n"
  . '<div style="font-size:12px;font-weight:bold;letter-spacing:2px;color:#d2691e;margin:2px 0 8px">PL RESENDE</div>' . "\n"
  . '<span style="color:#d2691e">&#9993;</span>&nbsp; <a href="mailto:info@plresende.com" style="color:#333;text-decoration:none">info@plresende.com</a><br>' . "\n"
  . '<span style="color:#d2691e">&#9742;</span>&nbsp; <a href="tel:+351222012425" style="color:#333;text-decoration:none">222 012 425</a> <span style="color:#999;font-size:11px">(chamada para a rede fixa nacional)</span><br>' . "\n"
  . '<span style="color:#d2691e;visibility:hidden">&#9742;</span>&nbsp; <a href="tel:+351963401241" style="color:#333;text-decoration:none">963 401 241</a> <span style="color:#999;font-size:11px">(chamada para a rede móvel nacional)</span><br>' . "\n"
  . '<span style="color:#d2691e">&#8853;</span>&nbsp; <a href="https://www.plresende.com" style="color:#333;text-decoration:none">www.plresende.com</a><br>' . "\n"
  . '<span style="color:#d2691e">&#8982;</span>&nbsp; Rua da Alegria, 1035 · 4000-048 Porto' . "\n"
  . '</td></tr></table></td></tr>' . "\n"
  . '<tr><td style="background:#252525;border-top:3px solid #d2691e;padding:18px 24px">' . "\n"
  . '<table role="presentation" cellpadding="0" cellspacing="0" style="width:100%"><tr>' . "\n"
  . '<td style="width:76px;padding-right:16px;border-right:1px solid #555;vertical-align:middle"><a href="https://www.plresende.com"><img src="https://sites.plresende.pt/logo-email.png" width="64" height="64" alt="PL Resende" style="display:block;border:0;border-radius:6px"></a></td>' . "\n"
  . '<td style="padding-left:16px;vertical-align:middle">' . "\n"
  . '<div style="font-size:19px;font-weight:bold;color:#ffffff;line-height:1.3">Tecnologia que avança<span style="color:#d2691e">.</span></div>' . "\n"
  . '<div style="font-size:12px;line-height:1.7;color:#e8945a;margin-top:4px">Software de Gestão &middot; Assistência TI &middot; Web &amp; Cloud &middot; Automação com IA &middot; Videovigilância &middot; Impressão e Arquivo Digital &middot; Soluções de Aluguer</div>' . "\n"
  . '</td></tr></table></td></tr>' . "\n"
  . '<tr><td style="padding:16px 24px 20px;font-size:10px;line-height:1.5;color:#999">' . "\n"
  . '<b style="letter-spacing:1px;color:#777">CONFIDENCIALIDADE</b><br>Esta mensagem e seus anexos são destinados à utilização exclusiva dos seus destinatários, e poderá conter informação privilegiada e confidencial. Se o leitor desta mensagem não for o seu destinatário ou um seu representante autorizado, fica expressamente notificado que qualquer divulgação desta comunicação é estritamente proibida. Se recebeu esta comunicação por engano, agradecemos que nos avise de imediato por e-mail e apague esta mensagem e todos os seus anexos do seu sistema.<br><br>' . "\n"
  . '<b style="letter-spacing:1px;color:#777">CONFIDENTIALITY</b><br>This message and its attachments are intended solely for the use of their addressees and may contain privileged and confidential information. If the reader of this message is not the intended recipient or an authorised representative, you are hereby notified that any disclosure of this communication is strictly prohibited. If you have received this communication in error, please notify us immediately by e-mail and delete this message and all its attachments from your system.' . "\n"
  . '</td></tr></table>' . "\n"
  . '</td></tr></table></body></html>' . "\n";
  $txtCli = "Olá {$nome},\n\nRecebemos o seu pedido de {$tipo} para {$empresa}. Vamos analisar as suas respostas e entramos em contacto no dia útil seguinte.\n\nCópia das suas respostas:\n\n{$copia}\n\nSe precisar de alguma coisa, responda a este email ou fale connosco pelo WhatsApp.\n\nCom os melhores cumprimentos,\n\nApoio ao Cliente · PL Resende\ninfo@plresende.com\n222 012 425 (chamada para a rede fixa nacional)\n963 401 241 (chamada para a rede móvel nacional)\nwww.plresende.com\nRua da Alegria, 1035 · 4000-048 Porto\n\nTecnologia que avança.\n";
  $fronteira = 'plr-' . bin2hex(random_bytes(8));
  $corpoCli = "--{$fronteira}\nContent-Type: text/plain; charset=UTF-8\nContent-Transfer-Encoding: 8bit\n\n{$txtCli}\n"
            . "--{$fronteira}\nContent-Type: text/html; charset=UTF-8\nContent-Transfer-Encoding: 8bit\n\n{$htmlCli}\n--{$fronteira}--\n";
  $cabCli = "From: PL Resende <{$REMETENTE}>\r\nReply-To: {$DESTINO}\r\nMIME-Version: 1.0\r\nContent-Type: multipart/alternative; boundary=\"{$fronteira}\"\r\n";
  if (!enviar($email, assunto('Recebemos o seu pedido de ' . $tipo . ' · PL Resende'), $corpoCli, $cabCli, $REMETENTE, $viaCli)) { $viaCli = 'FALHOU ' . $viaCli; }
}

registar("{$empresa} | interno=" . ($ok ? 'ok via ' . $via : 'FALHOU ' . $via) . " | cliente={$viaCli}");
echo $ok ? '{"ok":true}' : '{"ok":false}';
