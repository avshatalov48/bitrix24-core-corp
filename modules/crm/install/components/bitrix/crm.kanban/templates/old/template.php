<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use \Bitrix\Crm\Conversion\LeadConversionScheme;
use \Bitrix\Crm\Category\DealCategory;
use \Bitrix\Crm\Conversion\EntityConverter;
use \Bitrix\Main\Localization\Loc;
use \Bitrix\Crm\Kanban\Helper;

\CJSCore::RegisterExt('crm_common', array(
	'js' => array('/bitrix/js/crm/common.js')
));

\CJSCore::RegisterExt('crm_activity_type', array(
	'js' => array('/bitrix/js/crm/activity.js')
));

\CJSCore::RegisterExt('popup_menu', array(
	'js' => array('/bitrix/js/main/popup_menu.js')
));

\CJSCore::RegisterExt('main_dd', array(
	'js' => array('/bitrix/js/main/dd.js')
));

\CJSCore::Init(array(
					'main_dd',
					'popup_menu',
					'crm_common',
					'crm_activity_planner',
					'crm_activity_type',
					'currency'));

$APPLICATION->SetAdditionalCSS('/bitrix/themes/.default/crm-entity-show.css');

//settings page
if ($arParams['ENTITY_TYPE_CHR'] == 'DEAL')
{
	$pathColumnEdit = '/crm/configs/status/?ACTIVE_TAB=status_tab_DEAL_STAGE';
}
elseif ($arParams['ENTITY_TYPE_CHR'] == 'LEAD')
{
	$pathColumnEdit = '/crm/configs/status/?ACTIVE_TAB=status_tab_STATUS';
}
else
{
	$pathColumnEdit = '/crm/configs/status/?ACTIVE_TAB=status_tab_'. $arParams['ENTITY_TYPE_CHR'] .'_STATUS';
}
?>

<?/*if (empty($arResult['ITEMS']['items'])):?>
	<div class="crm-kanban">
		<div class="main-grid-empty-block">
			<div class="main-grid-empty-inner">
				<div class="main-grid-empty-image"></div>
				<div class="main-grid-empty-text"><?= Loc::getMessage('CRM_KANBAN_NO_DATA')?></div>
			</div>
		</div>
		<div class="crm-kanban-grid" id="kanban"></div>
	</div>

	<?return;?>

<?endif;*/?>

<div class="crm-kanban">
	<div class="crm-kanban-grid" id="kanban"></div>
</div>

