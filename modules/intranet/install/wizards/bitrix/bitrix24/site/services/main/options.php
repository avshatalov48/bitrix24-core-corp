<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

//desktop on index page, depending on SITE_ID
$sOptions = 'a:1:{s:7:"GADGETS";a:11:{s:13:"BIRTHDAY@5438";a:4:{s:6:"COLUMN";i:0;s:3:"ROW";i:0;s:8:"USERDATA";N;s:4:"HIDE";s:1:"N";}s:11:"HONOUR@8771";a:4:{s:6:"COLUMN";i:0;s:3:"ROW";i:1;s:8:"USERDATA";N;s:4:"HIDE";s:1:"N";}s:19:"NEW_EMPLOYEES@11193";a:4:{s:6:"COLUMN";i:0;s:3:"ROW";i:2;s:8:"USERDATA";N;s:4:"HIDE";s:1:"N";}s:14:"OFFICIAL@13359";a:3:{s:6:"COLUMN";i:1;s:3:"ROW";i:0;s:4:"HIDE";s:1:"N";}s:10:"LIFE@14720";a:4:{s:6:"COLUMN";i:1;s:3:"ROW";i:1;s:8:"USERDATA";N;s:4:"HIDE";s:1:"N";}s:10:"VIDEO@8095";a:4:{s:6:"COLUMN";i:1;s:3:"ROW";i:2;s:8:"USERDATA";N;s:4:"HIDE";s:1:"N";}s:12:"PHOTOS@11262";a:4:{s:6:"COLUMN";i:1;s:3:"ROW";i:3;s:8:"USERDATA";N;s:4:"HIDE";s:1:"N";}s:15:"desktop-actions";a:3:{s:6:"COLUMN";i:2;s:3:"ROW";i:0;s:4:"HIDE";s:1:"N";}s:9:"VOTE@4378";a:3:{s:6:"COLUMN";i:2;s:3:"ROW";i:1;s:4:"HIDE";s:1:"N";}s:22:"COMPANY_CALENDAR@20319";a:4:{s:6:"COLUMN";i:2;s:3:"ROW";i:2;s:8:"USERDATA";N;s:4:"HIDE";s:1:"N";}s:17:"SHARED_DOCS@14908";a:4:{s:6:"COLUMN";i:2;s:3:"ROW";i:3;s:8:"USERDATA";N;s:4:"HIDE";s:1:"N";}}}';
$arOptions = unserialize($sOptions);
WizardServices::SetUserOption('intranet', '~gadgets_mainpage_'.WIZARD_SITE_ID, $arOptions, $common = true);

$links = GetMessage('MAIN_OPT_DEF_LINKS1');

//personal desktop, depending on SITE_ID
$sOptions = 'a:1:{s:7:"GADGETS";a:9:{s:13:"BIRTHDAY@8298";a:4:{s:6:"COLUMN";i:0;s:3:"ROW";i:0;s:8:"USERDATA";N;s:4:"HIDE";s:1:"N";}s:14:"HTML_AREA@8623";a:4:{s:6:"COLUMN";i:0;s:3:"ROW";i:1;s:8:"USERDATA";a:1:{s:7:"content";s:1:" ";}s:4:"HIDE";s:1:"N";}s:13:"UPDATES@17676";a:4:{s:6:"COLUMN";i:1;s:3:"ROW";i:0;s:8:"USERDATA";N;s:4:"HIDE";s:1:"N";}s:11:"TASKS@11589";a:4:{s:6:"COLUMN";i:1;s:3:"ROW";i:1;s:8:"USERDATA";N;s:4:"HIDE";s:1:"N";}s:9:"BLOG@8601";a:4:{s:6:"COLUMN";i:1;s:3:"ROW";i:2;s:8:"USERDATA";N;s:4:"HIDE";s:1:"N";}s:15:"desktop-actions";a:3:{s:6:"COLUMN";i:2;s:3:"ROW";i:0;s:4:"HIDE";s:1:"N";}s:14:"CALENDAR@22972";a:4:{s:6:"COLUMN";i:2;s:3:"ROW";i:1;s:8:"USERDATA";N;s:4:"HIDE";s:1:"N";}s:13:"WEATHER@21928";a:4:{s:6:"COLUMN";i:2;s:3:"ROW";i:2;s:8:"USERDATA";N;s:4:"HIDE";s:1:"N";}s:12:"PROBKI@25675";a:4:{s:6:"COLUMN";i:2;s:3:"ROW";i:3;s:8:"USERDATA";N;s:4:"HIDE";s:1:"N";}}}';
$arOptions = unserialize($sOptions);
$arOptions['GADGETS']['HTML_AREA@8623']['USERDATA']['content'] = $links;
WizardServices::SetUserOption('intranet', '~gadgets_dashboard_'.WIZARD_SITE_ID, $arOptions, $common = true);

