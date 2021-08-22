<?php


namespace Bitrix\Tasks\Access\Model;


use Bitrix\Tasks\CheckList\Internals\CheckList;
use Bitrix\Tasks\Internals\Task\CheckListTable;

class ChecklistModel
{
	private
		$id = 0,
		$ownerId = 0,
		$entityId = 0;

	private $model;

	public static function createFromArray(array $data): self
	{
		$model = new self();

		$id = array_key_exists('ID', $data) ? (int)$data['ID'] : 0;
		$model->setId($id);

		$ownerId = array_key_exists('CREATED_BY', $data) ? (int)$data['CREATED_BY'] : 0;
		$model->setOwnerId($ownerId);

		$entityId = array_key_exists('ENTITY_ID', $data) ? (int)$data['ENTITY_ID'] : 0;
		$model->setEntityId($entityId);

		return $model;
	}

	public static function createFromChecklist(CheckList $checklist)
	{
		return self::createFromArray($checklist->getFields());
	}

	public static function createFromId(int $id): self
	{
		$model = new self();
		return $model->setId($id);
	}

	public function __construct()
	{

	}

	public function getId(): int
	{
		return $this->id;
	}

	public function getOwnerId(): int
	{
		if (!$this->ownerId && $this->load())
		{
			$this->ownerId = (int) $this->model->getCreatedBy();
		}
		return $this->ownerId;
	}

	public function getEntityId(): int
	{
		if (!$this->entityId && $this->load())
		{
			$this->entityId = (int) $this->model->getTaskId();
		}
		return $this->entityId;
	}

	private function setId(int $id): self
	{
		$this->id = $id;
		return $this;
	}

	private function setOwnerId(int $id): self
	{
		$this->ownerId = $id;
		return $this;
	}

	private function setEntityId(int $id): self
	{
		$this->entityId = $id;
		return $this;
	}

	private function load()
	{
		if ($this->model)
		{
			return $this->model;
		}

		if (!$this->id)
		{
			return null;
		}

		$this->model = CheckListTable::getById($this->id)->fetchObject();

		return $this->model;
	}
}