<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var CCrmEntityPopupComponent $component */

CJSCore::Init(array('clipboard'));
\Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/main/utils.js');
\Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/interface_form.js');
\Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/entity_event.js');

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
$tabMenuContainerId = "{$guid}_tabs_menu";
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

	$tabContainerClassName = 'crm-entity-section crm-entity-section-tabs';
	if($entityID <= 0)
	{
		$tabContainerClassName .= ' crm-entity-stream-section-planned-above-overlay';
	}

	?><div class="<?=$tabContainerClassName?>">
		<ul id="<?=htmlspecialcharsbx($tabMenuContainerId)?>" class="crm-entity-section-tabs-container"><?
		foreach($tabs as $tab)
		{
			$classNames = array('crm-entity-section-tab');
			if(isset($tab['active']) && $tab['active'])
			{
				$classNames[] = 'crm-entity-section-tab-current';
			}
			elseif(isset($tab['enabled']) && !$tab['enabled'])
			{
				$classNames[] = 'crm-entity-section-tab-disabled';
			}
			?><li data-tab-id="<?=htmlspecialcharsbx($tab['id'])?>" class="<?=implode(' ', $classNames)?>">
				<a class="crm-entity-section-tab-link" href="#"><?=htmlspecialcharsbx($tab['name'])?></a>
			</li><?
		}

		if($arResult['REST_USE'])
		{
			?><li class="crm-entity-section-tab">
				<a href="#" class="crm-entity-section-tab-link" onclick="BX.rest.Marketplace.open(<?=\CUtil::PhpToJSObject($arResult['REST_PLACEMENT_CONFIG'])?>);" class="crm-entity-section-tab-link"><?=\Bitrix\Main\Localization\Loc::getMessage('CRM_ENT_DETAIL_REST_BUTTON')?></a>
			</li><?
		}
		?></ul><?
	?></div><?
	?><div id="<?=htmlspecialcharsbx($tabContainerId)?>" style="position: relative;"><?
	foreach($tabs as $tab)
	{
		$tabID = $tab['id'];
		$className = "crm-entity-section crm-entity-section-info";
		$styleString = '';
		if ($tab['active'] !== true)
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
			<div class="crm-entity-card-container"><?
				$APPLICATION->IncludeComponent(
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
							'DETAIL_MANAGER_ID' => $guid
						)
					)
				);
			?></div>
			<div class="crm-entity-stream-container"><?
				$APPLICATION->IncludeComponent(
					"bitrix:crm.timeline",
					'',
					array_merge(
						array(
							'ENTITY_TYPE_ID' => $entityTypeID,
							'ENTITY_ID' => $entityID,
							'ENTITY_INFO' => $entityInfo,
							'ACTIVITY_EDITOR_ID' => $arResult['ACTIVITY_EDITOR_ID'],
							'READ_ONLY' => $readOnly
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
$copyPageUrlMessage = GetMessage("CRM_ENT_DETAIL_COPY_{$entityTypeName}_URL");
/*
* CRM_ENT_DETAIL_LEAD_URL_COPIED
* CRM_ENT_DETAIL_DEAL_URL_COPIED
* CRM_ENT_DETAIL_CONTACT_URL_COPIED
* CRM_ENT_DETAIL_COMPANY_URL_COPIED
* CRM_ENT_DETAIL_QUOTE_URL_COPIED
*/
$pageUrlCopiedMessage = GetMessage("CRM_ENT_DETAIL_{$entityTypeName}_URL_COPIED");

/*
 * CRM_ENT_DETAIL_DEAL_DELETE_DIALOG_TITLE
 * CRM_ENT_DETAIL_LEAD_DELETE_DIALOG_TITLE
 * CRM_ENT_DETAIL_CONTACT_DELETE_DIALOG_TITLE
 * CRM_ENT_DETAIL_COMPANY_DELETE_DIALOG_TITLE
 * CRM_ENT_DETAIL_QUOTE_DELETE_DIALOG_TITLE
 */
$deletionDialogTitle = GetMessage("CRM_ENT_DETAIL_{$entityTypeName}_DELETE_DIALOG_TITLE");
/*
 * CRM_ENT_DETAIL_DEAL_DELETE_DIALOG_MESSAGE
 * CRM_ENT_DETAIL_LEAD_DELETE_DIALOG_MESSAGE
 * CRM_ENT_DETAIL_CONTACT_DELETE_DIALOG_MESSAGE
 * CRM_ENT_DETAIL_COMPANY_DELETE_DIALOG_MESSAGE
 * CRM_ENT_DETAIL_QUOTE_DELETE_DIALOG_MESSAGE
 */
$deletionConfirmDialogContent = GetMessage("CRM_ENT_DETAIL_{$entityTypeName}_DELETE_DIALOG_MESSAGE");

/*
 * CRM_ENT_DETAIL_LEAD_DELETE_DIALOG_TITLE
 */
$exclusionDialogTitle = GetMessage("CRM_ENT_DETAIL_{$entityTypeName}_EXCLUDE_DIALOG_TITLE");

/*
 * CRM_ENT_DETAIL_LEAD_DELETE_DIALOG_MESSAGE
 */
$exclusionConfirmDialogContent = GetMessage("CRM_ENT_DETAIL_{$entityTypeName}_EXCLUDE_DIALOG_MESSAGE");
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
		}
	);
</script>
<?