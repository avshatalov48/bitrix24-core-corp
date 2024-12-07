<?php

namespace Bitrix\Tasks\Flow\Web;

use Bitrix\Main\Request;
use Bitrix\Tasks\Internals\Registry\TaskRegistry;

class FlowRequestService
{
	public function __construct(
		protected ?Request $request = null
	)
	{

	}

	public function getFlowIdFromRequest(int $taskId = 0): int
	{
		if (null === $this->request)
		{
			return 0;
		}

		$flowId = (int)$this->request['FLOW_ID'];
		$noFlow = (bool)$this->request->get('NO_FLOW');
		$sourceId = (int)($this->request->get('COPY') ?? $this->request->get('_COPY')); // help me
		$isCopy = $sourceId > 0;
		$isExistingTask = $taskId > 0;

		if ($flowId <= 0 && !$noFlow && $isExistingTask)
		{
			$flowId = (int)TaskRegistry::getInstance()->getObject($taskId, true)?->getFlowId();
		}

		if ($flowId <= 0 && !$isExistingTask && $isCopy)
		{
			$flowId = (int)TaskRegistry::getInstance()->getObject($sourceId, true)?->getFlowId();
		}

		return $flowId;
	}
}