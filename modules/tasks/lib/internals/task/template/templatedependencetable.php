<?php

namespace Bitrix\Tasks\Internals\Task\Template;

use Bitrix\Tasks\Internals\TaskDataManager;
use Bitrix\Tasks\Internals\Task\TemplateTable;

/**
 * Class TemplateDependenceTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_TemplateDependence_Query query()
 * @method static EO_TemplateDependence_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_TemplateDependence_Result getById($id)
 * @method static EO_TemplateDependence_Result getList(array $parameters = [])
 * @method static EO_TemplateDependence_Entity getEntity()
 * @method static \Bitrix\Tasks\Internals\Task\Template\EO_TemplateDependence createObject($setDefaultValues = true)
 * @method static \Bitrix\Tasks\Internals\Task\Template\EO_TemplateDependence_Collection createCollection()
 * @method static \Bitrix\Tasks\Internals\Task\Template\EO_TemplateDependence wakeUpObject($row)
 * @method static \Bitrix\Tasks\Internals\Task\Template\EO_TemplateDependence_Collection wakeUpCollection($rows)
 */
class TemplateDependenceTable extends TaskDataManager
{

	public static function getTableName()
	{
		return 'b_tasks_template_dependence';
	}

	public static function getClass()
	{
		return get_called_class();
	}

	public static function getMap()
	{
		return array(
			'ID' => [
				'data_type' => 'integer',
				'primary' => true,
			],
			'TEMPLATE_ID' => array(
				'data_type' => 'integer',
				'primary' => false,
			),
			'DEPENDS_ON_ID' => array(
				'data_type' => 'integer',
				'primary' => false,
			),

			// references
			'TEMPLATE' => array(
				'data_type' => TemplateTable::class,
				'reference' => array('=this.TEMPLATE_ID' => 'ref.ID')
			),
		);
	}
}