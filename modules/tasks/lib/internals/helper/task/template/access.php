<?
namespace Bitrix\Tasks\Internals\Helper\Task\Template;


use Bitrix\Main\ArgumentException;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\SystemException;
use Bitrix\Main\TaskOperationTable;
use Bitrix\Tasks\Internals\Task\Template\AccessTable;

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
	 */
	public static function getAvailableOperations($ids, array $parameters = [])
	{
		$userId = $parameters['USER_ID'];

		// update b_user_access for the chosen user
		$access = new \CAccess();
		$access->updateCodes(['USER_ID' => $userId]);

		$result = [];

		if (!is_array($ids) || empty($ids))
		{
			return $result;
		}

		$query = new Query(AccessTable::getEntity());
		$query->setSelect(['ENTITY_ID', 'OP_ID' => 'T2OP.OPERATION_ID']);
		$query->registerRuntimeField('', new ReferenceField(
			'T2OP',
			TaskOperationTable::getEntity(),
			['=this.TASK_ID' => 'ref.TASK_ID'],
			['join_type' => 'inner']
		));
		$query->whereIn('ENTITY_ID', $ids);
		$query->whereIn('GROUP_CODE', new SqlExpression(
			"SELECT REPLACE(UA.?#, SUBSTRING(UA.?#, LOCATE('_', UA.?#)), '') FROM ?# UA WHERE UA.?# = " . $userId,
			'ACCESS_CODE', 'ACCESS_CODE', 'ACCESS_CODE', 'b_user_access', 'USER_ID'
		));

		$res = $query->exec();

		while ($item = $res->fetch())
		{
			$result[$item['ENTITY_ID']][] = $item['OP_ID'];
		}

		return $result;
	}
}