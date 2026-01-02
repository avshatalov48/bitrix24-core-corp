<?php

namespace Bitrix\Tasks\Flow\Provider\Field;

class BaseFlowFieldProvider implements FlowFieldProviderInterface
{

	public function getModifiedFields(): array
	{
		return [
			'RESPONSIBLE_ID',
			'MATCH_WORK_TIME',
			'DEADLINE',
			'GROUP_ID',
			'TASK_CONTROL',
			'ALLOW_CHANGE_DEADLINE',
		];
	}
}