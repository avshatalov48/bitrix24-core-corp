<?php

namespace Bitrix\DocumentGenerator\Model;

use Bitrix\Main;
use Bitrix\Main\Entity\Event;
use Bitrix\Main\Entity\EventResult;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__DIR__.'/template.php');

/**
 * Class TemplateProviderTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_TemplateProvider_Query query()
 * @method static EO_TemplateProvider_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_TemplateProvider_Result getById($id)
 * @method static EO_TemplateProvider_Result getList(array $parameters = array())
 * @method static EO_TemplateProvider_Entity getEntity()
 * @method static \Bitrix\DocumentGenerator\Model\EO_TemplateProvider createObject($setDefaultValues = true)
 * @method static \Bitrix\DocumentGenerator\Model\EO_TemplateProvider_Collection createCollection()
 * @method static \Bitrix\DocumentGenerator\Model\EO_TemplateProvider wakeUpObject($row)
 * @method static \Bitrix\DocumentGenerator\Model\EO_TemplateProvider_Collection wakeUpCollection($rows)
 */
class TemplateProviderTable extends Main\Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_documentgenerator_template_provider';
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
			new Main\Entity\StringField('PROVIDER', [
				'primary' => true,
			]),
			new Main\Entity\ReferenceField('TEMPLATE', '\Bitrix\DocumentGenerator\Model\Template',
				['=this.TEMPLATE_ID' => 'ref.ID']
			),
		];
	}

	public static function onBeforeAdd(Event $event)
	{
		$result = new EventResult;
		$data = $event->getParameter("fields");

		if(isset($data['PROVIDER']))
		{
			$provider = mb_strtolower($data['PROVIDER']);
			$result->modifyFields(['PROVIDER' => $provider]);
		}

		return $result;
	}

	/**
	 * @param $string
	 * @return string
	 */
	public static function getClassNameFromFilterString($string)
	{
		return explode('_', $string)[0];
	}

	/**
	 * @param int $templateId
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 * @throws \Exception
	 */
	public static function deleteByTemplateId($templateId)
	{
		$data = static::getList(['filter' => ['TEMPLATE_ID' => $templateId]]);
		while($record = $data->fetch())
		{
			static::delete($record);
		}
	}
}