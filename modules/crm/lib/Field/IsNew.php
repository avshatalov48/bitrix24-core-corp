<?php

namespace Bitrix\Crm\Field;

use Bitrix\Crm\Field;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Context;
use Bitrix\Main\Result;

class IsNew extends Field
{
	protected function processLogic(Item $item, Context $context = null): Result
	{
		$factory = Container::getInstance()->getFactory($item->getEntityTypeId());

		if($factory && $factory->isStagesSupported())
		{
			$stages = $factory->getStages($item->getCategoryId())->getAll();
			$firstStage = reset($stages);

			$isNew = ($firstStage && $firstStage->getStatusId() === $item->getStageId());

			$item->set($this->getName(), $isNew);
		}

		return new Result();
	}
}
