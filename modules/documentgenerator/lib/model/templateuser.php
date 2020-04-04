<?php

namespace Bitrix\DocumentGenerator\Model;

use Bitrix\Main;
use Bitrix\Main\ORM\Event;

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
		if(substr($code, 0, 2) === 'SG' && substr($code, -2) !== '_K')
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
		if(substr($code, 0, 2) === 'SG' && substr($code, -2) === '_K')
		{
			$code = substr($code, 0, -2);
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