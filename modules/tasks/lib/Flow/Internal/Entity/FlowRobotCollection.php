<?php

namespace Bitrix\Tasks\Flow\Internal\Entity;

use Bitrix\Tasks\Flow\Internal\EO_FlowRobot_Collection;
use Bitrix\Tasks\Internals\InsertIgnoreTrait;
use Bitrix\Tasks\Internals\UniqueTrait;

class FlowRobotCollection extends EO_FlowRobot_Collection
{
	use InsertIgnoreTrait;
	use UniqueTrait;

	public function getInsertFields(): array
	{
		return ['FLOW_ID', 'STAGE_ID', 'BIZ_PROC_TEMPLATE_ID', 'STAGE_TYPE', 'ROBOT'];
	}

	public function getInsertValues(): string
	{
		$values = [];
		foreach ($this as $object)
		{
			$values[] = "({$object->getFlowId()}, {$object->getStageId()}, {$object->getBizProcTemplateId()}, '{$object->getStageType()}', '{$object->getRobot()}')";
		}

		return implode(',', $values);
	}
}