<?php

namespace Bitrix\Tasks\Flow\Responsible\ResponsibleQueue;

class ResponsibleQueueProvider
{
	private ResponsibleQueueRepository $responsibleQueueRepository;

	public function __construct()
	{
		$this->responsibleQueueRepository = new ResponsibleQueueRepository();
	}

	public function getResponsibleQueue(int $flowId): ResponsibleQueue
	{
		return $this->responsibleQueueRepository->get($flowId);
	}
}