<?php
namespace Bitrix\Sign\Internal;

use Bitrix\Main\Entity;
use Bitrix\Main\ORM\Data\Internal\DeleteByFilterTrait;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\Security\Random;
use Bitrix\Sign\File;
use Bitrix\Sign\Internal\Document\TemplateTable;
use Bitrix\Sign\Type\Document\InitiatedByType;
use Bitrix\Sign\Type\DocumentStatus;

/**
 * Class DocumentTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Document_Query query()
 * @method static EO_Document_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Document_Result getById($id)
 * @method static EO_Document_Result getList(array $parameters = [])
 * @method static EO_Document_Entity getEntity()
 * @method static \Bitrix\Sign\Internal\Document createObject($setDefaultValues = true)
 * @method static \Bitrix\Sign\Internal\DocumentCollection createCollection()
 * @method static \Bitrix\Sign\Internal\Document wakeUpObject($row)
 * @method static \Bitrix\Sign\Internal\DocumentCollection wakeUpCollection($rows)
 */
class DocumentTable extends Entity\DataManager
{
	use DeleteByFilterTrait;

	public static function getObjectClass()
	{
		return Document::class;
	}

	public static function getCollectionClass()
	{
		return DocumentCollection::class;
	}

	/**
	 * Returns DB table name for entity.
	 * @return string
	 */
	public static function getTableName(): string
	{
		return 'b_sign_document';
	}

	/**
	 * Returns entity map definition.
	 * @return array
	 */
	public static function getMap(): array
	{
		return [
			'ID' => new Entity\IntegerField('ID', [
				'primary' => true,
				'autocomplete' => true,
				'title' => 'ID'
			]),
			'TITLE' => new Entity\StringField('TITLE', [
				'title' => 'Title'
			]),
			'HASH' => new Entity\StringField('HASH', [
				'title' => 'Hash'
			]),
			'SEC_CODE' => new Entity\StringField('SEC_CODE', [
				'title' => 'Access code',
				'default_value' => Random::getString(20, true)
			]),
			'HOST' => new Entity\StringField('HOST', [
				'title' => 'Host'
			]),
			'BLANK_ID' => new Entity\IntegerField('BLANK_ID', [
				'title' => 'Blank id',
				'required' => true
			]),
			'ENTITY_TYPE' => new Entity\StringField('ENTITY_TYPE', [
				'title' => 'Entity type',
				'required' => true
			]),
			'ENTITY_ID' => new Entity\IntegerField('ENTITY_ID', [
				'title' => 'Entity id',
				'required' => true
			]),
			'META' => (new \Bitrix\Main\ORM\Fields\ArrayField('META', [
				'title' => 'Meta information'
			]))->configureSerializationJson(),
			'PROCESSING_STATUS' => new Entity\StringField('PROCESSING_STATUS', [
				'title' => 'Processing status',
				'required' => true,
				'default_value' => 'B'
			]),
			'PROCESSING_ERROR' => new Entity\StringField('PROCESSING_ERROR', [
				'title' => 'Processing error message'
			]),
			'LANG_ID' => new Entity\StringField('LANG_ID', [
				'title' => 'Language id (used for communications)',
				'default_value' => LANGUAGE_ID
			]),
			'RESULT_FILE_ID' => new Entity\IntegerField('RESULT_FILE_ID', [
				'title' => 'Result file',
			])
			,'CREATED_BY_ID' => new Entity\IntegerField('CREATED_BY_ID', [
				'title' => 'Created by user ID',
				'required' => true
			]),
			'MODIFIED_BY_ID' => new Entity\IntegerField('MODIFIED_BY_ID', [
				'title' => 'Modified by user ID',
				'required' => true
			]),
			'DATE_CREATE' => new Entity\DatetimeField('DATE_CREATE', [
				'title' => 'Created on',
				'required' => true
			]),
			'DATE_MODIFY' => new Entity\DatetimeField('DATE_MODIFY', [
				'title' => 'Modified on',
				'required' => true
			]),
			'DATE_SIGN' => new Entity\DatetimeField('DATE_SIGN', [
				'title' => 'Signed on',
			]),
			'STATUS' => new Entity\StringField('STATUS', [
				'title' => 'Status',
				'required' => true,
				'default_value' => DocumentStatus::NEW,
			]),
			'UID' => new Entity\StringField('UID', [
				'title' => 'UID',
			]),
			'SCENARIO' => (new Entity\IntegerField('SCENARIO', [
				'title' => 'SCENARIO',
			]))
				->configureNullable()
			,
			'VERSION' => (new Entity\IntegerField('VERSION', [
				'title' => 'VERSION',
			]))
				->configureDefaultValue(1)
			,
			'COMPANY_UID' => (new Entity\StringField('COMPANY_UID'))
				->configureNullable()
				->addValidator(new Entity\Validator\Length(32, 32))
			,
			'REPRESENTATIVE_ID' => (new Entity\IntegerField('REPRESENTATIVE_ID'))
				->configureNullable()
			,
			'PARTIES' => (new Entity\IntegerField('PARTIES'))
				->configureNullable()
				->configureDefaultValue(2)
			,
			'EXTERNAL_ID' => (new Entity\StringField('EXTERNAL_ID'))
				->configureNullable()
				->addValidator(new Entity\Validator\Length(null, 255))
			,
			'REGION_DOCUMENT_TYPE' => (new Entity\StringField('REGION_DOCUMENT_TYPE'))
				->configureNullable()
				->addValidator(new Entity\Validator\Length(null, 255))
			,
			(new IntegerField('SCHEME'))
				->configureTitle('Scheme')
				->configureNullable(false)
				->configureDefaultValue(0)
			,
			(new IntegerField('STOPPED_BY_ID'))
				->configureTitle('Stopped by id')
				->configureNullable()
				->configureRequired(false)
			,
			(new Entity\DatetimeField('EXTERNAL_DATE_CREATE'))
				->configureTitle('External Registration date')
				->configureNullable()
				->configureRequired(false)
			,
			(new Entity\StringField('PROVIDER_CODE'))
				->configureTitle("Provider code")
				->configureSize(255)
				->configureNullable()
			,
			(new IntegerField('TEMPLATE_ID'))
				->configureTitle('Template ID')
				->configureNullable()
			,
			(new IntegerField('GROUP_ID'))
				->configureTitle('Group ID')
				->configureNullable()
			,
			(new Entity\ReferenceField(
				'TEMPLATE',
				TemplateTable::class,
				['=this.TEMPLATE_ID' => 'ref.ID'],
			)),
			(new Entity\ReferenceField(
				'MEMBER',
				MemberTable::class,
				['=this.ID' => 'ref.DOCUMENT_ID'],
			)),
			(new IntegerField('CHAT_ID'))
				->configureTitle('Chat ID')
				->configureNullable()
			,
			(new IntegerField('CREATED_FROM_DOCUMENT_ID'))
				->configureTitle('Created from document ID')
				->configureNullable()
			,
			(new IntegerField('INITIATED_BY_TYPE'))
				->configureTitle('Initiated by type')
				->configureRequired()
				->configureDefaultValue(InitiatedByType::COMPANY->toInt())
			,
			(new IntegerField('HCMLINK_COMPANY_ID'))
				->configureTitle('HcmLink Company ID')
				->configureNullable()
			,
			(new Entity\DatetimeField('DATE_STATUS_CHANGED'))
				->configureTitle('Status changed date')
				->configureNullable()
			,
		];
	}

