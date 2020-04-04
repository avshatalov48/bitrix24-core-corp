<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

if (isset($arResult['ERROR']))
{
	ShowError($arResult['ERROR']);
	return;
}

use \Bitrix\Main\Localization\Loc;
use \Bitrix\Crm\Kanban\Helper;
use \Bitrix\Crm\Conversion\LeadConversionScheme;
use \Bitrix\Crm\Category\DealCategory;
use \Bitrix\Crm\Conversion\EntityConverter;

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
$demoAccess = \CJSCore::IsExtRegistered('intranet_notify_dialog') &&
			  \Bitrix\Main\ModuleManager::isModuleInstalled('im');



// js extension reg
\Bitrix\Main\UI\Extension::load([
	'ui.actionpanel',
	'ui.notification'
]);
$assetRoot = '/bitrix/js/crm/';
$langRoot = BX_ROOT . '/modules/crm/lang/' . LANGUAGE_ID . '/';
\CJSCore::registerExt('crm_common', array(
	'js' => array('/bitrix/js/crm/crm.js', '/bitrix/js/crm/common.js')
));
\CJSCore::registerExt('crm_activity_type', array(
	'js' => array('/bitrix/js/crm/activity.js')
));
\CJSCore::registerExt('crm_partial_entity_editor', array(
	'js' => array('/bitrix/js/crm/partial_entity_editor.js', '/bitrix/js/crm/dialog.js')
));
\CJSCore::registerExt('crm_entity_editor', array(
	'css' => array('/bitrix/components/bitrix/crm.entity.editor/templates/.default/style.css'),
	'js' => array(
		'/bitrix/components/bitrix/crm.entity.editor/templates/.default/script.js',
		'/bitrix/js/crm/interface_form.js',
		'/bitrix/js/crm/entity_event.js',
		'/bitrix/components/bitrix/crm.entity.editor/templates/.default/js/control.js'
	)
));
\CJSCore::registerExt('popup_menu', array(
	'js' => array('/bitrix/js/main/popup_menu.js')
));
\CJSCore::registerExt(
	'crm_kanban',
	array(
		'js'  => array(
			$assetRoot . 'kanban/actions.js',
			$assetRoot . 'kanban/grid.js',
			$assetRoot . 'kanban/item.js',
			$assetRoot . 'kanban/column.js',
			$assetRoot . 'kanban/dropzone.js'
		),
		'css' => array(
			$assetRoot . 'kanban/css/kanban.css',
		),
		'rel' => array(
			'kanban', 'ajax', 'color_picker', 'date', 'crm_activity_planner'
		),
		'lang' => $langRoot . 'kanban.php',
		'bundle_js' => 'crm_kanban',
		'bundle_css' => 'crm_kanban'
	)
);
\CJSCore::Init(array(
	'crm_common',
	'crm_kanban',
	'crm_visit_tracker',
	'crm_activity_type',
	'crm_partial_entity_editor',
	'crm_entity_editor',
	'popup_menu',
	'currency',
	'core_money_editor',
	'intranet_notify_dialog',
	'marketplace',
	'sidepanel',
	'uf'
));

include 'editors.php';
?>

<div id="crm_kanban"></div>

