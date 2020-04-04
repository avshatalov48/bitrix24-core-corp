<?if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

global $APPLICATION;
$arTabs = array();
$arTabs[] = array(
	'id' => 'tab_params',
	'name' => GetMessage('CRM_TAB_1'),
	'title' => GetMessage('CRM_TAB_1_TITLE'),
	'icon' => '',
	'fields'=> $arResult['FIELDS']['tab_params']
);

CCrmGridOptions::SetTabNames($arResult['FORM_ID'], $arTabs);

$formCustomHtml = $arResult['HIDDEN_FIELDS_HTML'].
	'<input type="hidden" name="PROP_ID" value="'.$arResult['PROP_ID'].'">'.
	'<input type="hidden" name="IBLOCK_ID" value="'.$arResult['IBLOCK_ID'].'">'.
	'<input type="hidden" name="action" id="action" value="">';

if (!empty($arResult['ERR_MSG']))
	ShowError($arResult['ERR_MSG']);

$APPLICATION->IncludeComponent(
	'bitrix:main.interface.form',
	'',
	array(
		'FORM_ID' => $arResult['FORM_ID'],
		'TABS' => $arTabs,
		'BUTTONS' => array(
			'standard_buttons' => true,
			'back_url' => $arResult['BACK_URL'],
			'custom_html' => $formCustomHtml
		),
		'DATA' => $arResult['LOC'],
		'SHOW_SETTINGS' => 'N',
		'THEME_GRID_ID' => $arResult['GRID_ID'],
		'SHOW_FORM_TAG' => 'Y'
	),
	$component,
	array('HIDE_ICONS' => 'Y')
);

?>
<script type="text/javascript">
	function reloadForm()
	{
		var _form = BX('form_' + '<?=CUtil::JSEscape($arResult['FORM_ID'])?>');
		var _flag = BX('action');
		if(!!_form && !!_flag)
		{
			_flag.value = 'reload';
			_form.submit();
		}
	}
	<?
	if('L' == $arResult['PROPERTY']['PROPERTY_TYPE']):
	?>
	window.oPropSet = {
		pTypeTbl: BX("list-tbl"),
		curCount: parseInt(<?=CUtil::JSEscape($arResult['MAX_NEW_ID'])?>)+5,
		intCounter: BX("PROPERTY_CNT")
	};

	function add_list_row()
	{
		var id = window.oPropSet.curCount++;
		window.oPropSet.intCounter.value = window.oPropSet.curCount;
		var newRow = window.oPropSet.pTypeTbl.insertRow(window.oPropSet.pTypeTbl.rows.length);
		var oCell, strContent;

		oCell = newRow.insertCell(-1);
		strContent = '<?=CUtil::JSEscape($arResult['LIST_VALUE_ID_CELL']); ?>';
		strContent = strContent.replace(/tmp_xxx/ig, id);
		oCell.innerHTML = strContent;
		oCell.setAttribute('class', 'bx-digit-cell bx-left');
		oCell = newRow.insertCell(-1);
		strContent = '<?=CUtil::JSEscape($arResult['LIST_VALUE_XMLID_CELL']); ?>';
		strContent = strContent.replace(/tmp_xxx/ig, id);
		oCell.innerHTML = strContent;
		oCell = newRow.insertCell(-1);
		strContent = '<?=CUtil::JSEscape($arResult['LIST_VALUE_VALUE_CELL']); ?>';
		strContent = strContent.replace(/tmp_xxx/ig, id);
		oCell.innerHTML = strContent;
		oCell = newRow.insertCell(-1);
		strContent = '<?=CUtil::JSEscape($arResult['LIST_VALUE_SORT_CELL']); ?>';
		strContent = strContent.replace(/tmp_xxx/ig, id);
		oCell.innerHTML = strContent;
		oCell.style.textAlign = 'center';
		oCell = newRow.insertCell(-1);
		strContent = '<?=CUtil::JSEscape($arResult['LIST_VALUE_DEF_CELL']); ?>';
		strContent = strContent.replace(/tmp_xxx/ig, id);
		oCell.innerHTML = strContent;
		oCell.setAttribute('class', 'bx-right');
		oCell.style.textAlign = 'center';
	}

	var obListBtn = BX('propedit_add_btn');

	if (!!obListBtn && !!window.oPropSet)
		BX.bind(obListBtn, 'click', add_list_row);
	<?
	endif;
	?>
</script>