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
// Defini��o do n�vel de compress�o (Default=m�ximo)
//$v_compress_level = '9';
$v_compress_level = '0';
 
require_once('../include/library.php');

// Essas vari�veis conter�o os indicadores de criptografia e compacta��o
$v_cs_cipher	= (trim($_POST['cs_cipher'])   <> ''?trim($_POST['cs_cipher'])   : '4');
$v_cs_compress	= (trim($_POST['cs_compress']) <> ''?trim($_POST['cs_compress']) : '4');

autentica_agente($key,$iv,$v_cs_cipher,$v_cs_compress);

$te_node_address 			= DeCrypt($key,$iv,$_POST['te_node_address']	,$v_cs_cipher, $v_cs_compress); 
$id_so           			= DeCrypt($key,$iv,$_POST['id_so']				,$v_cs_cipher, $v_cs_compress); 
$id_ip_rede     			= DeCrypt($key,$iv,$_POST['id_ip_rede']			,$v_cs_cipher, $v_cs_compress);
$te_ip 						= DeCrypt($key,$iv,$_POST['te_ip']				,$v_cs_cipher, $v_cs_compress); 
$te_nome_computador			= DeCrypt($key,$iv,$_POST['te_nome_computador']	,$v_cs_cipher, $v_cs_compress); 
$te_workgroup 				= DeCrypt($key,$iv,$_POST['te_workgroup']		,$v_cs_cipher, $v_cs_compress); 

conecta_bd_cacic();

/* Todas as vezes em que � feita a recupera��o das configura��es por um agente, � inclu�do 
o computador deste agente no BD, caso ainda n�o esteja inserido. */
if ($te_node_address || $id_so || $te_nome_computador || $te_ip || $te_workgroup || $id_ip_rede <> '')
	{
	inclui_computador_caso_nao_exista(	$te_node_address, 
										$id_so, 
										$id_ip_rede, 
										$te_ip, 
										$te_nome_computador,
										$te_workgroup);									
	}										
			
$v_te_workgroup = $te_workgroup;
if ($v_te_workgroup == '')
	{
	$v_array_te_dominio_windows	= explode('\\',DeCrypt($key,$iv,$_POST['te_dominio_windows'],$v_cs_cipher, $v_cs_compress));
	$v_te_workgroup = $v_array_te_dominio_windows[0];
	}

$query = "INSERT INTO historico_tcp_ip
									(te_node_address,
										id_so,
										te_nome_computador,
										dt_hr_alteracao,
										te_ip,
										te_mascara,
										id_ip_rede,
										te_gateway,
										te_serv_dhcp,
										te_nome_host,
										te_dominio_dns,
										te_dominio_windows,										
										te_dns_primario,
										te_dns_secundario,
										te_wins_primario,
										te_wins_secundario, 
                                        te_workgroup,
										te_origem_mac)
						VALUES ('" . $te_node_address . "', '" .
														$id_so . "', '" .
														$te_nome_computador . "', 
														NOW(), '" .
														$te_ip . "', '" .
														DeCrypt($key,$iv,$_POST['te_mascara']			,$v_cs_cipher, $v_cs_compress) . "', '" .
														$id_ip_rede  . "', '" .
														DeCrypt($key,$iv,$_POST['te_gateway']			,$v_cs_cipher, $v_cs_compress) . "', '" .
														DeCrypt($key,$iv,$_POST['te_serv_dhcp']			,$v_cs_cipher, $v_cs_compress) . "', '" .
														DeCrypt($key,$iv,$_POST['te_nome_host']			,$v_cs_cipher, $v_cs_compress) . "', '" .
														DeCrypt($key,$iv,$_POST['te_dominio_dns']		,$v_cs_cipher, $v_cs_compress) . "', '" .
														DeCrypt($key,$iv,$_POST['te_dominio_windows']	,$v_cs_cipher, $v_cs_compress) . "', '" .														
														DeCrypt($key,$iv,$_POST['te_dns_primario']		,$v_cs_cipher, $v_cs_compress) . "', '" .
														DeCrypt($key,$iv,$_POST['te_dns_secundario']	,$v_cs_cipher, $v_cs_compress) . "', '" .
														DeCrypt($key,$iv,$_POST['te_wins_primario']		,$v_cs_cipher, $v_cs_compress) . "', '" .
														DeCrypt($key,$iv,$_POST['te_wins_secundario']	,$v_cs_cipher, $v_cs_compress) . "', '" .
														$v_te_workgroup . "', '" .
														DeCrypt($key,$iv,$_POST['te_origem_mac'],$v_cs_cipher, $v_cs_compress) . "')";

$result = mysql_query($query);

//echo $query;
//exit;

// Lembre-se de que o computador j� existe. Ele � criado durante a obten��o das configura��es, no arquivo get_config.php.
$query = "	UPDATE 	computadores 
			SET		te_ip              	= '" . $te_ip . "', 
					te_nome_computador 	= '" . $te_nome_computador . "', 
					te_mascara         	= '" . DeCrypt($key,$iv,$_POST['te_mascara']			,$v_cs_cipher, $v_cs_compress) . "', 
					id_ip_rede         	= '" . $id_ip_rede . "',
					te_gateway         	= '" . DeCrypt($key,$iv,$_POST['te_gateway']			,$v_cs_cipher, $v_cs_compress) . "',
					te_serv_dhcp       	= '" . DeCrypt($key,$iv,$_POST['te_serv_dhcp']			,$v_cs_cipher, $v_cs_compress) . "',
					te_nome_host       	= '" . DeCrypt($key,$iv,$_POST['te_nome_host']			,$v_cs_cipher, $v_cs_compress) . "',
					te_dominio_windows 	= '" . DeCrypt($key,$iv,$_POST['te_dominio_windows']	,$v_cs_cipher, $v_cs_compress) . "',														
					te_dominio_dns     	= '" . DeCrypt($key,$iv,$_POST['te_dominio_dns']		,$v_cs_cipher, $v_cs_compress) . "',
					te_dns_primario    	= '" . DeCrypt($key,$iv,$_POST['te_dns_primario']		,$v_cs_cipher, $v_cs_compress) . "',
					te_dns_secundario  	= '" . DeCrypt($key,$iv,$_POST['te_dns_secundario']		,$v_cs_cipher, $v_cs_compress) . "',
					te_wins_primario   	= '" . DeCrypt($key,$iv,$_POST['te_wins_primario']		,$v_cs_cipher, $v_cs_compress) . "',
					te_wins_secundario 	= '" . DeCrypt($key,$iv,$_POST['te_wins_secundario']	,$v_cs_cipher, $v_cs_compress) .  "',
					te_workgroup 	   	= '" . $v_te_workgroup .  "',														
					te_origem_mac	 	= '" . DeCrypt($key,$iv,$_POST['te_origem_mac']			,$v_cs_cipher, $v_cs_compress) .  "'
			WHERE 	te_node_address  	= '" . $te_node_address . "' and
					id_so              	= '" . $id_so . "'";
$result = mysql_query($query);

echo '<?xml version="1.0" encoding="iso-8859-1" ?><STATUS>OK</STATUS>';

?>