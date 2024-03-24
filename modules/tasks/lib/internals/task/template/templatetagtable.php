<?php

namespace Bitrix\Tasks\Internals\Task\Template;

use Bitrix\Main\Entity\Validator\Length;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Tasks\Internals\TaskDataManager;
use Bitrix\Tasks\Internals\Task\TemplateTable;
use Bitrix\Main\UserTable;

/**
 * Class TemplateTagTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_TemplateTag_Query query()
 * @method static EO_TemplateTag_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_TemplateTag_Result getById($id)
 * @method static EO_TemplateTag_Result getList(array $parameters = [])
 * @method static EO_TemplateTag_Entity getEntity()
 * @method static \Bitrix\Tasks\Internals\Task\Template\EO_TemplateTag createObject($setDefaultValues = true)
 * @method static \Bitrix\Tasks\Internals\Task\Template\EO_TemplateTag_Collection createCollection()
 * @method static \Bitrix\Tasks\Internals\Task\Template\EO_TemplateTag wakeUpObject($row)
 * @method static \Bitrix\Tasks\Internals\Task\Template\EO_TemplateTag_Collection wakeUpCollection($rows)
 */
class TemplateTagTable extends TaskDataManager
{

	public static function getTableName()
	{
		return 'b_tasks_template_tag';
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
			'USER_ID' => array(
				'data_type' => 'integer',
				'primary' => false,
			),
			'NAME' => array(
				'data_type' => 'string',
				'primary' => false,
				'validation' => array(__CLASS__, 'validateName'),
			),

			// references
			'TEMPLATE' => array(
				'data_type' => TemplateTable::class,
				'reference' => array('=this.TEMPLATE_ID' => 'ref.ID')
			),
			'USER' => array(
				'data_type' => UserTable::class,
				'reference' => array('=this.USER_ID' => 'ref.ID')
			),
			(new ExpressionField(
				'MAX_ID',
				'MAX(%s)', ['ID']
			)),
		);
	}

	public static function validateName()
	{
		return array(
			new Length(null, 255),
		);
	}
}