<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2016 Bitrix
 */
namespace Bitrix\Crm\WebForm\Internals;

use Bitrix\Main\Entity;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\WebForm\Helper;

Loc::loadMessages(__FILE__);

/**
 * Class FormViewTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_FormView_Query query()
 * @method static EO_FormView_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_FormView_Result getById($id)
 * @method static EO_FormView_Result getList(array $parameters = array())
 * @method static EO_FormView_Entity getEntity()
 * @method static \Bitrix\Crm\WebForm\Internals\EO_FormView createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\WebForm\Internals\EO_FormView_Collection createCollection()
 * @method static \Bitrix\Crm\WebForm\Internals\EO_FormView wakeUpObject($row)
 * @method static \Bitrix\Crm\WebForm\Internals\EO_FormView_Collection wakeUpCollection($rows)
 */
class FormViewTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_crm_webform_view';
	}

	public static function getMap()
	{
		return array(
			'FORM_ID' => array(
				'data_type' => 'integer',
				'primary' => true,
			),
			'DATE_CREATE' => array(
				'data_type' => 'datetime',
				'default_value' => new DateTime(),
			)
		);
	}
}
