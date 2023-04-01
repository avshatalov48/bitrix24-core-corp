<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var CCrmEntityPopupComponent $component */

\Bitrix\Main\UI\Extension::load([
	'ui.design-tokens',
	'ui.fonts.opensans',
	'clipboard',
	'ui.alerts',
]);

if ($arResult['TODO_CREATE_NOTIFICATION_PARAMS'])
{
	Bitrix\Main\UI\Extension::load([
		'crm.activity.todo-create-notification',
	]);
}

\Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/main/utils.js');
\Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/interface_form.js');
\Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/entity_event.js');

$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
$APPLICATION->SetPageProperty('BodyClass', ($bodyClass ? $bodyClass.' ' : '').'no-paddings no-background');

$guid = $arResult['GUID'];
$entityTypeID = $arResult['ENTITY_TYPE_ID'];
$entityTypeName = CCrmOwnerType::ResolveName($entityTypeID);
$entityID = $arResult['ENTITY_ID'];
$extras = $arResult['EXTRAS'];
$entityInfo = $arResult['ENTITY_INFO'];
$tabs = $arResult['TABS'];
$readOnly = $arResult['READ_ONLY'];
$activeTabList = array_column($tabs, 'active');
array_unshift(
	$tabs,
	array('id'=> 'main', 'name' => GetMessage("CRM_ENT_DETAIL_MAIN_TAB"), 'active' => !in_array(true, $activeTabList, true))
);

$containerId = "{$guid}_container";
$tabContainerId = "{$guid}_tabs";
?><div id="<?=htmlspecialcharsbx($containerId)?>" class="crm-entity-wrap"><?

	if($arResult['ENABLE_PROGRESS_BAR'])
	{
		$APPLICATION->IncludeComponent(
			"bitrix:crm.entity.progressbar",
			'',
			array_merge(
				$arResult['PROGRESS_BAR'],
				array(
					'ENTITY_TYPE_ID' => $entityTypeID,
					'ENTITY_ID' => $entityID,
					'EXTRAS' => $extras,
					'CAN_CONVERT' => $arResult['CAN_CONVERT'],
					'CONVERSION_TYPE_ID' => $arResult['CONVERSION_TYPE_ID'],
					'CONVERSION_SCHEME' => $arResult['CONVERSION_SCHEME'],
					'READ_ONLY' => !$arResult['ENABLE_PROGRESS_CHANGE']
				)
			),
			$component,
			array('HIDE_ICONS' => 'Y')
		);
	}
	elseif ($arResult['ENABLE_STAGEFLOW'])
    {
		?><div class="crm-stageflow-wrap crm-entity-section-status-wrap" data-role="stageflow-wrap"></div><?php
    }