<script type="text/javascript">
	BX.message({
		CRM_KANBAN_ACTIVITY_MY: '<?= CUtil::JSEscape(Loc::getMessage('CRM_KANBAN_ACTIVITY_MY'));?>',
		CRM_KANBAN_ACTIVITY_PLAN: '<?= CUtil::JSEscape(Loc::getMessage('CRM_KANBAN_ACTIVITY_PLAN'));?>',
		CRM_KANBAN_ACTIVITY_PLAN_CALL: '<?= CUtil::JSEscape(Loc::getMessage('CRM_KANBAN_ACTIVITY_PLAN_CALL'));?>',
		CRM_KANBAN_ACTIVITY_PLAN_MEETING: '<?= CUtil::JSEscape(Loc::getMessage('CRM_KANBAN_ACTIVITY_PLAN_MEETING'));?>',
		CRM_KANBAN_ACTIVITY_PLAN_TASK: '<?= CUtil::JSEscape(Loc::getMessage('CRM_KANBAN_ACTIVITY_PLAN_TASK'));?>',
		CRM_KANBAN_ACTIVITY_MORE: '<?= CUtil::JSEscape(Loc::getMessage('CRM_KANBAN_ACTIVITY_MORE'));?>',
		CRM_KANBAN_ACTIVITY_LETSGO: '<?= CUtil::JSEscape(Loc::getMessage('CRM_KANBAN_ACTIVITY_LETSGO_' . $arParams['ENTITY_TYPE_CHR']));?>',
		CRM_KANBAN_RELOAD_PAGE: '<?= CUtil::JSEscape(Loc::getMessage('CRM_KANBAN_RELOAD_PAGE'));?>',
		CRM_KANBAN_INVOICE_PARAMS: '<?= CUtil::JSEscape(Loc::getMessage('CRM_KANBAN_INVOICE_PARAMS'));?>',
		CRM_KANBAN_INVOICE_PARAMS_SAVE: '<?= CUtil::JSEscape(Loc::getMessage('CRM_KANBAN_INVOICE_PARAMS_SAVE'));?>',
		CRM_KANBAN_INVOICE_PARAMS_CANCEL: '<?= CUtil::JSEscape(Loc::getMessage('CRM_KANBAN_INVOICE_PARAMS_CANCEL'));?>',
		CRM_KANBAN_INVOICE_PARAMS_DATE: '<?= CUtil::JSEscape(Loc::getMessage('CRM_KANBAN_INVOICE_PARAMS_DATE'));?>',
		CRM_KANBAN_INVOICE_PARAMS_COMMENT: '<?= CUtil::JSEscape(Loc::getMessage('CRM_KANBAN_INVOICE_PARAMS_COMMENT'));?>',
		CRM_KANBAN_INVOICE_PARAMS_DOCNUM: '<?= CUtil::JSEscape(Loc::getMessage('CRM_KANBAN_INVOICE_PARAMS_DOCNUM'));?>',
		CRM_KANBAN_FINAL_ALERT: '<?= CUtil::JSEscape(Loc::getMessage('CRM_KANBAN_FINAL_ALERT'));?>',
		CRM_KANBAN_CONVERT_POPUP_TITLE: '<?= CUtil::JSEscape(Loc::getMessage('CRM_KANBAN_CONVERT_POPUP_TITLE'));?>',
		CRM_KANBAN_CONVERT_SELECT_ENTITY: '<?= CUtil::JSEscape(Loc::getMessage('CRM_KANBAN_CONVERT_SELECT_ENTITY'));?>',
		CRM_KANBAN_FAIL_CONFIRM_DEAL: '<?= CUtil::JSEscape(Loc::getMessage('CRM_KANBAN_FAIL_CONFIRM_DEAL'));?>',
		CRM_KANBAN_FAIL_CONFIRM_LEAD: '<?= CUtil::JSEscape(Loc::getMessage('CRM_KANBAN_FAIL_CONFIRM_LEAD'));?>',
		CRM_KANBAN_FAIL_CONFIRM_INVOICE: '<?= CUtil::JSEscape(Loc::getMessage('CRM_KANBAN_FAIL_CONFIRM_INVOICE'));?>',
		CRM_KANBAN_FAIL_CONFIRM_QUOTE: '<?= CUtil::JSEscape(Loc::getMessage('CRM_KANBAN_FAIL_CONFIRM_QUOTE'));?>',
		CRM_KANBAN_FAIL_CONFIRM_APPLY: '<?= CUtil::JSEscape(Loc::getMessage('CRM_KANBAN_FAIL_CONFIRM_APPLY'));?>',
		CRM_KANBAN_FAIL_CONFIRM_CANCEL: '<?= CUtil::JSEscape(Loc::getMessage('CRM_KANBAN_FAIL_CONFIRM_CANCEL'));?>',
		CRM_KANBAN_ERROR_DISABLE_CONVERTED_LEAD: '<?= CUtil::JSEscape(Loc::getMessage('CRM_KANBAN_ERROR_DISABLE_CONVERTED_LEAD'));?>',
		CRM_KANBAN_NO_EMAIL: '<?= CUtil::JSEscape(Loc::getMessage('CRM_KANBAN_NO_EMAIL'));?>',
		CRM_KANBAN_NO_PHONE: '<?= CUtil::JSEscape(Loc::getMessage('CRM_KANBAN_NO_PHONE'));?>',
		CRM_KANBAN_NO_IMOL: '<?= CUtil::JSEscape(Loc::getMessage('CRM_KANBAN_NO_IMOL'));?>',
		CRM_KANBAN_NO_DATA: '<?= CUtil::JSEscape(Loc::getMessage('CRM_KANBAN_NO_DATA'));?>'
	});
</script>

<script type="text/javascript">
	BX.ready(function(){
		BX.CrmKanbanHelper.create({
			ENTITY_TYPE: '<?=CUtil::JSEscape($arParams['ENTITY_TYPE_CHR'])?>',
			GRID_ID: '<?= Helper::getGridId($arParams['ENTITY_TYPE_CHR'])?>',
			SHOW_ACTIVITY: '<?=CUtil::JSEscape($arParams['SHOW_ACTIVITY'])?>',
			AJAX_PATH: '/bitrix/components/bitrix/crm.kanban/ajax.php',
			CONTAINER: BX('kanban'),
			DATA: <?= CUtil::PhpToJSObject($arResult['ITEMS'])?>,
			EXTRA: <?= json_encode($arParams['EXTRA'])?>,
			PATH_COLUMN_EDIT: '<?= CUtil::JSEscape($pathColumnEdit)?>',
			ACCESS_CONFIG_PERMS: '<?= $arResult['ACCESS_CONFIG_PERMS'] ? 'Y' : 'N'?>',
			CURRENCY: '<?= $arParams['CURRENCY']?>'
		});
		BX.Currency.setCurrencyFormat('<?= $arParams['CURRENCY']?>', <?= \CUtil::PhpToJSObject(\CCurrencyLang::GetFormatDescription($arParams['CURRENCY']), false, true); ?>);
	});
</script>