$obSite = new CSite();
$obSite->Update(WIZARD_SITE_ID, Array("NAME" => COption::GetOptionString("main", "site_name", GetMessage("DEFAULT_SITE_NAME"))));

CGroup::SetSubordinateGroups(WIZARD_PERSONNEL_DEPARTMENT_GROUP, Array(WIZARD_EMPLOYEES_GROUP));
CGroup::SetSubordinateGroups(WIZARD_PORTAL_ADMINISTRATION_GROUP, Array(WIZARD_EMPLOYEES_GROUP));

if(LANGUAGE_ID == "ru")
{
	$vendor = "1c_bitrix_portal";
}
elseif(LANGUAGE_ID == "ua")
{
	$vendor = "ua_bitrix_portal";
}
else
{
	$vendor = "bitrix_portal";
}

COption::SetOptionString("main", "templates_visual_editor", "Y");
COption::SetOptionString("main", "upload_dir", "upload");
COption::SetOptionString("main", "component_cache_on","Y");
COption::SetOptionString("main", "save_original_file_name", "Y");
COption::SetOptionString("main", "captcha_registration", "N");
COption::SetOptionString("main", "use_secure_password_cookies", "Y");
COption::SetOptionString("main", "new_user_email_uniq_check", "Y");
COption::SetOptionString("main", "auth_comp2", "Y");
COption::SetOptionString("main", "vendor", $vendor);
COption::SetOptionString("main", "update_autocheck", "7");
COption::SetOptionString("main", "use_digest_auth", "Y");
COption::SetOptionString("main", "use_time_zones", "Y");
COption::SetOptionString("main", "auto_time_zone", "Y");
COption::SetOptionString("main", "map_top_menu_type", "top");
COption::SetOptionString("main", "map_left_menu_type", "left");
COption::SetOptionString("main", "url_preview_enable", "Y");
COption::SetOptionString("main", "imageeditor_proxy_enabled", "Y");

SetMenuTypes(Array("left" => GetMessage("MAIN_OPT_MENU_SECT"), "top" => GetMessage("MAIN_OPT_MENU_MAIN"), "bottom" => GetMessage("MAIN_OPT_MENU_BOTTOM"), "top_links" => GetMessage("MAIN_OPT_MENU_TOPLINKS"), "department" => GetMessage("MAIN_OPT_MENU_DEPARTMENT")),WIZARD_SITE_ID);
SetMenuTypes(Array("left" => GetMessage("MAIN_OPT_MENU_SECT"), "top" => GetMessage("MAIN_OPT_MENU_MAIN"), "bottom" => GetMessage("MAIN_OPT_MENU_BOTTOM"), "top_links" => GetMessage("MAIN_OPT_MENU_TOPLINKS"), "department" => GetMessage("MAIN_OPT_MENU_DEPARTMENT")),"");
COption::SetOptionString("fileman", "default_edit", "html");

COption::SetOptionString("iblock", "use_htmledit", "Y");
COption::SetOptionString("iblock", "combined_list_mode", "Y");

COption::SetOptionString("search", "use_stemming", "Y");
COption::SetOptionString("search", "include_mask", "*.php;*.html;*.htm;*.doc;*.ppt;*.xls;*.rtf;*.docx;*.xlsx;*.pptx;*.odt;*.odp;*.ods;*.pdf");
COption::SetOptionString("search", "exclude_mask", "/bitrix/*;/upload/*;");
COption::SetOptionString("search", "use_word_distance", "Y");
COption::SetOptionInt("search", "max_result_size", 100);

