<?php
namespace Bitrix\Crm\Cleaning;

use Bitrix\Main;
use Bitrix\Main\Result;

class CleaningManager
{
	public static function register($entityTypeID, $entityID)
	{
		[$entityTypeID, $entityID] = static::normalizeIds($entityTypeID, $entityID);

		Entity\CleaningTable::upsert(['ENTITY_TYPE_ID' => $entityTypeID, 'ENTITY_ID' => $entityID]);
	}

	public static function unregister($entityTypeID, $entityID)
	{
		[$entityTypeID, $entityID] = static::normalizeIds($entityTypeID, $entityID);

		Entity\CleaningTable::delete(['ENTITY_TYPE_ID' => $entityTypeID, 'ENTITY_ID' => $entityID]);
	}

	private static function normalizeIds($entityTypeID, $entityID): array
	{
		$entityTypeID = (int)$entityTypeID;
		$entityID = (int)$entityID;

		if ($entityTypeID <= 0)
		{
			throw new Main\ArgumentOutOfRangeException('entityTypeID', 1);
		}

		if ($entityID <= 0)
		{
			throw new Main\ArgumentOutOfRangeException('entityID', 1);
		}

		return [$entityTypeID, $entityID];
	}

	public static function getQueuedItems($limit = 10)
	{
		if(!is_int($limit))
		{
			$limit = (int)$limit;
		}

		if($limit <= 0)
		{
			$limit = 10;
		}

		$dbResult = Entity\CleaningTable::getList(
			array(
				'select' => array('ENTITY_TYPE_ID', 'ENTITY_ID'),
				'order' => array('CREATED_TIME' => 'ASC'),
				'limit' => $limit
			)
		);

		$items = array();
		while($fields = $dbResult->Fetch())
		{
			$items[] = $fields;
		}
		return $items;
	}

	public static function getCleaner(int $entityTypeId, int $entityId): Cleaner
	{
		$options = new Cleaner\Options($entityTypeId, $entityId);

		$cleaner = new Cleaner($options);

		static::customizeCleaner($cleaner);

		return $cleaner;
	}

	private static function customizeCleaner(Cleaner $cleaner): void
	{
		if ($cleaner->getOptions()->getEntityTypeId() === \CCrmOwnerType::Deal)
		{
			$cleaner->addJob(
				new class extends Cleaner\Job {
					public function run(Cleaner\Options $options): Result
					{
						\Bitrix\Crm\Binding\DealContactTable::unbindAllContacts($options->getEntityId());

						return new Result();
					}
				}
			);
		}

		if ($cleaner->getOptions()->getEntityTypeId() === \CCrmOwnerType::Quote)
		{
			$cleaner
				->addJob(
					new class extends Cleaner\Job {
						public function run(Cleaner\Options $options): Result
						{
							\Bitrix\Crm\Binding\QuoteContactTable::unbindAllContacts($options->getEntityId());

							return new Result();
						}
					}
				)
				->addJob(
					new class extends Cleaner\Job {
						public function run(Cleaner\Options $options): Result
						{
							return \Bitrix\Crm\QuoteElementTable::deleteByQuoteId($options->getEntityId());
						}
					}
				)
			;
		}

		if (\CCrmOwnerType::isUseDynamicTypeBasedApproach($cleaner->getOptions()->getEntityTypeId()))
		{
			$cleaner
				->addJob(
					new class extends Cleaner\Job {
						public function run(Cleaner\Options $options): Result
						{
							\Bitrix\Crm\Binding\EntityContactTable::deleteByItem(
								$options->getEntityTypeId(),
								$options->getEntityId(),
							);

							return new Result();
						}
					}
				)
				->addJob(
					new class extends Cleaner\Job {
						public function run(Cleaner\Options $options): Result
						{
							\Bitrix\Crm\Model\AssignedTable::deleteByItem(
								$options->getEntityTypeId(),
								$options->getEntityId(),
							);

							return new Result();
						}
					}
				)
				->addJob(
					new class extends Cleaner\Job {
						public function run(Cleaner\Options $options): Result
						{
							\Bitrix\Crm\Binding\OrderEntityTable::deleteByOwner(
								$options->getEntityTypeId(),
								$options->getEntityId(),
							);

							return new Result();
						}
					}
				)
			;
		}
	}
}
