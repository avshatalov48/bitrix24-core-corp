<?php
if (
	isset($_REQUEST['IFRAME']) && $_REQUEST['IFRAME'] == 'Y' ||
	isset($_REQUEST['landing_mode']) && $_REQUEST['landing_mode'] == 'edit'
)
{
	define('SITE_TEMPLATE_ID', 'landing24');
}

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');

if (SITE_TEMPLATE_ID !== 'landing24')
{
	localRedirect(SITE_DIR);
}
?>

<?$APPLICATION->IncludeComponent(
	'bitrix:landing.start',
	'.default',
	array(
		'COMPONENT_TEMPLATE' => '.default',
		'SEF_FOLDER' => SITE_DIR . 'kb/group/',
		'STRICT_TYPE' => 'Y',
		'SEF_MODE' => 'Y',
		'TYPE' => 'GROUP',
		'TILE_LANDING_MODE' => 'view',
		'TILE_SITE_MODE' => 'view',
		'EDIT_FULL_PUBLICATION' => 'Y',
		'EDIT_PANEL_LIGHT_MODE' => 'Y',
		'EDIT_DONT_LEAVE_FRAME' => 'Y',
		'DRAFT_MODE' => 'Y',
		'SEF_URL_TEMPLATES' => array(
			'landing_view' => 'wiki/#site_show#/view/#landing_edit#/',
			'domain_edit' => '',
			'domains' => '',
			'landing_edit' => 'wiki/#site_show#/#landing_edit#/',
			'site_edit' => 'wiki/edit/#site_edit#/',
			'site_show' => 'wiki/#site_show#/',
			'sites' => '',
			'roles' => '',
			'role_edit' => ''
		)
	),
	false
);
?>

<?require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');?>