<?php
namespace Bitrix\Crm\Agent\Duplicate\Background;

use Bitrix\Main\Type\DateTime;
use CTimeZone;

class Helper
{
	public static function getInstance(): Helper
	{
		static $instance = null;

		if ($instance === null)
		{
			$instance = new static();
		}

		return $instance;
	}

	public function getAgentClassName(string $entityTypeName, string $agentName): string
	{
		$result = '';

		if (in_array($entityTypeName, ['LEAD', 'COMPANY', 'CONTACT'], true))
		{
			$result =
				'Bitrix\\Crm\\Agent\\Duplicate\\Background\\'
				. ucfirst(strtolower($entityTypeName))
				. $agentName
			;
		}

		return $result;
	}

	public function getAgentState(int $userId, string $entityTypeName, string $agentName): array
	{
		/** @var IndexRebuild|Merge $agentClassName */
		$agentClassName = $this->getAgentClassName($entityTypeName, $agentName);
		$agent = $agentClassName::getInstance($userId);

		$state = ['IS_ACTIVE' => $agent->isActive() ? 'Y' : 'N'];
		$state += $agent->state()->getData();
		$state['STATUS'] = $agent->getStatusCode($state['STATUS']);
		$state['NEXT_STATUS'] = $agent->getStatusCode($state['NEXT_STATUS']);
		$state['DATETIME'] = DateTime::createFromTimestamp(
			$state['TIMESTAMP'] - CTimeZone::GetOffset($userId)
		)->toString();

		return $state;
	}
}