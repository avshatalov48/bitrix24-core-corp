<?php

namespace Bitrix\DocumentGenerator\Model;

use Bitrix\Main;
use Bitrix\Main\ORM\Event;

/**
 * Class TemplateUserTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_TemplateUser_Query query()
 * @method static EO_TemplateUser_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_TemplateUser_Result getById($id)
 * @method static EO_TemplateUser_Result getList(array $parameters = array())
 * @method static EO_TemplateUser_Entity getEntity()
 * @method static \Bitrix\DocumentGenerator\Model\EO_TemplateUser createObject($setDefaultValues = true)
 * @method static \Bitrix\DocumentGenerator\Model\EO_TemplateUser_Collection createCollection()
 * @method static \Bitrix\DocumentGenerator\Model\EO_TemplateUser wakeUpObject($row)
 * @method static \Bitrix\DocumentGenerator\Model\EO_TemplateUser_Collection wakeUpCollection($rows)
 */
class TemplateUserTable extends Main\Entity\DataManager
{
	const ALL_USERS = 'UA';

	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_documentgenerator_template_user';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return [
			new Main\Entity\IntegerField('TEMPLATE_ID', [
				'primary' => true,
			]),
			new Main\Entity\StringField('ACCESS_CODE'),
			new Main\Entity\ReferenceField('TEMPLATE', '\Bitrix\DocumentGenerator\Model\Template',
				['=this.TEMPLATE_ID' => 'ref.ID']
			),
		];
	}

	/**
	 * @param string $code
	 * @return string
	 */
	public static function addSocialGroupAccessSuffix($code)
	{
		if(mb_substr($code, 0, 2) === 'SG' && mb_substr($code, -2) !== '_K')
		{
			$code .= '_K';
		}

		return $code;
	}

	/**
	 * @param string $code
	 * @return string
	 */
	public static function removeSocialGroupAccessSuffix($code)
	{
		if(mb_substr($code, 0, 2) === 'SG' && mb_substr($code, -2) === '_K')
		{
			$code = mb_substr($code, 0, -2);
		}

		return $code;
	}

	/**
	 * @param Event $event
	 * @return Main\ORM\EventResult
	 */
	public static function onBeforeAdd(Event $event)
	{
		$code = $event->getParameter('fields')['ACCESS_CODE'];
		$code = static::addSocialGroupAccessSuffix($code);
		$result = new Main\ORM\EventResult();
		$result->modifyFields(['ACCESS_CODE' => $code]);

		return $result;
	}
}