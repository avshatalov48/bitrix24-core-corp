<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)die();
global $APPLICATION;
$APPLICATION->AddHeadScript('/bitrix/js/crm/interface_form.js');
// resort additional fields
$arFields = array();
$arrAddRequiredFields = array();
$arrAddFields = array();
$sFirstAddField = '';
foreach($arResult['FIELDS']['tab_1'] as $field):		
	$field['id'] = 'COMPANY_'.$field['id'];		
	if (strpos($field['id'], 'UF_') !== false):
		if ($field['required'] == 'Y'):
			$arrAddRequiredFields[] = $field;
		else:
			$arrAddFields[] = $field;
			if ($sFirstAddField == '')
				$sFirstAddField = $field['id'];			
		endif;
	else:	
		if ($field['id'] == 'COMPANY_CONTACT_ID' || $field['type'] == 'section')
			continue;	
		else if ($field['id'] == 'COMPANY_COMMENTS')
			$field['value'] = str_replace(":'COMMENTS'", ":'COMPANY_COMMENTS'", $field['value']);				
		$arFields[] = $field;	
	endif;
endforeach;
$arResult['FIELDS']['tab_1'] = array_merge($arFields, $arrAddRequiredFields, $arrAddFields);

$bChecked = (isset($_POST['CONVERT_COMPANY']) && $_POST['CONVERT_COMPANY'] == 'Y');
?>
<table cellpadding="0" cellspacing="0" border="0" class="bx-edit-table" style="padding-left: 20px; padding-right: 20px; margin-bottom: 2px">
<tr  style="background-color: #ffe27f;">
	<td class="bx-padding" colspan="2" style="background:none; text-align: center; font-weight: bold;">
			<input type="radio" value="Y" id="CONVERT_COMPANY_Y" name="CONVERT_COMPANY" <?=($bChecked ? 'checked' : '')?>
			onclick="var checked = this.checked; BX('COMPANY_form').style.display = checked ? '' : 'none'; BX('COMPANY_choise').style.display = !checked ? '' : 'none';" /><label for="CONVERT_COMPANY_Y" style="padding-right: 52px;"><?=GetMessage('CRM_FIELD_CONVERT_COMPANY')?></label>
			<input type="radio" value="N" id="CONVERT_COMPANY_N" name="CONVERT_COMPANY" <?=(!$bChecked ? 'checked' : '')?>
			onclick="var checked = this.checked; BX('COMPANY_form').style.display = !checked ? '' : 'none'; BX('COMPANY_choise').style.display = checked ? '' : 'none';"/><label for="CONVERT_COMPANY_N"><?=GetMessage('CRM_FIELD_CONVERT_COMPANY_LIST')?></label>
	</td>
</tr>
<tr id="COMPANY_choise" style="background-color: #FFF6D8;display:<?=(!$bChecked ? '' : 'none')?>">
	<td class="bx-field-name bx-padding" style="background:none"><?=GetMessage('CRM_FIELD_CONVERT_COMPANY_CHOISE')?>:</td>
	<td class="bx-field-value" style="background:none">
	<?
$GLOBALS["APPLICATION"]->IncludeComponent('bitrix:crm.entity.selector', 
	'', 
	array(
		'ENTITY_TYPE' => 'COMPANY',
		'INPUT_NAME' => 'COMPANY_COMPANY_ID',
		'INPUT_VALUE' => isset($arResult['ELEMENT']['COMPANY_ID']) ? $arResult['ELEMENT']['COMPANY_ID'] : '',
		'FORM_NAME' => $arResult['FORM_ID'],
		'MULTIPLE' => 'N'
	),
	$component,
	array('HIDE_ICONS' => 'Y')
);?>
	</td>
</tr>
</table>	
<div class="bx-edit-table" id="COMPANY_form" style="display:<?=($bChecked ? 'block' : 'none')?>">
<table cellpadding="0" cellspacing="0" border="0" class="bx-edit-table" id="tab_convert_company_edit_table" style="background-color: #fff6d8">
<?
$i = 0;
$cnt = count($arResult['FIELDS']['tab_1']);
$prevType = '';
$sClassSuf = '';
$sClassSuf2 = '';
$bAddFields = false;
$arUserSearchFields = array();
foreach($arResult['FIELDS']['tab_1'] as $field):
	$i++;
	if(!is_array($field))
		continue;

	if ($sFirstAddField == $field["id"]):
		$bAddFields = true;	
		$sClassSuf .= ' bx-add-name';
		$sClassSuf2 .= ' bx-add-value';
		?>
		<tr id="tr_company_add_fields"><td colspan="2" class="bx-add-fields-section"><a href="javascript:showAdditionalFields('company')" id="company_a"><?=GetMessage('CRM_ADDITIONAL_FIELDS')?></a></td></tr>
		<?
	endif;			
		
	$className = '';
	if($i == 1)
		$className .= ' bx-top';
	if($i == $cnt)
		$className .= ' bx-bottom';
			if ($i == $cnt && $sFirstAddField != '')
	{
		$sClassSuf .= ' bx-add-bottom'	;
		$sClassSuf2 .= ' bx-add-bottom'	;
	}		
	if($prevType == 'section')
		$className .= ' bx-after-heading';
