<?php

namespace Bitrix\Tasks\Flow\Provider\FlowTeam;

use Bitrix\Tasks\Flow\Flow;
use Bitrix\Tasks\Flow\FlowCollection;

interface FlowTeamProviderInterface
{
	/**
	 * @return int[]
	 */
	public function getTeamMembers(Flow $flow, ?int $offset = null, ?int $limit = null): array;

	/**
	 * @return array [
	 * 		flowId => usersCount
	 * ]
	 */
	public function getTeamCount(FlowCollection $flows): array;
}