COption::SetOptionString("statistic", "IMPORTANT_PAGE_PARAMS", "ID, IBLOCK_ID, SECTION_ID, ELEMENT_ID, PARENT_ELEMENT_ID, FID, TID, MID, UID, VOTE_ID, print, goto");
COption::SetOptionString("statistic", "DEFENCE_ON", "N");
COption::SetOptionString("statistic", "DEFENCE_STACK_TIME", "20");

/*
COption::SetOptionString("main", "event_log_logout", "Y");
COption::SetOptionString("main", "event_log_login_success", "Y");
COption::SetOptionString("main", "event_log_login_fail", "Y");
COption::SetOptionString("main", "event_log_register", "Y");
COption::SetOptionString("main", "event_log_register_fail", "Y");
COption::SetOptionString("main", "event_log_password_request", "Y");
COption::SetOptionString("main", "event_log_password_change", "Y");
COption::SetOptionString("main", "event_log_user_delete", "Y");
*/
COption::SetOptionString("main", "event_log_login_success", "Y");

COption::SetOptionString("main", 'CAPTCHA_presets', '2');
COption::SetOptionString("main", 'CAPTCHA_transparentTextPercent', '0');
COption::SetOptionString("main", 'CAPTCHA_arBGColor_1', 'FFFFFF');
COption::SetOptionString("main", 'CAPTCHA_arBGColor_2', 'FFFFFF');
COption::SetOptionString("main", 'CAPTCHA_numEllipses', '0');
COption::SetOptionString("main", 'CAPTCHA_numLines', '0');
COption::SetOptionString("main", 'CAPTCHA_textStartX', '40');
COption::SetOptionString("main", 'CAPTCHA_textFontSize', '26');
COption::SetOptionString("main", 'CAPTCHA_arTextColor_1', '000000');
COption::SetOptionString("main", 'CAPTCHA_arTextColor_2', '000000');
COption::SetOptionString("main", 'CAPTCHA_textAngel_1', '-15');
COption::SetOptionString("main", 'CAPTCHA_textAngel_2', '15');
COption::SetOptionString("main", 'CAPTCHA_textDistance_1', '-2');
COption::SetOptionString("main", 'CAPTCHA_textDistance_2', '-2');
COption::SetOptionString("main", 'CAPTCHA_bWaveTransformation', 'N');
COption::SetOptionString("main", 'CAPTCHA_arBorderColor', '000000');
COption::SetOptionString("main", 'CAPTCHA_arTTFFiles', 'bitrix_captcha.ttf');

COption::SetOptionString("main", "bx_fast_download", "Y");
COption::SetOptionString("main", "use_hot_keys", "N");
COption::SetOptionString("main", "user_profile_history", "Y");

//////// from wizard
COption::SetOptionString("main", "site_name", GetMessage("main_site_name")/*$wizard->GetVar("siteName"))*/, false, $site_id);
//COption::SetOptionString("main", "wizard_allow_group", "N", false, $site_id);
//COption::SetOptionString("main", "wizard_demo_data", "N", false, $site_id);
//COption::SetOptionString("main", "wizard_allow_guests", "N", false, $site_id);
$site_id = 's1';
if(!WIZARD_IS_INSTALLED)
	WizardServices::SetFilePermission(Array(SITE_ID, "/" ), Array("2" => "D"));
COption::SetOptionString("main", "new_user_registration", "N", false, "");
COption::SetOptionString("main", "new_user_registration_email_confirmation", "N", false, "");
COption::SetOptionString("socialnetwork", "allow_forum_user", "N", false, $site_id);
COption::SetOptionString("socialnetwork", "allow_forum_group", "N", false, $site_id);
///////////

WizardServices::SetUserOption("global", "settings", Array(
	"start_menu_preload" => "Y",
	"start_menu_title" => "N",
), $common = true);

