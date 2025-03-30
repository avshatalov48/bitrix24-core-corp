<?php

/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage extranet
 * @copyright 2001-2024 Bitrix
 */

namespace Bitrix\Extranet;

use Bitrix\Extranet\Enum\User\ExtranetRole;
use Bitrix\Extranet\Service\ServiceContainer;
use Bitrix\Extranet\Settings\CollaberInvitation;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bitrix\Socialnetwork\Collab\CollabFeature;
use Bitrix\Socialnetwork;

class PortalSettings
{
	private static $instances = null;

	final public static function getInstance(): static
	{
		if (self::$instances === null)
		{
			self::$instances = new static();
		}

		return self::$instances;
	}

	public function isCollabEnabled(): bool
	{
		if (Loader::includeModule('socialnetwork') && class_exists(CollabFeature::class))
		{
			return CollabFeature::isOn();
		}

		return Option::get('extranet', 'collaba_enabled', 'N') === 'Y';
	}

	public function isEnabledCollabersInvitation(): bool
	{
		return (new CollaberInvitation())->isEnabled();
	}

	public function canBeDeleted(): bool
	{
		return !ModuleManager::isModuleInstalled('bitrix24');
	}

	public function isModuleToggleable(): bool
	{
		return !$this->isCollabEnabled() || $this->hasExtranetEntities();
	}

	public function isExtranetUsersAvailable(): bool
	{
		return Option::get('bitrix24', 'feature_extranet', 'Y') === 'Y' // if extranet module is installed feature is on by default
			&& $this->hasExtranetEntities();
	}

	private function hasExtranetEntities(): bool
	{
		return $this->hasExtranetUsers() || $this->hasExtranetSocNetGroups();
	}

	private function hasExtranetSocNetGroups(): bool
	{
		if (!Loader::includeModule('socialnetwork'))
		{
			return false;
		}

		$extranetSiteId = \CExtranet::GetExtranetSiteID();
		$res = Socialnetwork\WorkgroupTable::getList([
			'filter' => [
				'=SITES.SITE_ID' => $extranetSiteId,
				'!=TYPE' => 'collab',
			],
			'select' => ['ID'],
			'limit' => 1,
		]);

		return (bool)$res->fetch();
	}

	private function hasExtranetUsers(): bool
	{
		$extranetUserIds = ServiceContainer::getInstance()
			->getUserService()
			->getUserIdsByRole(ExtranetRole::Extranet);

		return !empty($extranetUserIds);
	}
}
