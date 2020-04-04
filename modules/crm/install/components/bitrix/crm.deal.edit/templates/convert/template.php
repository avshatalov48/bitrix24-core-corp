<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
global $APPLICATION;
$APPLICATION->AddHeadScript('/bitrix/js/crm/interface_form.js');
// resort additional fields
$arFields = array();
$arrAddRequiredFields = array();
$arrAddFields = array();
foreach($arResult['FIELDS']['tab_1'] as $fieldKey => $field):
	if($field['id'] === 'SALE_ORDER')
	{
		unset($arResult['FIELDS']['tab_1'][$fieldKey]);
		continue;
	}
	$field['id'] = 'DEAL_'.$field['id'];		
	if (strpos($field['id'], 'UF_') !== false):
		if ($field['required'] == 'Y'):
			$arrAddRequiredFields[] = $field;
		else:
			$arrAddFields[] = $field;
			if ($sFirstAddField == '')
				$sFirstAddField = $field['id'];			
		endif;
	else:
		if ($field['id'] == 'DEAL_CONTACT_ID' || $field['id'] == 'DEAL_COMPANY_ID' || $field['type'] == 'section')
			continue;
		else if ($field['id'] == 'DEAL_COMMENTS')
			$field['value'] = str_replace(":'COMMENTS'", ":'DEAL_COMMENTS'", $field['value']);			
		if ($field['id'] == 'DEAL_EVENT_DESCRIPTION' || $field['id'] == 'DEAL_EVENT_DATE' || $field['id'] == 'DEAL_EVENT_ID')	
			$field['name'] = GetMessage('CRM_FIELD_'.str_replace('DEAL_', '', $field['id']));				
		$arFields[] = $field;
	endif;
endforeach;

$arResult['FIELDS']['tab_1'] = array_merge($arFields, $arrAddRequiredFields, $arrAddFields);

$bChecked = (isset($_POST['CONVERT_DEAL']) && $_POST['CONVERT_DEAL'] == 'Y');
?>
<table cellpadding="0" cellspacing="0" border="0" class="bx-edit-table" style="padding-left: 20px; padding-right: 20px">
<tr  style="background-color: #c9dcfd">
	<td class="bx-field-name bx-padding" style="background:none">
<input type="hidden" value="N" name="CONVERT_DEAL" >
<input type="checkbox" value="Y" id="CONVERT_DEAL" name="CONVERT_DEAL" <?=($bChecked ? 'checked' : '')?>>
	</td>
	<td class="bx-field-value" style="background:none">
	<label for="CONVERT_DEAL" style="font-weight: bold;"><?=GetMessage('CRM_FIELD_CONVERT_DEAL')?></label>
	</td>
</tr>
</table>	
<div class="bx-edit-table" id="DEAL_form" style="display:<?=($bChecked ? 'block' : 'none')?>">
<table cellpadding="0" cellspacing="0" border="0" class="bx-edit-table" id="tab_convert_deal_edit_table" style="background-color: #f0f4ff;">
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
		<tr id="tr_deal_add_fields"><td colspan="2" class="bx-add-fields-section"><a href="javascript:showAdditionalFields('deal')" id="deal_a"><?=GetMessage('CRM_ADDITIONAL_FIELDS')?></a></td></tr>
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
		"SHOW_TIME"=>'Y',
	),
	$component,
	array("HIDE_ICONS"=>true)
);?>
<?
			break;
		case 'intranet_user_search':
			$params = isset($field['componentParams']) ? $field['componentParams'] : array();
			if(!empty($params)):
				$params['SEARCH_INPUT_NAME'] = 'DEAL_'.$params['SEARCH_INPUT_NAME'];
				$params['INPUT_NAME'] = 'DEAL_'.$params['INPUT_NAME'];

				$rsUser = CUser::GetByID($val);
				if($arUser = $rsUser->Fetch()):
					$params['USER'] = $arUser;
				endif;
				?><input type="text" class="bx-crm-edit-input" id="<?=htmlspecialcharsbx($params['SEARCH_INPUT_NAME'])?>" name="<?=htmlspecialcharsbx($params['SEARCH_INPUT_NAME'])?>">
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
</div>
<script type="text/javascript">
	BX.ready(
		function()
		{
			var formID = 'DEAL_form';
			var prodEditor = BX.CrmProductEditor.getDefault();

			function handleShowDealForm()
			{
				var dealForm = BX('DEAL_form');
				dealForm.style.display = (dealForm.style.display == 'none' ? 'block' : 'none');

				if (this.checked)
					BX.onCustomEvent("CrmHandleShowProductEditor", [prodEditor]);
			}

			BX.addCustomEvent(
				prodEditor,
				'sumTotalChange',
				function(ttl)
				{
					var el = BX.findChild(BX(formID), { 'tag':'input', 'attr':{ 'name': 'DEAL_OPPORTUNITY' } }, true, false);
					if(el)
					{
						el.value = ttl;
					}
				}
			);

			BX.bind(
				BX.findChild(BX(formID), { 'tag':'select', 'attr':{ 'name': 'DEAL_CURRENCY_ID' } }, true, false),
				'change',
				function()
				{
					var currencyEl = BX.findChild(BX(formID), { 'tag':'select', 'attr':{ 'name': 'DEAL_CURRENCY_ID' } }, true, false);
					var opportunityEl = BX.findChild(BX(formID), { 'tag':'input', 'attr':{ 'name': 'DEAL_OPPORTUNITY' } }, true, false);

					var currencyId = currencyEl.value;
					var prevCurrencyId = prodEditor.getCurrencyId();

					prodEditor.setCurrencyId(currencyId);

					var oportunity = opportunityEl.value.length > 0 ? parseFloat(opportunityEl.value) : 0;
					if(isNaN(oportunity))
					{
						oportunity = 0;
					}

					if(prodEditor.getProductCount() == 0 && oportunity !== 0)
					{
						prodEditor.convertMoney(
							parseFloat(opportunityEl.value),
							prevCurrencyId,
							currencyId,
							function(sum)
							{
								opportunityEl.value = sum;
							}
						);
					}
				}
			);

			var checkbox = BX("CONVERT_DEAL");
			if (BX.type.isElementNode(checkbox) && checkbox.tagName.toUpperCase() === "INPUT"
				&& checkbox.getAttribute("type").toLowerCase() === "checkbox")
			{
				BX.bind(checkbox, "change", handleShowDealForm);
			}
		}
	);
</script><?
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
