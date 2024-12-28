<?php

namespace Bitrix\HumanResources\Model\HcmLink;

use Bitrix\HumanResources\Service\Container;
use Bitrix\Main\ORM;
use Bitrix\Main\ORM\Data\Internal\DeleteByFilterTrait;
use Bitrix\Main\ORM\Event;
use Bitrix\Main\Search\MapBuilder;
use Bitrix\Main\Type\DateTime;

/**
 * Class EmployeeTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Employee_Query query()
 * @method static EO_Employee_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Employee_Result getById($id)
 * @method static EO_Employee_Result getList(array $parameters = [])
 * @method static EO_Employee_Entity getEntity()
 * @method static \Bitrix\HumanResources\Model\HcmLink\Employee createObject($setDefaultValues = true)
 * @method static \Bitrix\HumanResources\Model\HcmLink\EmployeeCollection createCollection()
 * @method static \Bitrix\HumanResources\Model\HcmLink\Employee wakeUpObject($row)
 * @method static \Bitrix\HumanResources\Model\HcmLink\EmployeeCollection wakeUpCollection($rows)
 */
class EmployeeTable extends ORM\Data\DataManager
{
	use  DeleteByFilterTrait;

	const MODULE_NAME = 'humanresources';

	public static function getObjectClass(): string
	{
		return Employee::class;
	}

	public static function getCollectionClass(): string
	{
		return EmployeeCollection::class;
	}

	public static function getTableName(): string
	{
		return 'b_hr_hcmlink_employee';
	}

	public static function onAfterAdd(Event $event): void
	{
		$fields = $event->getParameter('fields');

		if (!empty($fields['DATA']) && is_array($fields['DATA']))
		{
			$personId = $event->getParameter('object')->getPersonId();
			self::savePersonIndex($fields['DATA'], $personId);
		}
	}

	public static function onAfterDelete(Event $event): void
	{
		$personId = $event->getParameter('object')->getPersonId();
		if (!empty($personId))
		{
			$personId = (int)($event->getParameter('personId') ?? 0);
			if ($personId > 0)
			{
				Container::getHcmLinkPersonRepository()->deleteSearchIndexByPersonId($personId);
			}
		}
	}

	public static function onAfterUpdate(Event $event): void
	{
		$fields = $event->getParameter('fields');

		if (!empty($fields['DATA']) && is_array($fields['DATA']))
		{
			$personId = $event->getParameter('object')->getPersonId();
			self::savePersonIndex($fields['DATA'], $personId);
		}
	}

	public static function getMap(): array
	{
		return [
			(new ORM\Fields\IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete()
				->configureTitle('ID')
			,
			(new ORM\Fields\IntegerField('PERSON_ID'))
				->configureRequired()
				->configureTitle('Person id')
			,
			(new ORM\Fields\Relations\Reference(
				'PERSON',
				PersonTable::class,
				ORM\Query\Join::on('this.PERSON_ID', 'ref.ID')
			))
			,
			(new ORM\Fields\StringField('CODE'))
				->configureRequired()
				->configureTitle('External employee id')
			,
			(new ORM\Fields\ArrayField('DATA'))
				->configureSerializationJson()
				->configureTitle('Extra data from integration')
			,
			(new ORM\Fields\DatetimeField('CREATED_AT'))
				->configureDefaultValue(new DateTime())
				->configureTitle('Employee created at')
			,
		];
	}

	private static function getAvailableFieldsForSearch(): array
	{
		return [
			'firstName',
			'lastName',
			'patronymicName',
			'snils',
			'birthDate',
			'position',
			'employeeNumber',
		];
	}

	private static function prepareContent(array $data): string
	{
		$builder = MapBuilder::create();

		foreach ($data as $key => $value)
		{
			if (in_array($key, self::getAvailableFieldsForSearch(), true))
			{
				$builder->addText((string)$value);
			}
		}

		return $builder->build();
	}

	private static function savePersonIndex($data, $personId): void
	{
		$result = self::prepareContent($data);

		if (Container::getHcmLinkPersonRepository()->hasPersonSearchIndex($personId))
		{
			Container::getHcmLinkPersonRepository()->updateSearchIndexByPersonId($personId, $result);
		}
		else
		{
			Container::getHcmLinkPersonRepository()->addSearchIndexByPersonId($personId, $result);
		}
	}
}