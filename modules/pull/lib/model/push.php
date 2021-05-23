<?php
namespace Bitrix\Pull\Model;

use Bitrix\Main;
use Bitrix\Main\Entity\Event;
use Bitrix\Main\Entity\FieldError;
use Bitrix\Main\Entity\Result;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Entity;

Loc::loadMessages(__FILE__);

/**
 * Class PushTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> USER_ID int mandatory
 * <li> DEVICE_TYPE string(50) optional
 * <li> APP_ID string(50) optional
 * <li> UNIQUE_HASH string(50) optional
 * <li> DEVICE_ID string(255) optional
 * <li> DEVICE_NAME string(50) optional
 * <li> DEVICE_TOKEN string(255) mandatory
 * <li> DATE_CREATE datetime mandatory
 * <li> DATE_AUTH datetime optional
 * <li> USER reference to {@link \Bitrix\User\UserTable}
 * </ul>
 *
 * @package Bitrix\Pull
 **/

class PushTable extends Main\Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_pull_push';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
				'title' => Loc::getMessage('PUSH_ENTITY_ID_FIELD'),
			),
			'USER_ID' => array(
				'data_type' => 'integer',
				'required' => true,
				'title' => Loc::getMessage('PUSH_ENTITY_USER_ID_FIELD'),
			),
			'DEVICE_TYPE' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateDeviceType'),
				'title' => Loc::getMessage('PUSH_ENTITY_DEVICE_TYPE_FIELD'),
			),
			'APP_ID' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateAppId'),
				'title' => Loc::getMessage('PUSH_ENTITY_APP_ID_FIELD'),
			),
			'UNIQUE_HASH' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateUniqueHash'),
				'title' => Loc::getMessage('PUSH_ENTITY_UNIQUE_HASH_FIELD'),
			),
			'DEVICE_ID' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateDeviceId'),
				'title' => Loc::getMessage('PUSH_ENTITY_DEVICE_ID_FIELD'),
			),
			'DEVICE_NAME' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateDeviceName'),
				'title' => Loc::getMessage('PUSH_ENTITY_DEVICE_NAME_FIELD'),
			),
			'DEVICE_TOKEN' => array(
				'data_type' => 'string',
				'required' => false,
				'validation' => array(__CLASS__, 'validateDeviceToken'),
				'title' => Loc::getMessage('PUSH_ENTITY_DEVICE_TOKEN_FIELD'),
			),
			'VOIP_TYPE' => array(
				'data_type' => 'string',
			),
			'VOIP_TOKEN' => array(
				'data_type' => 'string',
			),
			'DATE_CREATE' => array(
				'data_type' => 'datetime',
				'required' => true,
				'default_value' => new \Bitrix\Main\Type\DateTime,
				'title' => Loc::getMessage('PUSH_ENTITY_DATE_CREATE_FIELD'),
			),
			'DATE_AUTH' => array(
				'data_type' => 'datetime',
				'default_value' => new \Bitrix\Main\Type\DateTime,
				'title' => Loc::getMessage('PUSH_ENTITY_DATE_AUTH_FIELD'),
			),
			'USER' => array(
				'data_type' => 'Bitrix\Main\User',
				'reference' => array('=this.USER_ID' => 'ref.ID'),
			),
		);
	}
	/**
	 * Returns validators for DEVICE_TYPE field.
	 *
	 * @return array
	 */
	public static function validateDeviceType()
	{
		return array(
			new Main\Entity\Validator\Length(null, 50),
		);
	}
	/**
	 * Returns validators for APP_ID field.
	 *
	 * @return array
	 */
	public static function validateAppId()
	{
		return array(
			new Main\Entity\Validator\Length(null, 50),
		);
	}
	/**
	 * Returns validators for UNIQUE_HASH field.
	 *
	 * @return array
	 */
	public static function validateUniqueHash()
	{
		return array(
			new Main\Entity\Validator\Length(null, 50),
		);
	}

	/**
	 * Checks the data fields before saving to DB. Result stores in the $result object
	 *
	 * @param Result $result
	 * @param mixed $primary
	 * @param array $data
	 * @throws Main\ArgumentException
	 */
	public static function checkFields(Result $result, $primary, array $data)
	{
		parent::checkFields($result, $primary, $data);
		$pushManager = new \CPushManager();
		$availableDataTypes = array_keys($pushManager->getServices());

		if ($result instanceof Entity\AddResult)
		{
			$entity = self::getEntity();
			$tokensReceived = !empty($data["DEVICE_TOKEN"]) || !empty($data["VOIP_TOKEN"]);
			$checkToken = function($token) {
				return $token == null || preg_match('~^[a-f0-9]{64}$~i', $token);
			};

			if (!$data["DEVICE_TYPE"] || !in_array($data["DEVICE_TYPE"], $availableDataTypes))
			{
				$result->addError(new Entity\FieldError($entity->getField("DEVICE_TYPE"), "Wrong field value", FieldError::INVALID_VALUE));
			}
			if(!$tokensReceived)
			{
				$result->addError(new Entity\FieldError($entity->getField("DEVICE_TYPE"), "Tokens were not received", FieldError::INVALID_VALUE));
			}

			if ($data["DEVICE_TYPE"] == "APPLE")
			{
				if (!$checkToken($data["DEVICE_TOKEN"]) || !$checkToken($data["DEVICE_TOKEN_VOIP"]))
					$result->addError(new Entity\FieldError($entity->getField("DEVICE_TYPE"), "Wrong format of token for iOS", FieldError::INVALID_VALUE));
			}
		}
	}


	public static function onBeforeAdd(Event $event)
	{
		$result = new Entity\EventResult;
		$data = $event->getParameter("fields");

		if(!$data["APP_ID"])
		{
			if(defined("MOBILEAPP_DEFAULT_APP_ID"))
			{
				$data["APP_ID"] = MOBILEAPP_DEFAULT_APP_ID;
			}
			else
			{
				$data["APP_ID"] = "unknown";
			}
		}

		if(!$data["DEVICE_NAME"])
		{
			$data["DEVICE_NAME"] = $data["DEVICE_ID"];
		}

		$data["UNIQUE_HASH"] = \CPullPush::getUniqueHash($data["USER_ID"], $data["APP_ID"]);
		$data["DATE_AUTH"] = new Main\Type\DateTime();
		$result->modifyFields($data);

		return $result;
	}

	public static function onAfterAdd(Event $event)
	{
		parent::onAfterAdd($event);
		\CAgent::AddAgent("CPullPush::cleanTokens();", "pull", "N", 43200, "", "Y", ConvertTimeStamp(time() + \CTimeZone::GetOffset() + 30, "FULL"));
	}


	public static function onBeforeUpdate(Event $event)
	{
		parent::onBeforeUpdate($event);

		$result = new Entity\EventResult;
		$data = $event->getParameter("fields");
		$data["UNIQUE_HASH"] = \CPullPush::getUniqueHash($data["USER_ID"], $data["APP_ID"]);
		$data["DATE_AUTH"] = new Main\Type\DateTime();
		$result->modifyFields($data);

		return $result;
	}


	/**
	 * Returns validators for DEVICE_ID field.
	 *
	 * @return array
	 */
	public static function validateDeviceId()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}
	/**
	 * Returns validators for DEVICE_NAME field.
	 *
	 * @return array
	 */
	public static function validateDeviceName()
	{
		return array(
			new Main\Entity\Validator\Length(null, 50),
		);
	}
	/**
	 * Returns validators for DEVICE_TOKEN field.
	 *
	 * @return array
	 */
	public static function validateDeviceToken()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}
}

class_alias("Bitrix\\Pull\\Model\\PushTable", "Bitrix\\Pull\\PushTable", false);