<?
namespace Bitrix\Tasks\Internals\Helper\Task\Template;

use Bitrix\Tasks\Internals\Task\Template\DependenceTable;
use Bitrix\Tasks\Internals\RunTime;
use Bitrix\Tasks\Internals\DataBase\Structure\ClosureTree;

final class Dependence extends ClosureTree
{
	protected static function getDataController()
	{
		return DependenceTable::getClass();
	}

	protected static function getNodeColumnName()
	{
		return 'TEMPLATE_ID';
	}

	protected static function getParentNodeColumnName()
	{
		return 'PARENT_TEMPLATE_ID';
	}

	/**
	 * Get child count for $ids parents, according to the access rights
	 *
	 * @param $ids
	 * @param array $parameters
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function getDirectChildCount($ids, array $parameters = array())
	{
		$res = DependenceTable::getList(Runtime::apply(array(
			'filter' => array(
				'=DIRECT' => 1,
				'=PARENT_TEMPLATE_ID' => $ids,
			),
			'select' => array('PARENT_TEMPLATE_ID', 'RECORD_COUNT'),
			'group' => array('PARENT_TEMPLATE_ID'),
		), array(
			Runtime::getRecordCount(),
			RunTime\Task\Template::getAccessCheck(array(
				'USER_ID' => $parameters['USER_ID'],
				'REF_FIELD' => 'TEMPLATE_ID',
			)),
		)));
		$result = array();
		while($item = $res->fetch())
		{
			$result[$item['PARENT_TEMPLATE_ID']] = $item['RECORD_COUNT'];
		}

		return $result;
	}
}