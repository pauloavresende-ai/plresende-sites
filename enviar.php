<?php
// Recebe o questionário de sites.plresende.pt e envia-o por email à PL Resende.
// Devolve JSON {"ok":true} ou {"ok":false}. A página mostra a alternativa por WhatsApp se falhar.
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$DESTINO   = 'info@plresende.com';          // quem recebe os pedidos
$REMETENTE = 'info@plresende.com';          // conta de email que existe neste cPanel

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo '{"ok":false}'; exit; }
if (!empty($_POST['website'])) { echo '{"ok":true}'; exit; } // anti-spam (campo escondido)

function campo($k, $max = 1500) {
  $v = isset($_POST[$k]) ? trim((string)$_POST[$k]) : '';
  $v = str_replace(["\r", "\0"], '', $v);
  return mb_substr($v, 0, $max, 'UTF-8');
}

$nome = campo('nome', 120); $tel = campo('telefone', 40); $empresa = campo('empresa', 160);
$atividade = campo('atividade', 160); $descricao = campo('descricao'); $rgpd = campo('rgpd', 5);
if ($nome === '' || strlen(preg_replace('/\D/', '', $tel)) < 9 || $empresa === '' || $atividade === '' || $descricao === '' || $rgpd !== 'Sim') {
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

$assunto = '=?UTF-8?B?' . base64_encode('Pedido de site · ' . $empresa) . '?=';
$cab  = "From: PL Resende Sites <{$REMETENTE}>\r\n";
if ($email !== '') { $cab .= "Reply-To: {$email}\r\n"; }
$cab .= "MIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n";

$ok = @mail($DESTINO, $assunto, implode("\n", $linhas), $cab, '-f' . $REMETENTE);
echo $ok ? '{"ok":true}' : '{"ok":false}';