$links = GetMessage('MAIN_OPT_DEF_LINKS');

//desktop on index page
$sOptions = 'a:1:{s:7:"GADGETS";a:11:{s:13:"BIRTHDAY@5438";a:4:{s:6:"COLUMN";i:0;s:3:"ROW";i:0;s:8:"USERDATA";N;s:4:"HIDE";s:1:"N";}s:11:"HONOUR@8771";a:4:{s:6:"COLUMN";i:0;s:3:"ROW";i:1;s:8:"USERDATA";N;s:4:"HIDE";s:1:"N";}s:19:"NEW_EMPLOYEES@11193";a:4:{s:6:"COLUMN";i:0;s:3:"ROW";i:2;s:8:"USERDATA";N;s:4:"HIDE";s:1:"N";}s:14:"OFFICIAL@13359";a:3:{s:6:"COLUMN";i:1;s:3:"ROW";i:0;s:4:"HIDE";s:1:"N";}s:10:"LIFE@14720";a:4:{s:6:"COLUMN";i:1;s:3:"ROW";i:1;s:8:"USERDATA";N;s:4:"HIDE";s:1:"N";}s:10:"VIDEO@8095";a:4:{s:6:"COLUMN";i:1;s:3:"ROW";i:2;s:8:"USERDATA";N;s:4:"HIDE";s:1:"N";}s:12:"PHOTOS@11262";a:4:{s:6:"COLUMN";i:1;s:3:"ROW";i:3;s:8:"USERDATA";N;s:4:"HIDE";s:1:"N";}s:15:"desktop-actions";a:3:{s:6:"COLUMN";i:2;s:3:"ROW";i:0;s:4:"HIDE";s:1:"N";}s:9:"VOTE@4378";a:3:{s:6:"COLUMN";i:2;s:3:"ROW";i:1;s:4:"HIDE";s:1:"N";}s:22:"COMPANY_CALENDAR@20319";a:4:{s:6:"COLUMN";i:2;s:3:"ROW";i:2;s:8:"USERDATA";N;s:4:"HIDE";s:1:"N";}s:17:"SHARED_DOCS@14908";a:4:{s:6:"COLUMN";i:2;s:3:"ROW";i:3;s:8:"USERDATA";N;s:4:"HIDE";s:1:"N";}}}';
$arOptions = unserialize($sOptions);
WizardServices::SetUserOption('intranet', '~gadgets_mainpage', $arOptions, $common = true);

//personal desktop
$sOptions = 'a:1:{s:7:"GADGETS";a:9:{s:13:"BIRTHDAY@8298";a:4:{s:6:"COLUMN";i:0;s:3:"ROW";i:0;s:8:"USERDATA";N;s:4:"HIDE";s:1:"N";}s:14:"HTML_AREA@8623";a:4:{s:6:"COLUMN";i:0;s:3:"ROW";i:1;s:8:"USERDATA";a:1:{s:7:"content";s:1:" ";}s:4:"HIDE";s:1:"N";}s:13:"UPDATES@17676";a:4:{s:6:"COLUMN";i:1;s:3:"ROW";i:0;s:8:"USERDATA";N;s:4:"HIDE";s:1:"N";}s:11:"TASKS@11589";a:4:{s:6:"COLUMN";i:1;s:3:"ROW";i:1;s:8:"USERDATA";N;s:4:"HIDE";s:1:"N";}s:9:"BLOG@8601";a:4:{s:6:"COLUMN";i:1;s:3:"ROW";i:2;s:8:"USERDATA";N;s:4:"HIDE";s:1:"N";}s:15:"desktop-actions";a:3:{s:6:"COLUMN";i:2;s:3:"ROW";i:0;s:4:"HIDE";s:1:"N";}s:14:"CALENDAR@22972";a:4:{s:6:"COLUMN";i:2;s:3:"ROW";i:1;s:8:"USERDATA";N;s:4:"HIDE";s:1:"N";}s:13:"WEATHER@21928";a:4:{s:6:"COLUMN";i:2;s:3:"ROW";i:2;s:8:"USERDATA";N;s:4:"HIDE";s:1:"N";}s:12:"PROBKI@25675";a:4:{s:6:"COLUMN";i:2;s:3:"ROW";i:3;s:8:"USERDATA";N;s:4:"HIDE";s:1:"N";}}}';
$arOptions = unserialize($sOptions);
$arOptions['GADGETS']['HTML_AREA@8623']['USERDATA']['content'] = $links;
WizardServices::SetUserOption('intranet', '~gadgets_dashboard', $arOptions, $common = true);

