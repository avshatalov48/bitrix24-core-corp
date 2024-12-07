<?php

namespace Bitrix\Tasks\Flow\Provider;

use Bitrix\Main\SystemException;
use Bitrix\Tasks\Flow\Internal\Entity\FlowRobotCollection;
use Bitrix\Tasks\Flow\Internal\FlowRobotTable;
use Bitrix\Tasks\Flow\Provider\Exception\ProviderException;
use Bitrix\Tasks\Internals\Log\Logger;

class RobotProvider
{
	/**
	 * @throws ProviderException
	 */
	public function getAutoCreatedRobots(int $flowId): FlowRobotCollection
	{
		try
		{
			return FlowRobotTable::query()
				->setSelect(['ID', 'FLOW_ID', 'STAGE_ID', 'ROBOT', 'STAGE_TYPE'])
				->where('FLOW_ID', $flowId)
				->exec()
				->fetchCollection();
		}
		catch (SystemException $e)
		{
			Logger::log($e->getMessage());
			throw new ProviderException('Cannot get robots');
		}
	}
}