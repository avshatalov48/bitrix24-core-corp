<?
/**
 * This class could be changed (removed, renamed, relocated) in any time, so do not use it in public code
 *
 * @internal
 * @access private
 */

namespace Bitrix\Tasks\Processor\Task;

use Bitrix\Tasks\Internals\Helper\Task\Dependence;
use Bitrix\Tasks\Internals\RunTime;
use Bitrix\Tasks\Processor\Task\AutoCloser\Result\Impact;

final class AutoCloser extends \Bitrix\Tasks\Processor
{
	const STATUS_COMPLETE = 5;

	/**
	 * Get task parent tree, and complete some of sub-tasks
	 *
	 * @param $id int task
	 * @param mixed[] changed fields
	 * @param mixed[] $settings
	 * @return \Bitrix\Tasks\Processor\Task\Result
	 */
	public function processEntity($id, $data = array(), array $settings = array())
	{
		$result = parent::processEntity($id, $data, $settings);

		// todo: this code will not work if someone creates already closed sub-task with this auto_close = on
		$id = intval($id);
		if(!$id || $data['STATUS'] != static::STATUS_COMPLETE)
		{
			return $result;
		}

		// todo: Impact class is TEMPORAL, it should be replaced with (or at least inherited from) \Bitrix\Tasks\Item\Task when ready
		//$taskImpact = new Impact($id, $this->getUserId());

		$worker = $this;

		$globalTree = $this->getParentTree($id);
		$prevData = $globalTree->getNodeData($id);

		if($prevData)
		{
			// close sub-tasks, if current task (and\or sub tasks) has AUTO_CLOSE == Y
			if($prevData['AUTO_CLOSE'] == 'Y')
			{
				$subTree = $globalTree->getSubTree($id);
				$subTree->walkWidth(function($item, $itemId, $itemData, $parentId) use($subTree, $id, $worker) {

					//_print_r($itemData['TASK_ID'].' '.$itemData['TITLE'].' <- '.$parentId);

					$impactData = null;

					$parentData = $subTree->getNodeData($parentId);
					$parentImpact = $worker->getImpactById($parentId);
					if($parentImpact)
					{
						$parentData = $parentImpact;
					}

					if($parentData && $parentData['AUTO_CLOSE'] == 'Y')
					{
						if($itemData['STATUS'] != 5)
						{
							$itemData['ID'] = $itemData['__ID'];
							$impact = new Impact($itemData, $worker->getUserId());
							$impact->setDataUpdated(array('STATUS' => 5));

							$worker->addImpact($impact);
						}
					}
				});
			}

			// close parent tasks, if they have AUTO_CLOSE == Y
			$closeIndex = array();
			$globalTree->walkDepth(function($item, $itemId, $itemData, $parentId) use(&$closeIndex, $worker, $globalTree) {

				//_print_r($itemData['TASK_ID'].' '.$itemData['TITLE'].' <- '.$parentId);

				if(!array_key_exists($parentId, $closeIndex))
				{
					$closeIndex[$parentId] = $globalTree->getChildrenCount($parentId);
				}

				$impact = $worker->getImpactById($itemId);
				if($impact)
				{
					$itemData = $impact->getUpdatedData();
				}

				if($itemData['STATUS'] == 5) // was closed before or just closed
				{
					$closeIndex[$parentId]--;
				}

				if($parentId && !$closeIndex[$parentId] && !$worker->hasImpact($parentId))
				{
					// all children task closed
					$parentTaskData = $globalTree->getNodeData($parentId);

					if($parentTaskData['AUTO_CLOSE'] == 'Y')
					{
						$parentTaskData['ID'] = $parentTaskData['__ID'];
						$impact = new Impact($parentTaskData, $worker->getUserId());
						$impact->setDataUpdated(array('STATUS' => 5));

						$worker->addImpact($impact);
					}
				}
			});
		}

		$result->setData($this->affected);

		return $result;
	}

	private function getParentTree($id)
	{
		return Dependence::getParentTree($id, Runtime::apply(array(
			'select' => array(
				'TASK_ID',
				'PARENT_TASK_ID',
				'TITLE' => 'TASK.TITLE', // tmp
				'PARENT_ID' => 'TASK.PARENT_ID',
				'AUTO_CLOSE' => 'PARAMETER.VALUE',
				'STATUS' => 'TASK.STATUS',
			),
			'group' => array(
				'AUTO_CLOSE', // ensure we will not get duplicates in the result
			)
		), array(
			RunTime\Task::getTask(array('REF_FIELD' => 'TASK_ID', 'JOIN_TYPE' => 'inner')),
			RunTime\Task\Parameter::getParameter(array('REF_FIELD' => 'TASK_ID', 'CODE' => 2)),
		)));
	}
}