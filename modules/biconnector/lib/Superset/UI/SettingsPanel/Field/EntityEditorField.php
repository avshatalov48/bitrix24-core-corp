<?php

namespace Bitrix\BIConnector\Superset\UI\SettingsPanel\Field;

abstract class EntityEditorField
{
	abstract public function getFieldInitialData(): array;

	abstract public function getName(): string;

	abstract public function getType(): string;

	abstract protected function getFieldInfoData(): array;

	public function __construct(private string $id)
	{}

	final public function getFieldInfo(): array
	{
		return [
			'id' => $this->id,
			'title' => $this->getTitle() ?? '',
			'name' => $this->getName(),
			'type' => $this->getType(),
			'data' => $this->getFieldInfoData(),
			'isDragEnabled' => false,
		];
	}

	protected function getTitle(): ?string
	{
		return null;
	}
}
