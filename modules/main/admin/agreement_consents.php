<?php
/**
 * @global CUser $USER
 * @global CMain $APPLICATION
 */
require_once(__DIR__."/../include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/prolog.php");
define("HELP_FILE", "settings/agreement_consents.php");

$canEdit = $USER->CanDoOperation('edit_other_settings');
$canView = $USER->CanDoOperation('view_other_settings');
if(!$canEdit && !$canView)
{
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
}

global $adminPage;
$adminPage->hideTitle();

require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/prolog_admin_after.php");

global $adminSidePanelHelper;

$componentParameters = [
	'PATH_TO_USER_PROFILE' => BX_ROOT . '/admin/user_edit.php?ID=#id#&lang=' . LANGUAGE_ID,
	'PATH_TO_LIST' => BX_ROOT . '/admin/agreement_admin.php?lang=' . LANGUAGE_ID,
	'PATH_TO_ADD' => BX_ROOT . '/admin/agreement_edit.php?ID=0&lang=' . LANGUAGE_ID,
	'PATH_TO_EDIT' => BX_ROOT . '/admin/agreement_edit.php?ID=#id#&lang=' . LANGUAGE_ID,
	'PATH_TO_CONSENT_LIST' => BX_ROOT . '/admin/agreement_consents.php?AGREEMENT_ID=#id#&lang=' . LANGUAGE_ID,
	'CAN_EDIT' => $canEdit
];

if ($adminSidePanelHelper->isSidePanel())
{
	$APPLICATION->IncludeComponent(
		'bitrix:ui.sidepanel.wrapper',
		'',
		[
			'POPUP_COMPONENT_NAME' => 'bitrix:main.userconsent.consent.list',
			'POPUP_COMPONENT_TEMPLATE_NAME' => '',
			'POPUP_COMPONENT_PARAMS' => $componentParameters,
			'USE_UI_TOOLBAR' => 'Y'
		]
	);
}
else
{
	$componentParameters['ADMIN_MODE'] = true;

	$APPLICATION->IncludeComponent(
		'bitrix:main.userconsent.consent.list',
		'',
		$componentParameters
	);
}

require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_admin.php");
