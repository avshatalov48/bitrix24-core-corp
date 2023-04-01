<?php

namespace Bitrix\Crm\Timeline;

use Bitrix\Crm\Item;
use Bitrix\Crm\Service;
use Bitrix\Crm\Timeline\Traits\FinalSummaryControllerTrait;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\DI\ServiceLocator;

class DynamicController extends FactoryBasedController implements Interfaces\FinalSummaryController
{
	use FinalSummaryControllerTrait;

	public const ADD_EVENT_NAME = 'timeline_dynamic_add';
	public const REMOVE_EVENT_NAME = 'timeline_dynamic_remove';
	public const RESTORE_EVENT_NAME = 'timeline_dynamic_restore';

	protected $entityTypeId;

	protected function __construct(int $entityTypeId)
	{
		parent::__construct();
		$this->entityTypeId = $entityTypeId;
	}

	public static function getInstance(int $entityTypeId = null)
	{
		if ($entityTypeId <= 0)
		{
			throw new ArgumentException('Invalid value for $entityTypeId', 'entityTypeId');
		}

		$identifier = static::getServiceLocatorIdentifier($entityTypeId);

		if (!ServiceLocator::getInstance()->has($identifier))
		{
			$instance = new static($entityTypeId);
			ServiceLocator::getInstance()->addInstance($identifier, $instance);
		}

		return ServiceLocator::getInstance()->get($identifier);
	}

	protected static function getServiceLocatorIdentifier(int $entityTypeId = null): string
	{
		return parent::getServiceLocatorIdentifier() . ".{$entityTypeId}";
	}

	protected function getTrackedFieldNames(): array
	{
		return [
			Item::FIELD_NAME_TITLE,
			Item::FIELD_NAME_ASSIGNED,
			Item::FIELD_NAME_CATEGORY_ID,
			Item::FIELD_NAME_STAGE_ID,
			Item::FIELD_NAME_IS_MANUAL_OPPORTUNITY,
		];
	}

	public function getEntityTypeID(): int
	{
		return $this->entityTypeId;
	}

	protected function isFieldChangeShouldBeRegistered(
		string $fieldName,
		array $previousFields = [],
		array $currentFields = []
	): bool
	{
		if ($fieldName === Item::FIELD_NAME_STAGE_ID)
		{
			$previousCategoryId = $previousFields[Item::FIELD_NAME_CATEGORY_ID];
			$currentCategoryId = $currentFields[Item::FIELD_NAME_CATEGORY_ID];
			if ($previousCategoryId !== $currentCategoryId)
			{
				return false;
			}
		}

		return parent::isFieldChangeShouldBeRegistered($fieldName, $previousFields, $currentFields);
	}

	protected function prepareModificationEntryParams(
		int $entityID,
		array $previousFields,
		array $currentFields,
		string $fieldName
	): array
	{
		if ($fieldName === Item::FIELD_NAME_CATEGORY_ID)
		{
			$prevCategoryId = (int)$previousFields[$fieldName];
			$currentCategoryId = (int)$currentFields[$fieldName];
			$prevStageId = $previousFields[Item::FIELD_NAME_STAGE_ID];
			$currentStageId = $currentFields[Item::FIELD_NAME_STAGE_ID];

			$factory = Service\Container::getInstance()->getFactory($this->getEntityTypeId());
			$prevCategory = $factory ? $factory->getCategory($prevCategoryId) : null;
			$currentCategory = $factory ? $factory->getCategory($currentCategoryId) : null;
			$prevStage = ($factory && $prevStageId) ? $factory->getStage($prevStageId) : null;
			$currentStage = ($factory && $currentStageId) ? $factory->getStage($currentStageId) : null;

			return [
				'ENTITY_TYPE_ID' => $this->getEntityTypeID(),
				'ENTITY_ID' => $entityID,
				'AUTHOR_ID' => $this->resolveAuthorId($currentFields),
				'SETTINGS' => [
					'FIELD' => $fieldName,
					'START_CATEGORY_ID' => $prevCategoryId,
					'FINISH_CATEGORY_ID' => $currentCategoryId,
					'START_STAGE_ID' => $prevStageId,
					'FINISH_STAGE_ID' => $currentStageId,
					'START_CATEGORY_NAME' => $prevCategory ? $prevCategory->getName() : $prevCategoryId,
					'FINISH_CATEGORY_NAME' => $currentCategory ? $currentCategory->getName() : $currentCategoryId,
					'START_STAGE_NAME' => $prevStage ? $prevStage->getName() : $prevStageId,
					'FINISH_STAGE_NAME' => $currentStage ? $currentStage->getName() : $currentStageId,
				],
			];
		}

		return parent::prepareModificationEntryParams($entityID, $previousFields, $currentFields, $fieldName);
	}
}
