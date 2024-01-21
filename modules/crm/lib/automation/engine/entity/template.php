<?php
namespace Bitrix\Crm\Automation\Engine\Entity;

use Bitrix\Main;

/**
 * Class TemplateTable
 * @package Bitrix\Crm\Automation\Engine\Entity
 * @deprecated
 * @see \Bitrix\Bizproc\WorkflowTemplateTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Template_Query query()
 * @method static EO_Template_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Template_Result getById($id)
 * @method static EO_Template_Result getList(array $parameters = [])
 * @method static EO_Template_Entity getEntity()
 * @method static \Bitrix\Crm\Automation\Engine\Entity\EO_Template createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Automation\Engine\Entity\EO_Template_Collection createCollection()
 * @method static \Bitrix\Crm\Automation\Engine\Entity\EO_Template wakeUpObject($row)
 * @method static \Bitrix\Crm\Automation\Engine\Entity\EO_Template_Collection wakeUpCollection($rows)
 */
class TemplateTable extends Main\Entity\DataManager
{
	/**
	 * Get table name.
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_crm_automation_template';
	}

	/**
	 * Get table fields map.
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'ID' => array('primary' => true, 'data_type' => 'integer', 'autocomplete' => true,),
			'ENTITY_TYPE_ID' => array('data_type' => 'integer'),
			'ENTITY_STATUS' => array('data_type' => 'string'),
			'TEMPLATE_ID' => array('data_type' => 'integer'),
			'START_RULES' => array(
				'data_type' => 'string',
				'serialized' => true
			)
		);
	}

	public static function upsert(array $template)
	{
		$templateId = (int)$template['ID'];
		$iterator = static::getList(array(
			'select' => array('ID'),
			'filter' => array(
				'=ENTITY_TYPE_ID' => $template['ENTITY_TYPE_ID'],
				'=ENTITY_STATUS' => $template['ENTITY_STATUS']
			)
		));

		$ids = array();
		while ($row = $iterator->fetch())
		{
			$ids[] = (int)$row['ID'];
		}

		$toSave = array(
			'ENTITY_TYPE_ID' => $template['ENTITY_TYPE_ID'],
			'ENTITY_STATUS' => $template['ENTITY_STATUS'],
			'TEMPLATE_ID' => $template['TEMPLATE_ID'],
		);

		if (in_array($templateId, $ids, true))
		{
			static::update($templateId, $toSave);
		}
		else
		{
			static::add($toSave);
		}

		foreach ($ids as $id)
		{
			if ($id === $templateId)
				continue;

			static::delete($id);
		}
	}
}