//region Tabs
	//region mainTab info
	ob_start();
	$entityEditorInfo = $APPLICATION->IncludeComponent(
		'bitrix:crm.entity.editor',
		'',
		array_merge(
			$arResult['EDITOR'],
			array(
				'ENTITY_TYPE_ID' => $entityTypeID,
				'ENTITY_ID' => $entityID,
				'EXTRAS' => $extras,
				'READ_ONLY' => $readOnly,
				'INITIAL_MODE' => $arResult['INITIAL_MODE'],
				'DETAIL_MANAGER_ID' => $guid,
				'MODULE_ID' => 'crm',
				'MESSAGES' => $arResult['MESSAGES'],
			)
		)
	);
	$mainTabHTML = ob_get_clean();
	//endregion
	$menuTabs = array_map(function ($tab) use ($guid) {
		$locked = false;
		if (isset($tab['tariffLock']) && is_string($tab['tariffLock']) && $tab['tariffLock'] !== '')
		{
			$onClickValue = $tab['tariffLock'];
			$locked = true;
		}
		else
		{
			$onClickValue = "BX.onCustomEvent('".htmlspecialcharsbx(CUtil::JSEscape($guid."_click_".$tab['id']))."');";
		}
		$result = [
			'TEXT' => $tab['name'],
			'ID' => $tab['id'],
			'ON_CLICK' => $onClickValue,
			'IS_ACTIVE' => isset($tab['active']) && $tab['active'],
			'IS_PASSIVE' => isset($tab['enabled']) && !$tab['enabled'],
			'IS_LOCKED' => $locked,
		];

		if (!empty($tab['url']))
		{
			$result['URL'] = (string)$tab['url'];
			unset($result['ON_CLICK']);
		}

		return $result;
	}, $tabs);

	if($arResult['REST_USE'])
	{
		$menuTabs[] = [
			'ID' => 'crm_rest_marketplace',
			'TEXT' => \Bitrix\Main\Localization\Loc::getMessage('CRM_ENT_DETAIL_REST_BUTTON_2'),
			'ON_CLICK' => 'BX.rest.Marketplace.open(' . \CUtil::PhpToJSObject($arResult['REST_PLACEMENT_CONFIG']) . ')',
		];
	}
	$tabContainerClassName = 'crm-entity-section crm-entity-section-tabs';
	if($entityID <= 0)
	{
		$tabContainerClassName .= ' crm-entity-stream-section-planned-above-overlay';
	}?>
	<div class="<?=$tabContainerClassName?>" data-role="crm-item-detail-container">
		<?php
		$mode = false;
		$tabMenuContainerId = mb_strtolower(implode('_', [
			'crm_scope_detail',
			Bitrix\Crm\Entity\EntityEditorConfigScope::PERSONAL,
			$entityTypeName,
			0,
		]));
		// in case ui module is of the required version
		if (is_array($entityEditorInfo))
		{
			$tabMenuContainerIdParts = [
				'crm_scope_detail',
				$entityEditorInfo['ENTITY_CONFIG_SCOPE'],
				$entityTypeName,
			];

			if (
				isset($arResult['EXTRAS']['CATEGORY_ID'])
				&& $arResult['EXTRAS']['CATEGORY_ID'] > 0
			)
			{
				$tabMenuContainerIdParts[] =  $arResult['EXTRAS']['CATEGORY_ID'];
			}

			$tabMenuContainerIdParts[] = $entityEditorInfo['USER_SCOPE_ID'];

			$tabMenuContainerId = mb_strtolower(implode('_', $tabMenuContainerIdParts));
			if ($entityEditorInfo['ENTITY_CONFIG_SCOPE'] === Bitrix\Crm\Entity\EntityEditorConfigScope::PERSONAL)
			{
				$mode = true;
			}
			else if ($entityEditorInfo['CAN_UPDATE_COMMON_CONFIGURATION'] === true)
			{
				$mode = 'common';
			}
		}
		$results = $APPLICATION->IncludeComponent(
			"bitrix:main.interface.buttons",
			"",
			[
				"ID" => $tabMenuContainerId,
				"ITEMS" => $menuTabs,
				"DISABLE_SETTINGS" => true,
				"EDIT_MODE" => $mode,
				"THEME" => 'flat-adaptive',
			]
		);
		?>
	</div>
<?php
//endregion
	?><div id="<?=htmlspecialcharsbx($tabContainerId)?>" style="position: relative;"><?
	foreach($tabs as $tab)
	{
		$tabID = $tab['id'];
		$className = "crm-entity-section crm-entity-section-info";
		$styleString = '';
		if (($tab['active'] ?? null) !== true)
		{
			$className .= " crm-entity-section-tab-content-hide crm-entity-section-above-overlay";
			$styleString = 'style="display: none;"';
		}
		if($tabID !== 'main')
		{
			?><div data-tab-id="<?=htmlspecialcharsbx($tabID)?>" class="<?=$className?>" <?=$styleString?>><?
				if(isset($tab['html']))
				{
					echo $tab['html'];
				}
			?></div><?
			continue;
		}
		?><div data-tab-id="<?=htmlspecialcharsbx($tabID)?>" class="<?=$className?>" <?=$styleString?>>
			<div class="crm-entity-card-container"><?=$mainTabHTML?></div>
			<div class="crm-entity-stream-container"><?
				$APPLICATION->IncludeComponent(
					"bitrix:crm.timeline",
					'',
					array_merge(
						array(
							'ENTITY_TYPE_ID' => $entityTypeID,
							'ENTITY_ID' => $entityID,
							'ENTITY_INFO' => $entityInfo,
							'EXTRAS' => $arResult['EXTRAS'],
							'ACTIVITY_EDITOR_ID' => $arResult['ACTIVITY_EDITOR_ID'],
							'READ_ONLY' => $readOnly,
							'ENTITY_CONFIG_SCOPE' => $entityEditorInfo['ENTITY_CONFIG_SCOPE'],
							'USER_SCOPE_ID' => $entityEditorInfo['USER_SCOPE_ID'],
						),
						$arResult['TIMELINE']
					),
					$component,
					array('HIDE_ICONS' => 'Y')
				);

			?></div>
			<div style="clear: both;"></div>
			<?$APPLICATION->IncludeComponent('bitrix:crm.tracking.entity.details', '', []);?>
		</div><?
	}
	?></div><?

