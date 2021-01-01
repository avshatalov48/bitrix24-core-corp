<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2019 Bitrix
 */
namespace Bitrix\Crm\WebForm\Internals;

use Bitrix\Main\ORM;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class AgreementTable
 *
 * @package Bitrix\Crm\WebForm\Internals
 */
class AgreementTable extends ORM\Data\DataManager
{
	public static function getTableName()
	{
		return 'b_crm_webform_agreement';
	}

	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'FORM_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'AGREEMENT_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'CHECKED' => array(
				'data_type' => 'boolean',
				'required' => true,
				'default_value' => 'N',
				'values' => array('N','Y')
			),
			'REQUIRED' => array(
				'data_type' => 'boolean',
				'required' => true,
				'default_value' => 'Y',
				'values' => array('N','Y')
			),
		);
	}
}