?>
	<tr<?if($className <> ''):?> class="<?=$className?>"<?endif?> <?=($bAddFields ? 'style="display:none"' : '')?>>
<?

if($field["type"] == 'section'):
?>
		<td colspan="2" class="bx-heading"><?=$field["name"]?></td>
<?
else:
	$val = (isset($field["value"])? $field["value"] : $arParams["~DATA"][$field["id"]]);

	//default attributes
	if(!is_array($field["params"]))
		$field["params"] = array();
	if($field["type"] == '' || $field["type"] == 'text')
	{
		if($field["params"]["size"] == '')
			$field["params"]["size"] = "30";
	}
	elseif($field["type"] == 'textarea')
	{
		if($field["params"]["cols"] == '')
			$field["params"]["cols"] = "40";
		if($field["params"]["rows"] == '')
			$field["params"]["rows"] = "3";
	}
	elseif($field["type"] == 'date')
	{
		if($field["params"]["size"] == '')
			$field["params"]["size"] = "10";
	}
	
	$params = '';
	if(is_array($field["params"]) && $field["type"] <> 'file')
	{
		foreach($field["params"] as $p=>$v)
			$params .= ' '.$p.'="'.$v.'"';
	}

	if($field["colspan"] <> true):
		if($field["required"])
			$bWasRequired = true;
?>
		<td class="bx-field-name<?=$sClassSuf?><?if($field["type"] <> 'label') echo' bx-padding'?>"><?=($field["required"]? '<span class="required">*</span>':'')?><?=$field["name"]?>:</td>
<?
	endif
?>
		<td class="bx-field-value<?=$sClassSuf2?>"<?=($field["colspan"]? ' colspan="2"':'')?>>
<?
	switch($field["type"]):
		case 'label':
		case 'custom':
		case 'vertical_container':
			echo $val;
			break;
		case 'checkbox':
		case 'vertical_checkbox':
?>
<input type="hidden" name="<?=$field["id"]?>" value="N">
<input type="checkbox" name="<?=$field["id"]?>" value="Y"<?=($val == "Y"? ' checked':'')?><?=$params?>>
<?
			break;
		case 'textarea':
?>
<textarea name="<?=$field["id"]?>"<?=$params?>><?=$val?></textarea>
<?
			break;
		case 'list':
?>
<select name="<?=$field["id"]?>"<?=$params?>>
<?
			if(is_array($field["items"])):
				if(!is_array($val))
					$val = array($val);
				foreach($field["items"] as $k=>$v):
?>
	<option value="<?=htmlspecialcharsbx($k)?>"<?=(in_array($k, $val)? ' selected':'')?>><?=htmlspecialcharsbx($v)?></option>
<?
				endforeach;
?>
</select>
<?
			endif;
			break;
		case 'file':
			$arDefParams = array("iMaxW"=>150, "iMaxH"=>150, "sParams"=>"border=0", "strImageUrl"=>"", "bPopup"=>true, "sPopupTitle"=>false, "size"=>20);
			foreach($arDefParams as $k=>$v)
				if(!array_key_exists($k, $field["params"]))
					$field["params"][$k] = $v;
	
			echo CFile::InputFile($field["id"], $field["params"]["size"], $val);
			if($val <> '')
				echo '<br>'.CFile::ShowImage($val, $field["params"]["iMaxW"], $field["params"]["iMaxH"], $field["params"]["sParams"], $field["params"]["strImageUrl"], $field["params"]["bPopup"], $field["params"]["sPopupTitle"]);

			break;
		case 'date':
