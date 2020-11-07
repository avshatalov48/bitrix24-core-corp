<?php
namespace PHPSTORM_META
{
	registerArgumentsSet('bitrix_disk_serviceLocator_codes',
		'disk.urlManager',
		'disk.documentHandlersManager',
		'disk.rightsManager',
		'disk.ufManager',
		'disk.indexManager',
		'disk.recentlyUsedManager',
		'disk.restManager',
		'disk.subscriberManager',
		'disk.deletedLogManager',
		'disk.deletionNotifyManager'
	);

	expectedArguments(\Bitrix\Main\DI\ServiceLocator::get(), 0, argumentsSet('bitrix_disk_serviceLocator_codes'));

	override(\Bitrix\Main\DI\ServiceLocator::get(0), map([
        'disk.urlManager' => \Bitrix\Disk\UrlManager::class,
        'disk.documentHandlerManager' => \Bitrix\Disk\Document\DocumentHandlersManager::class,
		'disk.rightsManager' => \Bitrix\Disk\RightsManager::class,
		'disk.ufManager' => \Bitrix\Disk\Uf\UserFieldManager::class,
		'disk.indexManager' => \Bitrix\Disk\Search\IndexManager::class,
		'disk.recentlyUsedManager' => \Bitrix\Disk\RecentlyUsedManager::class,
		'disk.restManager' => \Bitrix\Disk\Rest\RestManager::class,
		'disk.subscriberManager' => \Bitrix\Disk\Bitrix24Disk\SubscriberManager::class,
		'disk.deletedLogManager' => \Bitrix\Disk\Internals\DeletedLogManager::class,
		'disk.deletionNotifyManager' => \Bitrix\Disk\Internals\DeletionNotifyManager::class,
    ]));
}