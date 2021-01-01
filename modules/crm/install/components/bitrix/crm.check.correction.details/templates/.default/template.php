<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

global $APPLICATION;

CJSCore::Init(array('date', 'popup', 'ajax', 'tooltip'));
\Bitrix\Main\UI\Extension::load(['sidepanel']);

\Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/slider.js');
\Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/js/crm/css/slider.css');
\Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/js/crm/css/crm.css');
\Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/themes/.default/crm-entity-show.css');

if(SITE_TEMPLATE_ID === 'bitrix24')
{
	\Bitrix\Main\Page\Asset::getInstance()->addCss("/bitrix/themes/.default/bitrix24/crm-entity-show.css");
}

$guid = $arResult['GUID']."_editor";
$prefix = mb_strtolower($guid);
$activityEditorID = "{$prefix}_editor";
$readOnly = $arResult['READ_ONLY'];
$containerId = "{$guid}_container";

$wrapperId = "wrapper_".mb_strtolower($prefix);

$editorContext = array(
	'CATEGORY_ID' => $arResult['CATEGORY_ID'],
	'PARAMS' => $arResult['CONTEXT_PARAMS']
);
?>
<div class="crm-helpdesk-link">
	<a href="https://helpdesk.bitrix24.ru/open/12301946" class="ui-link ui-link-dashed">
		<?=\Bitrix\Main\Localization\Loc::getMessage('CRM_CHECK_CORRECTION_HELPDESK_TITLE')?>
	</a>
</div>
<div id="<?=htmlspecialcharsbx($wrapperId)?>">
	<?
	$APPLICATION->IncludeComponent(
		'bitrix:crm.entity.editor',
		'',
		array(
			'ENTITY_TYPE_ID' => \CCrmOwnerType::CheckCorrection,
			'ENTITY_ID' => $arResult['ENTITY_ID'],
			'READ_ONLY' => $arResult['ENTITY_ID'] > 0,
			'INITIAL_MODE' => $arResult['INITIAL_MODE'],
			'GUID' => $guid,
			'CONFIG_ID' => $arResult['EDITOR_CONFIG_ID'],
			'ENTITY_CONFIG' => $arResult['ENTITY_CONFIG'],
			'ENTITY_CONTROLLERS' => $arResult['ENTITY_CONTROLLERS'],
			'ENTITY_FIELDS' => $arResult['ENTITY_FIELDS'],
			'ENTITY_DATA' => $arResult['ENTITY_DATA'],
			'ENABLE_MODE_TOGGLE' => false,
			'ENABLE_CONFIG_CONTROL' => false,
			'ENABLE_CONFIGURATION_UPDATE' => false,
			'DISABLE_REST' => true,
			'ENABLE_USER_FIELD_CREATION' => $arResult['ENABLE_USER_FIELD_CREATION'],
			'SERVICE_URL' => '/bitrix/components/bitrix/crm.check.correction.details/ajax.php?'.bitrix_sessid_get(),
			'EXTERNAL_CONTEXT_ID' => $arResult['EXTERNAL_CONTEXT_ID'],
			'CONTEXT_ID' => $arResult['CONTEXT_ID'],
			'CONTEXT' => $editorContext
		)
	);
	?>
</div>


<script>
	BX.addCustomEvent(window, "onCrmEntityCreate", function () {
		BX.SidePanel.Instance.getSliderByWindow(window).close();
		BX.SidePanel.Instance.postMessage(window, 'CRM:CheckCorrection:onSave');
	});
</script>