<script type="text/javascript">

	var Kanban;
	var ajaxHandlerPath = "<?= $this->getComponent()->getPath()?>/ajax.php";

	BX.ready(
		function()
		{
			"use strict";
			BX.Currency.setCurrencyFormat(
				"<?= $arParams['CURRENCY']?>",
				<?= \CUtil::PhpToJSObject(\CCurrencyLang::GetFormatDescription($arParams['CURRENCY']), false, true)?>
			);

			BX.Crm.PartialEditorDialog.entityEditorUrls =
			{
				<?= \CCrmOwnerType::DealName;?>: "<?= '/bitrix/components/bitrix/crm.deal.details/ajax.php?' . bitrix_sessid_get();?>",
				<?= \CCrmOwnerType::LeadName?>: "<?= '/bitrix/components/bitrix/crm.lead.details/ajax.php?' . bitrix_sessid_get();?>"
			};

			var schemeInline = BX.Crm.EntityScheme.create(
				'kanban_scheme',
				{
					current: <?= \CUtil::phpToJSObject($arResult['ITEMS']['scheme_inline']);?>
				}
			);

			var userFieldManagerInline = BX.Crm.EntityUserFieldManager.create(
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

			BX.Crm.EntityEditorBoolean.messages =
			{
				yes: "<?= CUtil::JSEscape(Loc::getMessage('MAIN_YES'));?>",
				no: "<?= CUtil::JSEscape(Loc::getMessage('MAIN_NO'));?>"
			};

			BX.Crm.EntityEditorSection.messages =
			{
				change: "<?= CUtil::JSEscape(Loc::getMessage('CRM_KANBAN_ED_CHANGE'));?>",
				cancel: "<?= CUtil::JSEscape(Loc::getMessage('CRM_KANBAN_ED_CANCEL'));?>"
			};

			Kanban = new BX.CRM.Kanban.Grid(
				{
					renderTo: BX("crm_kanban"),
					itemType: "BX.CRM.Kanban.Item",
					columnType: "BX.CRM.Kanban.Column",
					dropZoneType: "BX.CRM.Kanban.DropZone",
					canAddColumn: <?= $demoAccess ? 'true' : ($arResult['ACCESS_CONFIG_PERMS'] ? 'true' : 'false')?>,
					canEditColumn: <?= $demoAccess ? 'true' : ($arResult['ACCESS_CONFIG_PERMS'] ? 'true' : 'false')?>,
					canRemoveColumn: <?= $arResult['ACCESS_CONFIG_PERMS'] ? 'true' : 'false'?>,
					canSortColumn: <?= $arResult['ACCESS_CONFIG_PERMS'] ? 'true' : 'false'?>,
					canSortItem: true,
					bgColor: <?= (SITE_TEMPLATE_ID === 'bitrix24' ? '"transparent"' : 'null')?>,
					columns: <?= \CUtil::PhpToJSObject(array_values($data['columns']), false, false, true)?>,
					items: <?= \CUtil::PhpToJSObject($data['items'], false, false, true)?>,
					dropZones: <?= \CUtil::PhpToJSObject(array_values($data['dropzones']), false, false, true)?>,
					data:
						{
							schemeInline: schemeInline,
							userFieldManagerInline: userFieldManagerInline,
							contactCenterShow: <?= $arParams['HIDE_CC'] ? 'false' : 'true';?>,
							restDemoBlockShow: <?= $arParams['HIDE_REST'] ? 'false' : 'true';?>,
							reckonActivitylessItems: <?= \CCrmUserCounterSettings::getValue(\CCrmUserCounterSettings::ReckonActivitylessItems, true) ? 'true' : 'false';?>,
							ajaxHandlerPath: ajaxHandlerPath,
							entityType: "<?= \CUtil::JSEscape($arParams['ENTITY_TYPE_CHR'])?>",
							entityTypeInt: "<?= \CUtil::JSEscape($arParams['ENTITY_TYPE_INT'])?>",
							entityPath: "<?= \CUtil::JSEscape($arParams['ENTITY_PATH'])?>",
							editorConfigId: "<?= \CUtil::JSEscape($arParams['EDITOR_CONFIG_ID'])?>",
							quickEditorPath: {
								lead: "/bitrix/components/bitrix/crm.lead.details/ajax.php?<?= bitrix_sessid_get();?>",
								deal: "/bitrix/components/bitrix/crm.deal.details/ajax.php?<?= bitrix_sessid_get();?>"
							},
							params: <?= json_encode($arParams['EXTRA'])?>,
							gridId: "<?= Helper::getGridId($arParams['ENTITY_TYPE_CHR'])?>",
							showActivity: <?= $arParams['SHOW_ACTIVITY'] == 'Y' ? 'true' : 'false'?>,
							currency: "<?= $arParams['CURRENCY']?>",
							lastId: <?= (int)$data['last_id']?>,
							rights:
								{
									canAddColumn: <?= $arResult['ACCESS_CONFIG_PERMS'] ? 'true' : 'false'?>,
									canEditColumn: <?= $arResult['ACCESS_CONFIG_PERMS'] ? 'true' : 'false'?>,
									canRemoveColumn: <?= $arResult['ACCESS_CONFIG_PERMS'] ? 'true' : 'false'?>,
									canSortColumn: <?= $arResult['ACCESS_CONFIG_PERMS'] ? 'true' : 'false'?>,
									canImport: <?= $arResult['ACCESS_IMPORT'] ? 'true' : 'false'?>,
									canSortItem: true,
									canUseVisit: <?= \Bitrix\Crm\Activity\Provider\Visit::isAvailable() ? 'true' : 'false';?>
								},
							visitParams: <?= \CUtil::PhpToJSObject(\Bitrix\Crm\Activity\Provider\Visit::getPopupParameters(), false, false, true)?>,
							admins: <?= \CUtil::PhpToJSObject(array_values($arResult['ADMINS']))?>,
							userId: <?= $arParams['USER_ID'];?>,
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
							categories: <?= \CUtil::phpToJsObject(array_values($arResult['CATEGORIES']));?>
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
					CRM_KANBAN_POPUP_PARAMS_CANCEL: "<?= CUtil::JSEscape(Loc::getMessage('CRM_KANBAN_POPUP_PARAMS_CANCEL'));?>"
				}
			);
		}
	);
</script>

<?include $_SERVER['DOCUMENT_ROOT'] . $this->getFolder() . '/popups.php'?>

<?if ($arParams['ENTITY_TYPE_CHR'] == 'LEAD'):
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
<?endif;
