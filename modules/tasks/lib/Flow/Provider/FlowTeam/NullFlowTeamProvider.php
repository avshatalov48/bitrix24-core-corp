<?php

namespace Bitrix\Tasks\Flow\Provider\FlowTeam;

use Bitrix\Tasks\Flow\Flow;
use Bitrix\Tasks\Flow\FlowCollection;

class NullFlowTeamProvider implements FlowTeamProviderInterface
{
	public function getTeamMembers(Flow $flow, ?int $offset = null, ?int $limit = null): array
	{
		return [];
	}

	public function getTeamCount(FlowCollection $flows): array
	{
		return [];
	}
}