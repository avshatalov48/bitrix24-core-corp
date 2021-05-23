<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

/**
 * @var array $arParams
 * @var array $arResult
 * @var \CBitrixComponentTemplate $this
 * @var \CBitrixComponent $component
 * @global CMain $APPLICATION
 * @global CUser $USER
 * @global CDatabase $DB
 */

global $APPLICATION;

if (!empty($arResult['BUTTONS']))
{
	$APPLICATION->IncludeComponent(
		'bitrix:crm.interface.toolbar',
		'flat',
		array(
			'BUTTONS' => $arResult['BUTTONS']
		),
		$component,
		array(
			'HIDE_ICONS' => 'Y'
		)
	);
}
if (is_array($arResult['EXPORT_CSV_PARAMS']))
{
	\Bitrix\Main\UI\Extension::load('ui.stepprocessing');
	?>
	<script type="text/javascript">
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