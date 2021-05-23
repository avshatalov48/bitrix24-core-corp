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

$arTabs[] = array(
	'id' => 'tab_zip',
	'name' => GetMessage('CRM_TAB_2'),
	'title' => GetMessage('CRM_TAB_2_TITLE'),
	'icon' => '',
	'fields'=> $arResult['FIELDS']['tab_zip']
);

CCrmGridOptions::SetTabNames($arResult['FORM_ID'], $arTabs);

$formCustomHtml = '<input type="hidden" name="loc_id" value="'.$arResult['LOC_ID'].'"/>';
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
		'SHOW_SETTINGS' => 'Y',
		'THEME_GRID_ID' => $arResult['GRID_ID'],
		'SHOW_FORM_TAG' => 'Y'
	),
	$component,
	array('HIDE_ICONS' => 'Y')
);
?>
<script type="text/javascript">

BX.ready(
	function(){
		BX.crmLocationParams.init({
			formObj: document.forms["form_<?=$arResult['FORM_ID']?>"],
			oLangs: <?=CUtil::PhpToJsObject($arResult['SYS_LANGS'])?>,
			curLang: "<?=LANGUAGE_ID?>",
			mess: {
				CRM_DEL_ZIP: "<?=GetMessage('CRM_DEL_ZIP')?>"
					},
			ajaxUrl: "<?=$componentPath.'/ajax.php'?>"
		});

		BX.crmLocationCountries.init();
		BX.crmLocationRegions.init();
		BX.crmLocationCities.init();
		BX.crmLocationParams.hideLangFields();
	}
);
</script>