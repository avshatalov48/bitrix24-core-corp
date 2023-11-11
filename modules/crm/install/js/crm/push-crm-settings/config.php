<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$createTimeAliases = [];

if (\Bitrix\Main\Loader::includeModule('crm'))
{
	$map = \Bitrix\Crm\Service\Container::getInstance()->getTypesMap();
	foreach ($map->getFactories() as $factory)
	{
		$createTimeAliases[$factory->getEntityTypeId()] =
			$factory->getEntityFieldNameByMap(\Bitrix\Crm\Item::FIELD_NAME_CREATED_TIME)
		;
	}
}

return [
	'css' => 'dist/push-crm-settings.bundle.css',
	'js' => 'dist/push-crm-settings.bundle.js',
	'rel' => [
		'main.core.events',
		'crm.activity.todo-notification-skip-menu',
		'crm.activity.todo-ping-settings-menu',
		'main.popup',
		'crm.kanban.sort',
		'crm.kanban.restriction',
		'main.core',
	],
	'skip_core' => false,
	'settings' => [
		'createTimeAliases' => $createTimeAliases,
	],
];
