<?php

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$APPLICATION->IncludeComponent(
	'bitrix:crm.widget.custom.saletarget',
	'',
	array(),
	$component,
	array('HIDE_ICONS' => 'Y')
);
?>
<div data-role="vc-widget-content-salestarget"></div>
<script>
	BX.ready(function()
	{
		var initData = <?=Bitrix\Main\Web\Json::encode($arResult['INIT_DATA'])?>;
		var curFormat = <?=Bitrix\Main\Web\Json::encode($arResult['CURRENCY_FORMAT'])?>;

		var contentNode = document.querySelector('[data-role="vc-widget-content-salestarget"]');

		if (contentNode)
		{
			var widget = {
				getPanel: function()
				{
					return {
						getCurrencyFormat: function()
						{
							return curFormat;
						}
					}
				},
				_contentWrapper: contentNode,
				_data: {items: initData},
				onAfterConfigSave: function() {
					BX.Crm.Widget.Custom.SaleTarget.createContentNode(widget, {isNew: true})
				}
			};

			var content = BX.Crm.Widget.Custom.SaleTarget.createContentNode(widget, initData[0]);

			contentNode.appendChild(content);

			BX.addCustomEvent(
				window,
				'BX.Crm.Report.Dashboard.Content.SalesTarget:onSettingsButtonClick',
				function()
				{
					BX.Crm.Widget.Custom.SaleTarget.openConfigDialog(widget);
				}
			);
		}
	});
</script>
