<?php
namespace Bitrix\Rpa\Integration\Bizproc\Automation\Target;

use Bitrix\Bizproc\Automation\Target\BaseTarget;
use Bitrix\Main\Loader;
use Bitrix\Rpa\Integration\Bizproc\Automation\Factory;

if (!Loader::includeModule('bizproc'))
{
	return;
}

abstract class Base extends BaseTarget
{
	protected $fields = [];

	/**
	 * @param $fields
	 * @return $this
	 */
	public function setFields($fields): BaseTarget
	{
		$this->fields = $fields;

		return $this;
	}

	/**
	 * @param mixed $field
	 * @param mixed $value
	 * @return $this
	 */
	public function setField($field, $value): BaseTarget
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
			$fields = [];
			if ($fields)
			{
				$this->setFields($fields);
				$this->setDocumentId($id);
			}
		}
	}

	public function isAvailable(): bool
	{
		return Factory::canUseAutomation();
	}

	public function getAvailableTriggers(): ?array
	{
		return Factory::getAvailableTriggers($this->getDocumentType()[2]);
	}
}