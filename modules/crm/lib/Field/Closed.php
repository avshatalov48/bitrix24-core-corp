<?php

namespace Bitrix\Crm\Field;

use Bitrix\Crm\Field;
use Bitrix\Crm\Item;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Context;
use Bitrix\Main\Result;

class Closed extends Field
{
	protected function processLogic(Item $item, Context $context = null): Result
	{
		$factory = Container::getInstance()->getFactory($item->getEntityTypeId());
		if($factory && $factory->isStagesEnabled())
		{
			$stage = $factory->getStage($item->getStageId());
			if($stage)
			{
				$item->set($this->getName(), PhaseSemantics::isFinal($stage->getSemantics()));
			}
		}

		return new Result();
	}
}