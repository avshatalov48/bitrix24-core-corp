<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2015 Bitrix
 *
 * @access private
 *
 * Each method you put here you`ll be able to call as ENTITY_NAME.METHOD_NAME via AJAX and\or REST, so be careful.
 */

namespace Bitrix\Tasks\Dispatcher\PublicAction;

use Bitrix\Main\Data\Cache;
use Bitrix\Main\Result;
use Bitrix\Tasks\Item;
use Bitrix\Tasks\Manager;
use Bitrix\Tasks\Util;
use CTasks;

final class Task extends \Bitrix\Tasks\Dispatcher\RestrictedAction
{
	/**
	 * Get a task
	 */
	public function get($id, array $parameters = array())
	{
		// todo: field access policy here?
		// todo: move to \Bitrix\Tasks\Item\Task (don`t forget ENTITY_SELECT + CAN)
//		$select = array();
//		if(array_key_exists('select', $parameters) && count($parameters['select']))
//		{
//			$select = $parameters['select'];
//		}
//
//		$template = new \Bitrix\Tasks\Item\Task($id);
//
//		return array(
//			'DATA' => $template->getData($select),
//			'CAN' => ...
//		);

		$result = array();
		if ($id = $this->checkTaskId($id))
		{
			$mgrResult = Manager\Task::get(Util\User::getId(), $id, array(
				'ENTITY_SELECT' => $parameters[ 'ENTITY_SELECT' ],
				'PUBLIC_MODE' => true,
				'ERRORS' => $this->errors
			));

			if ($this->errors->checkNoFatals())
			{
				$result = array(
					'ID' => $id,
					'DATA' => $mgrResult[ 'DATA' ],
					'CAN' => $mgrResult[ 'CAN' ]
				);
			}
		}

		return $result;
	}

	/**
	 * Get a list of tasks
	 */
	public function find(array $parameters = array())
	{
		// todo: field access policy here?

		if (!array_key_exists('limit', $parameters) || intval($parameters[ 'limit' ] > 10))
		{
			$parameters['limit' ] = 10;
		}
		$selectIsEmpty = !array_key_exists('select', $parameters) || !count($parameters[ 'select' ]);

		$data = array();
		$result = \Bitrix\Tasks\Item\Task::find($parameters);

		if ($result->isSuccess())
		{
			/** @var Item $item */
			foreach ($result as $item)
			{
				// todo: in case of REST, ALL dates should be converted to the ISO string (write special exporter here, instead of Canonical)
				$data[] = $item->export($selectIsEmpty ? array() : '~'); // export ALL or only selected
			}
		}
		else
		{
			// clear DATA because we do not want error detail info sent to the client
			$this->errors->load($result->getErrors()->transform(array('DATA' => null)));
		}

		return array(
			'DATA' => $data,
		);
	}

	/**
	 * Get a list of tasks
	 * @deprecated
	 * @see \Bitrix\Tasks\Dispatcher\PublicAction\Task::find
	 */
	public function getList(array $order = array(), array $filter = array(), array $select = array(), array $parameters = array())
	{
		$result = array();

		$mgrResult = Manager\Task::getList(Util\User::getId(), array(
			'order' => $order,
			'legacyFilter' => $filter,
			'select' => $select,
		), array(
											   'PUBLIC_MODE' => true
										   ));

		$this->errors->load($mgrResult[ 'ERRORS' ]);

		if ($mgrResult[ 'ERRORS' ]->checkNoFatals())
		{
			$result = array(
				'DATA' => $mgrResult[ 'DATA' ],
				'CAN' => $mgrResult[ 'CAN' ]
			);
		}

		return $result;
	}

	/**
	 * Add a new task
	 */
	public function add(array $data, array $parameters = array('RETURN_DATA' => false))
	{
		// todo: move to \Bitrix\Tasks\Item\Task
		$mgrResult = Manager\Task::add(Util\User::getId(), $data, array(
			'PUBLIC_MODE' => true,
			'ERRORS' => $this->errors,
			'RETURN_ENTITY' => $parameters[ 'RETURN_ENTITY' ]
		));

		return array(
			'ID' => $mgrResult[ 'DATA' ][ 'ID' ],
			'DATA' => $mgrResult[ 'DATA' ],
			'CAN' => $mgrResult[ 'CAN' ],
		);
	}

	/**
	 * Update a task with some new data
	 */
	public function update($id, array $data, array $parameters = array())
	{
		$result = array();

		if ($id = $this->checkTaskId($id))
		{
			if (!empty($data)) // simply nothing to do, not a error
			{
				// todo: move to \Bitrix\Tasks\Item\Task
				$mgrResult = Manager\Task::update(Util\User::getId(), $id, $data, array(
					'PUBLIC_MODE' => true,
					'ERRORS' => $this->errors,
					'THROTTLE_MESSAGES' => $parameters[ 'THROTTLE_MESSAGES' ],

					// there also could be RETURN_CAN or RETURN_DATA, or both as RETURN_ENTITY
					'RETURN_ENTITY' => $parameters[ 'RETURN_ENTITY' ],
				));

				$result['ID' ] = $id;
				$result['DATA' ] = $mgrResult[ 'DATA' ];
				$result['CAN' ] = $mgrResult[ 'CAN' ];

				if ($this->errors->checkNoFatals())
				{
					if ($parameters[ 'RETURN_OPERATION_RESULT_DATA' ])
					{
						$task = $mgrResult[ 'TASK' ];
						$result['OPERATION_RESULT' ] = $task->getLastOperationResultData('UPDATE');
					}
				}
			}
		}

		return $result;
	}

	/**
	 * Delete a task
	 */
	public function delete($id, array $parameters = array())
	{
		$result = array();

		if ($id = $this->checkTaskId($id))
		{
			$result['ID' ] = $id;

			// todo: move to \Bitrix\Tasks\Item\Task
			// this will ONLY delete tags, members, favorites, old depedences, old files, clear cache
			$task = \CTaskItem::getInstance($id, Util\User::getId());

			try
            {
                $task->delete();

                $cache = Cache::createInstance();
                $cache->clean(CTasks::FILTER_LIMIT_CACHE_KEY, \CTasks::CACHE_TASKS_COUNT_DIR_NAME);
            }
            catch(\TasksException $e)
            {

            }
		}

		return $result;
	}

	public function settaskcontrol($id, $value, array $parameters = [])
	{
		$result = [];

		$result['ID'] = $id;

		$task = \CTaskItem::getInstance($id, Util\User::getId());
		$task->update(['TASK_CONTROL' => $value]);

		return $result;
	}

	public function setdeadline($id, $newDeadline, array $parameters = array())
	{
		$result = array();

		if ($id = $this->checkTaskId($id))
		{
			$result['ID' ] = $id;

			$task = \CTaskItem::getInstance($id, Util\User::getId());
			$task->update(array('DEADLINE' => $newDeadline));
		}

		return $result;
	}

	/**
	 * Set task to the Sprint id.
	 * @param int $id Task id.
	 * @param int $sprintId Sprint id.
	 * @return array
	 */
	public function setSprint($id, $sprintId)
	{
		$result = ['result' => false];
		$taskId = intval($id);
		$sprintId = intval($sprintId);

		if ($taskId && $sprintId)
		{
			$res = \Bitrix\Tasks\Kanban\SprintTable::addToSprint(
				$sprintId,
				$taskId
			);
			if ($res->isSuccess())
			{
				$result = ['result' => true];
			}
			else
			{
				$result['ERRORS'] = $res->getErrorMessages();
			}
		}

		return $result;
	}

	public function substractDeadline($id, $num, $type, array $parameters = array())
	{
		$num *= -1;

		return $this->adjustDeadline($id, $num, $type, $parameters);
	}

	public function adjustDeadline($id, $num, $type, array $parameters = array())
	{
		$result = array();

		if ($id = $this->checkTaskId($id))
		{
			$result['ID' ] = $id;

			$task = \CTaskItem::getInstance($id, Util\User::getId());
			$arTask = $task->getData(false);

			if (empty($arTask['DEADLINE']))
			{
				return $result;
			}

			$deadline = Util\Type\DateTime::createFromUserTime($arTask['DEADLINE']);
			$deadline = $deadline->add(($num < 0 ? '-' : '').abs($num).' '.$type);

			$task->update(array('DEADLINE' => $deadline));
		}

		return $result;
	}

	public function addtofavorite($id, array $parameters = array())
	{
		$result = array();

		if ($id = $this->checkTaskId($id))
		{
			$result['ID' ] = $id;

			$task = \CTaskItem::getInstance($id, Util\User::getId());
			$task->addToFavorite();
		}

		return $result;
	}

	public function removefromfavorite($id, array $parameters = array())
	{
		$result = array();

		if ($id = $this->checkTaskId($id))
		{
			$result['ID' ] = $id;

			$task = \CTaskItem::getInstance($id, Util\User::getId());
			$task->deleteFromFavorite();
		}

		return $result;
	}

	/**
	 * Delegates a task to a new responsible
	 */
	public function delegate($id, $userId)
	{
		$result = [];

		if ($id = $this->checkTaskId($id))
		{
			try
			{
				// todo: move to \Bitrix\Tasks\Item\Task
				$task = \CTaskItem::getInstance($id, Util\User::getId());
				$task->delegate($userId);
			}
			catch (\TasksException $exception)
			{
				$result['ERRORS'][] = $exception->getMessageOrigin();
			}
		}

		return $result;
	}

	/**
	 * Check if a specified task is readable by the current user
	 */
	public function checkCanRead($id)
	{
		$result = array('READ' => false);

		if ($id = $this->checkTaskId($id))
		{
			// todo: move to \Bitrix\Tasks\Item\Task
			$task = \CTaskItem::getInstance($id, Util\User::getId());
			$result['READ' ] = $task->checkCanRead();
		}

		return $result;
	}

	/**
	 * Start execution of a specified task
	 */
	public function start($id)
	{
		$result = array();

		if ($id = $this->checkTaskId($id))
		{
			// todo: move to \Bitrix\Tasks\Item\Task
			$task = \CTaskItem::getInstance($id, Util\User::getId());
			$task->startExecution();
		}

		return $result;
	}

	/**
	 * Pause execution of a specified task
	 */
	public function pause($id)
	{
		$result = array();

		if ($id = $this->checkTaskId($id))
		{
			// todo: move to \Bitrix\Tasks\Item\Task
			$task = \CTaskItem::getInstance($id, Util\User::getId());
			$task->pauseExecution();
		}

		return $result;
	}

	/**
	 * Complete a specified task
	 */
	public function complete($id)
	{
		$result = array();

		if ($id = $this->checkTaskId($id))
		{
			// todo: move to \Bitrix\Tasks\Item\Task
			$task = \CTaskItem::getInstance($id, Util\User::getId());
			$task->complete();
		}

		return $result;
	}

	/**
	 * Renew (switch to status "pending, await execution") a specified task
	 */
	public function renew($id)
	{
		$result = array();

		if ($id = $this->checkTaskId($id))
		{
			// todo: move to \Bitrix\Tasks\Item\Task
			$task = \CTaskItem::getInstance($id, Util\User::getId());
			$task->renew();
		}

		return $result;
	}

	/**
	 * Defer (put aside) a specified task
	 */
	public function defer($id)
	{
		$result = array();

		if ($id = $this->checkTaskId($id))
		{
			// todo: move to \Bitrix\Tasks\Item\Task
			$task = \CTaskItem::getInstance($id, Util\User::getId());
			$task->defer();
		}

		return $result;
	}

	/**
	 * Approve (confirm the result of) a specified task
	 */
	public function approve($id)
	{
		$result = array();

		if ($id = $this->checkTaskId($id))
		{
			// todo: move to \Bitrix\Tasks\Item\Task
			$task = \CTaskItem::getInstance($id, Util\User::getId());
			$task->approve();
		}

		return $result;
	}

	/**
	 * Disapprove (reject the result of) a specified task
	 */
	public function disapprove($id)
	{
		$result = array();

		if ($id = $this->checkTaskId($id))
		{
			// todo: move to \Bitrix\Tasks\Item\Task
			$task = \CTaskItem::getInstance($id, Util\User::getId());
			$task->disapprove();
		}

		return $result;
	}

	/**
	 * Become an auditor of a specified task
	 */
	public function enterAuditor($id)
	{
		$result = array();

		if ($id = $this->checkTaskId($id))
		{
			// todo: move to \Bitrix\Tasks\Item\Task
			$task = \CTaskItem::getInstance($id, Util\User::getId());
			$task->startWatch();
		}

		return $result;
	}

	/**
	 * Stop being an auditor of a specified task
	 */
	public function leaveAuditor($id)
	{
		$result = array();

		if ($id = $this->checkTaskId($id))
		{
			// todo: move to \Bitrix\Tasks\Item\Task
			$task = \CTaskItem::getInstance($id, Util\User::getId());
			$task->stopWatch();
		}

		return $result;
	}

	public function addAuditor($id, $auditorId)
	{
		$result = array();

		$task = \CTaskItem::getInstance($id, Util\User::getId());
		$arTask = $task->getData(false);
		$arTask['AUDITORS'][] = $auditorId;
		$task->update(array('AUDITORS' => $arTask['AUDITORS']));

		return $result;
	}

	public function addAccomplice($id, $accompliceId)
	{
		$result = array();

		$task = \CTaskItem::getInstance($id, Util\User::getId());
		$arTask = $task->getData(false);
		$arTask['ACCOMPLICES'][] = $accompliceId;
		$task->update(array('ACCOMPLICES' => $arTask['ACCOMPLICES']));

		return $result;
	}

	public function setGroup($id, $groupId)
	{
		$result = array();

		$task = \CTaskItem::getInstance($id, Util\User::getId());
		$task->update(array('GROUP_ID' => $groupId));

		return $result;
	}

	public function setResponsible($id, $responsibleId)
	{
		$result = array();

		$task = \CTaskItem::getInstance($id, Util\User::getId());
		$task->update(array('RESPONSIBLE_ID' => $responsibleId));

		return $result;
	}

	public function setOriginator($id, $originatorId)
	{
		$result = array();

		$task = \CTaskItem::getInstance($id, Util\User::getId());
		$task->update(array('CREATED_BY' => $originatorId));

		return $result;
	}
}