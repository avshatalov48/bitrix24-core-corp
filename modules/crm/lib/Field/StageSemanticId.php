<?php

namespace Bitrix\Crm\Field;

use Bitrix\Crm\Field;
use Bitrix\Crm\Item;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Context;
use Bitrix\Main\Result;

class StageSemanticId extends Field
{
	protected function processLogic(Item $item, Context $context = null): Result
	{
		$factory = Container::getInstance()->getFactory($item->getEntityTypeId());
		if ($factory && $factory->isStagesSupported())
		{
			$stage = $factory->getStage($item->getStageId());
			if ($stage)
			{
				$semantics = $stage->getSemantics();
				if (!PhaseSemantics::isDefined($semantics))
				{
					$semantics = PhaseSemantics::PROCESS;
				}

				$item->set($this->getName(), $semantics);
			}
		}

		return new Result();
	}
}
