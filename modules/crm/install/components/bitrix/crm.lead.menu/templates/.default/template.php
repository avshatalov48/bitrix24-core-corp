<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

/**
 * @var array $arParams
 * @var array $arResult
 * @var \CBitrixComponentTemplate $this
 * @global CMain $APPLICATION
 * @global CUser $USER
 * @global CDatabase $DB
 */

global $APPLICATION;

if (!empty($arResult['BUTTONS']))
{
	$type = $arParams['TYPE'];
	$template = 'type2';
	if($type === 'list')
	{
		$template = SITE_TEMPLATE_ID === 'bitrix24' ? 'title' : '';
	}
	else if($type === 'details')
	{
		$template = SITE_TEMPLATE_ID === 'bitrix24' ? 'slider' : 'type2';
	}

	$APPLICATION->IncludeComponent(
		'bitrix:crm.interface.toolbar',
		$template,
		array(
			'TOOLBAR_ID' => $arResult['TOOLBAR_ID'],
			'BUTTONS' => $arResult['BUTTONS']
		),
		$component,
		array('HIDE_ICONS' => 'Y')
	);
}

if(isset($arResult['SONET_SUBSCRIBE']) && is_array($arResult['SONET_SUBSCRIBE'])):
	$subscribe = $arResult['SONET_SUBSCRIBE'];
?><script type="text/javascript">
BX.ready(
	function()
	{
		BX.CrmSonetSubscription.create(
			"<?=CUtil::JSEscape($subscribe['ID'])?>",
			{
				"entityType": "<?=CCrmOwnerType::LeadName?>",
				"serviceUrl": "<?=CUtil::JSEscape($subscribe['SERVICE_URL'])?>",
				"actionName": "<?=CUtil::JSEscape($subscribe['ACTION_NAME'])?>"
			}
		);
	}
);
</script><?
endif;
if (is_array($arResult['STEXPORT_PARAMS']))
{
	\Bitrix\Main\UI\Extension::load(['ui.progressbar', 'ui.buttons', 'ajax']);
	\Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/common.js');
	\Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/export.js');
	?>
	<script type="text/javascript">
	BX.ready(
		function()
		{
			BX.Crm.ExportManager.create(
				"<?=CUtil::JSEscape($arResult['STEXPORT_PARAMS']['managerId'])?>",
				<?=CUtil::PhpToJSObject($arResult['STEXPORT_PARAMS'])?>
			);
		}
	);
	</script><?
}
if (array_key_exists("IS_NEED_TO_CHECK", $arResult))
{
	?><?$APPLICATION->IncludeComponent(
			"bitrix:crm.config.checker",
			"invisible"
	);?><?

}
