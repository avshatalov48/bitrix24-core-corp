<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository\Mapper;

use Bitrix\Main\Engine\Response\Converter;
use Bitrix\Tasks\CheckList\Task\TaskCheckListFacade;
use Bitrix\Tasks\Internals\Task\Template\TemplateObject;
use Bitrix\Tasks\V2\Internal\Entity\Group;
use Bitrix\Tasks\V2\Internal\Entity\User;
use Bitrix\Tasks\V2\Internal\Entity\UserCollection;
use Bitrix\Tasks\V2\Internal\Entity\Task\Priority;
use Bitrix\Tasks\V2\Internal\Entity\Template;

class TemplateMapper
{
	public function mapFromTemplateObject(TemplateObject $templateObject): Template
	{
		return new Template(
			id:              $templateObject->getId(),
			title:           $templateObject->getTitle(),
			description:     $templateObject->getDescription(),
			creator:         $this->mapMember($templateObject->getCreatedBy()),
			responsibleCollection:	$this->mapMemberCollection($templateObject->getResponsibleMemberId()),
			deadlineAfterTs: $templateObject->getDeadlineAfter(),
			startDatePlanTs: $templateObject->getStartDatePlanAfter(),
			endDatePlanTs:   $templateObject->getEndDatePlanAfter(),
			replicate:		 $templateObject->getReplicate(),
			checklist:       $this->mapChecklist($templateObject),
			group:           $this->mapGroup($templateObject),
			priority:        $this->mapPriority($templateObject),
			accomplices:     $this->mapMemberCollection($templateObject->getAccompliceMembersIds()),
			auditors:        $this->mapMemberCollection($templateObject->getAuditorMembersIds()),
		);
	}

	private function mapChecklist(TemplateObject $templateObject): ?array
	{
		$items = TaskCheckListFacade::getList(filter: ['TASK_ID' => $templateObject->getId()]);

		return Converter::toJson()->process($items);
	}

	private function mapGroup(TemplateObject $templateObject): ?Group
	{
		// TODO: frontend
		if ($templateObject->isGroupIdFilled() && $templateObject->getGroupId() !== 0)
		{
			$group = \Bitrix\Tasks\Internals\Registry\GroupRegistry::getInstance()->get($templateObject->getGroupId());

			if (!$group)
			{
				return null;
			}

			$groupId = (int)$group['ID'];
			$groupData = \Bitrix\Tasks\Integration\SocialNetwork\Group::getGroupData($groupId);

			return Group::mapFromArray([
				'id' => (int)$group['ID'],
				'name' => $group['NAME'],
				'image' => $groupData['IMAGE'] ?? '',
				'type' => $group['TYPE'],
			]);
		}

		return null;
	}

	private function mapMember(int $userId): User
	{
		return User::mapFromArray(['id' => $userId]);
	}

	private function mapMemberCollection(array $userIds): UserCollection
	{
		$userIds = array_map(static fn(int $id) => ['id' => $id], $userIds);

		return UserCollection::mapFromArray($userIds);
	}

	private function mapPriority(TemplateObject $templateObject): ?Priority
	{
		return match ((int)$templateObject->getPriority()) {
			\Bitrix\Tasks\Internals\Task\Priority::LOW => Priority::Low,
			\Bitrix\Tasks\Internals\Task\Priority::AVERAGE => Priority::Average,
			\Bitrix\Tasks\Internals\Task\Priority::HIGH => Priority::High,
		};
	}
}
