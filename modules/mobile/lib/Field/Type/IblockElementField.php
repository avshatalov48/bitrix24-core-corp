<?php

namespace Bitrix\Mobile\Field\Type;

use Bitrix\Mobile\Field\BoundEntitiesContainer;

class IblockElementField extends BaseField implements HasBoundEntities
{
	public const TYPE = 'iblock_element';

	/**
	 * @inheritDoc
	 */
	public function getFormattedValue()
	{
		if (empty($this->value))
		{
			return null;
		}

		if ($this->isMultiple())
		{
			$elementIds = [];

			foreach ((array)$this->getValue() as $value)
			{
				$elementIds[] = (int)$value;
			}

			return $elementIds;
		}

		return (int)$this->getValue();
	}

	public function getData(): array
	{
		$data = parent::getData();

		$elementIds = [];

		if (!empty($this->value))
		{
			foreach ((array)$this->getValue() as $value)
			{
				$elementIds[] = (int)$value;
			}
		}

		$entityList = [];

		$elements = BoundEntitiesContainer::getInstance()->getBoundEntities()[static::TYPE] ?? [];

		foreach ($elementIds as $elementId)
		{
			if (!empty($elements[$elementId]))
			{
				$entityList[] = [
					'id' => $elementId,
					'title' => $elements[$elementId]['NAME'],
				];
			}
		}

		$fieldInfo = $this->getUserFieldInfo();

		return array_merge($data, [
			'entityList' => $entityList,
			'selectorType' => $this->getSelectorType(),
			'provider' => [
				'options' => [
					'fieldInfo' => $fieldInfo,
				],
			],
		]);
	}

	protected function getSelectorType(): string
	{
		return 'iblock-element-user-field';
	}

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
			'iblock_element' => [
				'ids' => $value,
				'field' => $this,
			],
		];
	}
}
