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
				"entityType": "<?=CCrmOwnerType::DealName?>",
				"serviceUrl": "<?=CUtil::JSEscape($subscribe['SERVICE_URL'])?>",
				"actionName": "<?=CUtil::JSEscape($subscribe['ACTION_NAME'])?>"
			}
		);
	}
);
</script><?
endif;

if(isset($arResult['CATEGORY_SELECTOR']) && is_array($arResult['CATEGORY_SELECTOR'])):
	$categorySelector = $arResult['CATEGORY_SELECTOR'];
?><script type="text/javascript">
	BX.ready(
		function()
		{
			BX.CrmDealCategory.infos = <?=CUtil::PhpToJSObject($categorySelector['INFOS'])?>;
			BX.CrmDealCategorySelector.messages =
			{
				"create": "<?=CUtil::JSEscape($categorySelector['MESSAGES']['CREATE'])?>"
			};

			var selector = BX.CrmDealCategorySelector.create(
				"<?=CUtil::JSEscape($categorySelector['ID'])?>",
				{
					"createUrl": "<?=CUtil::JSEscape($categorySelector['CREATE_URL'])?>",
					"categoryListUrl": "<?=CUtil::JSEscape($categorySelector['CATEGORY_LIST_URL'])?>",
					"categoryCreateUrl": "<?=CUtil::JSEscape($categorySelector['CATEGORY_CREATE_URL'])?>",
					"canCreateCategory": <?=$categorySelector['CAN_CREATE_CATEGORY'] ? 'true' : 'false'?>
				}
			);

			<?if ($arResult['RC']['CAN_USE']):?>
				selector.getSelectorMenu().getItems().push(BX.CmrSelectorMenuItem.create({'delimiter': true}));
				selector.getSelectorMenu().getItems().push(
					BX.CmrSelectorMenuItem.create({
						'text': '<?=CUtil::JSEscape($arResult['RC']['NAME'])?>',
						'className': '<?=($arResult['RC']['IS_AVAILABLE'] ? '' : 'b24-tariff-lock')?>',
						'events': {
							'select': function (){
								<?if ($arResult['RC']['IS_AVAILABLE']):?>
									BX.SidePanel.Instance.open("<?=CUtil::JSEscape($arResult['RC']['PATH_TO_ADD'])?>");
								<?else:?>
									<?=$arResult['RC']['JS_AVAILABLE_POPUP_SHOWER']?>
								<?endif;?>
								selector.getSelectorMenu().close();
							}
						}
					})
				);
			<?endif;?>

		}
	);
</script><?
endif;

if(isset($arResult['CATEGORY_CHANGER'])):
	$categoryChanger = $arResult['CATEGORY_CHANGER'];
?><script type="text/javascript">
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
