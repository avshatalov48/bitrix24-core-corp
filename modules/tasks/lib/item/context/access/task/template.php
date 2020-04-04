<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 */

namespace Bitrix\Tasks\Item\Context\Access\Task;

use Bitrix\Main\Localization\Loc;

use Bitrix\Tasks\Item\Result;
use Bitrix\Tasks\Util\User;
use Bitrix\Tasks\Item;
use Bitrix\Tasks\Internals\RunTime;
use Bitrix\Tasks\Integration\SocialNetwork;

Loc::loadMessages(__FILE__);

final class Template extends \Bitrix\Tasks\Item\Context\Access
{
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
	 * Checks if $userId can do operation $operation on template $item.
	 * See
	 * \Bitrix\Tasks\Internals\RunTime\Task\Template::getAccessCheckSql()
	 * for SQL-equivalent of this.
	 *
	 * @param $item
	 * @param $userId
	 * @param $operation
	 * @return Result
	 */
	private function askEightBall($item, $userId, $operation)
	{
		if(!$userId)
		{
			$userId = $item->getUserId();
		}

		$result = new Result();

		if(!$this->isEnabled())
		{
			return $result;
		}

		// in socnet super-admin mode we can do everything
		if($userId == User::getId() && SocialNetwork\User::isAdmin($userId))
		{
			return $result;
		}

		$transState = $item->getTransitionState();
		if($transState && $transState->isInProgress())
		{
			// we are in transition state, everything is allowed
			return $result;
		}

		if($item->getId()) // check only for existing item
		{
			$allowed = false;

			// pristine means "the one that is in the database currently"
			$this->disable(); // disable rights checking when getting SE_ACCESS, because of possible fall into endless recursion
			if($item->wasFieldModified('SE_ACCESS'))
			{
				$access = $item->offsetGetPristine('SE_ACCESS');
			}
			else
			{
				// it could be already cached...
				$access = $item['SE_ACCESS'];
			}
			$this->enable(); // enable rights checking back

			if($access instanceof Item\Collection && $access->count()) // access info is available
			{
				$operationId = User::mapAccessOperationNames('task_template', array($operation));
				$operationId = $operationId[$operation];

				if($operationId)
				{
					// need to find if $userId has record for operation $operation
					foreach($access as $rule)
					{
						// todo: currently we have only Uxxx records, but when improving this, we will have to use b_user_access here
						if($rule->getGroupPrefix() == 'U')
						{
							$id = $rule->getGroupId();
							if($id == $userId)
							{
								if(User::checkAccessOperationInLevel($operationId, $rule['TASK_ID']))
								{
									$allowed = true;
									break;
								}
							}
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