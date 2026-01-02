<?php

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Tasks\Deadline\Configuration;
use Bitrix\Tasks\Deadline\Internals\Repository\Cache\Managed\CacheDeadlineUserOptionRepository;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$currentUserId = (int)CurrentUser::get()->getId();

$serviceLocator = ServiceLocator::getInstance();

$deadlineUserOptionRepository = $serviceLocator->get(CacheDeadlineUserOptionRepository::class);
$deadlineUserOption = $deadlineUserOptionRepository->getByUserId($currentUserId);

return [
	'css' => 'dist/menu.bundle.css',
	'js' => 'dist/menu.bundle.js',
	'rel' => [
		'ui.dialogs.messagebox',
		'main.core',
		'main.core.events',
		'main.popup',
		'pull.client',
	],
	'settings' => [
		'isDeadlineNotificationAvailable' => Configuration::isDeadlineNotificationAvailable($currentUserId),
		'skipDeadlineNotificationPeriod' => $deadlineUserOption->skipNotificationPeriod->value,
		'defaultDeadline' => $deadlineUserOption->defaultDeadlineInSeconds,
	],
	'skip_core' => false,
];