//groups desktop
$sOptions = 'a:1:{s:7:"GADGETS";a:8:{s:18:"SONET_GROUP_DESC@1";a:3:{s:6:"COLUMN";i:0;s:3:"ROW";i:0;s:4:"HIDE";s:1:"N";}s:17:"UPDATES_ENTITY@10";a:3:{s:6:"COLUMN";i:0;s:3:"ROW";i:1;s:4:"HIDE";s:1:"N";}s:7:"TASKS@4";a:3:{s:6:"COLUMN";i:0;s:3:"ROW";i:4;s:4:"HIDE";s:1:"N";}s:18:"SONET_GROUP_TAGS@5";a:3:{s:6:"COLUMN";i:0;s:3:"ROW";i:5;s:4:"HIDE";s:1:"N";}s:18:"SONET_GROUP_WIKI@6";a:3:{s:6:"COLUMN";i:0;s:3:"ROW";i:6;s:4:"HIDE";s:1:"N";}s:19:"SONET_GROUP_LINKS@7";a:3:{s:6:"COLUMN";i:1;s:3:"ROW";i:0;s:4:"HIDE";s:1:"N";}s:19:"SONET_GROUP_USERS@8";a:3:{s:6:"COLUMN";i:1;s:3:"ROW";i:1;s:4:"HIDE";s:1:"N";}s:18:"SONET_GROUP_MODS@9";a:3:{s:6:"COLUMN";i:1;s:3:"ROW";i:2;s:4:"HIDE";s:1:"N";}}}';
$arOptions = unserialize($sOptions);
WizardServices::SetUserOption('intranet', '~gadgets_sonet_group', $arOptions, $common = false, 0);

//users desktop
$sOptions = 'a:1:{s:7:"GADGETS";a:2:{s:22:"SONET_USER_LINKS@23750";a:3:{s:6:"COLUMN";i:0;s:3:"ROW";i:0;s:4:"HIDE";s:1:"N";}s:17:"SONET_USER_DESC@8";a:4:{s:6:"COLUMN";i:1;s:3:"ROW";i:0;s:8:"USERDATA";N;s:4:"HIDE";s:1:"N";}}}';
$arOptions = unserialize($sOptions);
WizardServices::SetUserOption('intranet', '~gadgets_sonet_user', $arOptions, $common = false, 0);

//rss news desktop
WizardServices::SetUserOption('intranet', '~gadgets_business_news', unserialize('a:1:{s:7:"GADGETS";a:1:{s:14:"RSSREADER@7338";a:4:{s:6:"COLUMN";i:0;s:3:"ROW";i:1;s:8:"USERDATA";N;s:4:"HIDE";s:1:"N";}}}'), $common = true);

