<?php

namespace Bitrix\Rpa\UserField;

use Bitrix\Main\Engine\Response\Converter;
use Bitrix\Main\NotImplementedException;
use Bitrix\Rpa\Model\FieldTable;

class UserField implements \ArrayAccess
{
	protected $data;
	protected $convertedName;
	protected $visibility;

	public function __construct(array $data, array $visibility)
	{
		$this->data = $data;
		$converter = new Converter(Converter::LC_FIRST | Converter::TO_CAMEL);
		$this->convertedName = $converter->process($this->getName());
		$this->visibility = $visibility;
	}

	public function getName(): string
	{
		return $this->data['FIELD_NAME'];
	}

	public function getTitle(): string
	{
		$titleFields = [
			'EDIT_FORM_LABEL',
			'LIST_COLUMN_LABEL',
			'LIST_FILTER_LABEL',
		];
		foreach($titleFields as $name)
		{
			if(!empty($this->data[$name]))
			{
				return $this->data[$name];
			}
		}

		return $this->getName();
	}

	public function toArray(): array
	{
		$this->data['TITLE'] = $this->getTitle();
		$this->data['FIELD'] = $this->getName();
		return $this->data;
	}

	public function getUserTypeId(): string
	{
		return $this->data['USER_TYPE_ID'];
	}

	public function getSettings(): ?array
	{
		return $this->data['SETTINGS'];
	}

	public function isVisible(): bool
	{
		return ($this->visibility[FieldTable::VISIBILITY_VISIBLE] ?? false);
	}

	public function isEditable(): bool
	{
		return ($this->visibility[FieldTable::VISIBILITY_EDITABLE] ?? false);
	}

	/**
	 * Returns true if this field is mandatory in visibility settings
	 *
	 * @return bool
	 */
	public function isMandatory(): bool
	{
		return ($this->visibility[FieldTable::VISIBILITY_MANDATORY] ?? false);
	}

	/**
	 * Returns true if this field is mandatory in common settings
	 *
	 * @return bool
	 */
	public function isMandatoryByDefault(): bool
	{
		return ($this->data['MANDATORY'] === 'Y');
	}

	public function isMultiple(): bool
	{
		return $this->data['MULTIPLE'] === 'Y';
	}

	public function isKanbanVisible(): bool
	{
		return ($this->visibility[FieldTable::VISIBILITY_KANBAN] ?? false);
	}

	public function isAvailableOnCreate(): bool
	{
		return ($this->visibility[FieldTable::VISIBILITY_CREATE] ?? false);
	}

	public function isBaseTypeFile(): bool
	{
		return ($this->data['USER_TYPE']['BASE_TYPE'] === 'file');
	}

	public function isBaseTypeDate(): bool
	{
		return ($this->data['USER_TYPE']['BASE_TYPE'] === 'datetime');
	}

	public function isBaseTypeBoolean(): bool
	{
		return ($this->data['USER_TYPE']['BASE_TYPE'] === 'boolean');
	}

	public function isBaseTypeNumerical(): bool
	{
		return ($this->data['USER_TYPE']['BASE_TYPE'] === 'integer' || $this->data['USER_TYPE']['BASE_TYPE'] === 'double');
	}

	public function offsetExists($offset)
	{
		return isset($this->data[$offset]);
	}

	public function offsetGet($offset)
	{
		return $this->data[$offset];
	}

	public function offsetSet($offset, $value)
	{
		throw new NotImplementedException('UserField object does not support editing');
	}

	public function offsetUnset($offset)
	{
		throw new NotImplementedException('UserField object does not support editing');
	}

	public function isValueEmpty($fieldValue): bool
	{
		if (is_array ($fieldValue) && $this->isMultiple())
		{
			foreach ($fieldValue as $singleValue)
			{
				if (!$this->isValueEmpty($singleValue))
				{
					return false;
				}
			}

			return true;
		}

		// Interpret bool 'false' as non-empty for boolean type
		if ($this->isBaseTypeBoolean() && (bool)$fieldValue === false)
		{
			return false;
		}

		if (
			$this->isBaseTypeNumerical()
			&& ($fieldValue === 0 || $fieldValue === 0.0 || $fieldValue === "0" || $fieldValue === "0.0" || $fieldValue === "0,0")
		)
		{
			return false;
		}

		return empty($fieldValue);
	}

	public function prepareNullValue($value)
	{
		if ($value === '' && $this->isBaseTypeNumerical())
		{
			return null;
		}

		return $value;
	}
}