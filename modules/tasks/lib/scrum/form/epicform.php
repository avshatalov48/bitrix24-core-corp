<?php

namespace Bitrix\Tasks\Scrum\Form;

use Bitrix\Main\ArgumentNullException;

class EpicForm
{
	private $id = 0;
	private $groupId = 0;
	private $name = '';
	private $description = '';
	private $createdBy = 0;
	private $modifiedBy = 0;
	private $color = '';

	/**
	 * Returns an array with keys for the client.
	 *
	 * @return array
	 */
	public function toArray(): array
	{
		return [
			'id' => $this->getId(),
			'groupId' => $this->getGroupId(),
			'name' => $this->getName(),
			'description' => $this->getDescription(),
			'createdBy' => $this->getCreatedBy(),
			'modifiedBy' => $this->getModifiedBy(),
			'color' => $this->getColor(),
		];
	}

	/**
	 * Returns a list of fields to create an epic.
	 *
	 * @return array
	 * @throws ArgumentNullException
	 */
	public function getFieldsToCreate(): array
	{
		$this->checkRequiredParametersToCreate();

		return [
			'NAME' => $this->name,
			'DESCRIPTION' => $this->description,
			'GROUP_ID' => $this->groupId,
			'CREATED_BY' => $this->createdBy,
			'COLOR' => $this->color,
		];
	}

	/**
	 * Returns a list of fields to update an epic.
	 *
	 * @return array
	 */
	public function getFieldsToUpdate(): array
	{
		$fields = [];

		if ($this->groupId)
		{
			$fields['GROUP_ID'] = $this->groupId;
		}

		if ($this->name)
		{
			$fields['NAME'] = $this->name;
		}

		if ($this->description)
		{
			$fields['DESCRIPTION'] = $this->description;
		}

		if ($this->createdBy)
		{
			$fields['CREATED_BY'] = $this->createdBy;
		}

		if ($this->modifiedBy)
		{
			$fields['MODIFIED_BY'] = $this->modifiedBy;
		}

		if ($this->color)
		{
			$fields['COLOR'] = $this->color;
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

		if (isset($fields['GROUP_ID']))
		{
			$this->setGroupId($fields['GROUP_ID']);
		}

		if (isset($fields['NAME']))
		{
			$this->setName($fields['NAME']);
		}

		if (isset($fields['DESCRIPTION']))
		{
			$this->setDescription($fields['DESCRIPTION']);
		}

		if (isset($fields['CREATED_BY']))
		{
			$this->setCreatedBy($fields['CREATED_BY']);
		}

		if (isset($fields['MODIFIED_BY']))
		{
			$this->setModifiedBy($fields['MODIFIED_BY']);
		}

		if (isset($fields['COLOR']))
		{
			$this->setColor($fields['COLOR']);
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

	public function getGroupId(): int
	{
		return $this->groupId;
	}

	public function setGroupId($groupId): void
	{
		$this->groupId = (is_numeric($groupId) ? (int) $groupId : 0);
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function setName($name): void
	{
		$this->name = (is_string($name) ? $name : '');
	}

	public function getDescription(): string
	{
		return $this->description;
	}

	public function setDescription($description): void
	{
		$this->description = (is_string($description) ? $description : '');
	}

	public function getCreatedBy(): int
	{
		return $this->createdBy;
	}

	public function setCreatedBy($createdBy): void
	{
		$this->createdBy = (is_numeric($createdBy) ? (int) $createdBy : 0);
	}

	public function getModifiedBy(): int
	{
		return $this->modifiedBy;
	}

	public function setModifiedBy($modifiedBy): void
	{
		$this->modifiedBy = (is_numeric($modifiedBy) ? (int) $modifiedBy : 0);
	}

	public function getColor(): string
	{
		return $this->color;
	}

	public function setColor($color): void
	{
		$this->color = (is_string($color) ? $color : '');
	}

	/**
	 * @throws ArgumentNullException
	 */
	private function checkRequiredParametersToCreate(): void
	{
		if (empty($this->name))
		{
			throw new ArgumentNullException('NAME');
		}

		if (empty($this->groupId))
		{
			throw new ArgumentNullException('GROUP_ID');
		}

		if (empty($this->createdBy))
		{
			throw new ArgumentNullException('CREATED_BY');
		}
	}
}