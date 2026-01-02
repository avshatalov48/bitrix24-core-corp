<?php

namespace Bitrix\Tasks\Flow\Provider\Member;

use Bitrix\Tasks\Flow\Distribution\FlowDistributionType;
use Bitrix\Tasks\Flow\Internal\Entity\Role;
use Bitrix\Tasks\Flow\Provider\Exception\ProviderException;
use Bitrix\Tasks\Flow\Provider\Member\Trait\FlowMemberTrait;
use Bitrix\Tasks\Flow\Provider\Member\Trait\FlowTeamCountTrait;

class QueueFlowMemberProvider extends AbstractFlowMemberProvider
{
	use FlowTeamCountTrait;
	use FlowMemberTrait;

	public function getResponsibleRole(): Role
	{
		return Role::QUEUE_ASSIGNEE;
	}

	public function getDistributionType(): FlowDistributionType
	{
		return FlowDistributionType::QUEUE;
	}

	/**
	 * @throws ProviderException
	 * @return string[]
	 * Return [those selected in the flow team selector to receive tasks]
	 */
	public function getResponsibleAccessCodes(int $flowId): array
	{
		return $this->getMemberAccessCodesByRole($this->getResponsibleRole(), $flowId);
	}

	/**
	 * @throws ProviderException
	 * @return string[]
	 * Return [those selected in the flow team selector to receive tasks]
	 */
	public function getTeamAccessCodes(int $flowId, ?int $offset = null, ?int $limit = null): array
	{
		return $this->getMemberAccessCodesByRole($this->getResponsibleRole(), $flowId, $offset, $limit);
	}
}