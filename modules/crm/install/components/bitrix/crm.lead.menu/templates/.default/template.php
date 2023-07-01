<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

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

if (!empty($arResult['BUTTONS']))
{
	$type = $arParams['TYPE'];
	$template = 'type2';
	if ($type === 'list')
	{
		$template = SITE_TEMPLATE_ID === 'bitrix24' ? 'title' : '';
	}
	else if ($type === 'details')
	{
		$template = SITE_TEMPLATE_ID === 'bitrix24' ? 'slider' : 'type2';
	}

	$APPLICATION->IncludeComponent(
		'bitrix:crm.interface.toolbar',
		$template,
		[
			'TOOLBAR_ID' => $arResult['TOOLBAR_ID'],
			'BUTTONS' => $arResult['BUTTONS'],
			'TOOLBAR_PARAMS' => ($arParams['TOOLBAR_PARAMS'] ?? []),
		],
		$component,
		[
			'HIDE_ICONS' => 'Y',
		]
	);
}

if (isset($arResult['SONET_SUBSCRIBE']) && is_array($arResult['SONET_SUBSCRIBE'])):
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
if (isset($arResult['EXPORT_CSV_PARAMS']) && is_array($arResult['EXPORT_CSV_PARAMS']))
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

if (array_key_exists("IS_NEED_TO_CHECK", $arResult))
{
	?><?$APPLICATION->IncludeComponent(
			"bitrix:crm.config.checker",
			"invisible"
	);?><?
}
