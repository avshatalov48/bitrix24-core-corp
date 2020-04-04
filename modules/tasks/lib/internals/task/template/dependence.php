<?
/**
 * Class DependenceTable
 *
 * @package Bitrix\Tasks
 **/

namespace Bitrix\Tasks\Internals\Task\Template;

use Bitrix\Main,
	Bitrix\Main\Localization\Loc;
//Loc::loadMessages(__FILE__);

/**
 * Class DependenceTable
 * @package Bitrix\Tasks\Internals\Task\Template
 *
 * Note: \Bitrix\Tasks\Internals\DataBase\Tree is deprecated,
 * @see \Bitrix\Tasks\Internals\Helper\Task\Template\Dependence instead.
 * Therefore, use this class ONLY as a datamanager class for table b_tasks_template_dep!
 */
class DependenceTable extends \Bitrix\Tasks\Internals\DataBase\Tree
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_tasks_template_dep';
	}

	public static function getIDColumnName()
	{
		return 'TEMPLATE_ID';
	}

	public static function getPARENTIDColumnName()
	{
		return 'PARENT_TEMPLATE_ID';
	}

	/**
	 * @return static
	 */
	public static function getClass()
	{
		return get_called_class();
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return array_merge(array(
			'TEMPLATE_ID' => array(
				'data_type' => 'integer',
				'primary' => true,
			),
			'PARENT_TEMPLATE_ID' => array(
				'data_type' => 'integer',
				'primary' => true,
			),

			// reference
			'TEMPLATE' => array(
				'data_type' => '\Bitrix\Tasks\Template',
				'reference' => array(
					'=this.TEMPLATE_ID' => 'ref.ID'
				),
				'join_type' => 'inner'
			),
			'PARENT_TEMPLATE' => array(
				'data_type' => '\Bitrix\Tasks\Template',
				'reference' => array(
					'=this.PARENT_TEMPLATE_ID' => 'ref.ID'
				),
				'join_type' => 'inner'
			),
		), parent::getMap('\Bitrix\Tasks\Internals\Task\Template\Dependence'));
	}
}