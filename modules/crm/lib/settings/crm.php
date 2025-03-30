<?php

namespace Bitrix\Crm\Settings;

use Bitrix\Crm\Relation;
use Bitrix\Crm\RelationIdentifier;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Application;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Result;
use Bitrix\Main\UserTable;

class Crm
{
	private const OPTION_MODULE = 'crm';
	private const OPTION_NAME = 'WAS_INITED';

	private const DOCUMENT_SIGNING_OPTION_NAME = 'DOCUMENTS_SIGNING_ENABLED';
	private const LF_GENERATION_OPTION_NAME = 'LIVE_FEED_RECORDS_GENERATION_ENABLED';
	private const WHATSAPP_SCENARIO_OPTION_NAME = 'WHATSAPP_SCENARIO_ENABLED';
	private const AUTOMATED_SOLUTION_LIST_OPTION_NAME = 'AUTOMATED_SOLUTION_LIST_ENABLED';
	private const WHATSAPP_GOTOCHAT_OPTION_NAME = 'WHATSAPP_GOTOCHAT_ENABLED';

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

		$logErrors = static function (Result $result) use ($isEnabled): void {
			AddMessage2Log(
				[
					'message' => $isEnabled
						? 'Error adding DEAL-SMART_DOCUMENT relation when enabling signing'
						: 'Error deleting DEAL-SMART_DOCUMENT relation when disabling signing'
					,
					'errors' => $result->getErrors(),
				],
				'crm',
			);
		};

		$logWarning = static function (Result $result) use ($isEnabled): void {
			AddMessage2Log(
				[
					'message' => $isEnabled
						? 'Warning adding DEAL-SMART_DOCUMENT relation when enabling signing'
						: 'Warning deleting DEAL-SMART_DOCUMENT relation when disabling signing'
					,
					'errors' => $result->getErrors(),
				],
				'crm',
			);
		};

		$connection = Application::getConnection();
		if (!$connection->lock('crm_set_document_singing_enabled'))
		{
			$result = (new Result())->addError(new Error('Could not acquire lock'));

			$logErrors($result);

			return;
		}

		$relationManager = Container::getInstance()->getRelationManager();

		// clean both runtime cache and ORM cache, since maybe the table was modified on a parallel hit
		$relationManager->cleanRelationsCache();

		$relationIdentifier = new RelationIdentifier(\CCrmOwnerType::Deal, \CCrmOwnerType::SmartDocument);
		if ($isEnabled)
		{
			$result = $relationManager->bindTypes(
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
			$result = $relationManager->unbindTypes($relationIdentifier);
		}

		if (
			$result->isSuccess()
			// those errors are kinda okay
			|| $result->getErrorCollection()->getErrorByCode(Relation\RelationManager::ERROR_CODE_BIND_TYPES_TYPES_ALREADY_BOUND)
			|| $result->getErrorCollection()->getErrorByCode(Relation\RelationManager::ERROR_CODE_UNBIND_TYPES_TYPES_NOT_BOUND)
		)
		{
			if (!$result->isSuccess())
			{
				$logWarning($result);
			}

			\Bitrix\Main\Config\Option::set(self::OPTION_MODULE, self::DOCUMENT_SIGNING_OPTION_NAME, $isEnabled);
		}
		else
		{
			// could not create/delete relation - dont set option
			$logErrors($result);
		}

		$connection->unlock('crm_set_document_singing_enabled');
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

	/**
	 * @deprecated Do not use, will be removed soon
	 */
	public static function isWhatsAppScenarioEnabled(): bool
	{
		return true;
	}

	/**
	 * @deprecated Do not use, will be removed soon
	 */
	public static function setWhatsAppScenarioEnabled(bool $isEnabled): void
	{
		\Bitrix\Main\Config\Option::set(
			self::OPTION_MODULE,
			self::WHATSAPP_SCENARIO_OPTION_NAME,
			$isEnabled
		);
	}

	/**
	 * @deprecated Do not use, will be removed soon
	 */
	public static function isAutomatedSolutionListEnabled(): bool
	{
		return true;
	}

	/**
	 * @deprecated Do not use, will be removed soon
	 */
	public static function setAutomatedSolutionListEnabled(bool $isEnabled): void
	{
		\Bitrix\Main\Config\Option::set(
			self::OPTION_MODULE,
			self::AUTOMATED_SOLUTION_LIST_OPTION_NAME,
			$isEnabled
		);
	}

	/**
	 * @deprecated Do not use, will be removed soon
	 */
	public static function isWhatsAppGoToChatEnabled(): bool
	{
		return true;
	}

	/**
	 * @deprecated Do not use, will be removed soon
	 */
	public static function setWhatsAppGoToChatEnabled(bool $value): void
	{
		\Bitrix\Main\Config\Option::set(
			self::OPTION_MODULE,
			self::WHATSAPP_GOTOCHAT_OPTION_NAME,
			$value
		);
	}

	public static function isBoxOrEtalon(): bool
	{
		return self::isBox() || self::isEtalon();
	}

	public static function isBox(): bool
	{
		return !Loader::includeModule('bitrix24');
	}

	public static function isEtalon(): bool
	{
		return Loader::includeModule('bitrix24') && \CBitrix24::isEtalon();
	}

	public static function isPortalCreatedBefore(int $targetTimestamp): bool
	{
		return static::getPortalCreatedTimestamp() < $targetTimestamp;
	}

	public static function getPortalCreatedTimestamp(): int
	{
		if (Loader::includeModule('bitrix24'))
		{
			$createdTime = \CBitrix24::getCreateTime();
			if ($createdTime)
			{
				return (int)$createdTime;
			}
		}

		$ttl = 60 * 60 * 24 * 365;
		$cacheId = 'crm_settings_portal_created_timestamp';

		$cacheManager = Application::getInstance()->getManagedCache();
		if ($cacheManager->read($ttl, $cacheId))
		{
			return (int)$cacheManager->get($cacheId);
		}

		$createdTimestamp = UserTable::query()
			->setSelect(['ID', 'DATE_REGISTER'])
			->where('ID', 1)
			->setLimit(1)
			->fetchObject()
			->getDateRegister()
			->getTimestamp();

		$cacheManager->set($cacheId, $createdTimestamp);

		return $createdTimestamp;
	}
}
