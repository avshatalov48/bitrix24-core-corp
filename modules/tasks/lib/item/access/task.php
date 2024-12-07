<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 */

namespace Bitrix\Tasks\Item\Access;

use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Tasks\Access\Model\TaskModel;
use Bitrix\Tasks\Access\Role\RoleDictionary;
use Bitrix\Tasks\Access\TaskAccessController;
use Bitrix\Tasks\Integration\SocialNetwork\Group;
use Bitrix\Tasks\Internals\Log\Log;
use Bitrix\Tasks\Item\Result;
use Bitrix\Tasks\Util\User;
use Bitrix\Tasks\Internals\Runtime;

final class Task extends \Bitrix\Tasks\Item\Access
{
	private $accessController;
	private $taskModel;

	/**
	 * Alters query parameters to check access rights on database side
	 *
	 * @param mixed[]|\Bitrix\Main\Entity\Query query parameters or query itself
	 * @param mixed[] $parameters
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public function addDataBaseAccessCheck($query, array $parameters = array())
	{
		if (!$this->isEnabled())
		{
			return $query;
		}

		$applyFilter = [];
		if (array_key_exists('=ID', $query['filter']))
		{
			$applyFilter = ['ID' => $query['filter']['=ID']];
		}

		$accessCheckSql = Runtime\Task::getAccessCheckSql([
			'USER_ID' => $parameters['USER_ID'],
			'APPLY_FILTER' => $applyFilter,
		])['sql'];

		if ($accessCheckSql !== '')
		{
			$query['filter']['@ID'] = new SqlExpression($accessCheckSql);
		}

		return Runtime::apply($query, []);
	}

	public function canCreate($item, $userId = 0)
	{
		return $this->makeResult(true, 'create');
	}

	public function canUpdate($item, $userId = 0)
	{
		return $this->makeResult(true, 'update');
	}

	public function canRead($item, $userId = 0)
	{
		$accessController = $this->getAccessController($item->getUserId());
		$res = $accessController->check(ActionDictionary::ACTION_TASK_READ, $this->getTaskModel($item));
		return $this->makeResult($res, 'read');
	}

	public function canDelete($item, $userId = 0)
	{
		$accessController = $this->getAccessController($item->getUserId());
		$res = $accessController->check(ActionDictionary::ACTION_TASK_REMOVE, $this->getTaskModel($item));
		return $this->makeResult($res, 'delete');
	}

	public function canFetchData($item, $userId = 0)
	{
		$accessController = $this->getAccessController($item->getUserId());
		$res = $accessController->check(ActionDictionary::ACTION_TASK_READ, $this->getTaskModel($item));
		return $this->makeResult($res, 'read');
	}

	private function getAccessController($userId = 0): TaskAccessController
	{
		if (!$this->accessController)
		{
			$userId = ($userId ?: (int) User::getId());
			$this->accessController = new TaskAccessController($userId);
		}
		return $this->accessController;
	}

	private function getTaskModel($item)
	{
		if (!$this->taskModel)
		{
			$this->taskModel = TaskModel::createFromTaskItem($item);
		}
		return $this->taskModel;
	}

	private function makeResult($res, $operation)
	{
		$result = new Result();

		if (!$this->isEnabled())
		{
			return $result;
		}

		if (!$res)
		{
			$result->addError('ACCESS_DENIED', Loc::getMessage('TASKS_TASK_ACCESS_DENIED', array(
				'#OP_NAME#' => Loc::getMessage('TASKS_COMMON_OP_'.mb_strtoupper($operation))
			)));
		}
		return $result;
	}
}