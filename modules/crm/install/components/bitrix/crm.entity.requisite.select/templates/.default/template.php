<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var CCrmEntityPopupComponent $component */

if($arResult['IFRAME'])
{
	$APPLICATION->RestartBuffer();
	?><!DOCTYPE html>
	<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?=LANGUAGE_ID ?>" lang="<?=LANGUAGE_ID ?>">
	<head>
		<script type="text/javascript">
			// Prevent loading page without header and footer
			if(window === window.top)
			{
				window.location = "<?=CUtil::JSEscape($APPLICATION->GetCurPageParam('', array('IFRAME'))); ?>";
			}
		</script>
		<?$APPLICATION->ShowHead();?>
		<style>.crm-iframe-popup,
			.crm-iframe-popup.crm-form-page,
			.crm-iframe-popup.crm-detail-page{
				background: #eef2f4 !important;
				padding: 0 15px 21px 21px;
			}</style>
	</head>
	<body class="crm-iframe-popup crm-detail-page template-<?= SITE_TEMPLATE_ID ?> <? if(!$arResult['IFRAME_USE_SCROLL']):?>crm-iframe-popup-no-scroll<?endif ?> <? $APPLICATION->ShowProperty('BodyClass'); ?>" onload="window.top.BX.onCustomEvent(window.top, 'crmEntityIframeLoad');" onunload="window.top.BX.onCustomEvent(window.top, 'crmEntityIframeUnload');">

	<div class="crm-iframe-workarea" id="tasks-content-outer">
	<div class="crm-iframe-sidebar"><?$APPLICATION->ShowViewContent("sidebar"); ?></div>
	<div class="crm-iframe-content"><?
}

\Bitrix\Main\UI\Extension::load([
	'ui.design-tokens',
	'ui.fonts.opensans',
]);

$APPLICATION->SetAdditionalCSS("/bitrix/components/bitrix/crm.entity.details/templates/.default/style.css");

?><div class="crm-entity-wrap">
	<div class="crm-entity-section crm-entity-section-requisite-selector">
		<?$APPLICATION->IncludeComponent(
			'bitrix:crm.entity.editor',
			'',
			array(
				'GUID' => $arResult['GUID'],
				'EXTERNAL_CONTEXT_ID' => $arResult['EXTERNAL_CONTEXT_ID'],
				'ENTITY_TYPE_ID' => $arResult['ENTITY_TYPE_ID'],
				'ENTITY_ID' => $arResult['ENTITY_ID'],
				'ENTITY_CONFIG' => $arResult['ENTITY_CONFIG'],
				'ENTITY_FIELDS' => $arResult['ENTITY_FIELDS'],
				'ENTITY_DATA' => $arResult['ENTITY_DATA'],
				'ENABLE_AJAX_FORM' => false,
				'ENABLE_SECTION_EDIT' => false,
				'ENABLE_SECTION_CREATION' => false,
				'ENABLE_USER_FIELD_CREATION' => false,
				'INITIAL_MODE' => 'EDIT',
				'ENABLE_MODE_TOGGLE' => false,
				'READ_ONLY' => false
			)
		);?>
		</div>
</div><?


$guid = $arResult['GUID'];
		$prexix = mb_strtolower($guid);
$containerID = "{$prexix}_container";
?>

<script type="text/javascript">
	BX.ready(
		function()
		{
			BX.Crm.EntityRequisiteSelector.create("<?=CUtil::JSEscape($guid)?>", {});
		}
	);
</script><?

if($arResult['IFRAME'])
{
			?></div>
		</div>
		</body>
	</html><?
	require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php');
	die();
}
