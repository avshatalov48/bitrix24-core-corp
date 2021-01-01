<?php
namespace Bitrix\Tasks\Scrum\Checklist;

use Bitrix\Main\Access\AccessibleItem;

class ItemModel implements AccessibleItem
{
	private $id;

	public static function createFromId(int $id): AccessibleItem
	{
		$model = new self();
		$model->setId($id);
		return $model;
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function setId(int $id): self
	{
		$this->id = $id;
		return $this;
	}
}