//user edit form customization
WizardServices::SetUserOption("form", "user_edit", Array(
	"tabs"=>"edit1--#--".GetMessage("main_opt_user_user")."--,--LAST_UPDATE--#--  ".GetMessage("main_opt_user_upd")."--,--LAST_LOGIN--#--  ".GetMessage("main_opt_user_last")."--,--NAME--#--  ".GetMessage("main_opt_user_name")."--,--LAST_NAME--#--  ".GetMessage("main_opt_user_lastname")."--,--SECOND_NAME--#--  ".GetMessage("main_opt_user_secondname")."--,--EMAIL--#--*E-Mail--,--LOGIN--#--*".GetMessage("main_opt_user_login")."--,--PASSWORD--#--*".GetMessage("main_opt_user_pass")."--,--edit1_csection1--#----".GetMessage("main_opt_user_str")."--,--UF_DEPARTMENT--#--  ".GetMessage("main_opt_user_dep")."--;--edit2--#--".GetMessage("main_opt_user_group")."--,--GROUP_ID--#--  ".GetMessage("main_opt_user_group_user")."--;--edit3--#--".GetMessage("main_opt_user_pers")."--,--PERSONAL_GENDER--#--  ".GetMessage("main_opt_user_sex")."--,--PERSONAL_BIRTHDAY--#--  ".GetMessage("main_opt_user_bith")."--,--PERSONAL_PHOTO--#--  ".GetMessage("main_opt_user_photo")."--,--PERSONAL_PROFESSION--#--  ".GetMessage("main_opt_user_spec")."--,--UF_INN--#--  ".GetMessage("main_opt_user_inn")."--,--PERSONAL_WWW--#--  ".GetMessage("main_opt_user_www")."--,--PERSONAL_ICQ--#--  ICQ--,--USER_PHONES--#----".GetMessage("main_opt_user_ph")."--,--PERSONAL_PHONE--#--  ".GetMessage("main_opt_user_ph1")."--,--PERSONAL_FAX--#--  ".GetMessage("main_opt_user_fax")."--,--PERSONAL_MOBILE--#--  ".GetMessage("main_opt_user_mobile")."--,--UF_SKYPE--#--  ".GetMessage("main_opt_user_skype")."--,--USER_POST_ADDRESS--#----".GetMessage("main_opt_user_addr")."--,--PERSONAL_COUNTRY--#--  ".GetMessage("main_opt_user_country")."--,--PERSONAL_STATE--#--  ".GetMessage("main_opt_user_reg")."--,--PERSONAL_CITY--#--  ".GetMessage("main_opt_user_city")."--,--PERSONAL_ZIP--#--  ".GetMessage("main_opt_user_zip")."--,--UF_DISTRICT--#--  ".GetMessage("main_opt_user_distr")."--,--PERSONAL_STREET--#--  ".GetMessage("main_opt_user_street")."--,--PERSONAL_MAILBOX--#--  ".GetMessage("main_opt_user_pb")."--,--PERSONAL_NOTES--#--  ".GetMessage("main_opt_user_notes")."--;--edit4--#--".GetMessage("main_opt_user_work")."--,--edit4_csection2--#----".GetMessage("main_opt_user_work_title")."--,--WORK_DEPARTMENT--#--  ".GetMessage("main_opt_user_work_dep")."--,--WORK_POSITION--#--  ".GetMessage("main_opt_user_work_title1")."--,--WORK_PROFILE--#--  ".GetMessage("main_opt_user_work_desc")."--,--USER_WORK_PHONES--#----".GetMessage("main_opt_user_ph")."--,--WORK_PHONE--#--  ".GetMessage("main_opt_user_ph1")."--,--UF_PHONE_INNER--#--  ".GetMessage("main_opt_user_internal_ph")."--,--WORK_FAX--#--  ".GetMessage("main_opt_user_fax")."--,--edit4_csection1--#----".GetMessage("main_opt_user_comp")."--,--WORK_COMPANY--#--  ".GetMessage("main_opt_user_comp_name")."--,--WORK_WWW--#--  ".GetMessage("main_opt_user_www")."--,--WORK_LOGO--#--  ".GetMessage("main_opt_user_comp_logo")."--,--USER_WORK_POST_ADDRESS--#----".GetMessage("main_opt_user_addr")."--,--WORK_COUNTRY--#--  ".GetMessage("main_opt_user_country")."--,--WORK_STATE--#--  ".GetMessage("main_opt_user_reg")."--,--WORK_CITY--#--  ".GetMessage("main_opt_user_city")."--,--WORK_ZIP--#--  ".GetMessage("main_opt_user_zip")."--,--WORK_STREET--#--  ".GetMessage("main_opt_user_street")."--,--WORK_MAILBOX--#--  ".GetMessage("main_opt_user_pb")."--,--WORK_NOTES--#--  ".GetMessage("main_opt_user_notes")."--;--edit5--#--".GetMessage("main_opt_user_blog")."--,--MODULE_TAB_blog--#--  ".GetMessage("main_opt_user_blog")."--;--edit6--#--".GetMessage("main_opt_user_forum")."--,--MODULE_TAB_forum--#--  ".GetMessage("main_opt_user_forum")."--;--edit7--#--".GetMessage("main_opt_user_learning")."--,--MODULE_TAB_learning--#--  ".GetMessage("main_opt_user_learning")."--;--user_fields_tab--#--".GetMessage("main_opt_user_addit")."--,--ACTIVE--#--  ".GetMessage("main_opt_user_active")."--,--user_fields_tab_csection2--#----".GetMessage("main_opt_user_userprop")."--,--USER_FIELDS_ADD--#--  ".GetMessage("main_opt_user_userprop_add")."--,--UF_1C--#--  ".GetMessage("main_opt_user_userprop_1c")."--,--user_fields_tab_csection3--#----".GetMessage("main_opt_user_notify")."--,--LID--#--  ".GetMessage("main_opt_user_notify_site")."--,--user_info_event--#--  ".GetMessage("main_opt_user_notify_do")."--,--user_fields_tab_csection1--#----".GetMessage("main_opt_user_admin")."--,--ADMIN_NOTES--#--  ".GetMessage("main_opt_user_admin")."--;--"
), $common = true);

