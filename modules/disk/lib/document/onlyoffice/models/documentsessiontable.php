<?php

namespace Bitrix\Disk\Document\OnlyOffice\Models;

use Bitrix\Disk\Document\OnlyOffice;
use Bitrix\Disk\Internals\DataManager;
use Bitrix\Main\Application;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\Entity\BooleanField;
use Bitrix\Main\ORM\Data\AddResult;
use Bitrix\Main\ORM\Event;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\TextField;
use Bitrix\Main\Security\Random;
use Bitrix\Main\Type\DateTime;

/**
 * Class DocumentSessionTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_DocumentSession_Query query()
 * @method static EO_DocumentSession_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_DocumentSession_Result getById($id)
 * @method static EO_DocumentSession_Result getList(array $parameters = [])
 * @method static EO_DocumentSession_Entity getEntity()
 * @method static \Bitrix\Disk\Document\OnlyOffice\Models\EO_DocumentSession createObject($setDefaultValues = true)
 * @method static \Bitrix\Disk\Document\OnlyOffice\Models\EO_DocumentSession_Collection createCollection()
 * @method static \Bitrix\Disk\Document\OnlyOffice\Models\EO_DocumentSession wakeUpObject($row)
 * @method static \Bitrix\Disk\Document\OnlyOffice\Models\EO_DocumentSession_Collection wakeUpCollection($rows)
 */
final class DocumentSessionTable extends DataManager
{
	public const TYPE_VIEW = 0;
	public const TYPE_EDIT = 2;

	public const STATUS_ACTIVE = 0;
	public const STATUS_NON_ACTIVE = 2;

	public const EXTERNAL_HASH_LENGTH = 128;

	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_disk_onlyoffice_document_session';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return [
			(new IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete()
			,
			new IntegerField('OBJECT_ID'),
			new IntegerField('VERSION_ID'),
			(new IntegerField('USER_ID'))
				->configureRequired()
			,
			(new IntegerField('OWNER_ID'))
				->configureRequired()
			,
			(new BooleanField('IS_EXCLUSIVE'))
				->configureDefaultValue(false),
			(new StringField('EXTERNAL_HASH'))
				->configureRequired()
				->configureSize(self::EXTERNAL_HASH_LENGTH)
				->configureDefaultValue(function() {
					return self::generateDocumentKey();
				})
			,
			(new DatetimeField('CREATE_TIME'))
				->configureRequired()
				->configureDefaultValue(function() {
					return new DateTime();
				})
			,
			(new IntegerField('TYPE'))
				->configureDefaultValue(self::TYPE_VIEW)
			,
			(new IntegerField('STATUS'))
				->configureDefaultValue(self::STATUS_ACTIVE)
			,
			new TextField('CONTEXT'),
		];
	}

	public static function generateDocumentKey(): string
	{
		$randomLength = self::EXTERNAL_HASH_LENGTH;
		$cloudRegistrationData = (new OnlyOffice\Configuration())->getCloudRegistrationData();
		if ($cloudRegistrationData)
		{
			$prefixLength = strlen($cloudRegistrationData['clientId']) + 1;
			$randomPart = Random::getString($randomLength - $prefixLength, true);

			return "{$cloudRegistrationData['clientId']}.{$randomPart}";
		}

		return Random::getString($randomLength, true);
	}

	public static function onAfterAdd(Event $event)
	{
		parent::onAfterAdd($event);

		$fields = $event->getParameter('fields');
		self::tryToAddInfo($fields);
	}

	public static function tryToAddInfo(array $fields): ?AddResult
	{
		try
		{
			return DocumentInfoTable::add([
				'EXTERNAL_HASH' => $fields['EXTERNAL_HASH'],
				'OBJECT_ID' => $fields['OBJECT_ID'],
				'VERSION_ID' => $fields['VERSION_ID'],
				'OWNER_ID' => $fields['OWNER_ID'],
			]);
		}
		catch (SqlQueryException $exception)
		{
			if (self::isDuplicateKeyError($exception))
			{
				return null;
			}
		}
	}

	private static function isDuplicateKeyError(SqlQueryException $exception): bool
	{
		return mb_strpos($exception->getDatabaseMessage(), '(1062)') !== false;
	}

	public static function deleteBatch(array $filter)
	{
		parent::deleteBatch($filter);
	}

	public static function clearOld(): void
	{

	}

	public static function clearTable(): void
	{
		$sql = "TRUNCATE TABLE " . self::getTableName();
		Application::getConnection()->queryExecute($sql);
	}

	public static function deactivateByHash(string $hash): void
	{
		parent::updateBatch(
			[
				'STATUS' => self::STATUS_NON_ACTIVE,
			],
			[
				'=EXTERNAL_HASH' => $hash,
			]
		);
	}
}