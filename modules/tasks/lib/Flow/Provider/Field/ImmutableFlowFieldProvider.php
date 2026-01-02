<?php

namespace Bitrix\Tasks\Flow\Provider\Field;

class ImmutableFlowFieldProvider implements FlowFieldProviderInterface
{
	public function getModifiedFields(): array
	{
		return [
			'MATCH_WORK_TIME',
			'DEADLINE',
			'GROUP_ID',
			'TASK_CONTROL',
			'ALLOW_CHANGE_DEADLINE',
		];
	}
}