//public panel
if(COption::GetOptionString("main", "show_panel_for_users", "") == '')
{
	COption::SetOptionString("main", "show_panel_for_users", serialize(array("G".WIZARD_ADMIN_SECTION_GROUP)));
	COption::SetOptionString("main", "hide_panel_for_users", serialize(array("G2")));
}

COption::SetOptionString("webdav", "webdav_allow_ext_doc_services_global", "Y");
COption::SetOptionString("webdav", "webdav_allow_ext_doc_services_local", "Y");

COption::SetOptionString('bizproc', 'log_skip_types', '1,2');

COption::SetOptionString('mail', 'disable_log', 'Y');
COption::SetOptionString('mail', 'connect_timeout', 20);

//used in the custom_mail() in the init.php
COption::SetOptionString("subscribe", "mail_additional_parameters", "CRM");

\Bitrix\Main\Config\Option::set('bizproc', 'delay_min_limit', 600);

if (!IsModuleInstalled("bitrix24"))
{
	COption::SetOptionString("intranet", "show_menu_preset_popup", "Y");
}

COption::SetOptionString("main", "~new_license14_5_sign", "Y");
COption::SetOptionString("main", "~new_license14_9_2_sign", "Y");
COption::SetOptionString("main", "~new_license17_5_sign", "Y");

if (IsModuleInstalled("bitrix24"))
{
	COption::SetOptionString("bizproc", "limit_simultaneous_processes", 2);
	COption::SetOptionString("bitrix24", "admin_limits_enabled", "Y");
	COption::SetOptionString("bitrix24", "absence_limits_enabled", "Y");
	COption::SetOptionString("bitrix24", "business_tools_available", "N");
	COption::SetOptionString("bitrix24", "allow_invite_users", "Y");
}
Bitrix\Main\Config\Option::set("main", "move_js_to_body", "Y");

\Bitrix\Main\Config\Option::set('crm', 'crm_lead_enabled_show', "Y");

// mail
\Bitrix\Main\Config\Option::set('intranet', 'path_mail_config', WIZARD_SITE_DIR.'mail/config/edit?id=#id#', WIZARD_SITE_ID);
\Bitrix\Main\Config\Option::set('intranet', 'path_mail_client', WIZARD_SITE_DIR.'mail/', WIZARD_SITE_ID);

if (\Bitrix\Main\ModuleManager::isModuleInstalled('bitrix24'))
{
	if (defined('B24_LANGUAGE_ID') && in_array(B24_LANGUAGE_ID, ['fr', 'de', 'pl', 'br', 'eu']))
	{
		\Bitrix\Main\Config\Option::set('main', 'track_outgoing_emails_read', 'N');
		\Bitrix\Main\Config\Option::set('main', 'track_outgoing_emails_click', 'N');
	}
}
