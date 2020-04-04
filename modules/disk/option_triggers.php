<?php

use Bitrix\Disk\Search\Reindex;
use Bitrix\Main\Event;
use Bitrix\Main\EventManager;
use Bitrix\Main\Loader;

$sent = false;
$sendBroadcastNotify = function () use(&$sent) {
	if (!Loader::includeModule('pull') || !Loader::includeModule('im'))
	{
		return;
	}

	if ($sent)
	{
		return;
	}

	\CPullStack::addBroadcast([
		'module_id' => 'disk',
		'command' => 'notify',
		'params' => [
			'setModuleOption' => true,
		],
	]);

	$sent = true;
};

$eventManager = EventManager::getInstance();

$eventManager->addEventHandler('main', 'OnAfterSetOption_disk_allow_use_external_link', $sendBroadcastNotify);
$eventManager->addEventHandler('main', 'OnAfterSetOption_disk_object_lock_enabled', $sendBroadcastNotify);

$eventManager->addEventHandler('main', 'OnAfterSetOption_disk_keep_version', function(Event $event){
	$value = $event->getParameter('value');
	if ($value === 'N')
	{
		\Bitrix\Main\Config\Option::set('disk', 'disk_version_limit_per_file', 1);
	}
});

$eventManager->addEventHandler('main', 'OnAfterSetOption_disk_version_limit_per_file', function(Event $event){
	$keep = \Bitrix\Main\Config\Option::get('disk', 'disk_keep_version', 'Y') === 'Y';
	$value = (int)$event->getParameter('value');

	if (($value > 1 || $value === 0) && !$keep)
	{
		\Bitrix\Main\Config\Option::set('disk', 'disk_keep_version', 'Y');
	}

	if ($value === 1 && $keep)
	{
		\Bitrix\Main\Config\Option::set('disk', 'disk_keep_version', 'N');
	}
});
/**
 * @see \Bitrix\Disk\Configuration::allowUseExtendedFullText
 * disk_allow_use_extended_fulltext
 */
$eventManager->addEventHandler('main', 'OnAfterSetOption_disk_allow_use_extended_fulltext', function(Event $event){
	$value = $event->getParameter('value');
	if ($value === 'Y')
	{
		Reindex\ExtendedIndex::restartExecution();
	}
	elseif ($value === 'N')
	{
		Reindex\ExtendedIndex::pauseExecution();
	}
});