<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 */

namespace Bitrix\Tasks\Item\Access\Task;

use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\NotImplementedException;

use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Tasks\Access\Model\TemplateModel;
use Bitrix\Tasks\Access\Permission\PermissionDictionary;
use Bitrix\Tasks\Access\Permission\TasksTemplatePermissionTable;
use Bitrix\Tasks\Access\TemplateAccessController;
use Bitrix\Tasks\Item\Result;
use Bitrix\Tasks\Provider\TemplateProvider;
use Bitrix\Tasks\Util\User;

Loc::loadMessages(__FILE__);

final class Template extends \Bitrix\Tasks\Item\Access
{
	private const LEGACY_LEVELS = [
		'full',
		'read'
	];

	/* @var TemplateAccessController $accessController */
	private $accessController;
	private $templateModel;

	/**
	 * Alters query parameters to check access rights on database side
	 *
	 * @param mixed[]|\Bitrix\Main\Entity\Query query parameters or query itself
	 * @param mixed[] $parameters
	 * @return mixed
	 */
	public function addDataBaseAccessCheck($query, array $parameters = array())
	{
		global $DB, $USER_FIELD_MANAGER;

		$provider = new TemplateProvider($DB, $USER_FIELD_MANAGER);
		$res = $provider->getList(
			['ID' => 'DESC'],
			[
				'BASE_TEMPLATE_ID' => 0
			],
			['ID'],
			[
				'USER_ID' => (int) User::getId(),
				'USER_IS_ADMIN' => false
			],
			[]
		);

		$templateIds = [0];
		while ($row = $res->Fetch())
		{
			$templateIds[] = $row['ID'];
		}

		$query['filter']['@ID'] = new SqlExpression(implode(',', $templateIds));

		return $query;
	}

	public function canRead($item, $userId = 0)
	{
		$accessController = $this->getAccessController($item->getUserId());
		$res = $accessController->check(ActionDictionary::ACTION_TEMPLATE_READ, $this->getTemplateModel($item));
		return $this->makeResult($res, 'read');
	}

	public function canFetchData($item, $userId = 0)
	{
		return $this->canRead($item, $userId);
	}

	public function canUpdate($item, $userId = 0)
	{
		$accessController = $this->getAccessController($item->getUserId());
		$res = $accessController->check(ActionDictionary::ACTION_TEMPLATE_EDIT, $this->getTemplateModel($item));
		return $this->makeResult($res, 'update');
	}

	public function canDelete($item, $userId = 0)
	{
		$accessController = $this->getAccessController($item->getUserId());
		$res = $accessController->check(ActionDictionary::ACTION_TEMPLATE_REMOVE, $this->getTemplateModel($item));
		return $this->makeResult($res, 'delete');
	}

	public function canCreate($item, $userId = 0)
	{
		$accessController = $this->getAccessController($item->getUserId());
		$res = $accessController->check(ActionDictionary::ACTION_TEMPLATE_CREATE, $this->getTemplateModel($item));
		return $this->makeResult($res, 'create');
	}

	public function canUpdateRights($item, $userId = 0)
	{
		return $this->canUpdate($item, $userId);
	}

	/**
	 * Grant access level to a specified template for a specified group, and then saves the template
	 *
	 * @param $templateId
	 * @param $groupCode
	 * @param $level
	 * @param array $parameters
	 * @return Result
	 * @throws NotImplementedException
	 * @throws \Exception
	 */
	public static function grantAccessLevel($templateId, $groupCode, $level, array $parameters = array())
	{
		$result = new Result();

		$templateId = intval($templateId);
		$groupCode = trim((string) $groupCode);
		$level = trim((string) $level);

		if(!$templateId || !$groupCode || !$level)
		{
			$result->addError('ILLEGAL_ARGUMENT', 'Illegal argument');
		}

		if(!in_array($level, self::LEGACY_LEVELS))
		{
			$result->addError('ILLEGAL_ARGUMENT', 'Unknown access level to grant');
		}

		$checkRights = !($parameters['CHECK_RIGHTS'] === false);
		if($checkRights)
		{
			throw new NotImplementedException('CHECK_RIGHTS === true is not supported currently');
		}

		if($result->isSuccess())
		{
			$wereErrors = false;
			$saveResults = [];

			$saveResult = new \Bitrix\Tasks\Util\Result();

			$neededLevel = PermissionDictionary::TEMPLATE_VIEW;

			if ($level === 'full')
			{
				$neededLevel = PermissionDictionary::TEMPLATE_FULL;
			}

			$addResult = TasksTemplatePermissionTable::add([
				'TEMPLATE_ID' 		=> $templateId,
				'ACCESS_CODE' 		=> $groupCode,
				'PERMISSION_ID' 	=> $neededLevel,
				'VALUE' 			=> 1
			]);
			if(!$addResult->isSuccess())
			{
				$saveResult->adoptErrors($addResult);
				$wereErrors = true;
			}

			$saveResults[] = $saveResult;

			$result->setData($saveResults);
			if($wereErrors)
			{
				$result->addWarning('ACTION_INCOMPLETE', 'Some levels were not granted');
			}
		}

		return $result;
	}

	public static function revokeAccessLevel($templateId, $userId, $level)
	{
		// todo
	}

	public static function revokeAll($templateId, array $parameters = array())
	{
		$result = new Result();

		$templateId = intval($templateId);

		if(!$templateId)
		{
			$result->addError('ILLEGAL_ARGUMENT', 'Illegal argument');
		}

		$checkRights = !($parameters['CHECK_RIGHTS'] === false);
		if($checkRights)
		{
			throw new NotImplementedException('CHECK_RIGHTS === true is not supported currently');
		}

		if($result->isSuccess())
		{
			// just kill them all at low level, without any check
			$dc = TasksTemplatePermissionTable::class;
			$grants = $dc::getList(array('filter' => array('=TEMPLATE_ID' => $templateId)))->fetchAll();
			$wereErrors = false;
			$delResults = array();
			foreach($grants as $grant)
			{
				$delResult = $dc::delete($grant['ID']);
				if(!$delResult->isSuccess())
				{
					$wereErrors = true;
				}

				$delResults[] = $delResult;
			}

			if($wereErrors)
			{
				$result->addWarning('ACTION_INCOMPLETE', 'Some grants were not removed');
			}

			$result->setData($delResults);
		}

		return $result;
	}

	private function getAccessController($userId = 0): TemplateAccessController
	{
		if (!$this->accessController)
		{
			$userId = ($userId ?: (int) User::getId());
			$this->accessController = new TemplateAccessController($userId);
		}
		return $this->accessController;
	}

	private function getTemplateModel($item)
	{
		if (!$this->templateModel)
		{
			$this->templateModel = TemplateModel::createFromId((int) $item->id);
		}
		return $this->templateModel;
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
			$result->addError('ACCESS_DENIED', Loc::getMessage('TASKS_TASK_TEMPLATE_ACCESS_DENIED', array(
				'#OP_NAME#' => Loc::getMessage('TASKS_COMMON_OP_'.mb_strtoupper($operation))
			)));
		}
		return $result;
	}
}