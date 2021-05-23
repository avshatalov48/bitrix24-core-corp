<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if($arResult["RESULT"]):?>
	<?if ($arResult["RESULT"]['ERROR']):?>	
		<span class="error"><?=$arResult["RESULT"]['ERROR'];?></span>
	<?else:?>
		<?=$arResult["RESULT"]['html_form'];?>	
	<?endif;?>	
	<?die();?>
<?else:?>
	<div style="background:#EEEEEE;padding:3px;border:1px solid #C2C2C2;width:320px;">
		<div id="payroll-panel" >
			<form  name="payroll_params" id="payroll_params" action="" method="POST">
				<table width="100%" >
					<tr>
						<td align="right">
							<?=GetMessage("ORG_LIST")?>
						</td>
						<td>
							<select class="payroll-input" name="USERORG">
								<?foreach ($arResult["ORG_LIST"] as $key=>$arOrgName):?>
									<?if ($arOrgName):?>						
										<option value="<?=$key?>"><?=$arOrgName;?></option>
									<?endif;?>
								<?endforeach;?>
							</select>
						</td>
					</tr>
					<tr>
						<td align="right">
							<?=GetMessage("REQUEST_DATA")?>
						</td>
						<td>
							<select size="1" onchange="DisableDateField()" class="payroll-input" name="ACTIONTYPE">
								<option selected value="PAYROLL"><?=GetMessage("PAYROLL");?></option>
								<option value="HOLIDAY"><?=GetMessage("HOLYDAY");?></option>
							</select>
						</td>
					</tr>
					<tr>				
						<td align="right">
							<?=GetMessage("PIN")?>
						</td>
						<td>
							<input type="password" onkeypress="if (event.keyCode==13) {RequestData(); return false;}" class="payroll-input" class="payroll-input" name="USERPIN" value="">
						</td>
					</tr>
					<tr>
						<td  align="right">
							<?=GetMessage("DATE")?>
						</td>
						<td>
							<select class="payroll-input payroll-date" name="MONTH">
								<?for($i=1;$i<=12;$i++):?>
									<option <?=($i==$arResult["CURRENT_MONTH"]-1)?"selected":"";?> value="<?=$i?>"><?=GetMessage("MONTH_".$i);?></option>					
								<?endfor;?>
							</select>
							<select class="payroll-input payroll-date" name="YEAR">
								<?for($i=$arResult["CURRENT_YEAR"]-$arParams["YEAR_OFFSET"];$i<=$arResult["CURRENT_YEAR"];$i++):?>
									<option <?=($i==$arResult["CURRENT_YEAR"])?"selected":"";?> value="<?=$i?>"><?=$i?></option>					
								<?endfor;?>
							</select>
							
						</td>
					</tr>
					<tr>
						<td>
							<span align="right"> <a href="<?=$arResult["ACTIVATION_FROM_URL"]?>"><?=GetMessage("GET_ANOTHER_PIN")?></a></spin>
						</td>
						<td align="right">						
							<input type="hidden" name="GETDATA" value="Y">
							<span class="popup-window-button" >
							<span class="popup-window-button-left"></span>
							<span class="popup-window-button-text" onclick="RequestData()"><?=GetMessage("SHOW")?></span>
							<span class="popup-window-button-right"></span>
							</span>
						</td>
					</tr>
				</table>
			</form>
		</div>
	</div>

<script>
	var arWindow;

	function DisableDateField()
	{
		if (document.getElementsByName('ACTIONTYPE')[0].value == "HOLIDAY")
		{
			document.getElementsByName('MONTH')[0].disabled=true;
			document.getElementsByName('YEAR')[0].disabled=true;
		}
		else
		{
			document.getElementsByName('MONTH')[0].disabled=false;
			document.getElementsByName('YEAR')[0].disabled=false;
		}
	}

	function RequestData()
	{
		var width = 550;
		var height = 220;
		var arCurrentMonth=<?=$arResult["CURRENT_MONTH"]?>;
		var arCurrentYear=<?=$arResult["CURRENT_YEAR"]?>;
		var arUserPin=document.getElementsByName('USERPIN')[0].value;
		var arMonth=document.getElementsByName('MONTH')[0].value;
		var arYear=document.getElementsByName('YEAR')[0].value;
		var arOrg=document.getElementsByName('USERORG')[0].value;
		var arAction=document.getElementsByName('ACTIONTYPE')[0].value;
		var query_str="";
		if (arUserPin == "")
		{
			alert("<?=GetMessage("PIN_TYPE_PLEASE")?>");
			return;
		}
		
		if (arAction == "PAYROLL")
		{
			width=850;
			height=500;
		}	
		
		if(navigator.userAgent.toLowerCase().indexOf("opera") != -1) 
		{ 
			w = document.body.offsetWidth; 
			h = document.body.offsetHeight; 
		}	
		else 
		{ 
			w = screen.width; 
			h = screen.height; 
		} 
		wleft = w/2 - width/2;
		wtop = h/2 - height/2;

		
		arWindow = window.open("",arAction,'width='+width+', height='+height+', top='+wtop+', left='+wleft+',toolbar=0,location=0, scrollbars=yes');
		BX("payroll_params").target = arAction;
		BX("payroll_params").submit();
		
				
	}
</script>
<?endif;?>
