<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$settings = [];
$langAdditional = [];
if (\Bitrix\Main\Loader::includeModule('crm'))
{
	$typesMap = \Bitrix\Crm\Service\Container::getInstance()->getTypesMap();
	foreach ($typesMap->getFactories() as $factory)
	{
		if (in_array($factory->getEntityTypeId(), [\CCrmOwnerType::Contact, \CCrmOwnerType::Company], true))
		{
			// contact/company categories are hidden from ui
			$settings['isCategoriesEnabled'][$factory->getEntityTypeId()] = false;
		}
		else
		{
			$settings['isCategoriesEnabled'][$factory->getEntityTypeId()] = $factory->isCategoriesEnabled();
		}
	}

	$langAdditional = \Bitrix\Crm\Service\Container::getInstance()->getLocalization()->loadMessages();
}

return [
	'js' => '/bitrix/js/crm/conversion/dist/conversion.bundle.js',
	'skip_core' => false,
	'rel' => [
		'crm.integration.analytics',
		'ui.analytics',
		'ui.dialogs.messagebox',
		'ui.forms',
		'crm.category-model',
		'ui.buttons',
		'ui.entity-selector',
		'main.core',
		'main.core.events',
		'main.popup',
	],
	'settings' => $settings,
	'lang_additional' => $langAdditional,
];
