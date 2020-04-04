<?
namespace Bitrix\Tasks\Integration\Intranet\Internals\Runtime;

use Bitrix\Main\Entity;
use Bitrix\Main\Loader;
use Bitrix\IBlock\SectionTable;

class Department extends \Bitrix\Tasks\Integration\Intranet
{
	public static function get(array $parameters = array())
	{
		if(!static::includeModule() || !Loader::includeModule('iblock'))
		{
			return array();
		}

		$rf = $parameters['REF_FIELD'];

		return array('runtime' =>
			array(
				new Entity\ReferenceField(
					'DEP',
					SectionTable::getEntity(),
					array('=ref.ID' => ((string) $rf != '' ? $rf : 'this').'.DEPARTMENT_ID'),
					array('join_type' => 'inner')
				)
			)
		);
	}

	public static function getSub(array $parameters = array())
	{
		if(!static::includeModule() || !Loader::includeModule('iblock'))
		{
			return array();
		}

		$rf = $parameters['REF_FIELD'];
		$rf = ((string) $rf != '' ? $rf : 'this');

		$conditions = array(
			'>=ref.LEFT_MARGIN' => $rf.'.LEFT_MARGIN',
			'<=ref.RIGHT_MARGIN' => $rf.'.RIGHT_MARGIN',
		);

		if(array_key_exists('ID', $parameters))
		{
			$conditions[$rf.'.ID'] = $parameters['ID'];
		}

		return array('runtime' =>
			array(
				new Entity\ReferenceField(
					'SUB_DEP',
					SectionTable::getEntity(),
					$conditions,
					array('join_type' => 'inner')
				)
			)
		);
	}
}