<?php

namespace Bitrix\Mobile\Field\Type;

use Bitrix\Mobile\Field\BoundEntitiesContainer;

class FileField extends BaseField implements HasBoundEntities
{
	public const TYPE = 'file';

	/**
	 * @inheritDoc
	 */
	public function getFormattedValue()
	{
		if (!$this->value)
		{
			return $this->isMultiple() ? [] : '';
		}

		return $this->value;
	}

	/**
	 * @inheritDoc
	 */
	public function getBoundEntities(): array
	{
		$value = $this->value;
		if (!$value)
		{
			return [];
		}

		if (!$this->isMultiple())
		{
			$value = [$value];
		}

		return [
			'file' => [
				'ids' => $value,
				'field' => $this,
			],
		];
	}

	/**
	 * @inheritDoc
	 */
	public function getData(): array
	{
		$data = parent::getData();

		$data['fileInfo'] = [];

		$boundFiles = BoundEntitiesContainer::getInstance()->getBoundEntities()['file'] ?? [];
		if ($this->isMultiple())
		{
			if ($this->value)
			{
				foreach ($this->value as $value)
				{
					$data['fileInfo'][$value] = $boundFiles[$value] ?? [];
				}
			}
		}
		else
		{
			$data['fileInfo'][$this->value] = $boundFiles[$this->value] ?? [];
		}

		return $data;
	}
}
