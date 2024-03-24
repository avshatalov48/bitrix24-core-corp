<?php

namespace Bitrix\BIConnector\Superset\UI\SettingsPanel\Section;

use Bitrix\BIConnector\Superset\UI\SettingsPanel\Field\EntityEditorField;

final class EntityEditorSection
{
	/**
	 * @var EntityEditorField[]
	 */
	private array $sectionFields = [];

	public function __construct(
		private string $name,
		private string $title = '',
		private string $iconClass = '',
	)
	{}

	/**
	 * @return EntityEditorField[]
	 */
	public function getFields(): array
	{
		return $this->sectionFields;
	}

	/**
	 * @param EntityEditorField ...$editorFieldList
	 * @return $this
	 */
	public function addField(EntityEditorField ...$editorFieldList): self
	{
		array_push($this->sectionFields, ...$editorFieldList);

		return $this;
	}

	public function setIconClass(string $iconClass): self
	{
		$this->iconClass = $iconClass;

		return $this;
	}

	private function getTitle(): string
	{
		return $this->title;
	}

	private function getName(): string
	{
		return $this->name;
	}

	private function getIconClass(): string
	{
		return $this->iconClass;
	}

	public function getConfig(): array
	{
		return [
			'name' => $this->getName(),
			'title' => $this->getTitle(),
			'enableTitle' => $this->getTitle() !== '',
			'type' => 'section',
			'elements' => [
				...$this->getSectionElementsInfo(),
			],
			'data' => [
				'isChangeable' => false,
				'isRemovable' => false,
				'iconClass' => $this->getIconClass(),
			],
		];
	}

	private function getSectionElementsInfo(): array
	{
		$elementsInfo = [];
		foreach ($this->getFields() as $field)
		{
			$elementsInfo[] = [
				'name' => $field->getName(),
			];
		}

		return $elementsInfo;
	}
}
