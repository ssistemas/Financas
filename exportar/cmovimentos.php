<?php
include("../conf/config.php"); // Inclui o arquivo com o sistema de segurança
protegePagina(); // Chama a função que protege a página
include '../conf/functions.php';
$usuario=$_SESSION['usuarioID'];

//===============================================
// MOVIMENTOS
//===============================================
if (isset($_POST['acao']) && $_POST['acao'] == 'cmovimentos') {
//Variável de mês a ser exportado
$mes = $_POST['mes'];
$ano = $_POST['ano'];

$mes_hoje = date('m');
$ano_hoje = date('Y');

$mesnome=mostraMes($mes);

//PDF
define ('FPDF_FONTPATH', 'font/');
require('./fpdf/fpdf.php');

//Sub classe para cabeçalho e rodapé
class PDF extends FPDF {

function Header(){
$nomeconta = $_POST['nome'];
$this->SetDrawColor(47,79,79);
$this->SetLineWidth(0.2);
$this->SetFillColor(0,110,110);
$this->SetFont('Arial','B',12);
$this->SetTextColor(255,255,255);
$this->Cell(15,1,utf8_decode(" $nomeconta"),'LBT',0,'L',1);
$this->SetFont('Arial','',8);
$data=date("d/m/Y H:i");
$this->Cell(4,1,"$data ",'RBT',1,'R',1);
$this->Ln(0.8);
}

function Footer(){
$nome=$_SESSION['usuarioNome'];
$this->SetY(-6);
$this->SetFont('Arial','I',8);
$this->AliasNbPages();
$this->SetTextColor(0,0,0);
$this->Cell(0,10,utf8_decode(" Origem de dados: Movimento Financeiro Loja Jaqueline Arteira.   |   Usuário: $nome.    "),0,0,'L');
$this->Cell(0,10,utf8_decode("Página ").$this->PageNo().'/{nb}',0,0,'R');
}
}

//Início do corpo do pdf
$pdf=new PDF ('P','cm','A4');
$pdf->AddPage();

$pdf->SetFont('Arial','',14);
$pdf->SetTextColor(0,0,255);
$pdf->Cell(19,1,utf8_decode('Relatório de Movimentos Mensal'),0,0,'C',0);
$pdf->Ln(0.7);

$pdf->SetFont('Arial','',12);
$pdf->SetTextColor(0,0,0);
$pdf->Cell(19,1,utf8_decode("$mesnome / $ano"),0,1,'L',0);

$qrv=mysql_query("SELECT * FROM cmovimentos WHERE conta=1 && usuario='$usuario' && mes='$mes' && ano='$ano' ORDER By dia");
if (mysql_num_rows($qrv)!==0){

$pdf->SetFillColor(169,169,169);
$pdf->SetFont('Arial','',12);
$pdf->SetTextColor(0,0,0);
$pdf->Cell(1,0.6,'Dia',0,0,'L',1);
$pdf->Cell(12,0.6,utf8_decode('Descrição'),0,0,'C',1);
$pdf->Cell(3,0.6,'Categoria',0,0,'C',1);
$pdf->Cell(3,0.6,'Valor',0,1,'C',1);

$qrg=mysql_query("SELECT SUM(valor) as total FROM cmovimentos WHERE tipo=1 && conta=1 && usuario='$usuario'");
$rowg=mysql_fetch_array($qrg);
$entradasg=$rowg['total'];

$qrg=mysql_query("SELECT SUM(valor) as total FROM cmovimentos WHERE tipo=0 && conta=1 && usuario='$usuario'");
$rowg=mysql_fetch_array($qrg);
$saidasg=$rowg['total'];

$resultado_geral=$entradasg-$saidasg;
$balancogeral=formata_dinheiro($resultado_geral);

$qr=mysql_query("SELECT SUM(valor) as total FROM cmovimentos WHERE tipo=1 && conta=1 && usuario='$usuario' && mes='$mes' && ano='$ano'");
$row=mysql_fetch_array($qr);
$entradas=$row['total'];

$qr=mysql_query("SELECT SUM(valor) as total FROM cmovimentos WHERE tipo=0 && conta=1 && usuario='$usuario' && mes='$mes' && ano='$ano'");
$row=mysql_fetch_array($qr);
$saidas=$row['total'];

$resultado_mes=$entradas-$saidas;

$qr=mysql_query("SELECT * FROM cmovimentos WHERE conta=1 && usuario='$usuario' && mes='$mes' && ano='$ano' ORDER By dia");
$cont=0;
while ($row=mysql_fetch_array($qr)){
$cont++;

$cat=$row['cat'];
$qr2=mysql_query("SELECT nome FROM ccategorias WHERE id='$cat'");
$row2=mysql_fetch_array($qr2);
$categoria=$row2['nome'];
$valor=formata_dinheiro($row['valor']);
$valortotal=formata_dinheiro($resultado_mes);

if ($cont%2==0){
$pdf->SetFillColor(211,211,211);}
if ($cont%2!=0){
$pdf->SetFillColor(232,232,232);}

$pdf->SetFont('Times','',9);
$pdf->SetTextColor(0,0,0);
$pdf->Cell(1,0.5,$row['dia'],0,0,'C',1);
$pdf->SetFont('Times','',9);
$pdf->Cell(12,0.5,utf8_decode($row['descricao']),0,0,'L',1);
$pdf->Cell(3,0.5,utf8_decode($row2['nome']),0,0,'L',1);
if  ($row['tipo']==1){
	$pdf->SetFont('Times','',9);
	$pdf->SetTextColor(0,0,255);
	$pdf->Cell(3,0.5,"   + $valor",0,1,'L',1);}
if  ($row['tipo']==0){
	$pdf->SetFont('Times','',9);
	$pdf->SetTextColor(255,0,0);
	$pdf->Cell(3,0.5,"   - $valor",0,1,'L',1);}

}

$pdf->SetFillColor(169,169,169);
$pdf->SetFont('Times','B',12);
$pdf->SetTextColor(0,0,0);
$pdf->Cell(15,0.7,'Total:',0,0,'L',1);
if  ($resultado_mes>=0){
	$pdf->SetFont('Arial','',12);
	$pdf->SetTextColor(0,0,215);
	$pdf->Cell(4,0.7,"$valortotal ",0,1,'R',1);}
if  ($resultado_mes<0){
	$pdf->SetFont('Arial','',12);
	$pdf->SetTextColor(255,0,0);
	$pdf->Cell(4,0.7,"$valortotal ",0,1,'R',1);}

$pdf->SetFont('Times','B',7);
$pdf->SetTextColor(0,0,0);
$pdf->Cell(16,0.6,utf8_decode('Balanço geral:'),0,0,'R',0);
if  ($resultado_geral>=0){
	$pdf->SetFont('Arial','',7);
	$pdf->SetTextColor(0,0,215);
	$pdf->Cell(3,0.6,"$balancogeral  ",0,1,'R',0);}
if  ($resultado_geral<0){
	$pdf->SetFont('Arial','',7);
	$pdf->SetTextColor(255,0,0);
	$pdf->Cell(3,0.6,"$balancogeral  ",0,1,'R',0);}

$pdf->Ln(0.5);
$pdf->Cell(19,0,'','B',1,'C',0);
$pdf->SetFont('Arial','',12);
$pdf->SetTextColor(0,0,255);
$pdf->Cell(19,0.7,utf8_decode('Fim do relatório.'),0,1,'C',0);

ob_start();
$pdf->Output("Movimentos_$mes-$ano.pdf",'D');

}else{
$pdf->SetFont('Times','',12);
$pdf->SetTextColor(0,0,0);
$pdf->Cell(19,1,utf8_decode('Não há movimentação para o período selecionado.'),0,0,'L',0);
$pdf->Ln(0.7);

$pdf->Ln(0.5);
$pdf->Cell(19,0,'','B',1,'C',0);
$pdf->SetFont('Arial','',12);
$pdf->SetTextColor(0,0,255);
$pdf->Cell(19,0.7,utf8_decode('Fim do relatório.'),0,1,'C',0);

ob_start();
$pdf->Output("Movimentos_$mes-$ano.pdf",'D');
}
}


