<?php
if (isset($_REQUEST['IFRAME']) && $_REQUEST['IFRAME'] == 'Y')
{
	define('SITE_TEMPLATE_ID', 'landing24');
	define('LANDING_PUB_INTRANET_MODE', true);
}

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');?>

<?if (isset($_REQUEST['IFRAME']) && $_REQUEST['IFRAME'] == 'Y'):?>

	<?$APPLICATION->IncludeComponent(
		'bitrix:landing.pub',
		'',
		array(
			'NOT_CHECK_DOMAIN' => 'Y',
			'SITE_TYPE' => 'KNOWLEDGE',
			'PAGE_URL_LANDING_VIEW' => '/kb/wiki/#site_edit#/view/#landing_edit#/',
			'PAGE_URL_SITE_SHOW' => '/kb/wiki/#site_edit#/',
			'PAGE_URL_SITES' => '/kb/',
			'PAGE_URL_ROLES' => '/kb/roles/',
			'SHOW_EDIT_PANEL' => 'Y',
			'CHECK_PERMISSIONS' => 'Y',
			'DRAFT_MODE' => 'Y'
		),
		null,
		array(
			'HIDE_ICONS' => 'Y'
		)
	);?>

<?else:?>

	<?$APPLICATION->IncludeComponent(
		'bitrix:landing.start',
		'.default',
		array(
			'COMPONENT_TEMPLATE' => '.default',
			'SEF_FOLDER' => '/kb/',
			'STRICT_TYPE' => 'Y',
			'SEF_MODE' => 'Y',
			'TYPE' => 'KNOWLEDGE',
			'TILE_LANDING_MODE' => 'view',
			'TILE_SITE_MODE' => 'view',
			'EDIT_FULL_PUBLICATION' => 'Y',
			'EDIT_PANEL_LIGHT_MODE' => 'Y',
			'REOPEN_LOCATION_IN_SLIDER' => 'Y',
			'DRAFT_MODE' => 'Y',
			'SEF_URL_TEMPLATES' => array(
				'domain_edit' => 'domain/edit/#domain_edit#/',
				'domains' => 'domains/',
				'landing_edit' => 'wiki/#site_show#/#landing_edit#/',
				'landing_view' => 'wiki/#site_show#/view/#landing_edit#/',
				'site_edit' => 'wiki/edit/#site_edit#/',
				'site_show' => 'wiki/#site_show#/',
				'sites' => ''
			)
		),
		false
	);
	?>

<?endif;?>

<?require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');?>