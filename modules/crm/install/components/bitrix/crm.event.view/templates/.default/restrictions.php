<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm\Restriction\RestrictionManager;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Localization\Loc;

/** @global CMain $APPLICATION */
/** @var CBitrixComponent $component */
/** @var array $arParams */
/** @var array $arResult */

if($arResult['ENABLE_CONTROL_PANEL'])
{
	$APPLICATION->IncludeComponent(
		'bitrix:crm.control_panel',
		'',
		array(
			'ID' => 'EVENT_LIST',
			'ACTIVE_ITEM_ID' => 'EVENT',
			'PATH_TO_COMPANY_LIST' => $arParams['PATH_TO_COMPANY_LIST'] ?? '',
			'PATH_TO_COMPANY_EDIT' => $arParams['PATH_TO_COMPANY_EDIT'] ?? '',
			'PATH_TO_CONTACT_LIST' => $arParams['PATH_TO_CONTACT_LIST'] ?? '',
			'PATH_TO_CONTACT_EDIT' => $arParams['PATH_TO_CONTACT_EDIT'] ?? '',
			'PATH_TO_DEAL_LIST' => $arParams['PATH_TO_DEAL_LIST'] ?? '',
			'PATH_TO_DEAL_EDIT' => $arParams['PATH_TO_DEAL_EDIT'] ?? '',
			'PATH_TO_LEAD_LIST' => $arParams['PATH_TO_LEAD_LIST'] ?? '',
			'PATH_TO_LEAD_EDIT' => $arParams['PATH_TO_LEAD_EDIT'] ?? '',
			'PATH_TO_QUOTE_LIST' => $arResult['PATH_TO_QUOTE_LIST'] ?? '',
			'PATH_TO_QUOTE_EDIT' => $arResult['PATH_TO_QUOTE_EDIT'] ?? '',
			'PATH_TO_INVOICE_LIST' => $arResult['PATH_TO_INVOICE_LIST'] ?? '',
			'PATH_TO_INVOICE_EDIT' => $arResult['PATH_TO_INVOICE_EDIT'] ?? '',
			'PATH_TO_REPORT_LIST' => $arParams['PATH_TO_REPORT_LIST'] ?? '',
			'PATH_TO_DEAL_FUNNEL' => $arParams['PATH_TO_DEAL_FUNNEL'] ?? '',
			'PATH_TO_EVENT_LIST' => $arParams['PATH_TO_EVENT_LIST'] ?? '',
			'PATH_TO_PRODUCT_LIST' => $arParams['PATH_TO_PRODUCT_LIST'] ?? ''
		),
		$component
	);
}

if (!(isset($arResult['INTERNAL']) && $arResult['INTERNAL'])):
?>
<script type="text/javascript">
	BX.ready(function() {
		<?php echo RestrictionManager::getHistoryViewRestriction()->prepareInfoHelperScript() . ';'; ?>
		var slider = top.BX && top.BX.SidePanel && top.BX.SidePanel.Instance.getSliderByWindow(window);
		if(slider)
		{
			slider.close();
		}
		else
		{
			BX.addCustomEvent("SidePanel.Slider:onCloseComplete", function(event) {
				location.href = "/crm/deal/";
			});
		}
	});
</script>
<?php
endif;

Container::getInstance()->getLocalization()->loadMessages();

$APPLICATION->IncludeComponent(
	'bitrix:main.ui.grid',
	'',
	[
		'GRID_ID'   => 'CRM_EVENT_LIST_RESTRICTED',
		'HEADERS'   => [['id' => 'ID', 'name' => 'ID']],
		'ROWS'      => [],
		'STUB'      => [
			'title' => Loc::getMessage('CRM_FEATURE_RESTRICTION_GRID_TITLE'),
			'description' => Loc::getMessage('CRM_FEATURE_RESTRICTION_GRID_TEXT'),
		],
	],
	$component,
	[
			'HIDE_ICONS' => 'Y',
	]
);
