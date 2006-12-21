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

// Essa vari�vel � usada pelo arquivo de include selecao_redes_inc.php e inicio_relatorios_inc.php.
$id_acao = 'cs_coleta_patrimonio';
require_once('../../include/inicio_relatorios_inc.php'); 
?>
<table width="90%" border="0" align="center">
  <tr> 
    <td class="cabecalho">Relat&oacute;rio de Informa&ccedil;&otilde;es Patrimoniais 
      e Local F&iacute;sica</td>
  </tr>
  <tr> 
    <td class="descricao">Este relat&oacute;rio 
      exibe informa&ccedil;&otilde;es de Patrim&ocirc;nio e Local 
      F&iacute;sica dos computadores das redes selecionadas. &Eacute; poss&iacute;vel 
      selecionar os sistemas operacionais desejados e tamb&eacute;m determinar 
      quais informa&ccedil;&otilde;es de Patrim&ocirc;nio e Local 
      F&iacute;sica ser&atilde;o exibidas no relat&oacute;rio.</td>
  </tr>
  <tr> 
    <td>
<?
require_once('../../include/library.php'); 
?>
				</td>
  </tr>
		
</table>
<form action="rel_patrimonio.php" target="_blank" method="post" ENCTYPE="multipart/form-data" name="forma"   onsubmit="return valida_form()">
  <table width="90%" border="0" align="center" cellpadding="5" cellspacing="1">
  <? 
  /*
  if ($_SESSION['cs_nivel_administracao']==1 || $_SESSION['cs_nivel_administracao']==2)
  	{
	?>
	<tr> 
      <td valign="top">
	  <table width="100%" border="0" cellpadding="0" cellspacing="1">
          <tr> 
            <td>&nbsp;</td>
          </tr>
          <tr> 
            <td class="label">Selecione os locais:</td>
          </tr>
          <tr> 
            <td height="1" bgcolor="#333333"></td>
          </tr>
          <tr> 
            <td height="1"><table border="0" cellpadding="0" cellspacing="0">
                <tr> 
                  <td>&nbsp;&nbsp;</td>
                  <td class="cabecalho_tabela"><div align="left">Dispon&iacute;veis:</div></td>
                  <td>&nbsp;&nbsp;</td>
                  <td width="40">&nbsp;</td>
                  <td nowrap>&nbsp;&nbsp;</td>
                  <td nowrap class="cabecalho_tabela">Selecionados:</td>
                  <td nowrap>&nbsp;&nbsp;</td>
                </tr>
                <tr> 
                  <td>&nbsp;</td>
                  <td> <div align="left"> 
                      <select multiple name="list11[]" size="10"  class="normal" onFocus="SetaClassDigitacao(this);" onBlur="SetaClassNormal(this);" >
                        <?
                        $query = "SELECT 	id_local,
											sg_local
                                  FROM 		locais
                                  ORDER BY	sg_local";
                        $result = mysql_query($query) or die('Erro na consulta � tabela "locais".');
                        while ($row = mysql_fetch_array($result)) 
							{ 
                            echo '<option value=' . $row['id_local'] . '>' . $row["sg_local"] . '</option>';
                        	}
						?>
                      </select>
                      </div></td>
                  <td>&nbsp;</td>
                  <td width="40"> <div align="center"> 
                      <input type="button" value="   &gt;   " onClick="move(this.form.elements['list11[]'],this.form.elements['list12[]'])" name="B132">					  
                      <br>
                      <br>
                      <input type="button" value="   &lt;   " onClick="move(this.form.elements['list12[]'],this.form.elements['list11[]'])" name="B232">					  
                    </div></td>
                  <td>&nbsp;</td>
                  <td><select multiple name="list12[]" size="10" class="normal" onFocus="SetaClassDigitacao(this);" onBlur="SetaClassNormal(this);" >
                    </select></td>
                  <td>&nbsp;</td>
                </tr>
              </table></td>
          </tr>
          <tr> 
            <td class="descricao">&nbsp;&nbsp;&nbsp;(Dica: 
              use SHIFT or CTRL para selecionar m&uacute;ltiplos itens)</td>
          </tr>
        </table></td>
    </tr>
    <tr> 
      <td valign="top">&nbsp;</td>
    </tr>  
	<?
	}
	*/
	?>
	
    <tr> 
      <td valign="top"><table width="100%" border="0" cellpadding="0" cellspacing="1">
          <tr> 
            <td>&nbsp;</td>
          </tr>
          <tr> 
            <td class="label">Selecione 
              as informa&ccedil;&otilde;es que deseja exibir:</td>
          </tr>
          <tr> 
            <td height="1" bgcolor="#333333"></td>
          </tr>
          <tr> 
            <td height="1"><table border="0" cellpadding="0" cellspacing="0">
                <tr> 
                  <td>&nbsp;&nbsp;</td>
                  <td class="cabecalho_tabela"><div align="left">Dispon&iacute;veis:</div></td>
                  <td>&nbsp;&nbsp;</td>
                  <td width="40">&nbsp;</td>
                  <td nowrap>&nbsp;&nbsp;</td>
                  <td nowrap class="cabecalho_tabela">Selecionados:</td>
                  <td nowrap>&nbsp;&nbsp;</td>
                </tr>
                <tr> 
                  <td>&nbsp;</td>
                  <td> <div align="left"> 
                      <select multiple name="list5[]" size="10"  class="normal" onFocus="SetaClassDigitacao(this);" onBlur="SetaClassNormal(this);" >
                        <?
						$where = ($_SESSION['cs_nivel_administracao']<>1 && $_SESSION['cs_nivel_administracao']<>2?' AND pat.id_local = '.$_SESSION['id_local']:'');						
                        $query = "SELECT
								  DISTINCT 	te_etiqueta, 
											nm_campo_tab_patrimonio, 
											in_destacar_duplicidade
                                  FROM 		patrimonio_config_interface pat,
								  			redes
                                  WHERE 	(nm_campo_tab_patrimonio in ('id_unid_organizacional_nivel1', 'id_unid_organizacional_nivel2', 'te_localizacao_complementar')
								  			OR in_exibir_etiqueta = 'S') AND
											redes.id_local = pat.id_local ".
											$where;
                        $result = mysql_query($query) or die('Erro na consulta � tabela "patrimonio_config_interface".');
                        while ($row = mysql_fetch_array($result)) 
							{ 
                            echo '<option value=", patrimonio.' . $row['nm_campo_tab_patrimonio'] . ' as &quot;' . $row['te_etiqueta'] . '&quot;';
							if (trim($row['in_destacar_duplicidade'])<>'' && trim($row['in_destacar_duplicidade'])<>'N') echo '#in_destacar_duplicidade.'.$row['in_destacar_duplicidade'];
							echo '">' . $row["te_etiqueta"] . '</option>';
                        	}
						?>
                      </select>
                      </div></td>
                  <td>&nbsp;</td>
                  <td width="40"> <div align="center"> 
				  		<?
                      //<input type="button" value="   &gt;   " onClick="copia(this.form.elements['list5[]'],this.form.elements['list7[]']); move(this.form.elements['list5[]'],this.form.elements['list6[]'])" name="B132">
					  ?>
                      <input type="button" value="   &gt;   " onClick="move(this.form.elements['list5[]'],this.form.elements['list6[]'])" name="B132">					  
                      <br>
                      <br>
					  <?
                      //<input type="button" value="   &lt;   " onClick="exclui(this.form.elements['list6[]'],this.form.elements['list8[]']); exclui(this.form.elements['list6[]'],this.form.elements['list7[]']); move(this.form.elements['list6[]'],this.form.elements['list5[]'])" name="B232">
					  ?>
                      <input type="button" value="   &lt;   " onClick="move(this.form.elements['list6[]'],this.form.elements['list5[]'])" name="B232">					  
                    </div></td>
                  <td>&nbsp;</td>
                  <td><select multiple name="list6[]" size="10" class="normal" onFocus="SetaClassDigitacao(this);" onBlur="SetaClassNormal(this);" >
                    </select></td>
                  <td>&nbsp;</td>
                </tr>
              </table></td>
          </tr>
          <tr> 
            <td class="descricao">&nbsp;&nbsp;&nbsp;(Dica: 
              use SHIFT or CTRL para selecionar m&uacute;ltiplos itens)</td>
          </tr>
        </table></td>
    </tr>
    <tr> 
      <td valign="top">&nbsp;</td>
    </tr>
	<tr><td>

  <table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
     <tr> 
     <td class="label" colspan="3">Informe os crit�rios para pesquisa de informa��es patrimoniais:</td>
     </tr>
     <tr> 
     <td height="1" bgcolor="#333333" colspan="3"></td>
     </tr>
  
    <tr> 
      <td valign="top"> 
	  	
        <?  
		$cor = 0;
		$where = ($_SESSION['cs_nivel_administracao']<>1 && $_SESSION['cs_nivel_administracao']<>2?' AND pat.id_local = '.$_SESSION['id_local']:'');
	
		$query = "SELECT 
				  DISTINCT	nm_campo_tab_patrimonio,
							te_etiqueta 
				  FROM 		patrimonio_config_interface pat,
					 		redes 
				  WHERE 	id_etiqueta not in ('etiqueta1','etiqueta2') AND
							redes.id_local = pat.id_local ".
							$where . "   
				  ORDER BY te_etiqueta";	

		$res_fields = mysql_query($query);

		while ($row_fields = mysql_fetch_array($res_fields)) 
			{
			?>
			<tr <? if ($cor) echo 'bgcolor="#E1E1E1"';?>> 
			<td nowrap><? echo $row_fields['te_etiqueta'];?></td>
			<td><select name="frm_condicao_<? echo $row_fields['nm_campo_tab_patrimonio']; ?>" class="normal" onFocus="SetaClassDigitacao(this);" onBlur="SetaClassNormal(this);" >
			<option value=""></option>
			<option value="<? echo 'patrimonio.'     .$row_fields['nm_campo_tab_patrimonio']." =       'frm_te_valor_condicao'"  ;?>" onClick="Verifica_Condicoes_Seta_Campo('<? echo "frm_te_valor_condicao_". $row_fields['nm_campo_tab_patrimonio']; ?>');">IGUAL A</option>		
			<option value="<? echo 'patrimonio.'     .$row_fields['nm_campo_tab_patrimonio']." <>      'frm_te_valor_condicao'"  ;?>" onClick="Verifica_Condicoes_Seta_Campo('<? echo "frm_te_valor_condicao_". $row_fields['nm_campo_tab_patrimonio']; ?>');">DIFERENTE DE</option>			
			<option value="<? echo 'patrimonio.'     .$row_fields['nm_campo_tab_patrimonio']." like    '%frm_te_valor_condicao%'";?>" onClick="Verifica_Condicoes_Seta_Campo('<? echo "frm_te_valor_condicao_". $row_fields['nm_campo_tab_patrimonio']; ?>');">CONTENHA</option>
			<option value="<? echo 'patrimonio.'     .$row_fields['nm_campo_tab_patrimonio']." like    'frm_te_valor_condicao%'" ;?>" onClick="Verifica_Condicoes_Seta_Campo('<? echo "frm_te_valor_condicao_". $row_fields['nm_campo_tab_patrimonio']; ?>');">INICIE COM</option>
			<option value="<? echo 'patrimonio.'     .$row_fields['nm_campo_tab_patrimonio']." like    '%frm_te_valor_condicao'" ;?>" onClick="Verifica_Condicoes_Seta_Campo('<? echo "frm_te_valor_condicao_". $row_fields['nm_campo_tab_patrimonio']; ?>');">TERMINE COM</option>				
			<option value="<? echo 'TRIM(patrimonio.'.$row_fields['nm_campo_tab_patrimonio'].") = '' and " 					   ;?>" onClick="Preenche_Condicao_VAZIO('<? echo "frm_te_valor_condicao_". $row_fields['nm_campo_tab_patrimonio']; ?>');"		 >VAZIO</option>		
			</select> </td>
			<td><input name="frm_te_valor_condicao_<? echo $row_fields['nm_campo_tab_patrimonio']; ?>" type="text" class="normal" onFocus="SetaClassDigitacao(this);" onBlur="SetaClassNormal(this);Verifica_Selecao(this,'<? echo "frm_condicao_". $row_fields['nm_campo_tab_patrimonio']; ?>');" size="50" maxlength="100"></td>
			</tr>
			<?			
			$cor=!$cor;
			}
			?>
	</table>
	
	</td></tr>
    <tr> 
      <td valign="top">&nbsp;</td>
    </tr>



    <tr> 
      <td valign="top"> 
        <?  $v_require = '../../include/' .($_SESSION['cs_nivel_administracao']<>1 && $_SESSION['cs_nivel_administracao']<>2?'selecao_redes_inc.php':'selecao_locais_inc.php');
		require_once($v_require);		
		?>

      </td>
    </tr>
    <tr> 
      <td valign="top">&nbsp;</td>
    </tr>
    <tr> 
      <td valign="top"> 
        <?  require_once('../../include/selecao_so_inc.php');		?>
      </td>
    </tr>
    <tr> 
      <td valign="top">&nbsp;</td>
    </tr>
    <tr> 
      <td valign="top"><br> <table width="100%" border="0" cellpadding="0" cellspacing="1">
          <tr> 
            <td height="1" bgcolor="#333333"></td>
          </tr>
          <tr> 
            <td>&nbsp;</td>
          </tr>
          <tr> 
            <td> <div align="center"> 
                <input name="submit" type="submit" value="        Gerar Relat&oacute;rio      " onClick="<? echo ($_SESSION['cs_nivel_administracao']<>1 && 
																				 						 $_SESSION['cs_nivel_administracao']<>2?"SelectAll(this.form.elements['list2[]'])":"SelectAll(this.form.elements['list12[]'])")?>, 
																										 SelectAll(this.form.elements['list4[]']), 
																										 SelectAll(this.form.elements['list6[]'])">								
              </div></td>
          </tr>
          <tr> 
            <td>&nbsp;</td>
          </tr>
        </table></td>
    </tr>
  </table>
</form>

</body>
</html>
