<?php

namespace Bitrix\Crm\Settings;

use Bitrix\Crm\Relation;
use Bitrix\Crm\RelationIdentifier;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\Loader;

class Crm
{
	private const OPTION_MODULE = 'crm';
	private const OPTION_NAME = 'WAS_INITED';

	private const UNIVERSAL_ACTIVITY_OPTION_NAME = 'UNIVERSAL_ACTIVITY_ENABLED';
	private const DOCUMENT_SIGNING_OPTION_NAME = 'DOCUMENTS_SIGNING_ENABLED';
	private const LF_GENERATION_OPTION_NAME = 'LIVE_FEED_RECORDS_GENERATION_ENABLED';
	private const TIMELINE_TODO_CALENDAR_SYNC_OPTION_NAME = 'TIMELINE_TODO_CALENDAR_SYNC_ENABLED';

	public static function wasInitiated(): bool
	{
		return (bool)\Bitrix\Main\Config\Option::get(self::OPTION_MODULE, self::OPTION_NAME, false);
	}

	public static function markAsInitiated(): void
	{
		if (!self::wasInitiated())
		{
			$pullManager = \Bitrix\Crm\Integration\PullManager::getInstance();

			if ($pullManager->isEnabled())
			{
				$channelShared = $pullManager->getChannelShared();
				if (is_array($channelShared))
				{
					self::setInitiatedOption();
					$pullManager->sendCrmInitiatedEvent($channelShared);
				}
			}
			else
			{
				self::setInitiatedOption();
			}
		}
	}

	public static function isMobileDynamicTypesEnabled(): bool
	{
		return (bool)\Bitrix\Main\Config\Option::get('main', 'mobile_crm_dynamic_types_is_active', true);
	}

	private static function setInitiatedOption(): void
	{
		\Bitrix\Main\Config\Option::set(self::OPTION_MODULE, self::OPTION_NAME, true);
		$GLOBALS['CACHE_MANAGER']->ClearByTag('crm_initiated');
	}

	/**
	 * @deprecated Do not use, will be removed soon
	 */
	public static function isUniversalActivityScenarioEnabled(): bool
	{
		return true;
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
		if ($isEnabled === (bool)\Bitrix\Main\Config\Option::get(self::OPTION_MODULE, self::DOCUMENT_SIGNING_OPTION_NAME, false))
		{
			return;
		}

		\Bitrix\Main\Config\Option::set(self::OPTION_MODULE, self::DOCUMENT_SIGNING_OPTION_NAME, $isEnabled);

		$relationManager = Container::getInstance()->getRelationManager();
		$relationIdentifier = new RelationIdentifier(\CCrmOwnerType::Deal, \CCrmOwnerType::SmartDocument);

		if ($isEnabled)
		{
			if (!$relationManager->areTypesBound($relationIdentifier))
			{
				try
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
				catch (SqlQueryException $e)
				{
					if (mb_strpos($e->getMessage(), 'Duplicate entry') === false)
					{
						throw $e;
					}
				}
			}
		}
		else
		{
			$relationManager->unbindTypes($relationIdentifier);
		}
	}

	public static function isLiveFeedRecordsGenerationEnabled(): bool
	{
		return (
			Loader::includeModule('socialnetwork')
			&& (bool)\Bitrix\Main\Config\Option::get(self::OPTION_MODULE, self::LF_GENERATION_OPTION_NAME, true)
		);
	}

	public static function setLiveFeedRecordsGenerationEnabled(bool $isEnabled): void
	{
		\Bitrix\Main\Config\Option::set(self::OPTION_MODULE, self::LF_GENERATION_OPTION_NAME, $isEnabled);
	}

	public static function isTimelineToDoCalendarSyncEnabled(): bool
	{
		return (bool)\Bitrix\Main\Config\Option::get(
			self::OPTION_MODULE,
			self::TIMELINE_TODO_CALENDAR_SYNC_OPTION_NAME,
			false
		);
	}

	public static function setTimelineToDoCalendarSyncEnabled(bool $isEnabled): void
	{
		\Bitrix\Main\Config\Option::set(
			self::OPTION_MODULE,
			self::TIMELINE_TODO_CALENDAR_SYNC_OPTION_NAME,
			$isEnabled
		);
	}
}