?>
<?$APPLICATION->IncludeComponent(
	"bitrix:main.calendar",
	"",
	array(
		"SHOW_INPUT"=>"Y",
		"INPUT_NAME"=> $field["id"],
		"INPUT_VALUE"=>$val,
		"INPUT_ADDITIONAL_ATTR"=>$params,
	),
	$component,
	array("HIDE_ICONS"=>true)
);?>
<?
			break;
		case 'intranet_user_search':
			$params = isset($field['componentParams']) ? $field['componentParams'] : array();
			if(!empty($params)):
				$params['SEARCH_INPUT_NAME'] = 'COMPANY_'.$params['SEARCH_INPUT_NAME'];
				$params['INPUT_NAME'] = 'COMPANY_'.$params['INPUT_NAME'];

				$rsUser = CUser::GetByID($val);
				if($arUser = $rsUser->Fetch()):
					$params['USER'] = $arUser;
				endif;
				?><input type="text" class="bx-crm-edit-input" name="<?=htmlspecialcharsbx($params['SEARCH_INPUT_NAME'])?>">
			<input type="hidden" name="<?=htmlspecialcharsbx($params['INPUT_NAME'])?>" value="<?=htmlspecialcharsbx($val)?>"><?
				$arUserSearchFields[] = $params;
				$APPLICATION->IncludeComponent(
					'bitrix:intranet.user.selector.new',
					'',
					array(
						'MULTIPLE' => 'N',
						'NAME' => $params['NAME'],
						'INPUT_NAME' => $params['SEARCH_INPUT_NAME'],
						'POPUP' => 'Y',
						'SITE_ID' => SITE_ID,
						'NAME_TEMPLATE' => $params['NAME_TEMPLATE']
					),
					null,
					array('HIDE_ICONS' => 'Y')
				);
			endif;
			break;
		case 'address':
			$params = isset($field['componentParams']) ? $field['componentParams'] : array();
			$addressData = isset($params['DATA']) ? $params['DATA'] : array();
			$addressServiceUrl = isset($params['SERVICE_URL']) ? $params['SERVICE_URL'] : '';
			$addressLabels = Bitrix\Crm\EntityAddress::getLabels();
			?><div class="crm-offer-info-data-wrap">
				<table class="crm-offer-info-table"><tbody>
				<?
			foreach($addressData as $itemKey => $item):
				$itemValue = isset($item['VALUE']) ? $item['VALUE'] : '';
				$itemName = isset($item['NAME']) ? $item['NAME'] : $itemKey;
				$itemLocality = isset($item['LOCALITY']) ? $item['LOCALITY'] : null;
				?><tr>
					<td class="crm-offer-info-left">
						<span class="crm-offer-info-label"><?=$addressLabels[$itemKey]?>:</span>
					</td>
					<td class="crm-offer-info-right">
						<div class="crm-offer-info-data-wrap"><?
							if(is_array($itemLocality)):
								$searchInputID = "COMPANY_{$arParams['FORM_ID']}_{$itemName}";
								$dataInputID = "COMPANY_{$arParams['FORM_ID']}_{$itemLocality['NAME']}";
								?><input class="crm-offer-item-inp" id="<?=$searchInputID?>" name="COMPANY_<?=$itemName?>" type="text" value="<?=htmlspecialcharsEx($itemValue)?>" />
								<input type="hidden" id="<?=$dataInputID?>" name="COMPANY_<?=$itemLocality['NAME']?>" value="<?=htmlspecialcharsbx($itemLocality['VALUE'])?>"/>
								<script type="text/javascript">
									BX.ready(
										function()
										{
											BX.CrmLocalitySearchField.create(
												"<?=$searchInputID?>",
												{
													localityType: "<?=$itemLocality['TYPE']?>",
													serviceUrl: "<?=$addressServiceUrl?>",
													searchInputId: "<?=$searchInputID?>",
													dataInputId: "<?=$dataInputID?>"
												}
											);
										}
									);
								</script><?
							else:
								if(isset($item['IS_MULTILINE']) && $item['IS_MULTILINE']):
									?><textarea class="bx-crm-edit-text-area" name="COMPANY_<?=htmlspecialcharsEx($itemName)?>"><?=$itemValue?></textarea><?
								else:
									?><input class="crm-offer-item-inp" name="COMPANY_<?=htmlspecialcharsEx($itemName)?>" type="text" value="<?=htmlspecialcharsEx($itemValue)?>" /><?
								endif;
							endif;
						?></div>
					</td>
				</tr><?
			endforeach;
			?></tbody></table>
			</div><?
		break;
		default:
?>
<input type="text" name="<?=$field["id"]?>" value="<?=htmlspecialcharsbx($val)?>"<?=$params?>>
<?
			break;
	endswitch;
?>
		</td>
<?endif?>
	</tr>
<?
	$prevType = $field["type"];
endforeach;
?>
</table>
</div><?
if(!empty($arUserSearchFields)):
?><script type="text/javascript">
	BX.ready(
		function()
		{<?
			foreach($arUserSearchFields as &$arField):
				$arUserData = array();
				if(isset($arField['USER'])):
					$nameFormat = isset($arField['NAME_TEMPLATE']) ? $arField['NAME_TEMPLATE'] : '';
					if($nameFormat === '')
						$nameFormat = CSite::GetNameFormat(false);
					$arUserData['id'] = $arField['USER']['ID'];
					$arUserData['name'] = CUser::FormatName($nameFormat, $arField['USER'], true, false);
				endif;
			?>
			BX.CrmUserSearchField.create(
				'<?=$arField['NAME']?>',
				document.getElementsByName('<?=$arField['SEARCH_INPUT_NAME']?>')[0],
				document.getElementsByName('<?=$arField['INPUT_NAME']?>')[0],
				'<?=$arField['NAME']?>',
				<?= CUtil::PhpToJSObject($arUserData)?>
			);<?
			endforeach;
			unset($arField);
		?>}
	);
</script><?
endif;
