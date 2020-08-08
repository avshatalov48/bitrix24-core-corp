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

\Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/main/utils.js');

if (!empty($arResult['BUTTONS']))
{
	$type = $arParams['TYPE'];

	if ($arParams['TYPE'] == 'show' && \Bitrix\Main\Loader::includeModule('intranet'))
	{
		$APPLICATION->includeComponent(
			'bitrix:intranet.binding.menu',
			'',
			array(
				'SECTION_CODE' => 'crm_detail',
				'MENU_CODE' => 'invoice',
				'CONTEXT' => [
					'ENTITY_ID' => $arParams['ELEMENT_ID']
				]
			)
		);

		?><script type="text/javascript">
		BX.ready(function() {
			var intranetBindingBtn = document.querySelector('.intranet-binding-menu-btn');
			var invoiceToolbar = BX('crm_invoice_toolbar');

			if (invoiceToolbar && intranetBindingBtn)
			{
				invoiceToolbar.insertBefore(intranetBindingBtn, invoiceToolbar.firstChild);
			}
		});
	</script><?
	}
	$APPLICATION->IncludeComponent(
		'bitrix:crm.interface.toolbar',
		$type === 'list' ?  (SITE_TEMPLATE_ID === 'bitrix24' ? 'title' : '') : 'type2',
		array(
			'TOOLBAR_ID' => 'crm_invoice_toolbar',
			'BUTTONS' => $arResult['BUTTONS']
		),
		$component,
		array('HIDE_ICONS' => 'Y')
	);
}
if (is_array($arResult['STEXPORT_PARAMS']))
{
	\Bitrix\Main\UI\Extension::load('ui.progressbar');
	\Bitrix\Main\UI\Extension::load('ui.buttons');
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
