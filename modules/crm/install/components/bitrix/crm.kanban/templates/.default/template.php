<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * @var array $arParams
 * @var array $arResult
 * @global \CMain $APPLICATION
 * @var \CBitrixComponentTemplate $this
 */

if (isset($arResult['ERROR']))
{
	ShowError($arResult['ERROR']);

	return;
}

use Bitrix\Crm\Category\DealCategory;
use Bitrix\Crm\Conversion\EntityConverter;
use Bitrix\Crm\Conversion\LeadConversionScheme;
use Bitrix\Crm\Integration\NotificationsManager;
use Bitrix\Crm\Integration\PullManager;
use Bitrix\Crm\Kanban\Helper;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Settings\CounterSettings;
use Bitrix\Crm\Tour;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;

Loc::loadMessages(__FILE__);

$this->addExternalCss('/bitrix/themes/.default/crm-entity-show.css');

$bodyClass = $APPLICATION->getPageProperty("BodyClass");
$APPLICATION->setPageProperty("BodyClass",
	($bodyClass ? $bodyClass." " : "").
	"no-all-paddings grid-mode pagetitle-toolbar-field-view crm-toolbar no-background"
);

$data = $arResult['ITEMS'];
$date = new \Bitrix\Main\Type\Date;
$isBitrix24 = \Bitrix\Main\ModuleManager::isModuleInstalled('bitrix24');
// $demoAccess =
// 	\CJSCore::IsExtRegistered('intranet_notify_dialog')
// 	&& \Bitrix\Main\ModuleManager::isModuleInstalled('im')
// ;

// js extension reg
Extension::load(['ui.actionpanel', 'ui.notification']);

\CJSCore::registerExt('crm_common', array(
	'js' => array('/bitrix/js/crm/crm.js', '/bitrix/js/crm/common.js')
));
\CJSCore::registerExt('crm_activity_type', array(
	'js' => array('/bitrix/js/crm/activity.js')
));
\CJSCore::registerExt('crm_partial_entity_editor', array(
	'js' => array('/bitrix/js/crm/partial_entity_editor.js', '/bitrix/js/crm/dialog.js')
));
\CJSCore::registerExt('popup_menu', array(
	'js' => array('/bitrix/js/main/popup_menu.js')
));

\CJSCore::Init(array(
	'crm_common',
	'crm.kanban',
	'crm.kanban.sort',
	'crm_visit_tracker',
	'crm_activity_type',
	'crm_partial_entity_editor',
	'crm.entity-editor',
	'popup_menu',
	'currency',
	'core_money_editor',
	'intranet_notify_dialog',
	'marketplace',
	'sidepanel',
	'uf'
));

include 'editors.php';

$isMergeEnabled = isset($arParams['PATH_TO_MERGE']) && $arParams['PATH_TO_MERGE'] !== '';
if ($isMergeEnabled)
{
	Extension::load(['crm.merger.batchmergemanager']);
}

$gridId = Helper::getGridId($arParams['ENTITY_TYPE_CHR']);

$entityTypeId = (int) $arParams['ENTITY_TYPE_INT'];

if (CounterSettings::getInstance()->isEnabled())
{
	$showActivity = isset($arParams['SHOW_ACTIVITY']) && $arParams['SHOW_ACTIVITY'] === 'Y' ? 'true' : 'false';
}
else
{
	$showActivity = 'false';
}
?>

<div id="crm_kanban"></div>

