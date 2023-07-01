<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 *
 *
 *
 *
 */

namespace Bitrix\Tasks\Util\Replicator\Task;

use Bitrix\Main\Localization\Loc;

use Bitrix\Tasks\CheckList\Task\TaskCheckListFacade;
use Bitrix\Tasks\Internals\Task\MemberTable;
use Bitrix\Tasks\Internals\Task\RelatedTable;
use Bitrix\Tasks\Internals\Task\LabelTable;
use Bitrix\Tasks\Internals\TaskTable;
use Bitrix\Tasks\Item\Result;
use Bitrix\Tasks\Item\Task;
use Bitrix\Tasks\Internals\RunTime;
use Bitrix\Tasks\Util\Type\DateTime;
use Bitrix\Tasks\Util\User;
use Bitrix\Tasks\Util\Collection;

Loc::loadMessages(__FILE__);

class FromTask extends \Bitrix\Tasks\Util\Replicator\Task
{
	protected static function getSourceClass()
	{
		return '\\Bitrix\\Tasks\\Item\\Task';
	}

	protected static function getConverterClass()
	{
		return '\\Bitrix\\Tasks\\Item\\Converter\\Task\\ToTask';
	}

	protected static function getFromCheckListFacade()
	{
		return TaskCheckListFacade::class;
	}

	protected static function getToCheckListFacade()
	{
		return TaskCheckListFacade::class;
	}

	/**
	 * Note: multitasking is not implemented here
	 *
	 * @param int|mixed[]|\Bitrix\Tasks\Item\Task $source
	 * @param int|mixed[]|\Bitrix\Tasks\Item\Task $destination
	 * @param array $parameters
	 * @param int $userId
	 * @return Result
	 */
	public function produceSub($source, $destination, array $parameters = array(), $userId = 0)
	{
		$result = new Result();

		Task::enterBatchState();

		if(!$userId)
		{
			$userId = User::getId();
		}

		$source = $this->getSourceInstance($source, $userId);
		$destination = $this->getDestinationInstance($destination, $userId);
		$created = new Collection();

		$data = $this->getSubItemData($source->getId(), $result, $userId);
		if($result->isSuccess() && !empty($data)) // has sub-tasks
		{
			$srcToDst = array($source->getId() => $destination->getId());
			$notSaved = array();

			foreach($data['TREE'] as $childId => $parentId)
			{
				if(!isset($parameters['COPY_DESTINATION_ITEM']) && $childId == $destination->getId()) // do not copy self one more time as a sub-task
				{
					continue;
				}

				if(isset($notSaved[$parentId])) // if parent was not saved, child can not be saved too
				{
					$notSaved[$childId] = true;
					continue;
				}

				$saveResult = $this->saveItemFromSource($data['DATA'][$childId], ['PARENT_ID' => $srcToDst[$parentId]], $userId);

				if($saveResult->isSuccess())
				{
					$srcToDst[$childId] = $saveResult->getInstance()->getId();
				}
				else
				{
					$notSaved[$childId] = true;
				}

				$created->push($saveResult);
			}

			if(!empty($notSaved))
			{
				$result->addError('SUB_ITEMS_CREATION_FAILURE', 'Some of the sub-tasks was not properly created');
			}
		}

		Task::leaveBatchState();

		$result->setData($created);

		return $result;
	}

