<?php

namespace Bitrix\Sign\Operation\Document\Template;

use Bitrix\Main;
use Bitrix\Sign\Contract;
use Bitrix\Sign\Item\MemberCollection;
use Bitrix\Sign\Result\Operation\Document\Template\PrepareMemberListForTemplateSendResult;
use Bitrix\Sign\Result\Result;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Service\Integration\HumanResources\StructureNodeService;
use Bitrix\Sign\Type\Document\InitiatedByType;
use Bitrix\Sign\Type\Member\EntityType;
use Bitrix\Sign\Type\Member\Role;
use Bitrix\Sign\Item\Member;

final class PrepareMemberListForTemplateSend implements Contract\Operation
{
	private readonly StructureNodeService $structureNodeService;

	/**
	 * @param int[] $signerUserIdList
	 * @param string[]|int[]|null $reviewerUserList
	 */
	public function __construct(
		private readonly array $signerUserIdList,
		private readonly ?int $companyId,
		private readonly InitiatedByType $initiatedByType = InitiatedByType::COMPANY,
		private readonly ?array $reviewerUserList = null,
		private readonly ?int $editorUserId = null,
		private readonly ?string $representativeRoleName = null,
		private readonly ?string $editorRoleName = null,
	)
	{
		$this->structureNodeService = Container::instance()->getHumanResourcesStructureNodeService();
	}

	public function launch(): Main\Result|PrepareMemberListForTemplateSendResult
	{
		if (count($this->signerUserIdList) === 0)
		{
			return Result::createByErrorData('Signers are not specified');
		}

		$members = new MemberCollection();
		$currentParty = ($this->initiatedByType === InitiatedByType::EMPLOYEE) ? 2 : 1;

		$representativeRoleId = $this->structureNodeService->getRoleIdByName((string)$this->representativeRoleName);
		$editorRoleId = $this->structureNodeService->getRoleIdByName((string)$this->editorRoleName);

		if ($this->reviewerUserList !== null && count($this->reviewerUserList) > 0)
		{
			foreach ($this->reviewerUserList as $value)
			{
				$reviewerRoleId = $this->structureNodeService->getRoleIdByName($value);
				$reviewerEntityType = $reviewerRoleId !== null ? EntityType::ROLE: EntityType::USER;
				$reviewerEntityId = $reviewerRoleId ?? (int)$value;

				if ($reviewerEntityId < 1)
				{
					continue;
				}

				$members->add(
					new Member(
						party: $currentParty++,
						entityType: $reviewerEntityType,
						entityId: $reviewerEntityId,
						role: Role::REVIEWER,
					),
				);
			}
		}

		if ((int)$this->editorUserId > 0)
		{
			$members->add(
				new Member(
					party: $currentParty++,
					entityType: EntityType::USER,
					entityId: $this->editorUserId,
					role: Role::EDITOR,
				),
			);
		}
		elseif ((int)$editorRoleId > 0)
		{
			$members->add(
				new Member(
					party: $currentParty++,
					entityType: EntityType::ROLE,
					entityId: $editorRoleId,
					role: Role::EDITOR,
				),
			);
		}

		if ((int)$this->companyId > 0)
		{
			$members->add(
				new Member(
					party: $currentParty++,
					entityType:(int)$representativeRoleId > 0 ? EntityType::ROLE : EntityType::COMPANY,
					entityId: $this->companyId,
					role: Role::ASSIGNEE,
				),
			);
		}

		foreach ($this->signerUserIdList as $entityId)
		{
			$members->add(
				new Member(
					party: ($this->initiatedByType === InitiatedByType::EMPLOYEE) ? 1 : $currentParty,
					entityType: EntityType::USER,
					entityId: $entityId,
					role: Role::SIGNER,
				),
			);
		}

		return new PrepareMemberListForTemplateSendResult($members, $representativeRoleId);
	}
}
