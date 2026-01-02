<?php
/** @global CMain $APPLICATION */
/** @global CUser $USER */

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Intranet\Internal\Entity\Portal\Template;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

\Bitrix\Main\Localization\Loc::loadLanguageFile(__FILE__);

Loader::includeModule('intranet');

\Bitrix\Main\UI\Extension::load([
	'popup',
	'ui.icons',
	'ui.buttons',
	'ui.fonts.roboto',
	'ui.fonts.montserrat',
	'ui.icon-set.actions',
]);

$currentSeasonName = (new Template\CurrentSeason())->getSeasonFromCurrentDate()->value;
?>
<!DOCTYPE html>
<html <?php if (LANGUAGE_ID === 'tr'): ?>lang="<?= LANGUAGE_ID ?>"<?php endif ?>>
<head>
	<meta name="viewport" content="user-scalable=no, initial-scale=1.0, maximum-scale=1.0, width=320">
	<meta http-equiv="Content-Type" content="text/html;charset=<?= SITE_CHARSET ?>"/>
	<link href="/favicon.ico" rel="shortcut icon" type="image/x-icon" />
	<link rel="preload" href="/bitrix/templates/login/images/bg/<?= $currentSeasonName ?>/horizontal-1920-preview.webp" as="image">
	<link rel="preload" href="/bitrix/templates/login/images/bg/<?= $currentSeasonName ?>/horizontal-1024-preview.webp" as="image">
	<link rel="preload" href="/bitrix/templates/login/images/bg/<?= $currentSeasonName ?>/vert-1024-preview.webp" as="image">
	<?php $APPLICATION->ShowCSS(true, true); ?>
	<?php $APPLICATION->ShowHeadStrings(); ?>
	<title><?php $APPLICATION->ShowTitle() ?></title>
</head>

<?php
$seasonCssClassName = "intranet-bg intranet-bg--{$currentSeasonName}";
$logoUrl = \Bitrix\Intranet\Portal::getInstance()->getSettings()->getDefaultLogo()->getWhite();
?>

<body class="<?= $seasonCssClassName ?> <?php $APPLICATION->ShowProperty("BodyClass"); ?>">
<?php
$APPLICATION->SetPageProperty('BodyClass', 'home ' . LANGUAGE_ID);
?>
<div class="intranet-body" id="login-auth-container">
	<div class="intranet-body__header">
		<div class="intranet-body__header-left"></div>
		<div class="intranet-body__header-right">
			<!-- teleport -->
		</div>
	</div>

	<div class="intranet-body__aside intranet-auth-cover">
		<div class="intranet-auth-cover-top">
			<div class="intranet-auth-cover-img-block">
				<a class="intranet-auth-cover__logo" href="<?= SITE_SERVER_NAME ?>">
					<img
						src="<?= htmlspecialcharsbx($logoUrl) ?>"
						class="intranet-auth-cover__logo-image"
						alt=""
					>
				</a>
			</div>
			<div class="intranet-auth-cover-header">
				<?=Loc::getMessage("INTRANET_LOGIN_AIR_PROMO");?>
			</div>
		</div>
	</div>
	<div class="intranet-body__main intranet-auth-form">
		<div class="intranet-body__main-content">


