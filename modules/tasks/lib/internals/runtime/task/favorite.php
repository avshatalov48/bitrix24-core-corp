<?
/**
 * @internal
 * @access private
 */

namespace Bitrix\Tasks\Internals\RunTime\Task;

use Bitrix\Main\Entity;

final class Favorite extends \Bitrix\Tasks\Internals\Runtime
{
	/**
	 * Returns a runtime field that indicates if tasks are in favorite of the given user(s)
	 *
	 * @param array $parameters
	 * @return array
	 */
	public static function getFlag(array $parameters)
	{
		$result = array();

		$parameters = static::checkParameters($parameters);
		$rf = $parameters['REF_FIELD'];

		// join favorite table
		$result[] = new Entity\ReferenceField(
			'FAVORITE',
			'Bitrix\Tasks\Task\Favorite',
			array(
				'=this.'.((string) $rf != '' ? $rf : 'ID') => 'ref.TASK_ID',
				'=ref.USER_ID' => array('?', $parameters['USER_ID'])
			)
		);

		// add flag-indicator
		$result[] = new Entity\ExpressionField(
			$parameters['NAME'],
			'CASE WHEN %s IS NOT NULL THEN 1 ELSE 0 END',
			array('FAVORITE.TASK_ID')
		);

		return array('runtime' => $result);
	}
}