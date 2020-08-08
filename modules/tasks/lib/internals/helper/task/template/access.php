<?
namespace Bitrix\Tasks\Internals\Helper\Task\Template;


use Bitrix\Main\ArgumentException;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Tasks\Access\TemplateAccessController;

final class Access
{
	/**
	 * Get list of available operations for templates specified by $ids under user specified by $parameters['USER_ID']
	 *
	 * @see \Bitrix\Tasks\Internals\RunTime\Task\Template::getAccessCheck()
	 * @see \Bitrix\Tasks\Internals\RunTime\Task\Template::getAccessCheckSql()
	 *
	 * @param $ids
	 * @param array $parameters
	 * @return array
	 * @throws ArgumentException
	 * @throws SystemException
	 *
	 * @deprecated since tasks 20.6.0
	 */
	public static function getAvailableOperations($ids, array $parameters = [])
	{
		$userId = $parameters['USER_ID'];

		// update b_user_access for the chosen user
		$access = new \CAccess();
		$access->updateCodes(['USER_ID' => $userId]);

		$result = [];

		if (
			!is_array($ids)
			|| empty($ids)
			|| !$userId
		)
		{
			return $result;
		}

		$ops = \Bitrix\Tasks\Util\User::getAccessOperationsForEntity('task_template');
		foreach ($ops as $id => $row)
		{
			$ops[$row['NAME']] = $id;
		}

		foreach ($ids as $id)
		{
			if (TemplateAccessController::can($userId, ActionDictionary::ACTION_TEMPLATE_EDIT, $id))
			{
				$result[$id][] = $ops['read'];
				$result[$id][] = $ops['update'];
				$result[$id][] = $ops['delete'];
			}
			elseif (TemplateAccessController::can($userId, ActionDictionary::ACTION_TEMPLATE_READ, $id))
			{
				$result[$id][] = $ops['read'];
			}
		}

		return $result;
	}
}