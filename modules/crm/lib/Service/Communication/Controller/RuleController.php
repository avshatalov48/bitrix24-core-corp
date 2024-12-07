<?php

namespace Bitrix\Crm\Service\Communication\Controller;

use Bitrix\Crm\Service\Communication\Channel\Channel;
use Bitrix\Crm\Service\Communication\Entity\CommunicationChannelRuleTable;
use Bitrix\Crm\Service\Communication\Entity\CommunicationChannelTable;
use Bitrix\Crm\Traits\Singleton;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\ORM\Data\AddResult;
use Bitrix\Main\ORM\Data\DeleteResult;
use Bitrix\Main\ORM\Data\UpdateResult;

final class RuleController
{
	use Singleton;

	public function add(
		string $title,
		int $channelId,
		int $queueConfigId,
		?array $searchTargets,
		array $rules,
		array $entities,
		array $settings = []
	): AddResult
	{
		return CommunicationChannelRuleTable::add([
			'TITLE' => $title,
			'CHANNEL_ID' => $channelId,
			'QUEUE_CONFIG_ID' => $queueConfigId,
			'SEARCH_TARGETS' => $searchTargets,
			'RULES' => $rules,
			'ENTITIES' => $entities,
			'SETTINGS' => $settings,
		]);
	}

	public function update(
		int $id,
		string $title,
		int $channelId,
		int $queueConfigId,
		?array $searchTargets,
		array $rules,
		array $entities,
		array $settings = []
	): UpdateResult
	{
		return CommunicationChannelRuleTable::update(
			$id,
			[
				'TITLE' => $title,
				'CHANNEL_ID' => $channelId,
				'QUEUE_CONFIG_ID' => $queueConfigId,
				'SEARCH_TARGETS' => $searchTargets,
				'RULES' => $rules,
				'ENTITIES' => $entities,
				'SETTINGS' => $settings,
			]
		);
	}

	public function deleteById(int $id): DeleteResult
	{
		return CommunicationChannelRuleTable::delete($id);
	}

	public function findRules(Channel $channel): array
	{
		$filter = [
			'MODULE_ID' => $channel->getModuleId(),
			'CODE' => $channel->getCode(),
		];

		return $this->getList($filter);
	}

	public function getList(array $filter = []): array
	{
		$prepareFilter = [];
		if (isset($filter['MODULE_ID']))
		{
			$prepareFilter['=CHANNEL.MODULE_ID'] = $filter['MODULE_ID'];
		}

		if (isset($filter['CODE']))
		{
			$prepareFilter['=CHANNEL.CODE'] = $filter['CODE'];
		}

		$query = CommunicationChannelRuleTable::query()
			->setSelect(['*'])
			->setOrder([
				'SORT' => 'DESC',
				'ID' => 'ASC',
			])
		;

		if (!empty($prepareFilter))
		{
			$query->setFilter($prepareFilter);
			$query->registerRuntimeField(
				'',
				new ReferenceField(
					'CHANNEL',
					CommunicationChannelTable::class,
					['=this.CHANNEL_ID' => 'ref.ID'],
					['join_type' => 'LEFT']
				)
			);
		}

		return $query->exec()->fetchAll();
	}
}
