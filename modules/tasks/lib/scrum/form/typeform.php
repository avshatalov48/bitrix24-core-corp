<?php

namespace Bitrix\Tasks\Scrum\Form;

use Bitrix\Main\ArgumentNullException;

class TypeForm
{
	private $id = 0;
	private $entityId = 0;
	private $name = '';
	private $sort = null;
	private $dodRequired = 'N';
	private $participants = [];

	/**
	 * Returns an array with keys for the client.
	 *
	 * @return array
	 */
	public function toArray(): array
	{
		return [
			'id' => $this->getId(),
			'entityId' => $this->getEntityId(),
			'name' => $this->getName(),
			'sort' => $this->getSort(),
			'dodRequired' => $this->getDodRequired(),
			'participants' => $this->getParticipantsList(),
		];
	}

	/**
	 * Checks if an object is empty based on an id. If id empty, it means that it was not possible to get data
	 * from the storage or did not fill out the id.
	 *
	 * @return bool
	 */
	public function isEmpty(): bool
	{
		return (empty($this->id));
	}

	/**
	 * Returns a list of fields to create a dod type.
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
	 * To fill the object with data obtained from the database.
	 *
	 * @param array $fields An array with fields.
	 */
	public function fillFromDatabase(array $fields): void
	{
		if (isset($fields['ID']))
		{
			$this->setId($fields['ID']);
		}

		if (isset($fields['ENTITY_ID']))
		{
			$this->setEntityId($fields['ENTITY_ID']);
		}

		if (isset($fields['NAME']))
		{
			$this->setName($fields['NAME']);
		}

		if (isset($fields['SORT']))
		{
			$this->setSort($fields['SORT']);
		}

		if (isset($fields['DOD_REQUIRED']))
		{
			$this->setDodRequired($fields['DOD_REQUIRED']);
		}
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function setId($id): void
	{
		$this->id = (is_numeric($id) ? (int) $id : 0);
	}

	public function getEntityId(): int
	{
		return $this->entityId;
	}

	public function setEntityId($entityId): void
	{
		$this->entityId = (is_numeric($entityId) ? (int) $entityId : 0);
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function setName($name): void
	{
		$this->name = (is_string($name) ? $name : '');
	}

	public function getSort(): int
	{
		return $this->sort === null ? 0 : $this->sort;
	}

	public function setSort($sort): void
	{
		$this->sort = (is_numeric($sort) ? (int) $sort : 0);
	}

	public function getDodRequired(): string
	{
		return $this->dodRequired;
	}

	public function setDodRequired($dodRequired): void
	{
		$listAvailableValues = ['Y', 'N'];

		if (is_string($dodRequired) && in_array($dodRequired, $listAvailableValues, true))
		{
			$this->dodRequired = $dodRequired;
		}
	}

	/**
	 * Returns a list of codes for save.
	 *
	 * @return array
	 */
	public function getParticipantsCodes(): array
	{
		return $this->participants;
	}

	public function setParticipantsCodes(array $participants): void
	{
		$this->participants = [];

		foreach ($participants as $code)
		{
			if (
				mb_substr($code, 0, 1) == 'U'
				|| preg_match('/^SG([0-9]+)_?([AEKM])?$/', $code, $match) && isset($match[2])
			)
			{
				$this->participants[] = $code;
			}
		}
	}

	/**
	 * Returns a list of entities for ui selector.
	 *
	 * @return array
	 */
	public function getParticipantsList(): array
	{
		$entityList = [];

		foreach ($this->participants as $code)
		{
			if (mb_substr($code, 0, 1) == 'U')
			{
				$entityList[] = [
					'id' => (int) mb_substr($code, 1),
					'entityId' => 'user',
				];
			}
			elseif (preg_match('/^SG([0-9]+)_?([AEKM])?$/', $code, $match) && isset($match[2]))
			{
				$entityList[] = [
					'id' => mb_substr($code, 2),
					'entityId' => 'project-roles',
				];
			}
		}

		return $entityList;
	}

	/**
	 * @param $participants
	 * @return void
	 */
	public function setParticipantsList($participants): void
	{
		if (!is_array($participants))
		{
			return;
		}

		$listCodes = [];

		foreach ($participants as $participant)
		{
			if (isset($participant['id']) && isset($participant['entityId']))
			{
				switch ($participant['entityId'])
				{
					case 'user':
						$listCodes[] = 'U' . $participant['id'];
						break;
					case 'project-roles':
						$listCodes[] = 'SG' . $participant['id'];
						break;
				}
			}
		}

		$this->participants = $listCodes;
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

	private function getArrayUniqueBasedOnValue($array, $key = 'id'): array
	{
		$result = [];
		$keyArray = [];

		foreach($array as $value)
		{
			if (!in_array($value[$key], $keyArray))
			{
				$keyArray[] = $value[$key];
				$result[] = $value;
			}
		}

		return $result;
	}
}