<script type="text/javascript">
	var Kanban;
	var ajaxHandlerPath = "<?= $this->getComponent()->getPath()?>/ajax.old.php";

	BX.ready(
		function()
		{
			"use strict";

			BX.CRM.Kanban.Restriction.init({
				isUniversalActivityScenarioEnabled: <?= \Bitrix\Crm\Settings\Crm::isUniversalActivityScenarioEnabled() ? 'true' : 'false' ?>,
				isLastActivityEnabled: <?= ($arResult['IS_LAST_ACTIVITY_ENABLED'] ?? false) ? 'true' : 'false' ?>,
			});

			<?php if (isset($arResult['RESTRICTED_VALUE_CLICK_CALLBACK'])):?>
				BX.addCustomEvent(window, 'onCrmRestrictedValueClick', function() {
					<?=$arResult['RESTRICTED_VALUE_CLICK_CALLBACK'];?>
				});
			<?php endif;?>

			BX.Crm.PartialEditorDialog.messages =
			{
				entityHasInaccessibleFields: "<?= CUtil::JSEscape(Loc::getMessage('CRM_KANBAN_HAS_INACCESSIBLE_FIELDS')) ?>",
			};

			BX.Currency.setCurrencyFormat(
				"<?= $arParams['CURRENCY']?>",
				<?= \CUtil::PhpToJSObject(\CCurrencyLang::GetFormatDescription($arParams['CURRENCY']), false, true)?>
			);

			BX.Crm.PartialEditorDialog.entityEditorUrls =
			{
				<?= \CCrmOwnerType::DealName;?>: "<?= '/bitrix/components/bitrix/crm.deal.details/ajax.php?' . bitrix_sessid_get();?>",
				<?= \CCrmOwnerType::LeadName?>: "<?= '/bitrix/components/bitrix/crm.lead.details/ajax.php?' . bitrix_sessid_get();?>"
			};

			var schemeInline = BX.UI.EntityScheme.create(
				'kanban_scheme',
				{
					current: <?= \CUtil::phpToJSObject($arResult['ITEMS']['scheme_inline']);?>
				}
			);

			var userFieldManagerInline = BX.UI.EntityUserFieldManager.create(
				'kanban_ufmanager',
				{
					entityId: 0,
					enableCreation: false
				}
			);

			BX.Crm.EntityEditorUser.messages =
			{
				change: "<?= CUtil::JSEscape(Loc::getMessage('CRM_KANBAN_ED_CHANGE_USER'));?>"
			};

			BX.UI.EntityEditorBoolean.messages =
			{
				yes: "<?= CUtil::JSEscape(Loc::getMessage('MAIN_YES'));?>",
				no: "<?= CUtil::JSEscape(Loc::getMessage('MAIN_NO'));?>"
			};

			BX.Crm.EntityEditorSection.messages =
			{
				change: "<?= CUtil::JSEscape(Loc::getMessage('CRM_KANBAN_ED_CHANGE'));?>",
				cancel: "<?= CUtil::JSEscape(Loc::getMessage('CRM_KANBAN_ED_CANCEL'));?>"
			};

			BX.CRM.Kanban.Item.messages =
			{
				company: "<?= CUtil::JSEscape(Loc::getMessage('CRM_KANBAN_COMPANY')) ?>",
				contact: "<?= CUtil::JSEscape(Loc::getMessage('CRM_KANBAN_CONTACT')) ?>",
				noname: "<?= CUtil::JSEscape(Loc::getMessage('FORMATNAME_NONAME')) ?>"
			};

			Kanban = new BX.CRM.Kanban.Grid(
				{
					renderTo: BX("crm_kanban"),
					itemType: "BX.CRM.Kanban.Item",
					columnType: "BX.CRM.Kanban.Column",
					dropZoneType: "BX.CRM.Kanban.DropZone",
					columnsRevert: <?= $arResult['CONFIG_BY_VIEW_MODE']['columnsRevert'] ?>,
					canAddColumn: <?= $arResult['CONFIG_BY_VIEW_MODE']['canAddColumn'] ?>,
					canEditColumn: <?= $arResult['CONFIG_BY_VIEW_MODE']['canEditColumn'] ?>,
					canRemoveColumn: <?= $arResult['CONFIG_BY_VIEW_MODE']['canRemoveColumn'] ?>,
					canSortColumn: <?= $arResult['CONFIG_BY_VIEW_MODE']['canSortColumn'] ?>,
					canSortItem: true,
					canChangeItemStage: <?= $arResult['CONFIG_BY_VIEW_MODE']['canChangeItemStage'] ?>,
					bgColor: <?= (SITE_TEMPLATE_ID === 'bitrix24' ? '"transparent"' : 'null')?>,
					columns: <?= \CUtil::PhpToJSObject(array_values($data['columns']), false, false, true)?>,
					items: <?= \CUtil::PhpToJSObject($data['items'], false, false, true)?>,
					dropZones: <?= \CUtil::PhpToJSObject(array_values($data['dropzones']), false, false, true)?>,
					emptyStubItems: <?= \CUtil::PhpToJSObject($arResult['STUB'] ?? null)?>,
					data:
						{
							schemeInline: schemeInline,
							userFieldManagerInline: userFieldManagerInline,
							contactCenterShow: <?= $arParams['HIDE_CC'] ? 'false' : 'true';?>,
							restDemoBlockShow: <?= $arParams['HIDE_REST'] ? 'false' : 'true';?>,
							reckonActivitylessItems: <?= \CCrmUserCounterSettings::getValue(\CCrmUserCounterSettings::ReckonActivitylessItems, true) ? 'true' : 'false';?>,
							ajaxHandlerPath: ajaxHandlerPath,
							entityType: "<?= \CUtil::JSEscape($arParams['ENTITY_TYPE_CHR'])?>",
							entityTypeInt: "<?= $entityTypeId ?>",
							typeInfo: <?= \CUtil::PhpToJSObject($arParams['ENTITY_TYPE_INFO'])?>,
							viewMode: "<?= \CUtil::JSEscape($arParams['VIEW_MODE'])?>",
							isDynamicEntity: <?= ($arParams['IS_DYNAMIC_ENTITY'] ? 'true' : 'false') ?>,
							entityPath: "<?= \CUtil::JSEscape($arParams['ENTITY_PATH'])?>",
							editorConfigId: "<?= \CUtil::JSEscape($arParams['EDITOR_CONFIG_ID'])?>",
							quickEditorPath: {
								lead: "/bitrix/components/bitrix/crm.lead.details/ajax.php?<?= bitrix_sessid_get();?>",
								deal: "/bitrix/components/bitrix/crm.deal.details/ajax.php?<?= bitrix_sessid_get();?>"
							},
							headersSections: <?= \CUtil::PhpToJSObject($arResult['HEADERS_SECTIONS'] ?? [])?>,
							defaultHeaderSectionId: "<?= \CUtil::JSEscape($arResult['DEFAULT_HEADER_SECTION_ID'] ?? '') ?>",
							params: <?= json_encode($arParams['EXTRA'])?>,
							gridId: "<?=\CUtil::JSEscape($gridId)?>",
							showActivity: <?= $showActivity ?>,
							currency: "<?= $arParams['CURRENCY']?>",
							lastId: <?= (int)$data['last_id']?>,
							rights:
								{
									canAddColumn: <?= $arResult['ACCESS_CONFIG_PERMS'] ? 'true' : 'false'?>,
									canEditColumn: <?= $arResult['ACCESS_CONFIG_PERMS'] ? 'true' : 'false'?>,
									canRemoveColumn: <?= $arResult['ACCESS_CONFIG_PERMS'] ? 'true' : 'false'?>,
									canSortColumn: <?= $arResult['ACCESS_CONFIG_PERMS'] ? 'true' : 'false'?>,
									canImport: <?= isset($arResult['ACCESS_IMPORT']) && $arResult['ACCESS_IMPORT'] ? 'true' : 'false'?>,
									canSortItem: true,
									canUseVisit: <?= \Bitrix\Crm\Activity\Provider\Visit::isAvailable() ? 'true' : 'false';?>
								},
							visitParams: <?= \CUtil::PhpToJSObject(\Bitrix\Crm\Activity\Provider\Visit::getPopupParameters(), false, false, true)?>,
							admins: <?= \CUtil::PhpToJSObject(array_values($arResult['ADMINS']))?>,
							userId: <?= $arParams['USER_ID'];?>,
							currentUser: <?=\Bitrix\Main\Web\Json::encode($arParams['LAYOUT_CURRENT_USER'])?>,
							customFields: <?= \CUtil::phpToJSObject(array_keys($arResult['MORE_FIELDS']));?>,
							customEditFields: <?= \CUtil::phpToJSObject(array_keys($arResult['MORE_EDIT_FIELDS']));?>,
							customSectionsFields: <?= \CUtil::phpToJSObject($arResult['FIELDS_SECTIONS']);?>,
							customDisabledFields: <?= \CUtil::phpToJSObject(array_fill_keys($arResult['FIELDS_DISABLED'], true));?>,
							userSelectorId: "kanban_multi_actions",
							linksPath: {
								marketplace: {
									url: "/marketplace/category/migration/?from=kanban"
								},
								importexcel: {
									url: "<?= \CUtil::jsEscape($arParams['PATH_TO_IMPORT']);?>"
								},
								dealCategory: {
									url: "<?= \CUtil::jsEscape($arParams['PATH_TO_DEAL_KANBANCATEGORY']);?>"
								},
								contact_center: {
									url: "<?= $isBitrix24 ? '/contact_center/?from=kanban' : '/services/contact_center/?from=kanban';?>"
								},
								rest_demo: {
									url: "<?= $arParams['REST_DEMO_URL'];?>",
									params: {
										width: 940
									}
								}
							},
							categories: <?= \CUtil::phpToJsObject(array_values($arResult['CATEGORIES']));?>,
							pullTag: "<?= \CUtil::JSEscape(PullManager::getInstance()->subscribeOnKanbanUpdate(
								$arParams['ENTITY_TYPE_CHR'],
								$arParams['EXTRA']
							)) ?>",
							eventKanbanUpdatedTag: "<?= PullManager::EVENT_KANBAN_UPDATED ?>",
							moduleId: "<?= \CUtil::JSEscape(PullManager::MODULE_ID) ?>",
							tariffRestrictions: {
								// We use negation so as not to confuse when working, since the default has always been allowed
								addItemNotPermittedByTariff: <?= !($arParams['EXTRA']['ADD_ITEM_PERMITTED_BY_TARIFF'] ?? true) ? 'true' : 'false' ?>,
							},
						}
				}
			);

			BX.addCustomEvent("Crm.Kanban.Grid:onSpecialItemDraw", BX.delegate(BX.Crm.KanbanComponent.onSpecialItemDraw, this));

			Kanban.draw();

			<?if ($arParams['ENTITY_TYPE_CHR'] == 'LEAD' || $arParams['ENTITY_TYPE_CHR'] == 'INVOICE'):?>
			BX.addCustomEvent("Crm.Kanban.Grid:onItemMovedFinal", BX.delegate(BX.Crm.KanbanComponent.columnPopup, this));
			<?endif;?>

			<?if ($arParams['ENTITY_TYPE_CHR'] == 'INVOICE'):?>
			BX.addCustomEvent("Crm.Kanban.Grid:onBeforeItemCapturedStart", BX.delegate(BX.Crm.KanbanComponent.dropPopup, this));
			<?endif;?>

			<?if ($arParams['ENTITY_TYPE_CHR'] == 'LEAD'):?>
			BX.addCustomEvent("onPopupClose", BX.proxy(BX.Crm.KanbanComponent.onPopupClose, this));
			<?endif;?>

			BX.message(
				{
					CRM_KANBAN_POPUP_PARAMS_SAVE: "<?= CUtil::JSEscape(Loc::getMessage('CRM_KANBAN_POPUP_PARAMS_SAVE'));?>",
					CRM_KANBAN_POPUP_PARAMS_CANCEL: "<?= CUtil::JSEscape(Loc::getMessage('CRM_KANBAN_POPUP_PARAMS_CANCEL'));?>",
					CRM_KANBAN_DELETE_SUCCESS_MULTIPLE: "<?= GetMessageJS('CRM_KANBAN_DELETE_SUCCESS_MULTIPLE') ?>",
					CRM_KANBAN_DELETE_SUCCESS: "<?= GetMessageJS('CRM_KANBAN_DELETE_SUCCESS') ?>",
					CRM_KANBAN_DELETE_CANCEL: "<?= GetMessageJS('CRM_KANBAN_DELETE_CANCEL') ?>",
					CRM_KANBAN_DELETE_RESTORE_SUCCESS: "<?= GetMessageJS('CRM_KANBAN_DELETE_RESTORE_SUCCESS') ?>",
					CRM_TYPE_ITEM_PARTIAL_EDITOR_TITLE: "<?= GetMessageJS('CRM_TYPE_ITEM_PARTIAL_EDITOR_TITLE')?>"
				}
			);

			new BX.Crm.Kanban.PullManager(Kanban);

			const sortSettings = BX.CRM.Kanban.Sort.Settings.createFromJson(
				'<?= \Bitrix\Main\Web\Json::encode($arResult['SORT_SETTINGS']) ?>',
			);
			BX.CRM.Kanban.Sort.SettingsController.init(Kanban, sortSettings);

			<?if ($isMergeEnabled):?>
				BX.Crm.BatchMergeManager.create(
					"<?=\CUtil::JSEscape($gridId)?>",
					{
						kanban: Kanban,
						entityTypeId: "<?= $entityTypeId ?>",
						mergerUrl: "<?=\CUtil::JSEscape($arParams['PATH_TO_MERGE'])?>"
					}
				);
			<?endif;?>

			<?php
				if (\Bitrix\Crm\Settings\Crm::isUniversalActivityScenarioEnabled()):
					$todoCreateNotification = (new \Bitrix\Crm\Activity\TodoCreateNotification($entityTypeId));
					$todoCreateNotificationSkipPeriod = $todoCreateNotification->getCurrentSkipPeriod();
					$factory = Container::getInstance()->getFactory($entityTypeId);
					$smartActivityNotificationSupported = $factory && $factory->isSmartActivityNotificationSupported();
			?>
				BX.Runtime.loadExtension('crm.push-crm-settings').then((exports) => {
					const PushCrmSettings = exports.PushCrmSettings;

					/** @see BX.Crm.PushCrmSettings */
					new PushCrmSettings({
						smartActivityNotificationSupported: <?= $smartActivityNotificationSupported ? 'true' : 'false' ?>,
						entityTypeId: <?= $entityTypeId ?>,
						rootMenu: Kanban.getSettingsButtonMenu(),
						targetItemId: 'crm_kanban_cc_delimiter',
						controller: BX.CRM.Kanban.Sort.SettingsController.Instance,
						restriction: BX.CRM.Kanban.Restriction.Instance,
						<?php if (is_string($todoCreateNotificationSkipPeriod)): ?>
						todoCreateNotificationSkipPeriod: '<?= \CUtil::JSEscape($todoCreateNotificationSkipPeriod) ?>',
						<?php endif; ?>
					});
				});
			<?php endif; ?>
		}
	);
