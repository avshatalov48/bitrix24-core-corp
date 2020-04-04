<?php

namespace Bitrix\DocumentGenerator\Model;

use Bitrix\Main;
use Bitrix\Main\Orm\Event;

class RegionTable extends Main\ORM\Data\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_documentgenerator_region';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return [
			new Main\Entity\IntegerField('ID', [
				'primary' => true,
				'autocomplete' => true,
			]),
			new Main\Entity\StringField('TITLE', [
				'required' => true,
			]),
			new Main\Entity\StringField('LANGUAGE_ID'),
			new Main\Entity\ExpressionField('CODE', '%d', 'ID'),
			new Main\Entity\StringField('FORMAT_DATE'),
			new Main\Entity\StringField('FORMAT_DATETIME'),
			new Main\Entity\StringField('FORMAT_NAME'),
			new Main\Entity\ReferenceField(
				'TEMPLATE',
				'\Bitrix\DocumentGenerator\Model\Template',
				['=this.ID' => 'ref.REGION']
			),
		];
	}

	/**
	 * @param Event $event
	 * @return Main\Orm\EventResult
	 */
	public static function onBeforeDelete(Event $event)
	{
		$id = $event->getParameter('primary')['ID'];

		if(TemplateTable::getRow(['filter' => ['=REGION' => $id, '=IS_DELETED' => 'N'], 'limit' => 1]))
		{
			Main\Localization\Loc::loadLanguageFile(__FILE__);
			$result = new Main\ORM\EventResult();
			$result->addError(new Main\ORM\EntityError(Main\Localization\Loc::getMessage('DOCGEN_MODEL_REGION_TEMPLATE_ERROR')));
			return $result;
		}

		RegionPhraseTable::deleteByRegionId($id);

		return new Main\Orm\EventResult();
	}
}