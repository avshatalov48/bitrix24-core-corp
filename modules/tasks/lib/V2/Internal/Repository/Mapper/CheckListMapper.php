<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository\Mapper;

use Bitrix\Main\Engine\Response\Converter;
use Bitrix\Main\Type\RandomSequence;
use Bitrix\Tasks\Access\Role\RoleDictionary;
use Bitrix\Tasks\CheckList\Node\Nodes;
use Bitrix\Tasks\V2\Internal\Entity;

class CheckListMapper
{
	public function mapToEntity(array $checkList): Entity\CheckList
	{
		$items = [];
		foreach ($checkList as $id => $item)
		{
			$accomplices = array_filter(
				$item['MEMBERS'],
				static fn (array $member): bool => $member['TYPE'] === RoleDictionary::ROLE_ACCOMPLICE,
			);

			$auditors = array_filter(
				$item['MEMBERS'],
				static fn (array $member): bool => $member['TYPE'] === RoleDictionary::ROLE_AUDITOR,
			);

			$items[] = new Entity\CheckList\CheckListItem(
				id: (int)$item['ID'],
				nodeId: (new RandomSequence($item['TITLE'] . $id))->randString(6),
				title: $item['TITLE'],
				creator: $this->mapUser((int)$item['CREATED_BY']),
				toggledBy: $this->mapUser((int)$item['TOGGLED_BY']),
				toggledDate: $item['TOGGLED_DATE'],
				accomplices: $this->mapUserCollection($accomplices),
				auditors: $this->mapUserCollection($auditors),
				attachments: $this->mapAttachmentCollection($item['ATTACHMENTS']),
				isComplete: $item['IS_COMPLETE'] === 'Y',
				isImportant: $item['IS_IMPORTANT'] === 'Y',
				parentId: (int)$item['PARENT_ID'],
				sortIndex: (int)$item['SORT_INDEX'],
				actions: $this->mapActions($item['ACTION']),
				collapsed: (bool)($item['COLLAPSED'] ?? null) === true,
				expanded: (bool)($item['EXPANDED'] ?? null) === true,
			);
		}

		return new Entity\CheckList(...$items);
	}

	public function mapToNodes(array $checkList): Nodes
	{
		$items = [];
		$nodeIdMapping = [];

		foreach ($checkList as $id => $item)
		{
			$itemId = $item['id'] ?? null;
			$item['id'] = (is_string($itemId) || (int)$itemId === 0) ? null : (int)$itemId;

			$isComplete = $item['isComplete'] ?? null;
			$item['isComplete'] = $isComplete === true || (int)$isComplete > 0;

			$isImportant = $item['isImportant'] ?? null;
			$item['isImportant'] = $isImportant === true || (int)$isImportant > 0;

			$auditors = $this->mapMembers($item['auditors'] ?? [], RoleDictionary::ROLE_AUDITOR);
			$accomplices = $this->mapMembers($item['accomplices'] ?? [], RoleDictionary::ROLE_ACCOMPLICE);

			$item['members'] = $auditors + $accomplices;

			unset($item['auditors'], $item['accomplices']);

			$items[$id] = $item;
			$nodeIdMapping[$id] = $item['nodeId'] ?? null;
		}

		$converter = new Converter(
			Converter::KEYS
			| Converter::RECURSIVE
			| Converter::TO_SNAKE
			| Converter::TO_UPPER
		);

		$convertedList = $converter->process($items);

		$data = [];
		foreach ($convertedList as $id => $item)
		{
			if (isset($nodeIdMapping[$id]))
			{
				$data[$nodeIdMapping[$id]] = $item;
			}
		}

		$nodes = Nodes::createFromArray(data: $data);
		$nodes->validate();

		return $nodes;
	}

	public function mapToArray(Nodes $nodes): array
	{
		return Converter::toJson()->process($nodes->toArray());
	}

	private function mapUser(int $userId): ?Entity\User
	{
		if ($userId <= 0)
		{
			return null;
		}

		return new Entity\User(
			id: $userId
		);
	}

	private function mapUserCollection(array $users): Entity\UserCollection
	{
		if (empty($users))
		{
			return new Entity\UserCollection();
		}

		$users = array_map(
			static fn (array $user): Entity\User => new Entity\User(
				id: (int)$user['ID'],
				name: $user['NAME'],
				image: new Entity\File(src: $user['IMAGE']),
			),
			$users
		);

		return new Entity\UserCollection(...$users);
	}

	private function mapAttachmentCollection(array $attachments): Entity\AttachmentCollection
	{
		if (empty($attachments))
		{
			return new Entity\AttachmentCollection();
		}

		$attachments = array_map(
			static fn (array $attachment): Entity\Attachment => new Entity\Attachment(
				id: (int)$attachment['ATTACHMENT_ID'],
				fileId: (string)$attachment['FILE_ID'],
			),
			$attachments
		);

		return new Entity\AttachmentCollection(...$attachments);
	}

	private function mapActions(array $actions): array
	{
		return Converter::toJson()->process($actions);
	}

	private function mapMembers(array $members, string $type): array
	{
		$mapped = [];
		foreach ($members as $member)
		{
			if (!is_array($member))
			{
				continue;
			}

			$mapped[(int)$member['id']] = [...$member, 'type' => $type];
		}

		return $mapped;
	}
}
