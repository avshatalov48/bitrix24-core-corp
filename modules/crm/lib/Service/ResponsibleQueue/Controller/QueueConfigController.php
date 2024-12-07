<?php

namespace Bitrix\Crm\Service\ResponsibleQueue\Controller;

use Bitrix\Crm\Service\ResponsibleQueue\Entity\QueueConfigMembersTable;
use Bitrix\Crm\Service\ResponsibleQueue\Entity\QueueConfigTable;
use Bitrix\Crm\Traits\Singleton;
use Bitrix\Main\ORM\Data\AddResult;
use Bitrix\Main\ORM\Data\DeleteResult;
use Bitrix\Main\ORM\Data\UpdateResult;

final class QueueConfigController
{
	use Singleton;

	public const TYPE_COMMUNICATION_CHANNEL_ROUTING = 'COMMUNICATION_CHANNEL_ROUTING';

	public function get(int $id): array
	{
		if ($id <= 0)
		{
			return [];
		}

		$queue = QueueConfigTable::getById($id)?->fetch();
		if (!$queue)
		{
			return [];
		}

		$queue['MEMBERS'] = QueueConfigMembersTable::getList([
			'select' => [
				'ENTITY_ID',
				'ENTITY_TYPE'
			],
			'filter' => ['=QUEUE_CONFIG_ID' => $id],
			'order' => [
				'SORT' => 'ASC',
				'ID' => 'ASC'
			],
		])->fetchAll();

		return $queue;
	}

	public function add(string $title, string $type, array $members, array $settings): AddResult
	{
		$result = QueueConfigTable::add([
			'TITLE' => $title,
			'TYPE' => $type,
			'SETTINGS' => $settings,
		]);

		if ($result->isSuccess())
		{
			$resultApplyMembers = QueueMembersController::getInstance()->update($result->getId(), $members);
			if (!$resultApplyMembers->isSuccess())
			{
				$result->addErrors($resultApplyMembers->getErrors());
			}
		}

		return $result;
	}

	public function update(int $id, array $members, array $settings): UpdateResult
	{
		$result =  QueueConfigTable::update(
			$id,
			[
				'SETTINGS' => $settings,
			]
		);

		if ($result->isSuccess())
		{
			$resultApplyMembers = QueueMembersController::getInstance()->update($id, $members);
			if (!$resultApplyMembers->isSuccess())
			{
				$result->addErrors($resultApplyMembers->getErrors());
			}
		}

		return $result;
	}

	public function deleteById(int $id): DeleteResult
	{
		$result = QueueConfigTable::delete($id);
		if ($result->isSuccess())
		{
			$membersIds = QueueConfigMembersTable::getList([
				'select' => ['ID'],
				'filter' => ['=QUEUE_CONFIG_ID' => $id],
			])->fetchCollection()?->getIdList();
			foreach ($membersIds as $memberId)
			{
				$membersResult = QueueConfigMembersTable::delete($memberId);
				if (!$membersResult->isSuccess())
				{
					$result->addError($membersResult->getError());
				}
			}
		}

		return $result;
	}
}
