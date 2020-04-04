<?php
namespace Bitrix\Crm\Counter;
use Bitrix\Crm\ActivityBindingTable;
use Bitrix\Main;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Entity\DatetimeField;

use Bitrix\Crm\LeadTable;
use Bitrix\Crm\DealTable;
use Bitrix\Crm\ActivityTable;
use Bitrix\Crm\PhaseSemantics;

class ActivityCounter extends EntityCounter
{
	/**
	 * @param int $typeID Type ID (see EntityCounterType).
	 * @param int $userID User ID.
	 * @param array|null $extras Additional Parameters.
	 * @throws Main\NotSupportedException
	 * @throws Main\ArgumentOutOfRangeException
	 */
	public function __construct($typeID, $userID = 0, array $extras = null)
	{
		if($typeID !== EntityCounterType::PENDING
			&& $typeID !== EntityCounterType::OVERDUE
			&& $typeID !== EntityCounterType::CURRENT)
		{
			$typeName = EntityCounterType::resolveName($typeID);
			throw new Main\NotSupportedException("The '{$typeName}' is not supported in current context");
		}

		parent::__construct(\CCrmOwnerType::Activity, $typeID, $userID, $extras);
	}
	/**
	 * Prepare entity query
	 * @param int $entityTypeID Entity Type ID
	 * @return Query
	 */
	protected function prepareEntityQuery($entityTypeID)
	{
		$connection = Main\Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

		$query = new Query(ActivityBindingTable::getEntity());
		$query->setCustomBaseTableAlias('b');
		$query->addSelect('ACTIVITY_ID', 'ACTIVITY_ID');

		$join = array(
			'=ref.ID' => 'this.ACTIVITY_ID',
			'=ref.COMPLETED' => new SqlExpression('?', 'N'),
			'=this.OWNER_TYPE_ID' => new SqlExpression($entityTypeID)
		);

		if($this->userID > 0)
		{
			$join['=ref.RESPONSIBLE_ID'] = new SqlExpression('?i', $this->userID);
		}

		if($this->typeID === EntityCounterType::PENDING)
		{
			$lowBound = new DateTime();
			$lowBound->setTime(0, 0, 0);

			$join['>=ref.DEADLINE'] = new SqlExpression(
				$sqlHelper->convertToDb($lowBound, new DatetimeField('D'))
			);

			$highBound = new DateTime();
			$highBound->setTime(23, 59, 59);

			$join['<=ref.DEADLINE'] = new SqlExpression(
				$sqlHelper->convertToDb($highBound, new DatetimeField('D'))
			);
		}
		elseif($this->typeID === EntityCounterType::OVERDUE)
		{
			$highBound = new DateTime();
			$highBound->setTime(0, 0, 0);

			$join['<ref.DEADLINE'] = new SqlExpression(
				$sqlHelper->convertToDb($highBound, new DatetimeField('D'))
			);
		}
		else//if($this->typeID === EntityCounterType::CURRENT)
		{
			$highBound = new DateTime();
			$highBound->setTime(23, 59, 59);

			$join['<=ref.DEADLINE'] = new SqlExpression(
				$sqlHelper->convertToDb($highBound, new DatetimeField('D'))
			);
		}

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

	/**
	 * Evaluate counter value
	 * @return int
	 */
	public function calculateValue()
	{
		$queries = array(
			$this->prepareEntityQuery(\CCrmOwnerType::Contact)->getQuery(),
			$this->prepareEntityQuery(\CCrmOwnerType::Company)->getQuery(),
			$this->prepareEntityQuery(\CCrmOwnerType::Lead)->getQuery(),
			$this->prepareEntityQuery(\CCrmOwnerType::Deal)->getQuery(),
			$this->prepareEntityQuery(\CCrmOwnerType::Order)->getQuery()
		);

		$dbResult = Main\Application::getConnection()->query(
			/** @lang MySQL */
			'SELECT COUNT(DISTINCT t.ACTIVITY_ID) QTY FROM ('.implode(' UNION ALL ', $queries).') t'
		);
		$fields = $dbResult->fetch();
		return is_array($fields) ? (int)$fields['QTY'] : 0;
	}
	/**
	 * @param array|null $params List Params (MASTER_ALIAS, MASTER_IDENTITY and etc).
	 * @throws Main\NotSupportedException
	 * @return void
	 */
	public function prepareEntityListFilter(array $params = null)
	{
		throw new Main\NotSupportedException("This method is not supported in current context");
	}
}