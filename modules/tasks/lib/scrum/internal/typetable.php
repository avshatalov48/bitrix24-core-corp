<?php

namespace Bitrix\Tasks\Scrum\Internal;

use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\Entity;
use Bitrix\Main\ORM\Fields;

/**
 * Class TypeTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Type_Query query()
 * @method static EO_Type_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_Type_Result getById($id)
 * @method static EO_Type_Result getList(array $parameters = array())
 * @method static EO_Type_Entity getEntity()
 * @method static \Bitrix\Tasks\Scrum\Internal\EO_Type createObject($setDefaultValues = true)
 * @method static \Bitrix\Tasks\Scrum\Internal\EO_Type_Collection createCollection()
 * @method static \Bitrix\Tasks\Scrum\Internal\EO_Type wakeUpObject($row)
 * @method static \Bitrix\Tasks\Scrum\Internal\EO_Type_Collection wakeUpCollection($rows)
 */
class TypeTable extends Entity\DataManager
{
	private $id = 0;
	private $entityId = 0;
	private $name = '';
	private $sort = 0;
	private $dodRequired = 'N';

	public static function getTableName()
	{
		return 'b_tasks_scrum_type';
	}

	public static function getMap()
	{
		$id = new Fields\IntegerField('ID');
		$id->configurePrimary(true);
		$id->configureAutocomplete(true);

		$entityId = new Fields\IntegerField('ENTITY_ID');

		$name = new Fields\StringField('NAME');
		$name->addValidator(new Fields\Validators\LengthValidator(1, 255));

		$sort = new Fields\IntegerField('SORT');
		$sort->configureDefaultValue(0);

		$dodRequired = new Fields\StringField('DOD_REQUIRED');
		$dodRequired->addValidator(new Fields\Validators\LengthValidator(1, 1));

		return [
			$id,
			$entityId,
			$name,
			$sort,
			$dodRequired,
		];
	}

	/**
	 * Creates an object of type.
	 *
	 * @param array $fields Fields to create object.
	 * @return TypeTable
	 */
	public static function createType(array $fields = []): TypeTable
	{
		$object = new self();

		if ($fields)
		{
			$object = self::fillObjectByData($object, $fields);
		}

		return $object;
	}

	/**
	 * Returns a list of fields to create an item type.
	 *
	 * @return array
	 * @throws ArgumentNullException
	 */
	public function getFieldsToCreate(): array
	{
		$this->checkRequiredParametersToCreate();

		return [
			'ENTITY_ID' => $this->entityId,
			'NAME' => $this->name,
			'SORT' => $this->sort,
			'DOD_REQUIRED' => $this->dodRequired,
		];
	}

	/**
	 * Returns a list of fields to update a type.
	 *
	 * @return array
	 */
	public function getFieldsToUpdate(): array
	{
		$fields = [];

		if ($this->entityId)
		{
			$fields['ENTITY_ID'] = $this->entityId;
		}

		if ($this->name)
		{
			$fields['NAME'] = $this->name;
		}

		if ($this->sort)
		{
			$fields['SORT'] = $this->sort;
		}

		if ($this->dodRequired)
		{
			$fields['DOD_REQUIRED'] = $this->dodRequired;
		}

		return $fields;
	}

	/**
	 * Checks if an object is empty based on an Id. If id empty, it means that it was not possible to get data
	 * from the storage or did not fill out the id.
	 *
	 * @return bool
	 */
	public function isEmpty(): bool
	{
		return (empty($this->id));
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function setId(int $id): void
	{
		$this->id = $id;
	}

	public function getEntityId(): int
	{
		return $this->entityId;
	}

	public function setEntityId(int $entityId): void
	{
		$this->entityId = $entityId;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function setName(string $name): void
	{
		$this->name = $name;
	}

	public function getSort(): int
	{
		return $this->sort;
	}

	public function setSort(int $sort): void
	{
		$this->sort = $sort;
	}

	public function getDodRequired(): string
	{
		return ($this->dodRequired ? $this->dodRequired : 'N');
	}

	public function setDodRequired(string $dodRequired): void
	{
		$listAvailableValues = ['Y', 'N'];

		if (in_array($dodRequired, $listAvailableValues, true))
		{
			$this->dodRequired = $dodRequired;
		}
	}

	private static function fillObjectByData(TypeTable $object, array $fields): TypeTable
	{
		if (isset($fields['ID']))
		{
			$object->setId($fields['ID']);
		}

		if (isset($fields['ENTITY_ID']))
		{
			$object->setEntityId($fields['ENTITY_ID']);
		}

		if (isset($fields['NAME']))
		{
			$object->setName($fields['NAME']);
		}

		if (isset($fields['SORT']))
		{
			$object->setSort($fields['SORT']);
		}

		if (isset($fields['DOD_REQUIRED']))
		{
			$object->setDodRequired($fields['DOD_REQUIRED']);
		}

		return $object;
	}

	/**
	 * @throws ArgumentNullException
	 */
	private function checkRequiredParametersToCreate(): void
	{
		if (empty($this->entityId))
		{
			throw new ArgumentNullException('ENTITY_ID');
		}

		if (empty($this->name))
		{
			throw new ArgumentNullException('NAME');
		}
	}
}