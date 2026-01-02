<?php

namespace Bitrix\HumanResources\Internals\Command\Structure\Node\CreateDepartmentCommand\Strategy;

use Bitrix\HumanResources\Contract\Service\NodeMemberService;
use Bitrix\HumanResources\Item\Node;
use Bitrix\HumanResources\Service\Container;
use Bitrix\HumanResources\Internals\Command\Structure\Node\CreateDepartmentCommand\Result\AddUsersToDepartmentStrategyResult;

class AddUsersStrategy extends BaseAddUsersStrategy
{
	private readonly NodeMemberService $nodeMemberService;

	public function __construct(
		public readonly Node $node,
		public readonly array $userIds = [],
		?NodeMemberService $nodeMemberService = null,
	)
	{
		parent::__construct();

		$this->nodeMemberService = $nodeMemberService ?? Container::getNodeMemberService();
	}

	public function execute(): AddUsersToDepartmentStrategyResult
	{
		$userMovedToRootIds = [];
		$nodeMemberCollection =  $this->nodeMemberService->saveUsersToDepartment($this->node, $this->userIds);

		foreach ($nodeMemberCollection as $nodeMember)
		{
			if ($nodeMember->nodeId === $this->node->id)
			{
				continue;
			}

			$userMovedToRootIds[] = $nodeMember->entityId;
		}

		return new AddUsersToDepartmentStrategyResult(
			$this->node,
			$userMovedToRootIds,
		);
	}
}