<?php
namespace Bitrix\Tasks\Integration\Bizproc\Automation\Target;

use Bitrix\Bizproc\Automation\Engine\TemplatesScheme;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Loader;
use Bitrix\Tasks\Integration\Bizproc\Automation\Factory;

if (!Loader::includeModule('bizproc'))
{
	return;
}

abstract class Base extends \Bitrix\Bizproc\Automation\Target\BaseTarget
{
	protected $fields = [];

	/**
	 * @param $fields
	 * @return $this
	 */
	public function setFields($fields)
	{
		$this->fields = $fields;
		return $this;
	}

	/**
	 * @param mixed $field
	 * @param mixed $value
	 * @return $this
	 */
	public function setField($field, $value)
	{
		$this->fields[$field] = $value;
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getFields()
	{
		if (count($this->fields) === 0 && $id = $this->getDocumentId())
		{
			$this->setFieldsById($id);
		}

		return $this->fields;
	}

	public function setFieldsById($id)
	{
		$id = (int)$id;
		if ($id > 0)
		{
			//$task = Tasks\Item\Task::getInstance($id, 1);
			//$fields = $task->getData();
			$itemIterator = \CTasks::getByID($id, false);
			$fields = $itemIterator->fetch();

			if ($fields)
			{
				$this->setFields($fields);
				$this->setDocumentId($id);
			}
		}
	}

	public function isAvailable()
	{
		return Factory::canUseAutomation();
	}

	public function getAvailableTriggers()
	{
		return Factory::getAvailableTriggers($this->getDocumentType()[2]);
	}

	public function getTemplatesScheme(): ?TemplatesScheme
	{
		$currentUserId = CurrentUser::get()->getId();

		$scheme = new \Bitrix\Tasks\Integration\Bizproc\Automation\Engine\TemplatesScheme($currentUserId);
		$scheme->build();

		return $scheme;
	}
}