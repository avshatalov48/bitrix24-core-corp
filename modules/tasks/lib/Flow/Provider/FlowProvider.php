<?php

namespace Bitrix\Tasks\Flow\Provider;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Flow\Efficiency\Command\EfficiencyCommand;
use Bitrix\Tasks\Flow\Efficiency\Efficiency;
use Bitrix\Tasks\Flow\Efficiency\EfficiencyService;
use Bitrix\Tasks\Flow\Efficiency\LastMonth;
use Bitrix\Tasks\Flow\Flow;
use Bitrix\Tasks\Flow\FlowCollection;
use Bitrix\Tasks\Flow\Internal\Entity\FlowOption;
use Bitrix\Tasks\Flow\Internal\FlowTable;
use Bitrix\Tasks\Flow\Internal\FlowTaskTable;
use Bitrix\Tasks\Flow\Option\Option;
use Bitrix\Tasks\Flow\Provider\Exception\FlowNotFoundException;
use Bitrix\Tasks\Flow\Provider\Exception\ProviderException;
use Bitrix\Tasks\Flow\Provider\Query\ExpandedFlowQuery;
use Bitrix\Tasks\Flow\Provider\Query\FlowQuery;
use Bitrix\Tasks\Flow\Provider\Query\FlowQueryBuilder;
use Bitrix\Tasks\Flow\FlowRegistry;
use Bitrix\Tasks\Flow\Provider\Query\FlowQueryEnrich;
use Bitrix\Tasks\Internals\Log\Logger;
use Bitrix\Tasks\Internals\Task\MemberTable;
use Exception;
use Throwable;

final class FlowProvider
{
	private static int $listCount = 0;

	public static function getListCount(): int
	{
		return self::$listCount;
	}

	/**
	 * @throws ProviderException
	 */
	public function getList(FlowQuery $flowQuery): FlowCollection
	{
		$flowCollection = new FlowCollection();

		$flowQuery->setOnlyPrimaries();
		try
		{
			$listFlowData = FlowQueryBuilder::build($flowQuery)->exec()->fetchAll();
			$primaries = array_column($listFlowData, 'ID');
			if (empty($primaries))
			{
				self::$listCount = 0;
				return $flowCollection;
			}

			if ($flowQuery->getCountTotal() !== 0)
			{
				self::$listCount = $this->getCount($flowQuery);
			}

			if (!$flowQuery->isOnlyPrimaries())
			{
				$primaryFilter = Query::filter()->whereIn('ID', $primaries);

				$query = (new FlowQuery())
					->setDistinct(false)
					->setSelect($flowQuery->getSelect())
					->setWhere($primaryFilter)
					->setAccessCheck(false);

				$listFlowData = FlowQueryEnrich::build($query)->exec()->fetchAll();
			}
		}
		catch (Exception $e)
		{
			throw new ProviderException($e->getMessage());
		}

		foreach ($listFlowData as $flowData)
		{
			$flowCollection->add(new Flow($flowData));
		}

		return $flowCollection;
	}

	/**
	 * @throws FlowNotFoundException
	 */
	public function getFlow(int $flowId, array $select = FlowRegistry::DEFAULT_SELECT): Flow
	{
		$flowRegistry = FlowRegistry::getInstance();

		$flowEntity = $flowRegistry->get($flowId, $select);
		if ($flowEntity === null)
		{
			throw new FlowNotFoundException('Flow not found');
		}

		$values = $flowEntity->collectValues();
		if ($flowEntity->hasMembers())
		{
			$values['MEMBERS'] = $flowEntity->getMembers()->toArray();
		}

		if ($flowEntity->hasQueue())
		{
			$values['QUEUE'] = $flowEntity->getQueue()->getUserIdList();
		}

		if ($flowEntity->hasOptions())
		{
			$values['OPTIONS'] = array_map(
				static fn (FlowOption $option): Option => new Option($flowId, $option->getName(), $option->getValue()),
				iterator_to_array($flowEntity->getOptions())
			);
		}

		return new Flow($values);
	}

	/**
	 * @return array [
	 *      flowId => tasksCount
	 * ]
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function getFlowTasksCount(int $userId, array $statuses, int ...$flowIds): array
	{
		if (empty($flowIds) || $userId <= 0 || empty($statuses))
		{
			return [];
		}

		$taskAssigneeField = MemberTable::query()
			->setSelect(['TASK_ID'])
			->where('USER_ID', $userId)
			->where('TYPE', MemberTable::MEMBER_TYPE_RESPONSIBLE);

		$taskMembersField = (new ReferenceField(
			'TASK_ORIGINATOR',
			MemberTable::getEntity(),
			Join::on('this.TASK_ID', 'ref.TASK_ID')
				->where('ref.USER_ID', $userId)
				->where('ref.TYPE', MemberTable::MEMBER_TYPE_ORIGINATOR)
				->whereNotIn('ref.TASK_ID', new SqlExpression($taskAssigneeField->getQuery()))
		))->configureJoinType(Join::TYPE_INNER);


		$data = FlowTaskTable::query()
			->addSelect('FLOW_ID')
			->addSelect(Query::expr()->setAlias('TASKS_COUNT')->countDistinct('TASK_ID'))
			->setGroup(['FLOW_ID'])
			->whereIn('TASK.STATUS', $statuses)
			->whereIn('FLOW_ID', $flowIds)
			->registerRuntimeField('TASK_ORIGINATOR', $taskMembersField)
			->exec()
			->fetchAll();

		$map = array_fill_keys($flowIds, 0);
		foreach ($data as $item)
		{
			$map[(int)$item['FLOW_ID']] = (int)$item['TASKS_COUNT'];
		}

		return $map;
	}

	public function getFlowFields(): array
	{
		$entity = FlowTable::getEntity();

		$fields = [];
		foreach ($entity->getFields() as $field)
		{
			$fields[$field->getName()] = $field->getTitle();
		}

		return $fields;
	}

	public function getEfficiency(Flow $flow): int
	{
		$currentEfficiency = (new Efficiency(new LastMonth()))->get($flow->getId());

		if ($flow->getEfficiency() !== $currentEfficiency)
		{
			$command = (new EfficiencyCommand())
				->setFlowId($flow->getId())
				->setOldEfficiency($flow->getEfficiency())
				->setNewEfficiency($currentEfficiency);

			try
			{
				/** @var EfficiencyService $service */
				$service = ServiceLocator::getInstance()->get('tasks.flow.efficiency.service');
				$service->update($command);
			}
			catch (\Bitrix\Tasks\Flow\Control\Exception\FlowNotFoundException)
			{

			}
			catch (Throwable $t)
			{
				Logger::logThrowable($t);
			}
		}

		return $currentEfficiency;
	}

	public function getCount(FlowQuery $flowQuery): int
	{
		$cntQuery = (new FlowQuery($flowQuery->getUserId()))
			->setSelect([Query::expr('COUNT_TOTAL')->countDistinct('ID')])
			->setWhere($flowQuery->getWhere())
			->setAccessCheck($flowQuery->needAccessCheck());

		return (int)FlowQueryBuilder::build($cntQuery)->exec()->fetch()['COUNT_TOTAL'];
	}

	/**
	 * @throws ProviderException
	 */
	public function isSameFlowExists(string $name, int $flowId = 0): bool
	{
		$query = (new ExpandedFlowQuery())
			->setAccessCheck(false)
			->whereName($name);

		if ($flowId > 0)
		{
			$query->whereId($flowId, '!=');
		}

		$flows = $this->getList($query);

		return !$flows->isEmpty();
	}
}
