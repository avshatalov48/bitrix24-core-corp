<?php

use Bitrix\Extranet\Service\ServiceContainer;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Loader;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$currentUserId = (int)CurrentUser::get()->getId();
$isCollaber = Loader::includeModule('extranet')
	&& ServiceContainer::getInstance()->getCollaberService()->isCollaberById($currentUserId);

$defaultCollabId = null;
if ($isCollaber && Loader::includeModule('socialnetwork'))
{
	$defaultCollab = \Bitrix\Socialnetwork\Collab\Provider\CollabDefaultProvider::getInstance()->getCollab($currentUserId);
	$defaultCollabId = $defaultCollab?->getId();
}

$isMobileContinueButton = COption::GetOptionString('intranet', 'allow_continue_button_mobile_popup', 'Y') == 'Y';
$isTabletContinueButton = COption::GetOptionString('intranet', 'allow_continue_button_tablet_popup', 'Y') == 'Y';

$continueInMobileBrowserClicked = $isMobileContinueButton && CUserOptions::GetOption('intranet', 'mobilePopupContinueToApp', 'N') === 'Y';
$continueInTabletBrowserClicked = $isTabletContinueButton && CUserOptions::GetOption('intranet', 'tabletPopupContinueToApp', 'N') === 'Y';

return [
	'css' => 'dist/mobile-popup.bundle.css',
	'js' => 'dist/mobile-popup.bundle.js',
	'rel' => [
		'main.core',
		'main.popup',
		'main.core.events',
		'ui.buttons',
		'ui.analytics',
		'ui.banner-dispatcher',
		'ui.fonts.montserrat',
	],
	'settings' => [
		'authLink' => Loader::includeModule('mobile') ? \Bitrix\Mobile\Deeplink::getAuthLink('mobapp_popup', $currentUserId) : '',
		'collabId' => $defaultCollabId,
		'isCollaber' => $isCollaber,
		'continueInMobileBrowserClicked' => $continueInMobileBrowserClicked,
		'continueInTabletBrowserClicked' => $continueInTabletBrowserClicked,
		'isMobileContinueButton' => $isMobileContinueButton,
		'isTabletContinueButton' => $isTabletContinueButton,
	],
	'skip_core' => false,
];
