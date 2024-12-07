<?php
namespace Bitrix\Tasks\Kanban;

use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\SystemException;
use Exception;

/**
 * Class ProjectsTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Projects_Query query()
 * @method static EO_Projects_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Projects_Result getById($id)
 * @method static EO_Projects_Result getList(array $parameters = [])
 * @method static EO_Projects_Entity getEntity()
 * @method static \Bitrix\Tasks\Kanban\EO_Projects createObject($setDefaultValues = true)
 * @method static \Bitrix\Tasks\Kanban\EO_Projects_Collection createCollection()
 * @method static \Bitrix\Tasks\Kanban\EO_Projects wakeUpObject($row)
 * @method static \Bitrix\Tasks\Kanban\EO_Projects_Collection wakeUpCollection($rows)
 */
class ProjectsTable extends DataManager
{
	private const DEFAULT_ORDER = 'asc';

	public static function getTableName(): string
	{
		return 'b_tasks_projects';
	}

	public static function getMap(): array
	{
		return [
			(new IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete(),

			(new StringField('ORDER_NEW_TASK'))
				->configureRequired(),
		];
	}

	/**
	 * Set project settings.
	 *
	 * @throws Exception
	 */
	public static function set(int $id, array $fields): void
	{
		$connection = Application::getConnection();

		$lockName = 'tasks_kanban_project_settings_' . $id;

		try
		{
			if ($connection->lock($lockName))
			{
				if (self::getById($id)->fetch())
				{
					self::update($id, $fields);
				}
				else
				{
					$fields['ID'] = $id;
					self::add($fields);
				}
			}

		}
		finally
		{
			$connection->unlock($lockName);
		}
	}

	/**
	 * Delete all rows after group delete.
	 *
	 * @throws Exception
	 */
	public static function onSocNetGroupDelete(int $groupId): void
	{
		self::delete($groupId);
	}

	/**
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public static function getOrder(int $groupId): string
	{
		$query = static::query();
		$query
			->setSelect(['ID', 'ORDER_NEW_TASK'])
			->where('ID', $groupId);

		$order = $query->exec()->fetchObject()?->getOrderNewTask();

		return $order ?? static::DEFAULT_ORDER;
	}
}