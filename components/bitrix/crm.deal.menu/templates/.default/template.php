<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

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

\Bitrix\Main\UI\Extension::load("ui.alerts");
\Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/category.js');

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

	$toolbarParams = ($arParams['TOOLBAR_PARAMS'] ?? []);
	$toolbarParams['CATEGORY_ID'] = isset($arResult['CATEGORY_ID']) ? $arResult['CATEGORY_ID'] : null;
	$APPLICATION->IncludeComponent(
		'bitrix:crm.interface.toolbar',
		$template,
		[
			'TOOLBAR_ID' => $arResult['TOOLBAR_ID'],
			'BUTTONS' => $arResult['BUTTONS'],
			'TOOLBAR_PARAMS' => $toolbarParams,
		],
		$component,
		[
			'HIDE_ICONS' => 'Y',
		]
	);
}

if(isset($arResult['SONET_SUBSCRIBE']) && is_array($arResult['SONET_SUBSCRIBE'])):
	$subscribe = $arResult['SONET_SUBSCRIBE'];
	?><script>
BX.ready(
	function()
	{
		BX.CrmSonetSubscription.create(
			"<?=CUtil::JSEscape($subscribe['ID'])?>",
			{
				"entityType": "<?=CCrmOwnerType::DealName?>",
				"serviceUrl": "<?=CUtil::JSEscape($subscribe['SERVICE_URL'])?>",
				"actionName": "<?=CUtil::JSEscape($subscribe['ACTION_NAME'])?>"
			}
		);
	}
);
</script><?
endif;

if(isset($arResult['CATEGORY_CHANGER'])):
	$categoryChanger = $arResult['CATEGORY_CHANGER'];
	?><script>
	BX.ready(
		function()
		{
			BX.CrmDealCategory.infos = <?=CUtil::PhpToJSObject(
				\Bitrix\Crm\Category\DealCategory::getJavaScriptInfos()
			)?>;

			BX.CrmDealCategorySelectDialog.messages =
				{
					title: "<?=GetMessageJS('CRM_DEAL_CATEGORY_SELECT_DLG_TITLE')?>",
					field: "<?=GetMessageJS('CRM_DEAL_CATEGORY_SELECT_DLG_FIELD')?>",
					saveButton: "<?=GetMessageJS('CRM_BUTTON_SAVE')?>",
					cancelButton: "<?=GetMessageJS('CRM_BUTTON_CANCEL')?>"
				};

			BX.Crm.DealCategoryChanger.create(
				"<?=CUtil::JSEscape($categoryChanger['ID'])?>",
				{
					entityId: <?=$categoryChanger['ENTITY_ID']?>,
					categoryIds: <?=CUtil::PhpToJSObject($categoryChanger['CATEGORY_IDS'])?>,
					serviceUrl: "<?=CUtil::JSEscape($categoryChanger['SERVICE_URL'])?>",
					action: "<?=CUtil::JSEscape($categoryChanger['ACTION_NAME'])?>"
				}
			);

			BX.Crm.DealCategoryChanger.messages =
				{
					dialogTitle: "<?=GetMessageJS('CRM_DEAL_MOVE_TO_CATEGORY_DLG_TITLE')?>",
					dialogSummary: "<?=GetMessageJS('CRM_DEAL_MOVE_TO_CATEGORY_DLG_SUMMARY')?>"
				};
		}
	);
</script><?
endif;
$exportCsvParams = $arResult['EXPORT_CSV_PARAMS'] ?? null;
if (is_array($exportCsvParams))
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
