<?php

use Bitrix\Disk\Document\OnlyOffice\OnlyOfficeHandler;
use Bitrix\Disk\Integration\MessengerCall;
use Bitrix\Main\Loader;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

Loader::requireModule('disk');

return [
	'js' => 'dist/disk.onlyoffice-im-integration.bundle.js',
	'rel' => [
		'main.core',
	],
	'skip_core' => false,
	'settings' => [
		'documents' => [
			'is_enabled' => MessengerCall::isEnabledDocuments(),
			'is_available' => MessengerCall::isAvailableDocuments(),
			'infohelper_code' => MessengerCall::getInfoHelperCodeForDocuments(),
		],
		'resumes' => [
			'is_enabled' => MessengerCall::isEnabledResumes(),
			'is_available' => MessengerCall::isAvailableDocuments(),
			'infohelper_code' => MessengerCall::getInfoHelperCodeForResume(),
		],
	],
];