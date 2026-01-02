<?php

namespace Bitrix\HumanResources\Internals\Command\Structure\Node\CreateDepartmentCommand\Strategy;

use Bitrix\HumanResources\Contract\Repository\NodeMemberRepository;
use Bitrix\HumanResources\Contract\Service\NodeMemberService;
use Bitrix\HumanResources\Item\Node;
use Bitrix\HumanResources\Service\Container;
use Bitrix\HumanResources\Type\MemberEntityType;
use Bitrix\HumanResources\Type\NodeEntityType;
use Bitrix\HumanResources\Internals\Command\Structure\Node\CreateDepartmentCommand\Result\MoveUsersToDepartmentStrategyResult;
use Bitrix\Main\Validation;

class MoveUsersStrategy extends BaseAddUsersStrategy
{
	private readonly NodeMemberService $nodeMemberService;
	private readonly NodeMemberRepository $nodeMemberRepository;

	public function __construct(
		public readonly Node $node,
		public readonly array $userIds = [],
		?NodeMemberService $nodeMemberService = null,
		?NodeMemberRepository $nodeMemberRepository = null,
	)
	{
		parent::__construct();

		$this->nodeMemberService = $nodeMemberService ?? Container::getNodeMemberService();
		$this->nodeMemberRepository = $nodeMemberRepository ?? Container::getNodeMemberRepository();
	}

	public function execute(): MoveUsersToDepartmentStrategyResult
	{
		$departmentUserIds = [];

		foreach ($this->userIds as $ids)
		{
			$departmentUserIds = array_merge($departmentUserIds, $ids);
			$departmentUserIds = array_unique($departmentUserIds);
		}

		$updatedDepartmentIds = [];

		$userCollection = $this->nodeMemberRepository->findAllByEntityIdsAndEntityTypeAndNodeType(
			entityIds: $departmentUserIds,
			entityType: MemberEntityType::USER,
			nodeType: NodeEntityType::DEPARTMENT,
		);

		foreach ($userCollection as $nodeMember)
		{
			if ($nodeMember->nodeId === $this->node->id)
			{
				continue;
			}

			$updatedDepartmentIds[] = $nodeMember->nodeId;
		}

		$this->nodeMemberService->moveUsersToDepartment($this->node, $this->userIds);

		return new MoveUsersToDepartmentStrategyResult(
			$this->node,
			$updatedDepartmentIds,
			$this->nodeMemberRepository->countAllByByNodeId($this->node->id)
		);
	}
}