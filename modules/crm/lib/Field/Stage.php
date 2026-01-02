<?php

namespace Bitrix\Crm\Field;

use Bitrix\Crm\Field;
use Bitrix\Crm\Item;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Context;
use Bitrix\Crm\Service\Factory;
use Bitrix\Crm\Service\UserPermissions;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

class Stage extends Field
{
	protected function processLogic(Item $item, Context $context = null): Result
	{
		$result = new Result();

		$factory = Container::getInstance()->getFactory($item->getEntityTypeId());
		if (!$factory)
		{
			return $result->addError($this->getFactoryNotFoundError($item->getEntityTypeId()));
		}

		$isCategoryChanged = $factory->isCategoriesSupported() && $item->isChanged(Item::FIELD_NAME_CATEGORY_ID);

		if (!$isCategoryChanged && !$this->isValueChanged($item))
		{
			return $result;
		}

		if ($this->isCurrentStageIdValid($factory, $item))
		{
			return $result;
		}

		if (!$isCategoryChanged && !$item->isNew())
		{
			$remindValue = $item->remindActual($this->getName());
			$item->set($this->getName(), $remindValue);

			return $result;
		}

		$newStageId = $this->pickFirstStageIdInCurrentCategory($factory, $item);
		if (!$newStageId)
		{
			return $result->addError(new Error('Stage in new category is not found'));
		}

		if ($isCategoryChanged || $item->isNew())
		{
			$item->set($this->getName(), $newStageId);

			return $result;
		}

		return $result;
	}

	public function isValueChanged(Item $item): bool
	{
		$fieldName = $this->getName();

		return $item->isNew()
			? $item->getDefaultValue($fieldName) !== $item->get($fieldName)
			: $item->isChanged($fieldName);
	}

	private function isCurrentStageIdValid(Factory $factory, Item $item): bool
	{
		$stageId = $item->get($this->getName());

		$stagesForCurrentCategory = $factory->getStages($item->getCategoryId());

		return in_array($stageId, $stagesForCurrentCategory->getStatusIdList(), true);
	}

	private function pickFirstStageIdInCurrentCategory(Factory $factory, Item $item): ?string
	{
		$currentStage = $factory->getStage((string)$item->get($this->getName()));

		$currentStageSemantics = $currentStage ? $currentStage->getSemantics() : null;
		if (!PhaseSemantics::isDefined($currentStageSemantics))
		{
			$currentStageSemantics = PhaseSemantics::PROCESS;
		}

		$stagesInCurrentCategory = $factory->getStages($item->getCategoryId());

		foreach($stagesInCurrentCategory as $stage)
		{
			if(
				$stage->getSemantics() === $currentStageSemantics
				|| (
					(
						empty($stage->getSemantics())
						|| $stage->getSemantics() === PhaseSemantics::PROCESS
					)
					&&
					(
						empty($currentStageSemantics)
						|| $currentStageSemantics === PhaseSemantics::PROCESS
					)
				)
			)
			{
				return $stage->getStatusId();
			}
		}

		return null;
	}

	public function processWithPermissions(Item $item, UserPermissions $userPermissions): Result
	{
		if (!$item->isNew())
		{
			$needCheckStage = true;

			$newStageId = $item->getStageId();
			$oldStageId = $item->remindActual(Item::FIELD_NAME_STAGE_ID);
			if ($oldStageId === $newStageId)
			{
				$needCheckStage = false;
			}

			if ($needCheckStage && $item->isCategoriesSupported() && ($item->getCategoryId() !== $item->remindActual('CATEGORY_ID')))
			{
				$needCheckStage = false;
			}

			if ($needCheckStage && $oldStageId === null) // item already not exist
			{
				$result = new Result();
				$result->addError(new Error(Loc::getMessage('CRM_TYPE_ITEM_NOT_FOUND')));

				return $result;
			}

			if ($needCheckStage && !$userPermissions->item()->canChangeStage(
				ItemIdentifier::createByItem($item),
				$oldStageId,
				$newStageId,
			))
			{
				$result = new Result();
				$result->addError(new Error(Loc::getMessage('CRM_PERMISSION_STAGE_TRANSITION_NOT_ALLOWED')));

				return $result;
			}
		}

		return parent::processWithPermissions($item, $userPermissions);
	}
}