	private function getSubItemData($id, Result $result, $userId = 0)
	{
		$queue = array($id);
		$met = array();
		$times = 0;

		$data = array();
		$flatTree = array();

		$select = array_keys(\Bitrix\Tasks\Util\UserField\Task::getScheme(0, $userId));
		$select[] = '*';

		$parameters = Runtime::apply(
			array(
				'filter' => array(
					'=PARENT_ID' => $id,
				),
				'select' => $select,
			),
			array(RunTime\Task::getAccessCheck(array( // have to check rights...
				'USER_ID' => $userId
			)))
		);

		// todo: use \Bitrix\Tasks\Internals\Helper\Task\Dependence here, instead of recursive calls
		// todo: or you can use \Bitrix\Tasks\Item\Task::find() with an appropriate runtime mixin applied on top
		while(!empty($queue))
		{
			if($times > 10000)
			{
				$result->addError('ILLEGAL_STRUCTURE.DEPTH', 'Insane iteration count faced');
				break;
			}

			$nextId = array_shift($queue);
			if(array_key_exists($nextId, $met))
			{
				$result->addError('ILLEGAL_STRUCTURE.LOOP', Loc::getMessage('TASKS_REPLICATOR_SUBTREE_LOOP'));
				break;
			}
			$met[$nextId] = true;

			$parameters['filter']['=PARENT_ID'] = $nextId;

			$res = TaskTable::getList(Runtime::cloneFields($parameters));
			while($item = $res->fetch())
			{
				$data[$item['ID']] = $item;
				$flatTree[$item['ID']] = $nextId;

				$queue[] = $item['ID'];
			}

			$times++;
		}

		if($result->isSuccess())
		{
			$ids = array_keys($data);

			// get checklist data
//			$res = CheckListTable::getList(array('filter' => array('=TASK_ID' => $ids)));
//			while($item = $res->fetch())
//			{
//				if(array_key_exists($item['TASK_ID'], $data))
//				{
//					$data[$item['TASK_ID']]['SE_CHECKLIST'][] = $item;
//				}
//			}

			// get member data
			$res = MemberTable::getList(array('filter' => array('=TASK_ID' => $ids, '=TYPE' => array('A', 'U'))));
			while($item = $res->fetch())
			{
				if(array_key_exists($item['TASK_ID'], $data))
				{
					$data[$item['TASK_ID']][$item['TYPE'] == 'A' ? 'ACCOMPLICES' : 'AUDITORS'][] = $item['USER_ID'];
				}
			}

			// get tag data
			$res = LabelTable::getList([
				'select' => [
					'*',
					'TASK_' => 'TASKS'
				],
				'filter' => [
					'=TASK_ID' => $ids,
				],
			]);

			while($item = $res->fetch())
			{
				if(array_key_exists($item['TASK_ID'], $data))
				{
					$data[$item['TASK_ID']]['TAGS'][] = $item['NAME'];
				}
			}

			// get depends on data
			$res = RelatedTable::getList(array('filter' => array('=TASK_ID' => $ids)));
			while($item = $res->fetch())
			{
				if(array_key_exists($item['TASK_ID'], $data))
				{
					$data[$item['TASK_ID']]['DEPENDS_ON'][] = $item['DEPENDS_ON_ID'];
				}
			}

			foreach ($ids as $taskId)
			{
				$data[$taskId] = array_merge($data[$taskId], $this->processDates($data[$taskId]));
			}
		}

		return array(
			'DATA' => $data,
			'TREE' => $flatTree,
		);
	}

	private function processDates(array $taskData): array
	{
		$result = [];

		/** @var DateTime $createdDate */
		$createdDate = clone $taskData['CREATED_DATE'];
		$createdDate->stripTime();

		$dates = [
			'DEADLINE',
			'START_DATE_PLAN',
			'END_DATE_PLAN',
		];
		foreach ($dates as $key)
		{
			if ($taskData[$key])
			{
				/** @var DateTime $dateDate */
				$dateDate = clone $taskData[$key];
				$dateDate->stripTime();

				$diff = $createdDate->getDiff($dateDate);
				$daysDiff = $diff->format('%d');
				$daysDiff = ($diff->invert ? -$daysDiff : +$daysDiff);

				($now = new DateTime())->addDay($daysDiff);

				/** @var DateTime $newDate */
				$newDate = clone $taskData[$key];
				$newDate->setDate(
					$now->getYearGmt(),
					$now->getMonthGmt(),
					$now->getDayGmt()
				);

				$result[$key] = $newDate;
			}
		}

		return $result;
	}
}