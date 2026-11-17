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

$email = campo('email', 160);
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) { $email = ''; }

$rotulos = [
  'nome'=>'Nome','telefone'=>'Telemóvel','email'=>'Email','empresa'=>'Empresa','nif'=>'NIF','zona'=>'Zona',
  'atividade'=>'Atividade','servicos'=>'Serviços','descricao'=>'Descrição','dominio_tem'=>'Já tem domínio',
  'dominio'=>'Domínio','site_atual'=>'Site / redes','logotipo'=>'Logótipo','fotos'=>'Fotografias',
  'plano'=>'Plano','contacto'=>'Contacto preferido','observacoes'=>'Observações','rgpd'=>'Consentimento RGPD'
];
$linhas = [];
foreach ($rotulos as $k => $r) { $v = campo($k); if ($v !== '') { $linhas[] = $r . ': ' . $v; } }
$linhas[] = '';
$linhas[] = 'Recebido em ' . date('d/m/Y H:i') . ' · IP ' . ($_SERVER['REMOTE_ADDR'] ?? '');

function assunto($t) { return '=?UTF-8?B?' . base64_encode($t) . '?='; }

// Envio por SMTP local (servidor de email do próprio cPanel), usado se mail() falhar
function smtp_local($de, $para, $assunto, $corpo, $cab) {
  $s = @fsockopen('127.0.0.1', 25, $en, $es, 10);
  if (!$s) { return 'sem ligação SMTP: ' . $es; }
  stream_set_timeout($s, 15);
  $ler = function () use ($s) { $r = ''; while (($l = fgets($s, 515)) !== false) { $r .= $l; if (isset($l[3]) && $l[3] === ' ') break; } return $r; };
  $cmd = function ($c, $esperado) use ($s, $ler) { fwrite($s, $c . "\r\n"); $r = $ler(); return strpos($r, $esperado) === 0 ? true : trim($r); };
  $ler();
  foreach ([['EHLO sites.plresende.pt', '250'], ["MAIL FROM:<{$de}>", '250'], ["RCPT TO:<{$para}>", '25'], ['DATA', '354']] as $p) {
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
  $erro = error_get_last()['message'] ?? 'mail() indisponível';
  $r = smtp_local($de, $para, $assunto, $corpo, $cab);
  if ($r === true) { $via = 'smtp'; return true; }
  $via = 'mail: ' . $erro . ' / smtp: ' . $r; return false;
}

$cab  = "From: PL Resende Sites <{$REMETENTE}>\r\n";
if ($email !== '') { $cab .= "Reply-To: {$email}\r\n"; }
$cab .= "MIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n";

$via = '';
$ok = enviar($DESTINO, assunto('Pedido de site · ' . $empresa), implode("\n", $linhas), $cab, $REMETENTE, $via);

// Confirmação automática para o cliente (se deixou email)
$viaCli = '-';
if ($ok && $email !== '') {
  $txtCli = "Olá {$nome},\n\nRecebemos o seu pedido de site para {$empresa}. Vamos analisar as suas respostas e entramos em contacto no dia útil seguinte.\n\nSe precisar, fale connosco pelo WhatsApp 96 340 1241 ou pelo 22 201 2425.\n\nPL Resende · Tecnologias de Informação\nRua da Alegria, 1035, 4000-048 Porto\nhttps://sites.plresende.pt\n";
  $cabCli = "From: PL Resende <{$REMETENTE}>\r\nReply-To: {$DESTINO}\r\nMIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n";
  if (!enviar($email, assunto('Recebemos o seu pedido de site · PL Resende'), $txtCli, $cabCli, $REMETENTE, $viaCli)) { $viaCli = 'FALHOU ' . $viaCli; }
}

registar("{$empresa} | interno=" . ($ok ? 'ok via ' . $via : 'FALHOU ' . $via) . " | cliente={$viaCli}");
echo $ok ? '{"ok":true}' : '{"ok":false}';
