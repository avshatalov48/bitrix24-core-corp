<?php

namespace Bitrix\Tasks\Internals\Task\Template;

use Bitrix\Tasks\Internals\TaskDataManager;
use Bitrix\Tasks\Internals\Task\TemplateTable;

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