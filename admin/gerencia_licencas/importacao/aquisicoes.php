<?
 /* 
 Copyright 2000, 2001, 2002, 2003, 2004, 2005 Dataprev - Empresa de Tecnologia e Informa��es da Previd�ncia Social, Brasil

 Este arquivo � parte do programa CACIC - Configurador Autom�tico e Coletor de Informa��es Computacionais

 O CACIC � um software livre; voc� pode redistribui-lo e/ou modifica-lo dentro dos termos da Licen�a P�blica Geral GNU como 
 publicada pela Funda��o do Software Livre (FSF); na vers�o 2 da Licen�a, ou (na sua opni�o) qualquer vers�o.

 Este programa � distribuido na esperan�a que possa ser  util, mas SEM NENHUMA GARANTIA; sem uma garantia implicita de ADEQUA��O a qualquer
 MERCADO ou APLICA��O EM PARTICULAR. Veja a Licen�a P�blica Geral GNU para maiores detalhes.

 Voc� deve ter recebido uma c�pia da Licen�a P�blica Geral GNU, sob o t�tulo "LICENCA.txt", junto com este programa, se n�o, escreva para a Funda��o do Software
 Livre(FSF) Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */
session_start();
require_once('../../../include/library.php');
conecta_bd_cacic();


if ($_POST['consultar']) {
		$v_textarea = $_POST['txtAquisicoes'];					
		$v_array_textarea = explode(";",$v_textarea);
	}
	
	
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<link rel="stylesheet"   type="text/css" href="/cacic2/include/cacic.css">

<title></title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
</head>

<body bgcolor="#FFFFFF" background="/cacic2/imgs/linha_v.gif" onLoad="SetaCampo('tipo_consulta')">
<script language="JavaScript" type="text/javascript" src="http://<? echo $_SERVER['HTTP_HOST'].'/cacic2/include/cacic.js';?>"></script>

<form action="<? echo $PHP_SELF; ?>" method="post" name="form1">
<table width="90%" border="0" align="center">
<tr> 
<td class="cabecalho">Formul&aacute;rio para importar dados de aquisi&ccedil;&otilde;es</td>
</tr>
<tr> 
<td>&nbsp;</td>
</tr>
</table>
<tr><td height="1" colspan="2" bgcolor="#333333"></td></tr>
<tr><td height="30" colspan="2"><table width="90%" border="0" align="center" cellpadding="0" cellspacing="1">
<tr><td colspan="2" class="label">Ctrl-C Ctrl-V</td></tr>
<table width="90%" border="0" align="center" cellpadding="0" cellspacing="1">
<tr> 
<td height="1" bgcolor="#333333"></td>
</tr>
<tr> 
<td height="28"><table width="96%" border="0" align="center" cellpadding="1" cellspacing="1">
<tr> 
            <td> 
              <TEXTAREA name="txtAquisicoes" rows=20 cols=80>
	      </TEXTAREA> 
              </td>
          </tr>
	  <tr>
            <td align="center"><input name="consultar" type="submit" id="consultar2" value="Importar"></td>
	  </tr>
        </table></td>
    </tr>
    <tr> 
      <td height="1" bgcolor="#333333"></td>
    </tr>
  </table>
  </form>
<?

if ($_POST['consultar']) {

?>
<p align="center" class="descricao">Testando</p> 
<table border="0" align="center" cellpadding="0" cellspacing="1">
  <tr> 
    <td height="1" bgcolor="#333333"></td>
  </tr>
  <tr> 
    <td> <table border="0" cellpadding="2" cellspacing="0" bordercolor="#333333" align="center">
        <tr bgcolor="#E1E1E1"> 
          <td align="center"  nowrap>&nbsp;</td>
          <td align="center"  nowrap>&nbsp;</td>
          <td align="center"  nowrap>&nbsp;</td>
          <td align="center"  nowrap class="cabecalho_tabela"><div align="center">Aquisicao</div></td> 
          <td nowrap >&nbsp;</td>
          <td nowrap class="cabecalho_tabela"><div align="center">Data</div></td>
          <td nowrap >&nbsp;</td>
          <td nowrap class="cabecalho_tabela"><div align="center">Processo</div></td> 
          <td nowrap >&nbsp;</td>
        </tr>
        <?  
	$Cor = 0;
	$NumRegistro = 1;

	for ($v1=0; $v1 < count($v_array_textarea) - 1; $v1 = $v1 + 3) {	
		  
	 ?>
        <tr <? if ($Cor) { echo 'bgcolor="#E1E1E1"'; } ?>> 
          <td nowrap>&nbsp;</td>
          <td nowrap class="opcao_tabela"><div align="left"><? echo $NumRegistro; ?></div></td>
          <td nowrap>&nbsp;</td>
          <td nowrap class="opcao_tabela"><div align="left"><? echo $v_array_textarea[$v1]; ?></div></td>
          <td nowrap>&nbsp;</td>
          <td nowrap class="opcao_tabela"><div align="left"><? echo $v_array_textarea[$v1+1]; ?></div></td>
          <td nowrap>&nbsp;</td>
<?	  $v_processo = explode("/",$v_array_textarea[$v1+2]);
 	  if ($v_processo[1] > "90") {
		$v_processo[1] = '19' . $v_processo[1]; }
	  else {
		$v_processo[1] = '20' . $v_processo[1]; }
?>
          <td nowrap class="opcao_tabela"><div align="left"><? echo $v_processo[1].'/'.$v_processo[0]; ?></div></td>
          <td nowrap>&nbsp;</td>
<?
	  $query = "INSERT INTO aquisicoes(id_aquisicao,nr_processo,temp_data) 
		    VALUES (" . $v_array_textarea[$v1] . ",'" . $v_processo[1] . "/" . $v_processo[0] . "','"   
			      . $v_array_textarea[$v1+1] . "')";

//	  $result = mysql_query($query) or die('erro no insert');
?>
          <? 
	$Cor=!$Cor;
	$NumRegistro++;
}
		
?>
      </table></td>
  </tr>
  <tr> 
    <td height="1" bgcolor="#333333"></td>
  </tr>
  <tr> 
    <td height="10">&nbsp;</td>
  </tr>
</table>
<?
				}
?>
</body>
</html>
