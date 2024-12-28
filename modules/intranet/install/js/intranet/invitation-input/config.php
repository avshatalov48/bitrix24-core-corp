<?php

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/invitation-input.bundle.css',
	'js' => 'dist/invitation-input.bundle.js',
	'rel' => [
		'main.core.cache',
		'main.core.events',
		'ui.entity-selector',
		'main.core',
		'phone_number',
	],
	'skip_core' => false,
	'settings' => [
		'isInvitationByPhoneAvailable' => Loader::includeModule("bitrix24")
			&& Option::get('bitrix24', 'phone_invite_allowed', 'N') === 'Y',
	],
];
