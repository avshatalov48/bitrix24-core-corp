<?php

use Bitrix\Disk\Document\OnlyOffice\LimitedEdit;
use Bitrix\Disk\Integration\Bitrix24Manager;
use Bitrix\Disk\Search\Reindex;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Event;
use Bitrix\Main\EventManager;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;

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
		Option::set('disk', 'disk_version_limit_per_file', 1);
	}
});

$eventManager->addEventHandler('main', 'OnAfterSetOption_disk_version_limit_per_file', function(Event $event){
	$keep = Option::get('disk', 'disk_keep_version', 'Y') === 'Y';
	$value = (int)$event->getParameter('value');

	if (($value > 1 || $value === 0) && !$keep)
	{
		Option::set('disk', 'disk_keep_version', 'Y');
	}

	if ($value === 1 && $keep)
	{
		Option::set('disk', 'disk_keep_version', 'N');
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


//option set: edit-time-limited-onlyoffice-access
$eventManager->addEventHandler('main', 'OnAfterSetOption_edit-time-limited-onlyoffice-access', function(Event $event){
	if (!ModuleManager::isModuleInstalled('bitrix24'))
	{
		return;
	}

	if (!Bitrix24Manager::isFeatureEnabled('disk_onlyoffice_trial_edit'))
	{
		return;
	}

	$configuration = new LimitedEdit\Configuration();
	$value = $event->getParameter('value');
	if ($value === 'N')
	{
		$configuration->disableLimitEdit();

		return;
	}
	if ($value === 'Y')
	{
		if ($configuration->isLimitEditEnabled())
		{
			return;
		}
		if ($configuration->wasLimitEditDisabled())
		{
			// it means that we already have disabled time limited edit and we should not enable it again.
			return;
		}

		$configuration->enableLimitEdit();
	}
});