?></div><?

/*
* CRM_ENT_DETAIL_COPY_LEAD_URL
* CRM_ENT_DETAIL_COPY_DEAL_URL
* CRM_ENT_DETAIL_COPY_CONTACT_URL
* CRM_ENT_DETAIL_COPY_COMPANY_URL
* CRM_ENT_DETAIL_COPY_QUOTE_URL
*/
$copyPageUrlMessage = $arResult['MESSAGES']['COPY_PAGE_URL'] ?? GetMessage("CRM_ENT_DETAIL_COPY_{$entityTypeName}_URL");
/*
* CRM_ENT_DETAIL_LEAD_URL_COPIED
* CRM_ENT_DETAIL_DEAL_URL_COPIED
* CRM_ENT_DETAIL_CONTACT_URL_COPIED
* CRM_ENT_DETAIL_COMPANY_URL_COPIED
* CRM_ENT_DETAIL_QUOTE_URL_COPIED
*/
$pageUrlCopiedMessage = $arResult['MESSAGES']['PAGE_URL_COPIED'] ?? GetMessage("CRM_ENT_DETAIL_{$entityTypeName}_URL_COPIED");

/*
 * CRM_ENT_DETAIL_DEAL_DELETE_DIALOG_TITLE
 * CRM_ENT_DETAIL_LEAD_DELETE_DIALOG_TITLE
 * CRM_ENT_DETAIL_CONTACT_DELETE_DIALOG_TITLE
 * CRM_ENT_DETAIL_COMPANY_DELETE_DIALOG_TITLE
 * CRM_ENT_DETAIL_QUOTE_DELETE_DIALOG_TITLE
 */
$deletionDialogTitle = $arResult['MESSAGES']['DELETE_DIALOG_TITLE'] ?? GetMessage("CRM_ENT_DETAIL_{$entityTypeName}_DELETE_DIALOG_TITLE");
/*
 * CRM_ENT_DETAIL_DEAL_DELETE_DIALOG_MESSAGE
 * CRM_ENT_DETAIL_LEAD_DELETE_DIALOG_MESSAGE
 * CRM_ENT_DETAIL_CONTACT_DELETE_DIALOG_MESSAGE
 * CRM_ENT_DETAIL_COMPANY_DELETE_DIALOG_MESSAGE
 * CRM_ENT_DETAIL_QUOTE_DELETE_DIALOG_MESSAGE
 */
$deletionConfirmDialogContent = $arResult['MESSAGES']['DELETE_DIALOG_MESSAGE'] ?? GetMessage("CRM_ENT_DETAIL_{$entityTypeName}_DELETE_DIALOG_MESSAGE");

/*
 * CRM_ENT_DETAIL_LEAD_DELETE_DIALOG_TITLE
 */
$exclusionDialogTitle = $arResult['MESSAGES']['EXCLUDE_DIALOG_TITLE'] ?? GetMessage("CRM_ENT_DETAIL_{$entityTypeName}_EXCLUDE_DIALOG_TITLE");

/*
 * CRM_ENT_DETAIL_LEAD_DELETE_DIALOG_MESSAGE
 */
