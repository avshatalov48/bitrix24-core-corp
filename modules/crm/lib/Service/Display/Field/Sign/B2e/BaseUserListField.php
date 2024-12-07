<?php

namespace Bitrix\Crm\Service\Display\Field\Sign\B2e;

use Bitrix\Crm\Item\SmartB2eDocument;
use Bitrix\Crm\Kanban\Entity\Dto\Sign\B2e\UserListDto;
use Bitrix\Crm\Service\Display\Field\UserField;
use Bitrix\Crm\Service\Display\Options;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Uri;
use Bitrix\Sign\FeatureResolver;
use Bitrix\Sign\Type\Member\Role;
use Bitrix\Sign\Type\MemberStatus;

abstract class BaseUserListField extends UserField
{
	protected int $visibleCount = 3;
	protected string $elementSeparator = '';
	private array $filterMemberStatus = [];

	public function setFilterMemberStatus(array $filterMemberStatus): self
	{
		if (Loader::includeModule('sign') === false)
		{
			return $this;
		}

		$this->filterMemberStatus = array_filter(
			$filterMemberStatus,
			static fn (string $status): bool => in_array($status, MemberStatus::getAll(), true)
		);

		return $this;
	}

	public function setVisibleCount(int $visibleCount): self
	{
		$this->visibleCount = $visibleCount;

		return $this;
	}

	protected function getFormattedValueForKanban($fieldValue, int $itemId, Options $displayOptions): string
	{
		if (($fieldValue instanceof UserListDto) === false)
		{
			return '';
		}

		if ($fieldValue->hasValidationErrors())
		{
			return '';
		}

		return $this->render($displayOptions, $itemId, $fieldValue);
	}

	protected function render(Options $displayOptions, $entityId, $value): string
	{
		if (($value instanceof UserListDto) === false)
		{
			return '';
		}

		if ($value->hasValidationErrors())
		{
			return '';
		}

		$this->setWasRenderedAsHtml(true);

		if ($value->total === 0)
		{
			return '';
		}

		if ($this->visibleCount < 1)
		{
			return '';
		}

		$entityId = (int)$entityId;

		if ($entityId < 1)
		{
			return '';
		}

		$items = [];
		foreach (array_slice($value->userIdList, 0, $this->visibleCount) as $userId)
		{
			$preparedValue = $this->renderSingleValue($userId, $entityId, $displayOptions);
			if ($preparedValue !== '')
			{
				$items[] = $preparedValue;
			}
		}

		if (count($items) === 0)
		{
			return '';
		}

		$userListBlock = implode($this->elementSeparator, $items);
		$url = $this->createUrl($entityId);
		$counterBlock = ($value->total > $this->visibleCount)
			? $this->prepareCounterHtml($value->total - $this->visibleCount)
			: '';

		return $this->prepareResultHtml($userListBlock, $counterBlock, $url);
	}

	protected function renderSingleValue($fieldValue, int $itemId, Options $displayOptions): string
	{
		$this->setWasRenderedAsHtml(true);

		return $this->getUserHtml((int)$fieldValue);
	}

	private function getUserHtml(int $userId): string
	{
		$linkedEntitiesValues = $this->getLinkedEntitiesValues();
		$user = $linkedEntitiesValues[$userId] ?? null;

		if (!is_array($user))
		{
			return '';
		}

		$title = $this->sanitizeString($user['FORMATTED_NAME'] ?? '');
		if ($title === '')
		{
			return '';
		}

		$photoUrl = $this->sanitizeString($user['PHOTO_URL'] ?? '');

		return $this->prepareElementHtml(
			$title,
			$photoUrl
		);
	}

	protected function prepareElementHtml(string $title, string $photoUrl): string
	{
		$userBlock = $photoUrl
			? sprintf('<img class="sign-b2e-user-list-item-image" src="%s">', $photoUrl)
			: '';

		return sprintf(
			'<div title="%s" class="sign-b2e-user-list-item">%s</div>',
			$title,
			$userBlock
		);
	}

	private function createUrl(int $entityId): string
	{
		if ($entityId < 1)
		{
			return '#';
		}

		$uri = new Uri('/bitrix/components/bitrix/sign.document.list/slider.php');
		$params = [
			'type' => 'document',
			'entity_id' => $entityId,
			'apply_filter' => 'Y',
			'clear_filter' => 'Y',
			'MEMBER_STATUS' => $this->filterMemberStatus,
		];
		$uri->addParams($params);

		return $uri->getUri();
	}

	protected function prepareResultHtml(string $userListBlock, string $counterBlock, string $url): string
	{
		return sprintf(
			'<div class="sign-b2e-user-list"><a href="%s">%s%s</a></div>',
			$url,
			$userListBlock,
			$counterBlock
		);
	}

	protected function prepareCounterHtml(int $count): string
	{
		return sprintf(
			'<span class="sign-b2e-user-list-counter"> + %d</a>',
			$count
		);
	}

	public function prepareLinkedEntities(
		array &$linkedEntities,
		$fieldValue,
		int $itemId,
		string $fieldId
	): void {
		if (($fieldValue instanceof UserListDto) === false)
		{
			return;
		}

		$fieldType = $this->getType();
		foreach ($fieldValue->userIdList as $userId)
		{
			$value = (int)$userId;
			if ($value <= 0)
			{
				continue;
			}
			$linkedEntities[$fieldType]['FIELD'][$itemId][$fieldId][$value] = $value;
			$linkedEntities[$fieldType]['ID'][$value] = $value;
		}
	}

	protected function prepareGroupChatHtml(): string
	{
		$chatType =
			match ($this->id)
			{
				SmartB2eDocument::FIELD_NAME_NOT_SIGNED_COMPANY_LIST => 1,
				SmartB2eDocument::FIELD_NAME_NOT_SIGNED_EMPLOYER_LIST => 2,
				SmartB2eDocument::FIELD_NAME_SIGN_CANCELLED_MEMBER_LIST => 3,
				default => null,
			};
		if (class_exists(FeatureResolver::class))
		{
			$featureResolver = FeatureResolver::instance();
			if ($chatType !== null && $featureResolver->released('createDocumentChat'))
			{
				$createGroupChatTitle = Loc::getMessage('CRM_FIELD_SIGN_B2E_CREATE_GROUP_CHAT');

				return "<span onclick=\"KanbanEntityCreateGroupChat.onCreateGroupChatButtonClickHandler(event)\" title=\"$createGroupChatTitle\" class=\"crm-kanban-create-group-chat\" chat-type=\"$chatType\"></span>";
			}
		}

		return '';
	}
}
