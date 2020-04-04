<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 */

namespace Bitrix\Tasks\Item\Context\Access;

use Bitrix\Tasks\Integration\SocialNetwork\Group;
use Bitrix\Tasks\Item\Result;
use Bitrix\Tasks\Util\User;
use Bitrix\Tasks\Item;
use Bitrix\Tasks\Internals\Runtime;

final class Task extends \Bitrix\Tasks\Item\Context\Access
{
	/**
	 * Alters query parameters to check access rights on database side
	 *
	 * @param mixed[]|\Bitrix\Main\Entity\Query query parameters or query itself
	 * @param mixed[] $parameters
	 * @return array
	 */
	public function addDataBaseAccessCheck($query, array $parameters = array())
	{
		if(!$this->isEnabled())
		{
			return $query;
		}

		return Runtime::apply($query, array(
			Runtime\Task::getAccessCheck(array(
				'USER_ID' => $parameters['USER_ID'],
			)
		)));
	}

	public function canCreate($item, $userId = 0)
	{
		$result = new Result();

		$userId = $item->getUserId();
		if(!User::isSuper($userId)) // no access check for admins
		{
			$rErrors = $result->getErrors();
			$data = $item;

			$state = $item->getTransitionState();
			if($state->isInProgress())
			{
				$data = $state;
			}

			if($data['RESPONSIBLE_ID'] != $userId && $data['CREATED_BY'] != $userId)
			{
				$rErrors->add('RESPONSIBLE_AND_ORIGINATOR_NOT_ALLOWED', 'You can not add task from other person to another person');
			}

			$groupId = intval($data['GROUP_ID']);
			if($groupId)
			{
				if(!Group::can($groupId, Group::ACTION_CREATE_TASKS, $userId))
				{
					$rErrors->add('PROJECT_ACCESS_DENIED', 'You are not allowed to create tasks in the group [group: '.$groupId.', user: '.$userId.']');
				}
			}
		}

		return $result;
	}

	public function canUpdate($item, $userId = 0)
	{
		/*
		 //todo
			$actionChangeDeadlineFields = array('DEADLINE', 'START_DATE_PLAN', 'END_DATE_PLAN', 'DURATION');
			$arGivenFieldsNames = array_keys($arFields);

			if (
				array_key_exists('CREATED_BY', $arFields)
				&& ( ! $this->isActionAllowed(self::ACTION_CHANGE_DIRECTOR) )
			)
			{
				throw new TasksException('Access denied for originator to be updated', TasksException::TE_ACTION_NOT_ALLOWED);
			}

			if (
				// is there fields to be checked for ACTION_CHANGE_DEADLINE?
				array_intersect($actionChangeDeadlineFields, $arGivenFieldsNames)
				&& ( ! $this->isActionAllowed(self::ACTION_CHANGE_DEADLINE) )
			)
			{
				throw new TasksException('Access denied for plan dates to be updated', TasksException::TE_ACTION_NOT_ALLOWED);
			}

			// Get list of fields, except just checked above
			$arGeneralFields = array_diff(
				$arGivenFieldsNames,
				array_merge($actionChangeDeadlineFields, array('CREATED_BY'))
			);

			// Is there is something more for update?
			if ( ! empty($arGeneralFields) )
			{
				if ( ! $this->isActionAllowed(self::ACTION_EDIT) )
					throw new TasksException('Access denied for task to be updated', TasksException::TE_ACTION_NOT_ALLOWED);
			}
		 */

		return new Result();
	}

	public function canUpdatePlanDates($item, $userId = 0)
	{
		$userId = intval($userId);
		if(!$userId)
		{
			$userId = $item->getUserId();
		}

		return User::isSuper($userId) || $this->isRoleCreatorOrDirectorOfCreator($item, $userId) || (
			$item['RESPONSIBLE_ID'] == $userId
			&&
			$item['ALLOW_CHANGE_DEADLINE'] == 'Y'
		);
	}

	// todo: refactor role mechanism, to be able to add new user-specified roles
	// todo: there will be system pre-defined roles, for backward compatibility
	// todo: avoid getting the entire task data when checking rights, it is better to do it in a lazy manner
	private function isRoleCreatorOrDirectorOfCreator($item, $userId)
	{
		if($item['CREATED_BY'] == $userId)
		{
			return true;
		}

		return array_key_exists($item['CREATED_BY'], $this->getSubordinate($userId));
	}

	private function getSubordinate($userId)
	{
		static $cache = array();

		if(!array_key_exists($userId, $cache))
		{
			$cache[$userId] = array_flip(\Bitrix\Tasks\Integration\Intranet\User::getSubordinate($userId, null, true));
		}

		return $cache[$userId];
	}
}