	/**
	 * Before delete handler.
	 * @param Entity\Event $event Event instance.
	 * @return Entity\EventResult
	 */
	public static function onBeforeDelete(Entity\Event $event): Entity\EventResult
	{
		$result = new Entity\EventResult();
		$primary = $event->getParameter('primary');

		if ($primary['ID'] ?? null)
		{
			// delete document file
			$res = self::getList([
				'select' => [
					'RESULT_FILE_ID'
				],
				'filter' => [
					'ID' => $primary['ID']
				],
				'limit' => 1
			]);
			if ($row = $res->fetch())
			{
				if ($row['RESULT_FILE_ID'])
				{
					File::delete($row['RESULT_FILE_ID']);
				}
			}

			// delete linked data
			$entities = [
				MemberTable::class
			];
			/** @var \Bitrix\Main\Entity\DataManager $entity */
			foreach ($entities as $entity)
			{
				$res = $entity::getList([
					'select' => [
						'ID'
					],
					'filter' => [
						'DOCUMENT_ID' => $primary['ID']
					]
				]);
				while ($row = $res->fetch())
				{
					$resDel = $entity::delete($row['ID']);

					if (!$resDel->isSuccess())
					{
						$error = $resDel->getErrors()[0];
						$result->addError(new \Bitrix\Main\ORM\EntityError(
							$error->getMessage(),
							$error->getCode()
						));

						break;
					}
				}
			}
		}

		return $result;
	}
}
