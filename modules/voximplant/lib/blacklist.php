<?php
namespace Bitrix\Voximplant;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\PhoneNumber;
use Bitrix\Main\Type\DateTime;
Loc::loadMessages(__FILE__);

/**
 * Class BlacklistTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> PHONE_NUMBER string(20) optional
 * </ul>
 *
 * @package Bitrix\Voximplant
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Blacklist_Query query()
 * @method static EO_Blacklist_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Blacklist_Result getById($id)
 * @method static EO_Blacklist_Result getList(array $parameters = [])
 * @method static EO_Blacklist_Entity getEntity()
 * @method static \Bitrix\Voximplant\EO_Blacklist createObject($setDefaultValues = true)
 * @method static \Bitrix\Voximplant\EO_Blacklist_Collection createCollection()
 * @method static \Bitrix\Voximplant\EO_Blacklist wakeUpObject($row)
 * @method static \Bitrix\Voximplant\EO_Blacklist_Collection wakeUpCollection($rows)
 */

class BlacklistTable extends Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_voximplant_blacklist';
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
				'title' => Loc::getMessage('BLACKLIST_ENTITY_ID_FIELD'),
			),
			'PHONE_NUMBER' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validatePhoneNumber'),
				'title' => Loc::getMessage('BLACKLIST_ENTITY_PHONE_NUMBER_FIELD'),
			),
			'NUMBER_STRIPPED' => array(
				'data_type' => 'string',
			),
			'NUMBER_E164' => array(
				'data_type' => 'string',
			),
			'INSERTED' => array(
				'data_type' => 'datetime',

			)
		);
	}
	/**
	 * Returns validators for PHONE_NUMBER field.
	 *
	 * @return array
	 */
	public static function validatePhoneNumber()
	{
		return array(
			new Entity\Validator\Length(null, 20),
		);
	}

	public static function onBeforeAdd(Entity\Event $event)
	{
		$result = new Entity\EventResult();
		$data = $event->getParameter("fields");
		$phoneNumber = $data["PHONE_NUMBER"];

		$numberStripped = \CVoxImplantPhone::stripLetters($phoneNumber);
		$numberParsed = PhoneNumber\Parser::getInstance()->parse($phoneNumber);
		$numberE164 = $numberParsed->isValid() ? $numberParsed->format(PhoneNumber\Format::E164) : $numberStripped;

		$result->modifyFields([
			"NUMBER_STRIPPED" => $numberStripped,
			"NUMBER_E164" => $numberE164,
			"INSERTED" => new DateTime()
		]);
		return $result;
	}
}
?>