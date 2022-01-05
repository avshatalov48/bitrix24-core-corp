<?php

namespace Bitrix\DocumentGenerator\Model;

use Bitrix\DocumentGenerator\DataProvider;
use Bitrix\DocumentGenerator\DataProviderManager;
use Bitrix\DocumentGenerator\Integration\Bitrix24Manager;
use Bitrix\Main;
use Bitrix\Main\Entity\Event;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class DocumentTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Document_Query query()
 * @method static EO_Document_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_Document_Result getById($id)
 * @method static EO_Document_Result getList(array $parameters = array())
 * @method static EO_Document_Entity getEntity()
 * @method static \Bitrix\DocumentGenerator\Model\EO_Document createObject($setDefaultValues = true)
 * @method static \Bitrix\DocumentGenerator\Model\EO_Document_Collection createCollection()
 * @method static \Bitrix\DocumentGenerator\Model\EO_Document wakeUpObject($row)
 * @method static \Bitrix\DocumentGenerator\Model\EO_Document_Collection wakeUpCollection($rows)
 */
class DocumentTable extends FileModel
{
	protected static $fileFieldNames = [
		'FILE_ID', 'IMAGE_ID', 'PDF_ID',
	];

	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_documentgenerator_document';
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
			new Main\Entity\StringField('NUMBER', [
				'required' => true,
			]),
			new Main\Entity\IntegerField('TEMPLATE_ID', [
				'required' => true,
			]),
			new Main\Entity\StringField('PROVIDER', [
				'validation' => function()
				{
					return [
						function($value)
						{
							if(DataProviderManager::checkProviderName($value) || empty($value))
							{
								return true;
							}
							else
							{
								return Loc::getMessage('DOCUMENTGENERATOR_MODEL_TEMPLATE_CLASS_VALIDATION', ['#CLASSNAME#' => $value, '#PARENT#' => DataProvider::class]);
							}
						},
					];
				},
			]),
			new Main\Entity\StringField('VALUE', [
				'required' => true,
			]),
			new Main\Entity\IntegerField('FILE_ID', [
				'required' => true,
			]),
			new Main\Entity\IntegerField('IMAGE_ID'),
			new Main\Entity\IntegerField('PDF_ID'),
			new Main\Entity\DatetimeField('CREATE_TIME', [
				'required' => true,
				'default_value' => function(){return new Main\Type\DateTime();},
			]),
			new Main\Entity\DatetimeField('UPDATE_TIME', [
				'required' => true,
				'default_value' => function(){return new Main\Type\DateTime();},
			]),
			new Main\Entity\TextField('VALUES', [
				'serialized' => true
			]),
			new Main\Entity\IntegerField('CREATED_BY'),
			new Main\Entity\IntegerField('UPDATED_BY'),
			new Main\Entity\ReferenceField(
				'TEMPLATE',
				'\Bitrix\DocumentGenerator\Model\Template',
				['=this.TEMPLATE_ID' => 'ref.ID']
			),
		];
	}

	public static function onAfterDelete(Event $event)
	{
		$eventData = $event->getParameters();
		ExternalLinkTable::deleteByDocumentId($eventData['primary']['ID']);
		return parent::onAfterDelete($event);
	}

	public static function onAfterAdd(Event $event)
	{
		Bitrix24Manager::increaseDocumentsCount();
	}

	/**
	 * @param $oldProvider
	 * @param $oldValue
	 * @param $newProvider
	 * @param $newValue
	 */
	public static function transferOwnership($oldProvider, $oldValue, $newProvider, $newValue)
	{
		if(!DataProviderManager::checkProviderName($oldProvider) || !DataProviderManager::checkProviderName($newProvider))
		{
			return;
		}

		$filter = [
			'=PROVIDER' => $oldProvider,
			'=VALUE' => $oldValue,
		];

		$entity = static::getEntity();
		$connection = $entity->getConnection();

		$connection->query(sprintf(
			'UPDATE %s SET %s WHERE %s',
			$connection->getSqlHelper()->quote($entity->getDbTableName()),
			$connection->getSqlHelper()->prepareUpdate($entity->getDbTableName(), [
				'PROVIDER' => mb_strtolower($newProvider),
				'VALUE' => $newValue,
			])[0],
			Main\ORM\Query\Query::buildFilterSql($entity, $filter)
		));
	}

	/**
	 * @internal
	 * @param array $filter
	 * @return Main\Result
	 */
	public static function deleteList(array $filter)
	{
		$result = new Main\Result();
		$documents = static::getList(['select' => ['ID'], 'filter' => $filter]);
		while($document = $documents->fetch())
		{
			$deleteResult = static::delete($document['ID']);
			if(!$deleteResult->isSuccess())
			{
				$result->addErrors($deleteResult->getErrors());
			}
		}

		return $result;
	}
}