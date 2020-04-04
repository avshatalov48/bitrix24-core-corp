<? if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
global $APPLICATION;
/** @var array $arResult */

$APPLICATION->SetPageProperty("BodyClass", ($bodyClass ? $bodyClass." " : "") . "no-all-paddings no-background");
CJSCore::Init(array('popup'));

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
?>
<?if (!$arResult['HIDE_HELP']):?>
<div class="crm-config-automation-desc-container" data-role="help-container">
	<div class="crm-config-automation-desc-head">
		<div class="crm-config-automation-desc-title">
			<span class="crm-config-automation-desc-title-item"><?=htmlspecialcharsbx(GetMessage('CRM_CONFIG_AUTOMATION_HELP_TITLE'))?></span>
		</div>
		<div class="crm-config-automation-desc-close-icon" data-role="close-btn">
			<span class="crm-config-automation-desc-close-icon-item"></span>
		</div>
	</div><!--crm-config-automation-desc-head-->
	<div class="crm-config-automation-desc-main">
		<div class="crm-config-automation-desc-visual-block">
			<span class="crm-config-automation-desc-visual-block-item"></span>
		</div>
		<div class="crm-config-automation-desc-list-block">
			<ul class="crm-config-automation-desc-list">
				<li class="crm-config-automation-desc-list-item"><?=htmlspecialcharsbx(GetMessage('CRM_CONFIG_AUTOMATION_HELP_1_1'))?></li>
				<li class="crm-config-automation-desc-list-item"><?=htmlspecialcharsbx(GetMessage('CRM_CONFIG_AUTOMATION_HELP_1_2'))?></li>
				<li class="crm-config-automation-desc-list-item"><?=htmlspecialcharsbx(GetMessage('CRM_CONFIG_AUTOMATION_HELP_1_3'))?></li>
			</ul>
			<div class="crm-config-automation-desc-subtitle">
				<span class="crm-config-automation-desc-subtitle-item"><?=htmlspecialcharsbx(GetMessage('CRM_CONFIG_AUTOMATION_HELP_TITLE_2'))?></span>
			</div>
			<ul class="crm-config-automation-desc-list">
				<li class="crm-config-automation-desc-list-item"><?=htmlspecialcharsbx(GetMessage('CRM_CONFIG_AUTOMATION_HELP_2_1'))?></li>
				<li class="crm-config-automation-desc-list-item"><?=htmlspecialcharsbx(GetMessage('CRM_CONFIG_AUTOMATION_HELP_2_2'))?></li>
				<li class="crm-config-automation-desc-list-item"><?=htmlspecialcharsbx(GetMessage('CRM_CONFIG_AUTOMATION_HELP_2_3'))?></li>
			</ul>
		</div>
	</div><!--crm-config-automation-desc-main-->
</div><!--crm-config-automation-desc-container-->
<?endif;?>
<?if ($arResult['CATEGORIES'] && count($arResult['CATEGORIES']) > 1):?>
	<div class="crm-config-automation-button-container">
		<div class="ui-btn ui-btn-dropdown ui-btn-light-border" data-role="category-selector" data-categories="<?=htmlspecialcharsbx(\Bitrix\Main\Web\Json::encode($arResult['CATEGORIES']))?>">
			<?=htmlspecialcharsbx($arResult['CATEGORY_NAME'])?>
		</div>
	</div><!--pagetitle-container-->
<?endif?>
<?$APPLICATION->IncludeComponent(
	'bitrix:crm.automation',
	'',
	array(
		'ENTITY_TYPE_ID'     => $arResult['ENTITY_TYPE_ID'],
		'ENTITY_CATEGORY_ID' => $arResult['ENTITY_CATEGORY'],
		'back_url'           => '/crm/configs/automation/'.$arResult['ENTITY_TYPE_NAME'].'/'.$arResult['ENTITY_CATEGORY'].'/',
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
			var menuItems = [], i, categories = BX.parseJSON(categorySelector.getAttribute('data-categories'));

			if (!BX.type.isArray(categories))
				return false;

			for (i = 0; i < categories.length; ++i)
			{
				menuItems.push({
					text: categories[i].name,
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

		var helpContainer = document.querySelector('[data-role="help-container"]');

		if (helpContainer)
		{
			var closeButton = document.querySelector('[data-role="close-btn"]');
			if (closeButton)
			{
				BX.bind(closeButton, 'click', function()
				{
					BX.ajax(
						{
							url: "/bitrix/components/bitrix/crm.config.automation/ajax.php?<?=bitrix_sessid_get()?>",
							method: "POST",
							dataType: "json",
							data:
								{ "ACTION" : "HIDE_HELP"}
						}
					);
					BX.remove(helpContainer);
				});
			}
		}
	})
</script>
