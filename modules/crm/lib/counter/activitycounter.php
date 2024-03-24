<?php
namespace Bitrix\Crm\Counter;
use Bitrix\Crm\ActivityBindingTable;
use Bitrix\Crm\ActivityTable;
use Bitrix\Crm\DealTable;
use Bitrix\Crm\LeadTable;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Main;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Entity\DatetimeField;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Type\DateTime;

class ActivityCounter extends EntityCounter
{
	/**
	 * @param int $typeID Type ID (see EntityCounterType).
	 * @param int $userID User ID.
	 * @param array|null $extras Additional Parameters.
	 * @throws Main\NotSupportedException
	 */
	public function __construct($typeID, $userID = 0, array $extras = null)
	{
		// @todo check this types
		$supportedCounterTypes = [
			EntityCounterType::PENDING,
			EntityCounterType::OVERDUE,
			EntityCounterType::CURRENT,
			EntityCounterType::INCOMING_CHANNEL,
			EntityCounterType::IDLE,
			EntityCounterType::PENDING|EntityCounterType::INCOMING_CHANNEL,
			EntityCounterType::READY_TODO,
			EntityCounterType::ALL,
		];

		if (in_array($typeID, $supportedCounterTypes, true))
		{
			parent::__construct(\CCrmOwnerType::Activity, $typeID, $userID, $extras);

			return;
		}

		$typeName = EntityCounterType::resolveName($typeID);
		throw new Main\NotSupportedException("The '{$typeName}' is not supported in current context");
	}

	/**
	 * Prepare entity query
	 * @param int $entityTypeID Entity Type ID
	 * @return Query
	 */
	protected function prepareEntityQuery($entityTypeID)
	{
		$query = new Query(ActivityBindingTable::getEntity());
		$query->setCustomBaseTableAlias('b');
		$query->addSelect('ACTIVITY_ID', 'ACTIVITY_ID');

		$join = $this->prepareActivityTableJoin((int)$entityTypeID);

		$query->registerRuntimeField(
			'',
			new ReferenceField('a',
				ActivityTable::getEntity(),
				$join,
				array('join_type' => 'INNER')
			)
		);

		if($entityTypeID === \CCrmOwnerType::Lead)
		{
			$query->registerRuntimeField(
				'',
				new ReferenceField('l',
					LeadTable::getEntity(),
					array(
						'=ref.ID' => 'this.OWNER_ID',
						'=ref.STATUS_SEMANTIC_ID' => new SqlExpression('?', PhaseSemantics::PROCESS),
					),
					array('join_type' => 'INNER')
				)
			);
		}
		elseif($entityTypeID === \CCrmOwnerType::Deal)
		{
			$query->registerRuntimeField(
				'',
				new ReferenceField('d',
					DealTable::getEntity(),
					array(
						'=ref.ID' => 'this.OWNER_ID',
						'=ref.STAGE_SEMANTIC_ID' => new SqlExpression('?', PhaseSemantics::PROCESS),
					),
					array('join_type' => 'INNER')
				)
			);
		}

		return $query;
	}

	protected function prepareActivityTableJoin(int $entityTypeID): array
	{
		$sqlHelper = Main\Application::getConnection()->getSqlHelper();

		$join = [
			'=ref.ID' => 'this.ACTIVITY_ID',
			'=ref.COMPLETED' => new SqlExpression('?', 'N'),
			'=this.OWNER_TYPE_ID' => new SqlExpression($entityTypeID)
		];

		if ($this->userID > 0)
		{
			$join['=ref.RESPONSIBLE_ID'] = new SqlExpression('?i', $this->userID);
		}

		if ($this->typeID === EntityCounterType::PENDING)
		{
			$lowBound = new DateTime();
			$lowBound->setTime(0, 0, 0);

			$join['>=ref.DEADLINE'] = new SqlExpression(
				$sqlHelper->convertToDb($lowBound, new DatetimeField('D'))
			);

			$highBound = new DateTime();
			$this->convertToUserTime($highBound);
			$highBound->setTime(23, 59, 59);
			$this->convertFromUserTime($highBound);

			$join['<=ref.DEADLINE'] = new SqlExpression(
				$sqlHelper->convertToDb($highBound, new DatetimeField('D'))
			);
		}
		elseif ($this->typeID === EntityCounterType::OVERDUE)
		{
			$highBound = new DateTime();
			$this->convertToUserTime($highBound);
			$highBound->setTime(0, 0, 0);
			$this->convertFromUserTime($highBound);

			$join['<ref.DEADLINE'] = new SqlExpression(
				$sqlHelper->convertToDb($highBound, new DatetimeField('D'))
			);
		}
		else//if($this->typeID === EntityCounterType::CURRENT)
		{
			$highBound = new DateTime();
			$this->convertToUserTime($highBound);
			$highBound->setTime(23, 59, 59);
			$this->convertFromUserTime($highBound);

			$join['<=ref.DEADLINE'] = new SqlExpression(
				$sqlHelper->convertToDb($highBound, new DatetimeField('D'))
			);
		}

		return $join;
	}

	public function prepareEntityListFilter(array $params = null): array
	{
		if ($params === null)
		{
			$params = [];
		}

		$sql = $this->getEntityListSqlExpression($params);
		if (empty($sql))
		{
			return [];
		}

		$masterAlias = $params['MASTER_ALIAS'] ?? 'L';
		$masterIdentity = $params['MASTER_IDENTITY'] ?? 'ID';

		return [
			'__CONDITIONS' => [
				[
					'SQL' => "{$masterAlias}.{$masterIdentity} IN ({$sql})",
				],
			],
		];
	}

	private function convertToUserTime(DateTime $date)
	{
		$diff = $this->getUserTimeOffset();

		if ($diff !== 0)
		{
			$date->add(($diff < 0 ? '-' : '') . 'PT' . abs($diff) . 'S');
		}

		return $date;
	}

	private function convertFromUserTime(DateTime $date)
	{
		$diff = $this->getUserTimeOffset();

		if ($diff !== 0)
		{
			$date->add(($diff > 0 ? '-' : '') . 'PT' . abs($diff) . 'S');
		}

		return $date;
	}

	private function getUserTimeOffset(): int
	{
		static $offset;

		if ($offset === null)
		{
			$offset = \CTimeZone::GetOffset($this->getUserID());
		}

		return (int)$offset;
	}
}
