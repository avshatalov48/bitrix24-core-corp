<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Crm\Integration;

/**
 * @var array $arParams
 * @var array $arResult
 * @var \CBitrixComponentTemplate $this
 * @var \CBitrixComponent $component
 * @global \CMain $APPLICATION
 * @global \CUser $USER
 * @global \CDatabase $DB
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
			[
				'SECTION_CODE' => Integration\Intranet\BindingMenu\SectionCode::DETAIL,
				'MENU_CODE' => Integration\Intranet\BindingMenu\CodeBuilder::getMenuCode(\CCrmOwnerType::Invoice),
				'CONTEXT' => [
					'ENTITY_ID' => $arParams['ELEMENT_ID'],
				],
			]
		);

		?><script>
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
if (is_array($arResult['EXPORT_CSV_PARAMS']))
{
	\Bitrix\Main\UI\Extension::load('ui.stepprocessing');
	?>
	<script>
		BX.ready(
			function()
			{
				var initFieldAppend = function(actionData)
				{
					/**
					 * @var {FormData} actionData
					 * @var {BX.UI.StepProcessing.Process} this
					 */
					var initialOptions = this.getDialog().getOptionFieldValues();
					Object.keys(initialOptions).forEach(name => {
						if (!(initialOptions[name] instanceof File))
						{
							actionData.append('INITIAL_OPTIONS['+name+']', initialOptions[name]);
						}
					});
				};
				BX.UI.StepProcessing.ProcessManager
					.create(<?= \CUtil::PhpToJSObject($arResult['EXPORT_CSV_PARAMS']) ?>)
					.setHandler(BX.UI.StepProcessing.ProcessCallback.RequestStart, initFieldAppend)
				;
				BX.UI.StepProcessing.ProcessManager
					.create(<?= \CUtil::PhpToJSObject($arResult['EXPORT_EXCEL_PARAMS']) ?>)
					.setHandler(BX.UI.StepProcessing.ProcessCallback.RequestStart, initFieldAppend)
				;
			}
		);
	</script><?
}