$exclusionConfirmDialogContent = $arResult['MESSAGES']['EXCLUDE_DIALOG_MESSAGE'] ?? GetMessage("CRM_ENT_DETAIL_{$entityTypeName}_EXCLUDE_DIALOG_MESSAGE");
$exclusionConfirmDialogContentHelp = GetMessage("CRM_ENT_DETAIL_EXCLUDE_DIALOG_MESSAGE_HELP");

?><script type="text/javascript">
	BX.ready(
		function()
		{
			BX.Crm.Page.initialize();

			BX.Crm.Page.context = '<?=\CUtil::jsEscape(mb_strtolower($entityTypeName)) ?>-<?=intval($entityID) ?>';

			BX.Crm.EntityDetailManager.messages =
			{
				copyPageUrl: "<?=CUtil::JSEscape($copyPageUrlMessage)?>",
				pageUrlCopied: "<?=CUtil::JSEscape($pageUrlCopiedMessage)?>",
				deletionDialogTitle: "<?=CUtil::JSEscape($deletionDialogTitle)?>",
				deletionConfirmDialogContent: "<?=CUtil::JSEscape($deletionConfirmDialogContent)?>",
				deletionWarning: "<?=CUtil::JSEscape(GetMessage("CRM_ENT_DETAIL_DELETION_WARNING"))?>",
				goToDetails: "<?=CUtil::JSEscape(GetMessage("CRM_ENT_DETAIL_DELETION_GO_TO_DETAILS"))?>",
				exclusionDialogTitle: "<?=CUtil::JSEscape($exclusionDialogTitle)?>",
				exclusionConfirmDialogContent: "<?=CUtil::JSEscape($exclusionConfirmDialogContent)?>",
				exclusionConfirmDialogContentHelp: "<?=CUtil::JSEscape($exclusionConfirmDialogContentHelp)?>"
			};

			BX.Crm.EntityDetailManager.entityListUrls = <?=CUtil::PhpToJSObject($arResult['ENTITY_LIST_URLS'])?>;
			BX.CrmEntityManager.entityCreateUrls = <?=CUtil::PhpToJSObject($arResult['ENTITY_CREATE_URLS'])?>;

			BX.Crm.EntityDetailFactory.create(
				"<?=CUtil::JSEscape($guid)?>",
				{
					entityTypeId: <?=$entityTypeID?>,
					entityId: <?=$entityID?>,
					tabs: <?=CUtil::PhpToJSObject($tabs)?>,
					containerId: "<?=CUtil::JSEscape($containerId)?>",
					tabContainerId: "<?=CUtil::JSEscape($tabContainerId)?>",
					tabMenuContainerId: "<?=CUtil::JSEscape($tabMenuContainerId)?>",
					serviceUrl: "<?=CUtil::JSEscape($arResult['SERVICE_URL'])?>",
					analyticParams: <?=CUtil::PhpToJSObject($arResult['ANALYTIC_PARAMS'])?>
				}
			);

			BX.CrmDealCategory.infos = <?=CUtil::PhpToJSObject(
				Bitrix\Crm\Category\DealCategory::getJavaScriptInfos($arResult['DEAL_CATEGORY_ACCESS']['CREATE'])
			)?>;
			BX.CrmDealCategorySelectDialog.messages =
				{
					title: "<?=GetMessageJS('CRM_ENT_DETAIL_DEAL_CATEGORY_DLG_TITLE')?>",
					field: "<?=GetMessageJS('CRM_ENT_DETAIL_DEAL_CATEGORY_DLG_FIELD')?>",
					saveButton: "<?=GetMessageJS('CRM_ENT_DETAIL_BUTTON_SAVE')?>",
					cancelButton: "<?=GetMessageJS('CRM_ENT_DETAIL_BUTTON_CANCEL')?>"
				};

			<? if ($arResult['TODO_CREATE_NOTIFICATION_PARAMS']) { ?>
				new BX.Crm.Activity.TodoCreateNotification(<?=CUtil::PhpToJSObject($arResult['TODO_CREATE_NOTIFICATION_PARAMS'], false, true, true)?>);
			<? } ?>
		}
	);
</script>