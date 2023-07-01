<?php

namespace Bitrix\Tasks\Scrum\Form;

use Bitrix\Main\ArgumentNullException;

class ItemForm
{
	private $id = 0;
	private $entityId = 0;
	private $typeId = 0;
	private $epicId = null;
	private $active = 'Y';
	private $name = '';
	private $description = '';
	private $sort = null;
	private $createdBy = 0;
	private $modifiedBy = 0;
	private $storyPoints = null;
	private $sourceId = null;
	private $info = null;

	private $tmpId = '';

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
			'typeId' => $this->getTypeId(),
			'epicId' => $this->getEpicId(),
			'active' => $this->getActive(),
			'name' => $this->getName(),
			'description' => $this->getDescription(),
			'sort' => $this->getSort(),
			'createdBy' => $this->getCreatedBy(),
			'modifiedBy' => $this->getModifiedBy(),
			'storyPoints' => $this->getStoryPoints(),
			'sourceId' => $this->getSourceId(),
			'info' => $this->getInfo(),
			'tmpId' => $this->getTmpId(),
		];
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

	/**
	 * Returns a list of fields to update an item.
	 *
	 * @return array
	 */
	public function getFieldsToUpdateItem(): array
	{
		$fields = [];

		if ($this->name)
		{
			$fields['NAME'] = $this->name;
		}

		if ($this->description)
		{
			$fields['DESCRIPTION'] = $this->description;
		}

		if ($this->entityId)
		{
			$fields['ENTITY_ID'] = $this->entityId;
		}

		if ($this->typeId)
		{
			$fields['TYPE_ID'] = $this->typeId;
		}

		if ($this->epicId !== null)
		{
			$fields['EPIC_ID'] = $this->epicId;
		}

		if ($this->sort !== null)
		{
			$fields['SORT'] = $this->sort;
		}

		if ($this->createdBy)
		{
			$fields['CREATED_BY'] = $this->createdBy;
		}

		if ($this->modifiedBy)
		{
			$fields['MODIFIED_BY'] = $this->modifiedBy;
		}

		if ($this->storyPoints !== null)
		{
			$fields['STORY_POINTS'] = $this->storyPoints;
		}

		if ($this->info)
		{
			$fields['INFO'] = $this->info;
		}

		return $fields;
	}

	/**
	 * Returns a list of fields to create a task item.
	 *
	 * @return array
	 * @throws ArgumentNullException
	 */
	public function getFieldsToCreateTaskItem(): array
	{
		$this->checkRequiredParametersToCreateTaskItem();

		return [
			'ENTITY_ID' => $this->entityId,
			'ACTIVE' => 'Y',
			'SORT' => $this->getSort(),
			'CREATED_BY' => $this->createdBy,
			'MODIFIED_BY' => $this->createdBy,
			'STORY_POINTS' => $this->storyPoints,
			'SOURCE_ID' => $this->sourceId,
			'EPIC_ID' => $this->epicId,
		];
	}

	/**
	 * To fill the object with data obtained from the database.
	 *
	 * @param array $fields An array with fields.
	 */
	public function fillFromDatabase(array $fields): void
	{
		if ($fields['ID'] ?? null)
		{
			$this->setId($fields['ID']);
		}

		if ($fields['ENTITY_ID'] ?? null)
		{
			$this->setEntityId($fields['ENTITY_ID']);
		}

		if ($fields['TYPE_ID'] ?? null)
		{
			$this->setTypeId($fields['TYPE_ID']);
		}

		if ($fields['EPIC_ID'] ?? null)
		{
			$this->setEpicId($fields['EPIC_ID']);
		}

		if ($fields['ACTIVE'] ?? null)
		{
			$this->setActive($fields['ACTIVE']);
		}

		if ($fields['NAME'] ?? null)
		{
			$this->setName($fields['NAME']);
		}

		if ($fields['DESCRIPTION'] ?? null)
		{
			$this->setDescription($fields['DESCRIPTION']);
		}

		if ($fields['SORT'] ?? null)
		{
			$this->setSort($fields['SORT']);
		}

		if ($fields['CREATED_BY'] ?? null)
		{
			$this->setCreatedBy($fields['CREATED_BY']);
		}

		if ($fields['MODIFIED_BY'] ?? null)
		{
			$this->setModifiedBy($fields['MODIFIED_BY']);
		}

		if (($fields['STORY_POINTS'] ?? '') <> '')
		{
			$this->setStoryPoints($fields['STORY_POINTS']);
		}

		if ($fields['SOURCE_ID'] ?? null)
		{
			$this->setSourceId($fields['SOURCE_ID']);
		}

		if ($fields['INFO'] ?? null)
		{
			$this->setInfo($fields['INFO']);
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

	public function getTypeId(): int
	{
		return $this->typeId;
	}

	public function setTypeId($typeId): void
	{
		$this->typeId = (is_numeric($typeId) ? (int) $typeId : 0);
	}

	public function getEpicId(): int
	{
		return $this->epicId === null ? 0 : $this->epicId;
	}

	public function setEpicId($epicId): void
	{
		$this->epicId = (is_numeric($epicId) ? (int) $epicId : 0);
	}

	public function getActive(): string
	{
		return $this->active;
	}

	public function setActive($active): void
	{
		$availableValues = ['Y', 'N'];

		if (in_array($active, $availableValues, true))
		{
			$this->active = $active;
		}
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

	public function getSort(): int
	{
		return $this->sort === null ? 0 : $this->sort;
	}

	public function setSort($sort): void
	{
		$this->sort = (is_numeric($sort) ? (int) $sort : 0);
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

	public function getStoryPoints(): string
	{
		return $this->storyPoints === null ? '' : $this->storyPoints;
	}

	public function setStoryPoints($storyPoints): void
	{
		$this->storyPoints = (is_string($storyPoints) ? $storyPoints : '');
	}

	public function getSourceId(): int
	{
		return $this->sourceId === null ? 0 : $this->sourceId;
	}

	public function setSourceId($sourceId): void
	{
		$this->sourceId = (is_numeric($sourceId) ? (int) $sourceId : 0);
	}

	public function getInfo(): ItemInfo
	{
		return ($this->info === null ? new ItemInfo() : $this->info);
	}

	public function setInfo(ItemInfo $info): void
	{
		$this->info = $info;
	}

	public function getTmpId(): string
	{
		return $this->tmpId;
	}

	public function setTmpId($tmpId): void
	{
		$this->tmpId = (is_string($tmpId) ? $tmpId : '');
	}

	/**
	 * @throws ArgumentNullException
	 */
	private function checkRequiredParametersToCreateTaskItem(): void
	{
		if (empty($this->entityId))
		{
			throw new ArgumentNullException('ENTITY_ID');
		}

		if (empty($this->createdBy))
		{
			throw new ArgumentNullException('CREATED_BY');
		}

		if (empty($this->sourceId))
		{
			throw new ArgumentNullException('SOURCE_ID');
		}
	}
}