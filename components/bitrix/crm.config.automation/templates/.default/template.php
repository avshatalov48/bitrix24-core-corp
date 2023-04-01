<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}
global $APPLICATION;
/** @var array $arResult */

$APPLICATION->SetPageProperty(
	"BodyClass",
	(isset($bodyClass) && !empty($bodyClass) ? $bodyClass." " : "") . "no-all-paddings no-background"
);

\Bitrix\Main\UI\Extension::load([
	'ui.design-tokens',
	'ui.fonts.opensans',
	'popup',
]);

if (empty($arParams['DISABLE_TOP_MENU']) || $arParams['DISABLE_TOP_MENU'] != 'Y')
{
	$APPLICATION->IncludeComponent(
		'bitrix:crm.control_panel',
		'',
		array(
			'ID'                   => 'BP_EDIT',
			'ACTIVE_ITEM_ID'       => '',
			'PATH_TO_COMPANY_LIST' => isset($arResult['PATH_TO_COMPANY_LIST']) ? $arResult['PATH_TO_COMPANY_LIST'] : '',
			'PATH_TO_COMPANY_EDIT' => isset($arResult['PATH_TO_COMPANY_EDIT']) ? $arResult['PATH_TO_COMPANY_EDIT'] : '',
			'PATH_TO_CONTACT_LIST' => isset($arResult['PATH_TO_CONTACT_LIST']) ? $arResult['PATH_TO_CONTACT_LIST'] : '',
			'PATH_TO_CONTACT_EDIT' => isset($arResult['PATH_TO_CONTACT_EDIT']) ? $arResult['PATH_TO_CONTACT_EDIT'] : '',
			'PATH_TO_DEAL_LIST'    => isset($arResult['PATH_TO_DEAL_LIST']) ? $arResult['PATH_TO_DEAL_LIST'] : '',
			'PATH_TO_DEAL_EDIT'    => isset($arResult['PATH_TO_DEAL_EDIT']) ? $arResult['PATH_TO_DEAL_EDIT'] : '',
			'PATH_TO_LEAD_LIST'    => isset($arResult['PATH_TO_LEAD_LIST']) ? $arResult['PATH_TO_LEAD_LIST'] : '',
			'PATH_TO_LEAD_EDIT'    => isset($arResult['PATH_TO_LEAD_EDIT']) ? $arResult['PATH_TO_LEAD_EDIT'] : '',
			'PATH_TO_QUOTE_LIST'   => isset($arResult['PATH_TO_QUOTE_LIST']) ? $arResult['PATH_TO_QUOTE_LIST'] : '',
			'PATH_TO_QUOTE_EDIT'   => isset($arResult['PATH_TO_QUOTE_EDIT']) ? $arResult['PATH_TO_QUOTE_EDIT'] : '',
			'PATH_TO_INVOICE_LIST' => isset($arResult['PATH_TO_INVOICE_LIST']) ? $arResult['PATH_TO_INVOICE_LIST'] : '',
			'PATH_TO_INVOICE_EDIT' => isset($arResult['PATH_TO_INVOICE_EDIT']) ? $arResult['PATH_TO_INVOICE_EDIT'] : '',
			'PATH_TO_REPORT_LIST'  => isset($arResult['PATH_TO_REPORT_LIST']) ? $arResult['PATH_TO_REPORT_LIST'] : '',
			'PATH_TO_DEAL_FUNNEL'  => isset($arResult['PATH_TO_DEAL_FUNNEL']) ? $arResult['PATH_TO_DEAL_FUNNEL'] : '',
			'PATH_TO_EVENT_LIST'   => isset($arResult['PATH_TO_EVENT_LIST']) ? $arResult['PATH_TO_EVENT_LIST'] : '',
			'PATH_TO_PRODUCT_LIST' => isset($arResult['PATH_TO_PRODUCT_LIST']) ? $arResult['PATH_TO_PRODUCT_LIST'] : ''
		),
		$component
	);
}

$this->setViewTarget("inside_pagetitle_below", 100); ?>
<div class="crm-config-automation-subtitle">
	<?= htmlspecialcharsbx($arResult['SUBTITLE']) ?>
</div>
<?php $this->endViewTarget();

$APPLICATION->IncludeComponent(
	'bitrix:crm.automation',
	'',
	array(
		'ENTITY_TYPE_ID' => $arResult['ENTITY_TYPE_ID'],
		'ENTITY_ID' => $arParams['ENTITY_ID'] ?? null,
		'ENTITY_CATEGORY_ID' => $arResult['ENTITY_CATEGORY'],
		'back_url' => $arParams['~back_url'] ?? sprintf(
			'/crm/%s/automation/%d/',
			strtolower($arResult['ENTITY_TYPE_NAME']),
			$arResult['ENTITY_CATEGORY']
		),
		'CATEGORY_SELECTOR' => (
			$arResult['CATEGORY_NAME']
				? ['TEXT' => $arResult['CATEGORY_NAME']]
				: null
		),
	),
	$component
);
?>
<script>
	BX.ready(function()
	{
		var categorySelector = document.querySelector('[data-role="category-selector"]');
		if (categorySelector)
		{
			var menuItems = [], i, categories = <?= \Bitrix\Main\Web\Json::encode($arResult['CATEGORIES']) ?>;

			if (!BX.type.isArray(categories))
				return false;

			for (i = 0; i < categories.length; ++i)
			{
				menuItems.push({
					text: BX.Text.encode(categories[i].name),
					category: categories[i],
					onclick: function(e, item)
					{
						e.preventDefault();
						var url = window.location.href.replace(/\/([0-9]+)\//, '/'+item.category.id+'/');
						window.location.href = url;
					}
				});
			}

			var menuId = 'crm-automation-config-' + Math.random();

			BX.bind(categorySelector, 'click', function(e)
			{
				BX.PopupMenu.show(
					menuId,
					categorySelector,
					menuItems,
					{
						autoHide: true,
						offsetLeft: (BX.pos(categorySelector)['width'] / 2),
						angle: { position: 'top', offset: 0 }
					}
				);
			});
		}
	})
</script>
