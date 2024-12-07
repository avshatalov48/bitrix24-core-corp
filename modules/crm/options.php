<?php
##############################################
# Bitrix: SiteManager                        #
# Copyright (c) 2002-2010 Bitrix             #
# https://www.bitrixsoft.com                  #
# mailto:admin@bitrixsoft.com                #
##############################################

use Bitrix\Main\Localization\Loc;
use Bitrix\Main;

Loc::loadMessages(__FILE__);
Loc::loadMessages($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/main/options.php');

$module_id = 'crm';
CModule::IncludeModule($module_id);

$MOD_RIGHT = $APPLICATION->GetGroupRight($module_id);
if($MOD_RIGHT>='R'):

	// set up form
	$sHost = $_SERVER['HTTP_HOST'];
	if (mb_strpos($sHost, ':') !== false)
		$sHost = mb_substr($sHost, 0, mb_strpos($sHost, ':'));

	ob_start();
	$GLOBALS["APPLICATION"]->IncludeComponent('bitrix:intranet.user.selector',
		'',
		array(
			'INPUT_NAME' => 'sale_deal_assigned_by_id_tmp',
			'INPUT_VALUE' => COption::GetOptionString("crm", "sale_deal_assigned_by_id", ""),
			'MULTIPLE' => 'N'
		),
		null,
		array('HIDE_ICONS' => 'Y')
	);
	$sVal = ob_get_contents();
	ob_end_clean();

	$arOptionsBase = array(
		array("sale_deal_opened", Loc::getMessage("CRM_SALE_DEAL_OPENED_2"), "Y", array("checkbox")),
		array("sale_deal_probability", Loc::getMessage("CRM_SALE_DEAL_PROBABILITY_2"), "100", array("text")),
		array("sale_deal_assigned_by_id_tmp", Loc::getMessage("CRM_SALE_DEAL_ASSIGNED_BY_ID_2"), $sVal, array("statichtml")),
		Loc::getMessage("CRM_PROXY_TITLE"),
		array("proxy_scheme", Loc::getMessage("CRM_PROXY_SCHEME"), "http", array("selectbox", array("http" => "HTTP", "https" => "HTTPS"))),
		array("proxy_host", Loc::getMessage("CRM_PROXY_SERVER"), "", array("text")),
		array("proxy_port", Loc::getMessage("CRM_PROXY_PORT"), "80", array("text")),
		array("proxy_username", Loc::getMessage("CRM_PROXY_USERNAME"), "", array("text")),
		array("proxy_password", Loc::getMessage("CRM_PROXY_PASSWORD"), "", array("password")),
	);
	$arOptionsPath = array(
		array('path_to_lead_list', Loc::getMessage('CRM_OPTIONS_PATH_TO_LEAD_LIST'), '/crm/lead/list/', Array('text', '40')),
		array('path_to_lead_show', Loc::getMessage('CRM_OPTIONS_PATH_TO_LEAD_SHOW'), '/crm/lead/show/#lead_id#/', Array('text', '40')),
		array('path_to_lead_details', Loc::getMessage('CRM_OPTIONS_PATH_TO_LEAD_DETAILS'), '/crm/lead/details/#lead_id#/', Array('text', '40')),
		array('path_to_lead_edit', Loc::getMessage('CRM_OPTIONS_PATH_TO_LEAD_EDIT'), '/crm/lead/edit/#lead_id#/', Array('text', '40')),
		array('path_to_lead_convert', Loc::getMessage('CRM_OPTIONS_PATH_TO_LEAD_CONVERT'), '/crm/lead/convert/', Array('text', '40')),
		array('path_to_lead_import', Loc::getMessage('CRM_OPTIONS_PATH_TO_LEAD_IMPORT'), '/crm/lead/import/', Array('text', '40')),
		array('path_to_lead_widget', Loc::getMessage('CRM_OPTIONS_PATH_TO_LEAD_WIDGET'), '/crm/lead/widget/', Array('text', '40')),
		array('path_to_lead_kanban', Loc::getMessage('CRM_OPTIONS_PATH_TO_LEAD_KANBAN'), '/crm/lead/kanban/', Array('text', '40')),
		array('path_to_lead_calendar', Loc::getMessage('CRM_OPTIONS_PATH_TO_LEAD_CALENDAR'), '/crm/lead/calendar/', Array('text', '40')),
		array('path_to_deal_list', Loc::getMessage('CRM_OPTIONS_PATH_TO_DEAL_LIST'), '/crm/deal/list/', Array('text', '40')),
		array('path_to_deal_show', Loc::getMessage('CRM_OPTIONS_PATH_TO_DEAL_SHOW'), '/crm/deal/show/#deal_id#/', Array('text', '40')),
		array('path_to_deal_details', Loc::getMessage('CRM_OPTIONS_PATH_TO_DEAL_DETAILS'), '/crm/deal/details/#deal_id#/', Array('text', '40')),
		array('path_to_deal_edit', Loc::getMessage('CRM_OPTIONS_PATH_TO_DEAL_EDIT'), '/crm/deal/edit/#deal_id#/', Array('text', '40')),
		array('path_to_deal_import', Loc::getMessage('CRM_OPTIONS_PATH_TO_DEAL_IMPORT'), '/crm/deal/import/', Array('text', '40')),
		array('path_to_deal_widget', Loc::getMessage('CRM_OPTIONS_PATH_TO_DEAL_WIDGET'), '/crm/deal/widget/', Array('text', '40')),
		array('path_to_deal_kanban', Loc::getMessage('CRM_OPTIONS_PATH_TO_DEAL_KANBAN'), '/crm/deal/kanban/', Array('text', '40')),
		array('path_to_deal_calendar', Loc::getMessage('CRM_OPTIONS_PATH_TO_DEAL_CALENDAR'), '/crm/deal/calendar/', Array('text', '40')),
		array('path_to_quote_list', Loc::getMessage('CRM_OPTIONS_PATH_TO_QUOTE_LIST'), '/crm/quote/list/', Array('text', '40')),
		array('path_to_quote_show', Loc::getMessage('CRM_OPTIONS_PATH_TO_QUOTE_SHOW'), '/crm/quote/show/#quote_id#/', Array('text', '40')),
		array('path_to_quote_details', Loc::getMessage('CRM_OPTIONS_PATH_TO_QUOTE_DETAILS'), '/crm/type/7/details/#quote_id#/', Array('text', '40')),
		array('path_to_quote_edit', Loc::getMessage('CRM_OPTIONS_PATH_TO_QUOTE_EDIT'), '/crm/quote/edit/#quote_id#/', Array('text', '40')),
		array('path_to_quote_import', Loc::getMessage('CRM_OPTIONS_PATH_TO_QUOTE_IMPORT'), '/crm/quote/import/', Array('text', '40')),
		array('path_to_quote_kanban', Loc::getMessage('CRM_OPTIONS_PATH_TO_QUOTE_KANBAN'), '/crm/quote/kanban/', Array('text', '40')),
		array('path_to_contact_list', Loc::getMessage('CRM_OPTIONS_PATH_TO_CONTACT_LIST'), '/crm/contact/list/', Array('text', '40')),
		array('path_to_contact_category', Loc::getMessage('CRM_OPTIONS_PATH_TO_CONTACT_CATEGORY'), '/crm/contact/category/#category_id#/', Array('text', '40')),
		array('path_to_contact_show', Loc::getMessage('CRM_OPTIONS_PATH_TO_CONTACT_SHOW'), '/crm/contact/show/#contact_id#/', Array('text', '40')),
		array('path_to_contact_edit', Loc::getMessage('CRM_OPTIONS_PATH_TO_CONTACT_EDIT'), '/crm/contact/edit/#contact_id#/', Array('text', '40')),
		array('path_to_contact_details', Loc::getMessage('CRM_OPTIONS_PATH_TO_CONTACT_DETAILS'), '/crm/contact/details/#contact_id#/', Array('text', '40')),
		array('path_to_contact_import', Loc::getMessage('CRM_OPTIONS_PATH_TO_CONTACT_IMPORT'), '/crm/contact/import/', Array('text', '40')),
		array('path_to_contact_widget', Loc::getMessage('CRM_OPTIONS_PATH_TO_CONTACT_WIDGET'), '/crm/contact/widget/', Array('text', '40')),
		array('path_to_company_list', Loc::getMessage('CRM_OPTIONS_PATH_TO_COMPANY_LIST'), '/crm/company/list/', Array('text', '40')),
		array('path_to_company_category', Loc::getMessage('CRM_OPTIONS_PATH_TO_COMPANY_CATEGORY'), '/crm/company/category/#category_id#/', Array('text', '40')),
		array('path_to_company_show', Loc::getMessage('CRM_OPTIONS_PATH_TO_COMPANY_SHOW'), '/crm/company/show/#company_id#/', Array('text', '40')),
		array('path_to_company_edit', Loc::getMessage('CRM_OPTIONS_PATH_TO_COMPANY_EDIT'), '/crm/company/edit/#company_id#/', Array('text', '40')),
		array('path_to_company_details', Loc::getMessage('CRM_OPTIONS_PATH_TO_COMPANY_DETAILS'), '/crm/company/details/#company_id#/', Array('text', '40')),
		array('path_to_company_import', Loc::getMessage('CRM_OPTIONS_PATH_TO_COMPANY_IMPORT'), '/crm/company/import/', Array('text', '40')),
		array('path_to_company_widget', Loc::getMessage('CRM_OPTIONS_PATH_TO_COMPANY_WIDGET'), '/crm/company/widget/', Array('text', '40')),
		array('path_to_invoice_list', Loc::getMessage('CRM_OPTIONS_PATH_TO_INVOICE_LIST'), '/crm/invoice/list/', Array('text', '40')),
		array('path_to_invoice_show', Loc::getMessage('CRM_OPTIONS_PATH_TO_INVOICE_SHOW'), '/crm/invoice/show/#invoice_id#/', Array('text', '40')),
		array('path_to_invoice_edit', Loc::getMessage('CRM_OPTIONS_PATH_TO_INVOICE_EDIT'), '/crm/invoice/edit/#invoice_id#/', Array('text', '40')),
		array('path_to_invoice_recur', Loc::getMessage('CRM_OPTIONS_PATH_TO_INVOICE_RECUR'), '/crm/invoice/recur/', Array('text', '40')),
		array('path_to_invoice_recur_show', Loc::getMessage('CRM_OPTIONS_PATH_TO_INVOICE_RECUR_SHOW'), '/crm/invoice/recur/show/#invoice_id#/', Array('text', '40')),
		array('path_to_invoice_recur_edit', Loc::getMessage('CRM_OPTIONS_PATH_TO_INVOICE_RECUR_EDIT'), '/crm/invoice/recur/edit/#invoice_id#/', Array('text', '40')),
		array('path_to_invoice_payment', Loc::getMessage('CRM_OPTIONS_PATH_TO_INVOICE_PAYMENT'), '/crm/invoice/payment/#invoice_id#/', Array('text', '40')),
		array('path_to_invoice_widget', Loc::getMessage('CRM_OPTIONS_PATH_TO_INVOICE_WIDGET'), '/crm/invoice/widget/', Array('text', '40')),
		array('path_to_invoice_kanban', Loc::getMessage('CRM_OPTIONS_PATH_TO_INVOICE_KANBAN'), '/crm/invoice/kanban/', Array('text', '40')),
		array('path_to_user_profile', Loc::getMessage('CRM_OPTIONS_PATH_TO_USER_PROFILE'), '/company/personal/user/#user_id#/', Array('text', '40')),
		array('path_to_user_bp', Loc::getMessage('CRM_OPTIONS_PATH_TO_BP'), '/company/personal/bizproc/', Array('text', '40')),
		array('path_to_activity_list', Loc::getMessage('CRM_OPTIONS_PATH_TO_ACTIVITY_LIST'), '/crm/activity/', Array('text', '40')),
		array('path_to_activity_show', Loc::getMessage('CRM_OPTIONS_PATH_TO_ACTIVITY_SHOW'), '/crm/activity/?ID=#activity_id#&open_view=#activity_id#', Array('text', '40')),
		array('path_to_activity_edit', Loc::getMessage('CRM_OPTIONS_PATH_TO_ACTIVITY_EDIT'), '/crm/activity/?ID=#activity_id#&open_edit=#activity_id#', Array('text', '40')),
		array('path_to_deal_category_list', Loc::getMessage('CRM_OPTIONS_PATH_TO_DEAL_CATEGORY_LIST'), '/crm/configs/deal_category/', Array('text', '40')),
		array('path_to_deal_category_edit', Loc::getMessage('CRM_OPTIONS_PATH_TO_DEAL_CATEGORY_EDIT'), '/crm/configs/deal_category/&open_edit=#category_id#', Array('text', '40')),
		array('path_to_webform_list', Loc::getMessage('CRM_OPTIONS_PATH_TO_WEBFORM_LIST'), '/crm/webform/list/', Array('text', '40')),
		array('path_to_webform_edit', Loc::getMessage('CRM_OPTIONS_PATH_TO_WEBFORM_EDIT'), '/crm/webform/edit/#form_id#/', Array('text', '40')),
		array('path_to_webform_fill', Loc::getMessage('CRM_OPTIONS_PATH_TO_WEBFORM_FILL'), '/pub/form/#form_code#/#form_sec#/', Array('text', '40')),
		array('path_to_button_list', Loc::getMessage('CRM_OPTIONS_PATH_TO_BUTTON_LIST'), '/crm/button/', Array('text', '40')),
		array('path_to_button_edit', Loc::getMessage('CRM_OPTIONS_PATH_TO_BUTTON_EDIT'), '/crm/button/edit/#id#/', Array('text', '40')),
		array('path_to_activity_custom_type_list', Loc::getMessage('CRM_OPTIONS_PATH_TO_ACTIVITY_CUSTOM_TYPE_LIST'), '/crm/configs/custom_activity/', Array('text', '40')),
		array('path_to_start', Loc::getMessage('CRM_OPTIONS_PATH_TO_START'), '/crm/start/', Array('text', '40')),
		array('path_to_perm_list', Loc::getMessage('CRM_OPTIONS_PATH_TO_PERM_LIST'), '/crm/configs/perms/', Array('text', '40')),
		array('path_to_config_checker', Loc::getMessage('CRM_OPTIONS_PATH_TO_CONFIG_CHECKER'), '/crm/configs/checker/', Array('text', '40')),
		array('path_to_order_list', Loc::getMessage('CRM_OPTIONS_PATH_TO_ORDER_LIST'), '/shop/orders/', Array('text', '40')),
		array('path_to_order_details', Loc::getMessage('CRM_OPTIONS_PATH_TO_ORDER_DETAILS'), '/shop/orders/details/#order_id#/', Array('text', '40')),
		array('path_to_order_check_details', Loc::getMessage('CRM_OPTIONS_PATH_TO_ORDER_CHECK_DETAILS'), '/shop/orders/check/details/#check_id#/', Array('text', '40')),
		array('path_to_order_shipment_details', Loc::getMessage('CRM_OPTIONS_PATH_TO_ORDER_SHIPMENT_DETAILS'), '/shop/orders/shipment/details/#shipment_id#/', Array('text', '40')),
		array('path_to_order_payment_details', Loc::getMessage('CRM_OPTIONS_PATH_TO_ORDER_PAYMENT_DETAILS'), '/shop/orders/payment/details/#payment_id#/', Array('text', '40')),
		array('path_to_order_form', Loc::getMessage('CRM_OPTIONS_PATH_TO_ORDER_FORM'), '/shop/orderform/', Array('text', '40')),
		array('path_to_order_import_instagram', Loc::getMessage('CRM_OPTIONS_PATH_TO_ORDER_IMPORT_INSTAGRAM'), '/shop/import/instagram/', Array('text', '40')),
		array('path_to_lead_status_list', Loc::getMessage('CRM_OPTIONS_PATH_TO_LEAD_STATUS_LIST'), '/crm/type/1/categories/', Array('text', '40')),
		array('path_to_company_category', Loc::getMessage('CRM_OPTIONS_PATH_TO_LEAD_STATUS_LIST'), '/crm/type/1/categories/', Array('text', '40')),
	);

	$arAllOptions = array_merge($arOptionsPath, $arOptionsBase);

if($MOD_RIGHT>='Y' || $USER->IsAdmin()):

	if ($_SERVER['REQUEST_METHOD']=='GET' && $RestoreDefaults <> '' && check_bitrix_sessid())
	{
		$defaultCatalogId = Main\Config\Option::get('crm', 'default_product_catalog_id');
		COption::RemoveOption($module_id);
		Main\Config\Option::set('crm', 'default_product_catalog_id', $defaultCatalogId);
	}

	if($_SERVER['REQUEST_METHOD']=='POST' && $Update <> '' && check_bitrix_sessid())
	{
		$arOptions = $arAllOptions;
		foreach($arOptions as $option)
		{
			if(!is_array($option))
				continue;

			$name = $option[0];
			$val = ${$name};
			if($option[3][0] == 'checkbox' && $val != 'Y')
				$val = 'N';
			if($option[3][0] == 'multiselectbox')
				$val = @implode(',', $val);
			if ($option[3][0] == "password")
			{
				if (isset($_REQUEST[$name . '_delete']) && $_REQUEST[$name . '_delete'] == "Y")
				{
					$val = '';
				}
				elseif ($val == '')
				{
					continue;
				}
			}
			if($name == 'sale_deal_assigned_by_id_tmp')
			{
				$name = 'sale_deal_assigned_by_id';
				if (is_array($val) && count($val) > 0)
					$val = $val[0];
			}

			COption::SetOptionString($module_id, $name, $val, $option[1]);
		}

		\Bitrix\Crm\Preview\Route::setCrmRoutes();

		if($_REQUEST["back_url_settings"] <> '')
			LocalRedirect($_REQUEST["back_url_settings"]);
		else
			LocalRedirect($APPLICATION->GetCurPage()."?mid=".urlencode($mid)."&lang=".urlencode(LANGUAGE_ID)."&back_url_settings=".urlencode($_REQUEST["back_url_settings"]));
	}

endif; //if($MOD_RIGHT>="W"):

$aTabs = array();
$aTabs[] = array('DIV' => 'set', 'TAB' => Loc::getMessage('MAIN_TAB_SET'), 'ICON' => 'crm_settings', 'TITLE' => Loc::getMessage('MAIN_TAB_TITLE_SET'));
$aTabs[] = array('DIV' => 'path', 'TAB' => Loc::getMessage('CRM_TAB_PATH'), 'ICON' => 'crm_path', 'TITLE' => Loc::getMessage('CRM_TAB_TITLE_PATH'));
//$aTabs[] = array('DIV' => 'rights', 'TAB' => Loc::getMessage('MAIN_TAB_RIGHTS'), 'ICON' => 'crm_settings', 'TITLE' => Loc::getMessage('MAIN_TAB_TITLE_RIGHTS'));

$tabControl = new CAdminTabControl('tabControl', $aTabs);
?>
<?$tabControl->Begin();?>
<form method="POST" action="<?echo $APPLICATION->GetCurPage()?>?mid=<?=htmlspecialcharsbx($mid)?>&lang=<?=LANGUAGE_ID?>">
<?$tabControl->BeginNextTab();?>
<?__AdmSettingsDrawList('crm', $arOptionsBase);?>
<?//$tabControl->BeginNextTab();?>
<?//__AdmSettingsDrawList('crm', $arOptionsBase);?>
<?$tabControl->BeginNextTab();?>
<?__AdmSettingsDrawList('crm', $arOptionsPath);?>
<?//$tabControl->BeginNextTab();?>
<?//require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/admin/group_rights.php');?>
<?$tabControl->Buttons();?>
<script>
function RestoreDefaults()
{
	if(confirm('<?echo AddSlashes(Loc::getMessage('MAIN_HINT_RESTORE_DEFAULTS_WARNING'))?>'))
		window.location = "<?echo $APPLICATION->GetCurPage()?>?RestoreDefaults=Y&lang=<?echo LANG?>&mid=<?echo urlencode($mid)."&".bitrix_sessid_get();?>";
}
</script>
<input type="submit" name="Update" <?if ($MOD_RIGHT<'W') echo "disabled" ?> value="<?echo Loc::getMessage('MAIN_SAVE')?>">
<input type="reset" name="reset" value="<?echo Loc::getMessage('MAIN_RESET')?>">
<input type="hidden" name="Update" value="Y">
<?=bitrix_sessid_post();?>
<input type="button" <?if ($MOD_RIGHT<'W') echo "disabled" ?> title="<?echo Loc::getMessage('MAIN_HINT_RESTORE_DEFAULTS')?>" OnClick="RestoreDefaults();" value="<?echo Loc::getMessage('MAIN_RESTORE_DEFAULTS')?>">
<?$tabControl->End();?>
</form>
<?endif;
?>
