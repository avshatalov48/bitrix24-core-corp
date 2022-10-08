<?php

namespace Bitrix\Crm\Settings;

use Bitrix\Crm\Relation;
use Bitrix\Crm\RelationIdentifier;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Loader;

class Crm
{
	private const OPTION_MODULE = 'crm';
	private const OPTION_NAME = 'WAS_INITED';

	private const UNIVERSAL_ACTIVITY_OPTION_NAME = 'UNIVERSAL_ACTIVITY_ENABLED';
	private const DOCUMENT_SIGNING_OPTION_NAME = 'DOCUMENTS_SIGNING_ENABLED';

	public static function wasInitiated(): bool
	{
		return (bool)\Bitrix\Main\Config\Option::get(self::OPTION_MODULE, self::OPTION_NAME, false);
	}

	public static function markAsInitiated(): void
	{
		if (!self::wasInitiated())
		{
			\Bitrix\Main\Config\Option::set(self::OPTION_MODULE, self::OPTION_NAME, true);
			$GLOBALS['CACHE_MANAGER']->ClearByTag('crm_initiated');
			\Bitrix\Crm\Integration\PullManager::getInstance()->sendCrmInitiatedEvent();
		}
	}

	public static function isUniversalActivityScenarioEnabled(): bool
	{
		return (bool)\Bitrix\Main\Config\Option::get(self::OPTION_MODULE, self::UNIVERSAL_ACTIVITY_OPTION_NAME, false);
	}

	public static function setUniversalActivityScenarioEnabled(bool $isEnabled): void
	{
		\Bitrix\Main\Config\Option::set(self::OPTION_MODULE, self::UNIVERSAL_ACTIVITY_OPTION_NAME, $isEnabled);
	}

	public static function isDocumentSigningEnabled(): bool
	{
		return (
			Loader::includeModule('sign')
			&& (bool)\Bitrix\Main\Config\Option::get(self::OPTION_MODULE, self::DOCUMENT_SIGNING_OPTION_NAME, false)
		);
	}

	public static function setDocumentSigningEnabled(bool $isEnabled): void
	{
		\Bitrix\Main\Config\Option::set(self::OPTION_MODULE, self::DOCUMENT_SIGNING_OPTION_NAME, $isEnabled);

		$relationManager = Container::getInstance()->getRelationManager();
		$relationIdentifier = new RelationIdentifier(\CCrmOwnerType::Deal, \CCrmOwnerType::SmartDocument);

		if ($isEnabled)
		{
			$relationManager->bindTypes(
				new Relation(
					$relationIdentifier,
					(new Relation\Settings())
						->setRelationType(Relation\RelationType::CONVERSION)
						->setIsChildrenListEnabled(false)
					,
				)
			);
		}
		else
		{
			$relationManager->unbindTypes($relationIdentifier);
		}
	}
}