<?if ($arParams['ENTITY_TYPE_CHR'] == 'LEAD'):
	Loc::loadMessages($_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/crm.lead.list/templates/.default/template.php');
	?>

<script type="text/javascript">
	BX.ready(
		function()
		{
			BX.CrmEntityType.captions =
			{
				'<?=CCrmOwnerType::LeadName?>': '<?=CCrmOwnerType::GetDescription(CCrmOwnerType::Lead)?>',
				'<?=CCrmOwnerType::ContactName?>': '<?=CCrmOwnerType::GetDescription(CCrmOwnerType::Contact)?>',
				'<?=CCrmOwnerType::CompanyName?>': '<?=CCrmOwnerType::GetDescription(CCrmOwnerType::Company)?>',
				'<?=CCrmOwnerType::DealName?>': '<?=CCrmOwnerType::GetDescription(CCrmOwnerType::Deal)?>',
				'<?=CCrmOwnerType::InvoiceName?>': '<?=CCrmOwnerType::GetDescription(CCrmOwnerType::Invoice)?>',
				'<?=CCrmOwnerType::QuoteName?>': '<?=CCrmOwnerType::GetDescription(CCrmOwnerType::Quote)?>'
			};
			BX.CrmLeadConversionScheme.messages =
				<?=CUtil::PhpToJSObject(LeadConversionScheme::getJavaScriptDescriptions(false))?>;
			BX.CrmLeadConverter.messages =
			{
				accessDenied: '<?=GetMessageJS('CRM_LEAD_CONV_ACCESS_DENIED')?>',
				generalError: '<?=GetMessageJS('CRM_LEAD_CONV_GENERAL_ERROR')?>',
				dialogTitle: '<?=GetMessageJS('CRM_LEAD_CONV_DIALOG_TITLE')?>',
				syncEditorLegend: '<?=GetMessageJS('CRM_LEAD_CONV_DIALOG_SYNC_LEGEND')?>',
				syncEditorFieldListTitle: '<?=GetMessageJS('CRM_LEAD_CONV_DIALOG_SYNC_FILED_LIST_TITLE')?>',
				syncEditorEntityListTitle: '<?=GetMessageJS('CRM_LEAD_CONV_DIALOG_SYNC_ENTITY_LIST_TITLE')?>',
				continueButton: '<?=GetMessageJS('CRM_LEAD_CONV_DIALOG_CONTINUE_BTN')?>',
				cancelButton: '<?=GetMessageJS('CRM_LEAD_CONV_DIALOG_CANCEL_BTN')?>',
				selectButton: '<?=GetMessageJS('CRM_LEAD_CONV_ENTITY_SEL_BTN')?>',
				openEntitySelector: '<?=GetMessageJS('CRM_LEAD_CONV_OPEN_ENTITY_SEL')?>',
				entitySelectorTitle: '<?=GetMessageJS('CRM_LEAD_CONV_ENTITY_SEL_TITLE')?>',
				contact: '<?=GetMessageJS('CRM_LEAD_CONV_ENTITY_SEL_CONTACT')?>',
				company: '<?=GetMessageJS('CRM_LEAD_CONV_ENTITY_SEL_COMPANY')?>',
				noresult: '<?=GetMessageJS('CRM_LEAD_CONV_ENTITY_SEL_SEARCH_NO_RESULT')?>',
				search : '<?=GetMessageJS('CRM_LEAD_CONV_ENTITY_SEL_SEARCH')?>',
				last : '<?=GetMessageJS('CRM_LEAD_CONV_ENTITY_SEL_LAST')?>'
			};
			BX.CrmLeadConverter.permissions =
			{
				contact: <?=CUtil::PhpToJSObject($arResult['CAN_CONVERT_TO_CONTACT'])?>,
				company: <?=CUtil::PhpToJSObject($arResult['CAN_CONVERT_TO_COMPANY'])?>,
				deal: <?=CUtil::PhpToJSObject($arResult['CAN_CONVERT_TO_DEAL'])?>
			};
			BX.CrmLeadConverter.settings =
			{
				serviceUrl: '<?='/bitrix/components/bitrix/crm.lead.show/ajax.php?action=convert&'.bitrix_sessid_get()?>',
				enablePageRefresh: false,
				enableRedirectToShowPage: false,
				config: <?=CUtil::PhpToJSObject($arResult['CONVERSION_CONFIG']->toJavaScript())?>
			};
			BX.CrmDealCategory.infos = <?=CUtil::PhpToJSObject(
				DealCategory::getJavaScriptInfos(EntityConverter::getPermittedDealCategoryIDs())
			)?>;
			BX.CrmDealCategorySelectDialog.messages =
			{
				title: '<?=GetMessageJS('CRM_LEAD_LIST_CONV_DEAL_CATEGORY_DLG_TITLE')?>',
				field: '<?=GetMessageJS('CRM_LEAD_LIST_CONV_DEAL_CATEGORY_DLG_FIELD')?>',
				saveButton: '<?=GetMessageJS('CRM_LEAD_LIST_BUTTON_SAVE')?>',
				cancelButton: '<?=GetMessageJS('CRM_LEAD_LIST_BUTTON_CANCEL')?>'
			};
		}
	);
</script>

<?endif;?>