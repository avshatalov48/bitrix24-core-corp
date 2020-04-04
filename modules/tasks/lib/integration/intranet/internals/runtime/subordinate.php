<?
namespace Bitrix\Tasks\Integration\Intranet\Internals\Runtime;

use Bitrix\Main\Entity;
use Bitrix\Intranet\Internals\SubordinationCacheTable;

class Subordinate extends \Bitrix\Tasks\Integration\Intranet
{
	public static function getSubordinateFilter($parameters)
	{
		if(!static::includeModule())
		{
			return array();
		}

		$rf = $parameters['REF_FIELD'];

		return array('runtime' =>
				array(
					new Entity\ReferenceField(
						'ISB',
						SubordinationCacheTable::getEntity(),
						array(
							'=ref.DIRECTOR' => array('?', $parameters['USER_ID']),
							'=ref.SUBORDINATE' => (((string) $rf != '' ? $rf : 'this')).'.USER_ID',
						),
						array('join_type' => 'inner')
					)
				)
			);
	}
}