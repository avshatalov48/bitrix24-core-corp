<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 */

namespace Bitrix\Tasks\Item\Access\Task;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\NotImplementedException;
use Bitrix\Main\UserAccessTable;

use Bitrix\Tasks\Item;
use Bitrix\Tasks\Item\Result;
use Bitrix\Tasks\Internals\RunTime;
use Bitrix\Tasks\Integration\SocialNetwork;
use Bitrix\Tasks\Util\User;

Loc::loadMessages(__FILE__);

final class Template extends \Bitrix\Tasks\Item\Access
{
	private static $accessCodes = null;

	/**
	 * Alters query parameters to check access rights on database side
	 *
	 * @param mixed[]|\Bitrix\Main\Entity\Query query parameters or query itself
	 * @param mixed[] $parameters
	 * @return mixed
	 */
	public function addDataBaseAccessCheck($query, array $parameters = array())
	{
		if(!$this->isEnabled())
		{
			return $query;
		}

		return RunTime::apply($query, array(
			RunTime\Task\Template::getAccessCheck(array(
				'OPERATION_NAME' => array(
					'read'
				),
				'USER_ID' => $parameters['USER_ID'],
			))
		));
	}

	public function canRead($item, $userId = 0)
	{
		return $this->askEightBall($item, $userId, 'read');
	}

	public function canUpdate($item, $userId = 0)
	{
		return $this->askEightBall($item, $userId, 'update');
	}

	public function canDelete($item, $userId = 0)
	{
		return $this->askEightBall($item, $userId, 'delete');
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

		$level = User::getAccessLevel('TASK_TEMPLATE', $level);
		if(!$level)
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
			$saveResults = array();

			$dc = Item\Task\Template\Access::getDataSourceClass();

			// todo: there could be several levels to add

			$saveResult = new \Bitrix\Tasks\Util\Result();

			// just add through orm
			$addResult = $dc::add(array(
				'ENTITY_ID' => $templateId,
				'GROUP_CODE' => $groupCode,
				'TASK_ID' => $level['ID'],
			));
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
			$dc = Item\Task\Template\Access::getDataSourceClass();
			$grants = $dc::getList(array('filter' => array('=ENTITY_ID' => $templateId)))->fetchAll();
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

	/**
	 * Checks if $userId can do operation $operation on template $item.
	 * See
	 * \Bitrix\Tasks\Internals\RunTime\Task\Template::getAccessCheckSql()
	 * for SQL-equivalent of this.
	 *
	 * @param Item $item
	 * @param $userId
	 * @param $operation
	 * @return Result
	 */
	private function askEightBall($item, $userId, $operation)
	{
		$result = new Result();

		if (!$userId)
		{
			$userId = $item->getUserId();
		}

		if (!$this->isEnabled())
		{
			return $result;
		}

		// in socnet super-admin mode we can do everything
		if ($userId == User::getId() && SocialNetwork\User::isAdmin($userId))
		{
			return $result;
		}

		$transState = $item->getTransitionState();
		if ($transState && $transState->isInProgress())
		{
			// we are in transition state, everything is allowed
			return $result;
		}

		if ($item->getId()) // check only for existing item
		{
			$allowed = false;

			// pristine means "the one that is in the database currently"
			$this->disable(); // disable rights checking when getting SE_ACCESS, because of possible fall into endless recursion

			if ($item->isFieldModified('SE_ACCESS'))
			{
				$access = $item->offsetGetPristine('SE_ACCESS');
			}
			else
			{
				// it could be already cached...
				$access = $item['SE_ACCESS'];
			}

			$this->enable(); // enable rights checking back

			if ($access instanceof Item\Collection && $access->count()) // access info is available
			{
				$operationId = User::mapAccessOperationNames('task_template', [$operation]);
				$operationId = $operationId[$operation];

				if ($operationId)
				{
					// need to find if $userId has record for operation $operation
					/** @var Item\Task\Template\Access $rule */
					foreach ($access as $rule)
					{
						$proceed = false;
						$entityId = $rule->getGroupId();
						$entityPrefix = $rule->getGroupPrefix();
						$entityCode = $entityPrefix . $entityId;

						if ($entityPrefix == 'U')
						{
							$proceed = $entityId == $userId;
						}
						else if ($entityPrefix == 'SG' || $entityPrefix == 'DR')
						{
							if (!static::$accessCodes[$userId])
							{
								$accessCodes = UserAccessTable::getList([
									'select' => ['ACCESS_CODE'],
									'filter' => [
										'=USER_ID' => $userId,
										'!PROVIDER_ID' => 'group'
									]
								])->fetchAll();

								foreach ($accessCodes as $key => $value)
								{
									$code = $value['ACCESS_CODE'];

									if ($str = strstr($code, '_'))
									{
										$code = str_replace($str, '', $code);
									}

									static::$accessCodes[$userId][] = $code;
								}
							}

							$proceed = in_array($entityCode, static::$accessCodes[$userId]);
						}

						if ($proceed && User::checkAccessOperationInLevel($operationId, $rule['TASK_ID']))
						{
							$allowed = true;
							break;
						}
					}
				}
			}

			if(!$allowed)
			{
				$result->addError('ACCESS_DENIED', Loc::getMessage('TASKS_TASK_TEMPLATE_ACCESS_DENIED', array(
					'#OP_NAME#' => Loc::getMessage('TASKS_COMMON_OP_'.ToUpper($operation))
				)));
			}
		}

		return $result;
	}
}