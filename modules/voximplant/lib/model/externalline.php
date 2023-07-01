<?php

namespace Bitrix\Voximplant\Model;

use Bitrix\Main\Entity;
use Bitrix\Main\ORM\Event;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\PhoneNumber;
use Bitrix\Main\Type\DateTime;
use Bitrix\Voximplant\SipTable;

/**
 * Class ExternalLineTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_ExternalLine_Query query()
 * @method static EO_ExternalLine_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_ExternalLine_Result getById($id)
 * @method static EO_ExternalLine_Result getList(array $parameters = array())
 * @method static EO_ExternalLine_Entity getEntity()
 * @method static \Bitrix\Voximplant\Model\EO_ExternalLine createObject($setDefaultValues = true)
 * @method static \Bitrix\Voximplant\Model\EO_ExternalLine_Collection createCollection()
 * @method static \Bitrix\Voximplant\Model\EO_ExternalLine wakeUpObject($row)
 * @method static \Bitrix\Voximplant\Model\EO_ExternalLine_Collection wakeUpCollection($rows)
 */
class ExternalLineTable extends Base
{
	const TYPE_REST_APP = "rest-app";
	const TYPE_SIP = "sip";
	/**
	 * @inheritdoc
	 */
	public static function getTableName()
	{
		return 'b_voximplant_external_line';
	}

	/**
	 * @inheritdoc
	 */
	public static function getMap()
	{
		return [
			new Entity\IntegerField('ID', [
				'primary' => true,
				'autocomplete' => true
			]),
			new Entity\StringField('TYPE'),
			new Entity\StringField('NUMBER'),
			new Entity\StringField('NORMALIZED_NUMBER'),
			new Entity\StringField('NAME'),
			new Entity\IntegerField('REST_APP_ID'),
			new Entity\IntegerField('SIP_ID'),
			new Entity\BooleanField('IS_MANUAL', [
				'values' => ['N', 'Y']
			]),
			new Entity\BooleanField('CRM_AUTO_CREATE', [
				'values' => ['N', 'Y'],
				'default_value' => 'Y',
			]),
			new Entity\DateTimeField('DATE_CREATE', [
				'default_value' => function()
				{
					return new DateTime();
				}
			]),
			new Reference('SIP', SipTable::class, Join::on('this.SIP_ID', 'ref.ID'), ['join_type' => 'left'])
		];
	}

	public static function getMergeFields()
	{
		return ["SIP_ID", "NORMALIZED_NUMBER", "TYPE"];
	}

	public static function onBeforeAdd(Entity\Event $event)
	{
		$result = new Entity\EventResult();
		$data = $event->getParameter("fields");
		$phoneNumber = $data["NUMBER"];

		$result->modifyFields([
			"NORMALIZED_NUMBER" => PhoneNumber\Parser::getInstance()->parse($phoneNumber)->format(PhoneNumber\Format::E164),
			"DATE_CREATE" => new DateTime()
		]);
		return $result;
	}

	public static function onBeforeUpdate(Event $event)
	{
		$result = new Entity\EventResult();
		$data = $event->getParameter("fields");
		$phoneNumber = $data["NUMBER"];

		$result->modifyFields([
			"NORMALIZED_NUMBER" => PhoneNumber\Parser::getInstance()->parse($phoneNumber)->format(PhoneNumber\Format::E164),
		]);
		return $result;
	}
}