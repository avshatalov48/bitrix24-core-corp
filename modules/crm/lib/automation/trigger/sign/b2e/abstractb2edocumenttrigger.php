<?php

namespace Bitrix\Crm\Automation\Trigger\Sign\B2e;

use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Result;
use Bitrix\Sign\Config\Feature;
use Bitrix\Sign\Config\Storage;
use Bitrix\Crm\Automation;

class AbstractB2eDocumentTrigger extends Automation\Trigger\BaseTrigger
{
	private const SUPPORTED_TYPE_LIST = [
		\CCrmOwnerType::SmartB2eDocument,
	];

	public static function isEnabled(): bool
	{
		return Loader::includeModule('sign')
			&& method_exists(Storage::instance(), 'isB2eAvailable')
			&& Storage::instance()->isB2eAvailable();
	}

	public static function isSupported($entityTypeId): bool
	{
		if (\CCrmOwnerType::isPossibleDynamicTypeId($entityTypeId) && self::isB2eRobotEnabled())
		{
			$factory = Container::getInstance()->getFactory($entityTypeId);

			return
				static::areDynamicTypesSupported()
				&& !is_null($factory)
				&& $factory->isAutomationEnabled()
				&& $factory->isStagesEnabled();
		}

		return in_array($entityTypeId, self::SUPPORTED_TYPE_LIST, true);
	}

	private static function isB2eRobotEnabled(): bool
	{
		if (static::isEnabled() === false)
		{
			return false;
		}

		$feature = Feature::instance();

		if (!method_exists($feature, 'isB2eRobotEnabled'))
		{
			return false;
		}

		return $feature->isB2eRobotEnabled();
	}

	public static function executeBySmartDocumentId(
		int $smartDocumentId,
		array $inputData = null,
	): Result
	{
		$result = new Result();
		if ($smartDocumentId < 1)
		{
			return $result->addError(new Error('Invalid smart document id'));
		}

		$bindings[] = [
			'OWNER_ID' => $smartDocumentId,
			'OWNER_TYPE_ID' => \CCrmOwnerType::SmartB2eDocument,
		];

		$itemIdentifier= new ItemIdentifier(
			\CCrmOwnerType::SmartB2eDocument,
			$smartDocumentId,
		);
		$relatedIdentifierList = Container::getInstance()->getRelationManager()->getParentElements($itemIdentifier);

		foreach ($relatedIdentifierList as $identifier)
		{
			if (\CCrmOwnerType::isPossibleDynamicTypeId($identifier->getEntityTypeId()) === false)
			{
				continue;
			}

			$bindings[] = [
				'OWNER_ID' => $identifier->getEntityId(),
				'OWNER_TYPE_ID' => $identifier->getEntityTypeId(),
			];
		}

		return static::execute($bindings, $inputData);
	}

	public static function getGroup(): array
	{
		return ['paperwork'];
	}

	public static function toArray(): array
	{
		$result = parent::toArray();
		if (
			static::isEnabled()
			&& Loader::includeModule('bitrix24')
			&& !\Bitrix\Bitrix24\Feature::isFeatureEnabled('sign_b2e')
		)
		{
			$result['LOCKED'] = [
				'INFO_CODE' => 'limit_office_e_signature',
			];
		}

		return $result;
	}
}
