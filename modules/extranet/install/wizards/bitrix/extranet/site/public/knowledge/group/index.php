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
			'SITE_TYPE' => 'GROUP',
			'SHOW_EDIT_PANEL' => 'N',
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
		'bitrix:landing.socialnetwork.group_redirect',
		'',
		array(
		),
		null,
		array(
			'HIDE_ICONS' => 'Y'
		)
	);?>

<?endif;?>

<?require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');?>