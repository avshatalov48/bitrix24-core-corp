<?php

namespace Bitrix\Tasks\Flow\Notification\Command;

class ForcedManualDistributorChangeCommand
{
	private int $flowId;

	public function __construct(int $flowId)
	{
		$this->flowId = $flowId;
	}

	public function getFlowId(): int
	{
		return $this->flowId;
	}
}