//============================================================
// FATURA
//============================================================
if (isset($_POST['acao']) && $_POST['acao'] == 'fatura') {
//Variável de mês a ser exportado
$conta = $_POST['conta'];
$mes = $_POST['mes'];
$ano = $_POST['ano'];

$mes_hoje = date('m');
$ano_hoje = date('Y');

$mesnome=mostraMes($mes);

//PDF
define ('FPDF_FONTPATH', 'font/');
require('./fpdf/fpdf.php');

//Sub classe para cabeçalho e rodapé
class PDF extends FPDF {

function Header(){
$nomeconta=$_POST['nome'];
$this->SetDrawColor(47,79,79);
$this->SetLineWidth(0.2);
$this->SetFillColor(0,110,110);
$this->SetFont('Arial','B',12);
$this->SetTextColor(255,255,255);
$this->Cell(15,1,utf8_decode(" $nomeconta"),'LBT',0,'L',1);
$this->SetFont('Arial','',8);
$data=date("d/m/Y H:i");
$this->Cell(4,1,"$data ",'RBT',1,'R',1);
$this->Ln(0.8);
}

function Footer(){
$nome=$_SESSION['usuarioNome'];
$this->SetY(-6);
$this->SetFont('Arial','I',8);
$this->AliasNbPages();
$this->SetTextColor(0,0,0);
$this->Cell(0,10,utf8_decode(" Origem de dados: Movimento Financeiro Loja Jaqueline Arteira.   |   Usuário: $nome.    "),0,0,'L');
$this->Cell(0,10,utf8_decode("Página ").$this->PageNo().'/{nb}',0,0,'R');
}
}

//Início do corpo do pdf
$pdf=new PDF ('P','cm','A4');
$pdf->AddPage();

$pdf->SetFont('Arial','',14);
$pdf->SetTextColor(0,0,255);
$pdf->Cell(19,1,utf8_decode("Fatura com vencimento para o próximo m&ecirc;s."),0,0,'C',0);
$pdf->Ln(0.7);

//calculo da fatura
$qr=mysql_query("SELECT SUM(valor) as total FROM cmovimentos WHERE tipo='0' && conta='$conta' && usuario='$usuario' && mes='$mes' && ano='$ano'");
$row=mysql_fetch_array($qr);
$saidas=$row['total'];
$fatura=formata_dinheiro($saidas);

$pdf->SetFont('Arial','',11);
$pdf->SetTextColor(0,0,0);
$pdf->Cell(19,1,utf8_decode("$mesnome / $ano. Valor: $fatura."),0,1,'L',0);


$qrvf=mysql_query("SELECT * FROM cmovimentos WHERE conta='$conta' && usuario='$usuario' && mes='$mes' && ano='$ano' ORDER By dia");
if (mysql_num_rows($qrvf)!==0){

$pdf->SetFillColor(169,169,169);
$pdf->SetFont('Arial','',12);
$pdf->SetTextColor(0,0,0);
$pdf->Cell(1,0.6,'Dia',0,0,'L',1);
$pdf->Cell(12,0.6,utf8_decode('Descrição'),0,0,'C',1);
$pdf->Cell(3,0.6,'Categoria',0,0,'C',1);
$pdf->Cell(3,0.6,'Valor',0,1,'C',1);

$qr=mysql_query("SELECT SUM(valor) as total FROM cmovimentos WHERE tipo=0 && conta='$conta' && usuario='$usuario' && mes='$mes' && ano='$ano'");
$row=mysql_fetch_array($qr);
$saidas=$row['total'];

$qr=mysql_query("SELECT * FROM cmovimentos WHERE tipo=0 && conta='$conta' && usuario='$usuario' && mes='$mes' && ano='$ano' ORDER By dia");
$cont=0;
while ($row=mysql_fetch_array($qr)){
$cont++;

$cat=$row['cat'];
$qr2=mysql_query("SELECT nome FROM ccategorias WHERE id='$cat'");
$row2=mysql_fetch_array($qr2);
$categoria=$row2['nome'];
$valor=formata_dinheiro($row['valor']);
$valortotal=formata_dinheiro($saidas);

if ($cont%2==0){
$pdf->SetFillColor(211,211,211);}
if ($cont%2!=0){
$pdf->SetFillColor(232,232,232);}

$pdf->SetFont('Times','',9);
$pdf->SetTextColor(0,0,0);
$pdf->Cell(1,0.5,$row['dia'],0,0,'C',1);
$pdf->SetFont('Times','',9);
$pdf->Cell(12,0.5,utf8_decode($row['descricao']),0,0,'L',1);
$pdf->Cell(3,0.5,utf8_decode($row2['nome']),0,0,'L',1);
$pdf->SetFont('Times','',9);
$pdf->SetTextColor(0,0,255);
$pdf->Cell(3,0.5,"   $valor",0,1,'L',1);


}

$pdf->SetFillColor(169,169,169);
$pdf->SetFont('Times','B',12);
$pdf->SetTextColor(0,0,0);
$pdf->Cell(15,0.7,'Total:',0,0,'L',1);
$pdf->SetFont('Arial','',12);
$pdf->SetTextColor(0,0,215);
$pdf->Cell(4,0.7,"$valortotal ",0,1,'R',1);

$pdf->Ln(0.5);

ob_start();
$pdf->Output("Fatura_$mes-$ano.pdf",'D');

}else{
$pdf->SetFont('Times','',12);
$pdf->SetTextColor(0,0,0);
$pdf->Cell(19,1,utf8_decode('Não há fatura para o período selecionado.'),0,0,'L',0);
$pdf->Ln(0.7);

$pdf->Ln(0.5);

ob_start();
$pdf->Output("Fatura_$mes-$ano.pdf",'D');
}

}

?>