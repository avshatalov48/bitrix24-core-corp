<?
/**
 * @internal
 * @access private
 */

namespace Bitrix\Tasks\Internals\RunTime\Task;

use Bitrix\Main\Entity;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Tasks\Access\Model\UserModel;
use Bitrix\Tasks\Access\Permission\TasksTemplatePermissionTable;
use Bitrix\Tasks\Util\User;
use Bitrix\Tasks\Integration\SocialNetwork;

final class Template extends \Bitrix\Tasks\Internals\Runtime
{
	/**
	 * Returns runtime field that is, being attached to an ORM query, leaves visible only items with certain operations allowed
	 *
	 * @param array $parameters
	 *  <li>OPERATION_NAME string[]|string
	 *  <li>OPERATION_ID int[]|int
	 *  <li>USER_ID int
	 * @return array
	 */
	public static function getAccessCheck(array $parameters)
	{
		$result = array();

		$parameters = static::checkParameters($parameters);

		// in socnet super-admin mode we can see all templates, but in other case...
		if(!($parameters['USER_ID'] == User::getId() && SocialNetwork\User::isAdmin()))
		{
			$query = static::getAccessCheckSql($parameters);

			$rtName = isset($parameters['NAME']) ? (string) $parameters['NAME'] : 'ACCESS';
			$rf = $parameters['REF_FIELD'];
			$rfName = ((string) $rf != '' ? $rf : 'ID');

			$sql = $query['sql'];

			// make virtual entity to be able to join it
			$entity = Entity\Base::compileEntity('TasksAccessCheck'.randString().'Table', array(
				new Entity\IntegerField('TEMPLATE_ID', array(
					'primary' => true
				))
			), array(
				'table_name' => '('.preg_replace('#/\*[^(/\*)(\*/)]*\*/#', '', $sql).')', // remove possible comments, orm does not like them
			));

			$result[] = new ReferenceField(
				$rtName,
				$entity,
				array(
					'=this.'.$rfName => 'ref.TEMPLATE_ID',
				),
				array('join_type' => 'inner')
			);
		}

		return array('runtime' => $result);
	}

	/**
	 * Returns sql that is, being attached to a select query, leaves visible only items with certain operations allowed
	 *
	 * @param array $parameters
	 *  <li>OPERATION_NAME string[]|string
	 *  <li>OPERATION_ID int[]|int
	 *  <li>USER_ID int
	 * @return array
	 */
	public static function getAccessCheckSql(array $parameters)
	{
		$parameters = static::checkParameters($parameters);

		$userId = $parameters['USER_ID'];
		$user = UserModel::createFromId($userId);
		$accessCodes = $user->getAccessCodes();

		if (empty($accessCodes))
		{
			$accessCodes = ['UUU'];
		}

		$q = new Entity\Query(TasksTemplatePermissionTable::getEntity());
		$q->setSelect(['TEMPLATE_ID' => 'TEMPLATE_ID']);
		$q->whereIn('ACCESS_CODE', $accessCodes);
		$q->setGroup(['TEMPLATE_ID']);

		return array(
			'sql' => $q->getQuery(),
		);
	}
}