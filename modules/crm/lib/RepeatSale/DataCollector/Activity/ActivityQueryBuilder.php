<?php

namespace Bitrix\Crm\RepeatSale\DataCollector\Activity;

use Bitrix\Crm\ActivityBindingTable;
use Bitrix\Crm\ActivityTable;
use Bitrix\Crm\Timeline\Entity\TimelineBindingTable;
use Bitrix\Crm\Timeline\Entity\TimelineTable;
use Bitrix\Crm\Timeline\TimelineType;
use Bitrix\Main\Entity\Query\Join;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\ORM\Query\Query;

class ActivityQueryBuilder
{
	public function buildActivityQuery(int $entityTypeId, int $entityId, string $providerId, int $limit): Query
	{
		return ActivityTable::query()
			->setSelect(['*'])
			->where('OWNER_TYPE_ID', $entityTypeId)
			->where('OWNER_ID', $entityId)
			->where('PROVIDER_ID', $providerId)
			->setOrder(['CREATED' => 'DESC'])
			->setLimit($limit)
		;
	}

	public function buildActivityWithBindingQuery(int $entityTypeId, int $entityId, string $providerId, int $limit): Query
	{
		return ActivityTable::query()
			->setSelect(['SUBJECT', 'DESCRIPTION'])
			->registerRuntimeField(
				'',
				new ReferenceField(
					'BIND',
					ActivityBindingTable::getEntity(),
					['=ref.ACTIVITY_ID' => 'this.ID']
				)
			)
			->where('BIND.OWNER_TYPE_ID', $entityTypeId)
			->where('BIND.OWNER_ID', $entityId)
			->where('PROVIDER_ID', $providerId)
			->whereNotNull('DESCRIPTION')
			->setOrder(['CREATED' => 'DESC'])
			->setLimit($limit)
		;
	}

	public function buildCommentsQuery(int $entityTypeId, int $entityId, int $limit): Query
	{
		return TimelineTable::query()
			->setSelect(['COMMENT'])
			->registerRuntimeField(
				'',
				new ReferenceField(
					'BIND',
					TimelineBindingTable::getEntity(),
					['=this.ID' => 'ref.OWNER_ID'],
					['join_type' => Join::TYPE_LEFT]
				)
			)
			->where('TYPE_ID', TimelineType::COMMENT)
			->where('BIND.ENTITY_TYPE_ID', $entityTypeId)
			->where('BIND.ENTITY_ID', $entityId)
			->setOrder(['CREATED' => 'DESC'])
			->setLimit($limit)
		;
	}
}
