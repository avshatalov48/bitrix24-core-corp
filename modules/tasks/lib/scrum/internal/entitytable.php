<?php
namespace Bitrix\Tasks\Scrum\Internal;

use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\Entity;
use Bitrix\Main\Entity\Query\Join;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\ORM\Fields\Relations\OneToMany;
use Bitrix\Main\ORM\Fields\Validators;
use Bitrix\Main\SystemException;
use Bitrix\Main\Text\Emoji;
use Bitrix\Main\Web\Json;
use Bitrix\Tasks\Scrum\Form\EntityForm;
use Bitrix\Tasks\Scrum\Form\EntityInfo;

/**
 * Class EntityTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Entity_Query query()
 * @method static EO_Entity_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Entity_Result getById($id)
 * @method static EO_Entity_Result getList(array $parameters = [])
 * @method static EO_Entity_Entity getEntity()
 * @method static \Bitrix\Tasks\Scrum\Internal\EO_Entity createObject($setDefaultValues = true)
 * @method static \Bitrix\Tasks\Scrum\Internal\EO_Entity_Collection createCollection()
 * @method static \Bitrix\Tasks\Scrum\Internal\EO_Entity wakeUpObject($row)
 * @method static \Bitrix\Tasks\Scrum\Internal\EO_Entity_Collection wakeUpCollection($rows)
 */
class EntityTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_tasks_scrum_entity';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 * @throws ArgumentTypeException
	 * @throws SystemException
	 */
	public static function getMap()
	{
		$id = new Fields\IntegerField('ID');
		$id->configurePrimary(true);
		$id->configureAutocomplete(true);

		$groupId = new Fields\IntegerField('GROUP_ID');

		$entityType = new Fields\EnumField('ENTITY_TYPE');
		$entityType->addValidator(new Validators\LengthValidator(1, 20));
		$entityType->configureValues([
			EntityForm::BACKLOG_TYPE,
			EntityForm::SPRINT_TYPE
		]);
		$entityType->configureDefaultValue(EntityForm::SPRINT_TYPE);

		$name = new Fields\StringField('NAME');
		$name->addValidator(new Validators\LengthValidator(null, 255));

		$sort = new Fields\IntegerField('SORT');

		$createdBy = new Fields\IntegerField('CREATED_BY');

		$modifiedBy = new Fields\IntegerField('MODIFIED_BY');

		$dateStart = new Fields\DatetimeField('DATE_START');

		$dateEnd = new Fields\DatetimeField('DATE_END');

		//todo add default timezone from server and user
		$dateStartTz = new Fields\StringField('DATE_START_TZ');
		$dateStartTz->addValidator(new Validators\LengthValidator(null, 50));
		$dateEndTz = new Fields\StringField('DATE_END_TZ');
		$dateEndTz->addValidator(new Validators\LengthValidator(null, 50));

		$status = new Fields\EnumField('STATUS');
		$status->addValidator(new Validators\LengthValidator(null, 20));
		$status->configureValues([
			EntityForm::SPRINT_ACTIVE,
			EntityForm::SPRINT_PLANNED,
			EntityForm::SPRINT_COMPLETED
		]);

		$info = new Fields\ObjectField('INFO');
		$info->configureObjectClass(EntityInfo::class);
		$info->configureSerializeCallback(function (?EntityInfo $entityInfo)
		{
			if (!$entityInfo)
			{
				return [];
			}

			try
			{
				$data = $entityInfo->getInfoData();
				$data[$entityInfo->getSprintGoalKey()] = Emoji::encode($data[$entityInfo->getSprintGoalKey()]);

				return Json::encode($data);
			}
			catch(ArgumentException $e)
			{
				return [];
			}
		});
		$info->configureUnserializeCallback(function ($value)
		{
			$entityInfo = new EntityInfo();

			try
			{
				$data = (is_string($value) && !empty($value) ? Json::decode($value) : []);
				if (isset($data[$entityInfo->getSprintGoalKey()]))
				{
					$data[$entityInfo->getSprintGoalKey()] = Emoji::decode($data[$entityInfo->getSprintGoalKey()]);
				}

				$entityInfo->setInfoData($data);
			}
			catch(ArgumentException $e) {}

			return $entityInfo;
		});

		$items = new OneToMany('ITEMS', ItemTable::class, 'ENTITY');
		$items->configureJoinType(Join::TYPE_LEFT);

		return [
			$id,
			$groupId,
			$entityType,
			$name,
			$sort,
			$createdBy,
			$modifiedBy,
			$dateStart,
			$dateEnd,
			$dateStartTz,
			$dateEndTz,
			$status,
			$info,
			$items
		];
	}

	/**
	 * Group deletion handler.
	 *
	 * @param int $groupId Group id.
	 * @return bool
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	public static function OnSocNetGroupDelete($groupId)
	{
		$groupId = (int) $groupId;

		if ($groupId > 0)
		{
			self::deleteByGroupId($groupId);
		}

		return true;
	}

	/**
	 * Deletes an item by group id.
	 *
	 * @param int $groupId Group id.
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	public static function deleteByGroupId(int $groupId)
	{
		$connection = Application::getConnection();

		$queryObjectResult = $connection->query(
			'SELECT ID FROM '.self::getTableName().' WHERE GROUP_ID = '.(int) $groupId
		);
		while ($entity = $queryObjectResult->fetch())
		{
			ItemTable::deleteByEntityId($entity['ID']);
		}

		$connection->queryExecute('DELETE FROM '.self::getTableName().' WHERE GROUP_ID = '.(int) $groupId);
	}
}