<?php

namespace Bitrix\Crm\Field;

use Bitrix\Crm\Field;
use Bitrix\Crm\Item;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Context;
use Bitrix\Main\Error;
use Bitrix\Main\Result;

class Stage extends Field
{
	protected function processLogic(Item $item, Context $context = null): Result
	{
		$result = new Result();

		if ($this->isValueChanged($item))
		{
			$stageId = $item->get($this->getName());
			$stages = Container::getInstance()->getFactory($item->getEntityTypeId())->getStages($item->getCategoryId());
			if (!in_array($stageId, $stages->getStatusIdList(), true))
			{
				$result->addError($this->getValueNotValidError());
			}
		}
		elseif ($item->isChanged(Item::FIELD_NAME_CATEGORY_ID))
		{
			// pick up first stage from new category
			$factory = Container::getInstance()->getFactory($item->getEntityTypeId());
			if(!$factory)
			{
				return $result;
			}

			$newStageId = null;
			$currentStage = $factory->getStage($item->get($this->getName()));
			$currentStageSemantics = $currentStage ? $currentStage->getSemantics() : null;
			foreach($factory->getStages($item->getCategoryId()) as $stage)
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
					$newStageId = $stage->getStatusId();
					break;
				}
			}

			if($newStageId)
			{
				$item->set($this->getName(), $newStageId);
			}
			else
			{
				$result->addError(new Error('Stage in new category is not found'));
			}
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
}
