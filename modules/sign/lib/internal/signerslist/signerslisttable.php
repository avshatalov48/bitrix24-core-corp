<?php
namespace Bitrix\Sign\Internal\SignersList;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Event;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\Entity;
use Bitrix\Sign\Trait\ORM\UpdateByFilterTrait;

/**
 * Class SignersListTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_SignersList_Query query()
 * @method static EO_SignersList_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_SignersList_Result getById($id)
 * @method static EO_SignersList_Result getList(array $parameters = [])
 * @method static EO_SignersList_Entity getEntity()
 * @method static \Bitrix\Sign\Internal\SignersList\SignersList createObject($setDefaultValues = true)
 * @method static \Bitrix\Sign\Internal\SignersList\SignersListCollection createCollection()
 * @method static \Bitrix\Sign\Internal\SignersList\SignersList wakeUpObject($row)
 * @method static \Bitrix\Sign\Internal\SignersList\SignersListCollection wakeUpCollection($rows)
 */
class SignersListTable extends DataManager
{
	use UpdateByFilterTrait;

	public static function getObjectClass(): string
	{
		return SignersList::class;
	}

	public static function getCollectionClass(): string
	{
		return SignersListCollection::class;
	}

	public static function getTableName()
	{
		return 'b_sign_signers_list';
	}

	public static function onBeforeDelete(Event $event): Entity\EventResult
	{

		$result = new Entity\EventResult();
		$primary = $event->getParameter('primary');

		if ($primary['ID'] ?? null)
		{
			$res = SignersListUserTable::getList([
				'select' => ['USER_ID'],
				'filter' => [
					'LIST_ID' => $primary['ID']
				]
			]);

			while ($row = $res->fetch())
			{
				$delResult = SignersListUserTable::delete(['LIST_ID' => $primary['ID'], 'USER_ID' => $row['USER_ID']]);
				if (!$delResult->isSuccess())
				{
					$error = $delResult->getErrors()[0];
					$result->addError(new \Bitrix\Main\ORM\EntityError(
						$error->getMessage(),
						$error->getCode()
					));

					break;
				}
			}
		}

		return $result;
	}

	public static function getMap()
	{
		return [
			(new Fields\IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete()
			,
			(new Fields\StringField('TITLE'))
				->configureRequired()
				->configureNullable(false)
			,
			(new Fields\IntegerField('CREATED_BY_ID'))
				->configureRequired()
				->configureNullable(false)
			,
			(new Fields\IntegerField('MODIFIED_BY_ID'))
				->configureRequired(false)
				->configureNullable()
			,
			(new Fields\DatetimeField('DATE_CREATE'))
				->configureRequired()
				->configureNullable(false)
			,
			(new Fields\DatetimeField('DATE_MODIFY'))
				->configureRequired(false)
				->configureNullable()
			,
		];
	}
}
