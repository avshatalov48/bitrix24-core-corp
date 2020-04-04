<?php

namespace Bitrix\Faceid;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;


/**
 * Class AgreementTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> USER_ID int mandatory
 * <li> NAME string(100) mandatory
 * <li> EMAIL string(255) mandatory
 * <li> DATE datetime mandatory
 * <li> IP_ADDRESS string(39) mandatory
 * </ul>
 *
 * @package Bitrix\Faceid
 **/

class AgreementTable extends Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_faceid_agreement';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			new Entity\IntegerField('ID', array(
				'primary' => true,
				'autocomplete' => true
			)),
			new Entity\IntegerField('USER_ID', array(
				'required' => true
			)),
			new Entity\StringField('NAME', array(
				'validation' => array(__CLASS__, 'validateName')
			)),
			new Entity\StringField('EMAIL', array(
				'required' => true,
				'validation' => array(__CLASS__, 'validateEmail')
			)),
			new Entity\DatetimeField('DATE', array(
				'required' => true
			)),
			new Entity\StringField('IP_ADDRESS', array(
				'required' => true,
				'validation' => array(__CLASS__, 'validateIpAddress')
			)),
		);
	}
	/**
	 * Returns validators for NAME field.
	 *
	 * @return array
	 */
	public static function validateName()
	{
		return array(
			new Entity\Validator\Length(null, 100),
		);
	}
	/**
	 * Returns validators for EMAIL field.
	 *
	 * @return array
	 */
	public static function validateEmail()
	{
		return array(
			new Entity\Validator\Length(null, 255),
		);
	}
	/**
	 * Returns validators for IP_ADDRESS field.
	 *
	 * @return array
	 */
	public static function validateIpAddress()
	{
		return array(
			new Entity\Validator\Length(null, 39),
		);
	}

	/**
	 * Checks if User have access to the faceid
	 *
	 * @param $userId
	 *
	 * @return bool
	 */
	public static function checkUser($userId)
	{
		$hasAgreement = \Bitrix\Faceid\AgreementTable::getList(array(
			'select' => array(new \Bitrix\Main\Entity\ExpressionField('X', '1')),
			'filter' => array(
				'=USER_ID' => $userId
			)
		))->fetch();

		return !empty($hasAgreement);
	}

	public static function getAgreementText($asRichHtml = false)
	{
		Loc::loadMessages(__FILE__);

		if ($asRichHtml)
		{
			$msg = Loc::getMessage("FACEID_LICENSE_AGREEMENT_HTML_RICH");
		}
		else
		{
			$msg = nl2br(Loc::getMessage("FACEID_LICENSE_AGREEMENT_HTML"));
		}

		return $msg;
	}

	public static function onAfterAdd(Entity\Event $event)
	{
		// add default source lead, when it is the first time when face recognition used
		$optionName = 'ftracker_lead_source';
		$optionValue = 'FACE_TRACKER';

		$source = Option::get('faceid', $optionName, '');
		if ($source == '')
		{
			// set only if it doesn't exist
			$sources = \Bitrix\Main\Loader::includeModule('crm') ? \CCrmStatus::GetStatusList('SOURCE') : array();

			if (!empty($sources) && !isset($sources[$optionValue]))
			{
				$c = new \CCrmStatus('SOURCE');
				$c->Add(array(
					'STATUS_ID' => $optionValue,
					'NAME' => Loc::getMessage("FACEID_LEAD_SOURCE_DEFAULT")
				));

				\Bitrix\Main\Config\Option::set('faceid', $optionName, $optionValue);
			}
		}
	}
}