<?php
/** @global CMain $APPLICATION */
/** @global CUser $USER */

use Bitrix\Intranet\Integration\Templates\Air\AirTemplate;
use Bitrix\Main\Loader;
use Bitrix\Intranet;
use Bitrix\Intranet\Integration\Templates\Bitrix24\ThemePicker;

define("BX_SKIP_USER_LIMIT_CHECK", true);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

Loader::includeModule('intranet');

$siteTitle = Intranet\Portal::getInstance()->getSettings()->getTitle();
?>
	<!DOCTYPE html>
	<html>
	<head>
		<meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
		<title><?= htmlspecialcharsbx($siteTitle) ?></title>
		<?php

		$APPLICATION->ShowHead(false);
		ThemePicker::getInstance()->showHeadAssets();
		?>
	</head>
	<body class="<?= AirTemplate::getBodyClasses() ?>" id="workarea-content"><?php

	ThemePicker::getInstance()->showBodyAssets();
	$APPLICATION->IncludeComponent(
		'bitrix:intranet.menu',
		'',
		[
			"ROOT_MENU_TYPE" => file_exists($_SERVER['DOCUMENT_ROOT'] . SITE_DIR . '.superleft.menu_ext.php') ? 'superleft' : 'top',
			"MENU_CACHE_TYPE" => "Y",
			"MENU_CACHE_TIME" => "604800",
			"MENU_CACHE_USE_GROUPS" => "N",
			"MENU_CACHE_USE_USERS" => "Y",
			"CACHE_SELECTED_ITEMS" => "N",
			"MENU_CACHE_GET_VARS" => [],
			"MAX_LEVEL" => "1",
			"USE_EXT" => "Y",
			"DELAY" => "N",
			"ALLOW_MULTI_SELECT" => "N",
			"ADD_ADMIN_PANEL_BUTTONS" => "N",
		],
		false
	);
	?>
	</body>
	</html>
<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