</script>

<?include $_SERVER['DOCUMENT_ROOT'] . $this->getFolder() . '/popups.php'?>

<?if (isset($arParams['ENTITY_TYPE_CHR']) && $arParams['ENTITY_TYPE_CHR'] === 'LEAD'):
	print (Tour\NumberOfClients::getInstance())->build();

	Loc::loadMessages($_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/crm.lead.list/templates/.default/template.php');
	?>
	<script type="text/javascript">
		BX.ready(
			function()
			{
				BX.CrmEntityType.captions =
				{
					<?= CCrmOwnerType::LeadName?>: "<?= CCrmOwnerType::GetDescription(CCrmOwnerType::Lead)?>",
					<?= CCrmOwnerType::ContactName?>: "<?= CCrmOwnerType::GetDescription(CCrmOwnerType::Contact)?>",
					<?= CCrmOwnerType::CompanyName?>: "<?= CCrmOwnerType::GetDescription(CCrmOwnerType::Company)?>",
					<?= CCrmOwnerType::DealName?>: "<?= CCrmOwnerType::GetDescription(CCrmOwnerType::Deal)?>",
					<?= CCrmOwnerType::InvoiceName?>: "<?= CCrmOwnerType::GetDescription(CCrmOwnerType::Invoice)?>",
					<?= CCrmOwnerType::OrderName?>: "<?= CCrmOwnerType::GetDescription(CCrmOwnerType::Order)?>",
					<?= CCrmOwnerType::QuoteName?>: "<?= CCrmOwnerType::GetDescription(CCrmOwnerType::Quote)?>"
				};
				BX.CrmLeadConversionScheme.messages =
					<?= \CUtil::PhpToJSObject(LeadConversionScheme::getJavaScriptDescriptions(false))?>;
				BX.CrmLeadConverter.messages =
				{
					accessDenied: "<?= GetMessageJS('CRM_LEAD_CONV_ACCESS_DENIED')?>",
					generalError: "<?= GetMessageJS('CRM_LEAD_CONV_GENERAL_ERROR')?>",
					dialogTitle: "<?= GetMessageJS('CRM_LEAD_CONV_DIALOG_TITLE')?>",
					syncEditorLegend: "<?= GetMessageJS('CRM_LEAD_CONV_DIALOG_SYNC_LEGEND')?>",
					syncEditorFieldListTitle: "<?= GetMessageJS('CRM_LEAD_CONV_DIALOG_SYNC_FILED_LIST_TITLE')?>",
					syncEditorEntityListTitle: "<?= GetMessageJS('CRM_LEAD_CONV_DIALOG_SYNC_ENTITY_LIST_TITLE')?>",
					continueButton: "<?= GetMessageJS('CRM_LEAD_CONV_DIALOG_CONTINUE_BTN')?>",
					cancelButton: "<?= GetMessageJS('CRM_LEAD_CONV_DIALOG_CANCEL_BTN')?>",
					selectButton: "<?= GetMessageJS('CRM_LEAD_CONV_ENTITY_SEL_BTN')?>",
					openEntitySelector: "<?= GetMessageJS('CRM_LEAD_CONV_OPEN_ENTITY_SEL')?>",
					entitySelectorTitle: "<?= GetMessageJS('CRM_LEAD_CONV_ENTITY_SEL_TITLE')?>",
					contact: "<?= GetMessageJS('CRM_LEAD_CONV_ENTITY_SEL_CONTACT')?>",
					company: "<?= GetMessageJS('CRM_LEAD_CONV_ENTITY_SEL_COMPANY')?>",
					noresult: "<?= GetMessageJS('CRM_LEAD_CONV_ENTITY_SEL_SEARCH_NO_RESULT')?>",
					search : "<?= GetMessageJS('CRM_LEAD_CONV_ENTITY_SEL_SEARCH')?>",
					last : "<?= GetMessageJS('CRM_LEAD_CONV_ENTITY_SEL_LAST')?>"
				};
				BX.CrmLeadConverter.permissions =
				{
					contact: <?= CUtil::PhpToJSObject($arResult['CAN_CONVERT_TO_CONTACT'])?>,
					company: <?= CUtil::PhpToJSObject($arResult['CAN_CONVERT_TO_COMPANY'])?>,
					deal: <?= CUtil::PhpToJSObject($arResult['CAN_CONVERT_TO_DEAL'])?>
				};
				BX.CrmLeadConverter.settings =
				{
					serviceUrl: "<?='/bitrix/components/bitrix/crm.lead.show/ajax.php?action=convert&'.bitrix_sessid_get()?>",
					enablePageRefresh: false,
					enableRedirectToShowPage: false,
					config: <?= CUtil::PhpToJSObject($arResult['CONVERSION_CONFIG']->toJavaScript())?>
				};
				BX.CrmDealCategory.infos = <?= \CUtil::PhpToJSObject(
					DealCategory::getJavaScriptInfos(EntityConverter::getPermittedDealCategoryIDs())
				)?>;
				BX.CrmDealCategorySelectDialog.messages =
				{
					title: "<?=GetMessageJS('CRM_LEAD_LIST_CONV_DEAL_CATEGORY_DLG_TITLE')?>",
					field: "<?=GetMessageJS('CRM_LEAD_LIST_CONV_DEAL_CATEGORY_DLG_FIELD')?>",
					saveButton: "<?=GetMessageJS('CRM_LEAD_LIST_BUTTON_SAVE')?>",
					cancelButton: "<?=GetMessageJS('CRM_LEAD_LIST_BUTTON_CANCEL')?>"
				};
			}
		);
	</script>
<?elseif (isset($arParams['ENTITY_TYPE_CHR']) && $arParams['ENTITY_TYPE_CHR'] === 'DEAL'):
	print (Tour\NumberOfClients::getInstance())->build();

	NotificationsManager::showSignUpFormOnCrmShopCreated();
	print (Tour\SortByLastActivityTime::getInstance())->build();
endif;

if (!empty($arResult['RESTRICTED_FIELDS_ENGINE']))
{
	Extension::load(['crm.restriction.filter-fields']);

	echo $arResult['RESTRICTED_FIELDS_ENGINE'];
}
