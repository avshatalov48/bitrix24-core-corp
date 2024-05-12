<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm\Service\Container;
use Bitrix\Main\Loader;

$langAdditional = [];
if (Loader::includeModule('crm'))
{
	$langAdditional['CRM_COMMON_COPILOT'] = Container::getInstance()
		->getLocalization()
		->loadMessages()['CRM_COMMON_COPILOT']
		?? null
	;
}

return [
	'css' => 'dist/copilot-textarea.bundle.css',
	'js' => 'dist/copilot-textarea.bundle.js',
	'rel' => [
		'main.core.events',
		'main.popup',
		'ai.copilot',
		'main.core',
		'ui.design-tokens',
	],
	'skip_core' => false,
	'lang_additional' => $langAdditional,
];
