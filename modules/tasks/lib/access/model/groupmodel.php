<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Access\Model;

use Bitrix\Main\Loader;

class GroupModel
{
	private $id;

	private
		$archived,
		$tasksEnabled;

	public static function createFromId(int $id): GroupModel
	{
		$model = new self();
		return $model->setId($id);
	}

	private function __construct()
	{
	}

	public function setId(int $id): self
	{
		$this->id = $id;
		return $this;
	}

	/**
	 * @return bool
	 */
	public function isArchived(): bool
	{
		if (!Loader::includeModule("socialnetwork"))
		{
			return false;
		}

		if ($this->archived === null)
		{
			if (!$this->id)
			{
				$this->archived = false;
			}
			else
			{
				$group = \CSocNetGroup::getById($this->id);
				$this->archived = ($group['CLOSED'] === 'Y') ? true : false;
			}
		}
		return $this->archived;
	}

	/**
	 * @return bool
	 */
	public function isTasksEnabled(): bool
	{
		if (!Loader::includeModule("socialnetwork"))
		{
			return false;
		}

		if ($this->tasksEnabled === null)
		{
			if (!$this->id)
			{
				$this->tasksEnabled = false;
			}
			else
			{
				$this->tasksEnabled = \CSocNetFeatures::IsActiveFeature(SONET_ENTITY_GROUP, $this->id, 'tasks');
			}
		}
		return $this->tasksEnabled;
	}
}