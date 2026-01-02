<?php

namespace Bitrix\Crm\Integration\UI\EntityEditor;

use Bitrix\Crm\Entity\EntityEditorConfig;
use Bitrix\Crm\Integration\UI\EntityEditor\Configuration\Element;
use Bitrix\Crm\Integration\UI\EntityEditor\Configuration\Section;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\InvalidOperationException;
use ReturnTypeWillChange;

final class Configuration
{
	/**
	 * @var array<string, Section>
	 */
	private array $sections = [];
	private ?EntityEditorConfig $entityEditorConfig = null;

	/**
	 * @param Section[] $sections
	 */
	public function __construct(array $sections)
	{
		foreach ($sections as $section)
		{
			$this->addSection($section);
		}
	}

	public function setEntityEditorConfig(EntityEditorConfig $config): self
	{
		$this->entityEditorConfig = $config;

		return $this;
	}

	public static function fromArray(array $configuration): self
	{
		$isColumn = isset($configuration[0]['type']) && $configuration[0]['type'] === 'column';
		if ($isColumn)
		{
			$configuration = $configuration[0]['elements'] ?? [];
		}

		$sections = array_map([Section::class, 'fromArray'], $configuration);
		$sections = array_filter($sections, static fn (?Section $section) => $section !== null);

		return new self($sections);
	}

	public function addSection(Section $section): self
	{
		$this->sections[$section->getName()] = $section;

		return $this;
	}

	public function removeSection(string $name): self
	{
		unset($this->sections[$name]);

		return $this;
	}

	public function getSection(string $name): ?Section
	{
		return $this->sections[$name] ?? null;
	}

	public function getSectionFirst(): ?Section
	{
		return reset($this->sections);
	}

	public function clearSections(): self
	{
		$this->sections = [];

		return $this;
	}

	public function getSections(): array
	{
		return $this->sections;
	}

	public function getSectionNames(): array
	{
		return array_keys($this->sections);
	}

	public function removeElements(array $elements): self
	{
		foreach ($this->sections as $section)
		{
			foreach ($elements as $element)
			{
				$section->removeElement($element);
			}
		}

		return $this;
	}

	/**
	 * @return Element[]
	 */
	public function getElements(): array
	{
		$elements = [];
		foreach ($this->sections as $section)
		{
			foreach ($section->getElements() as $element)
			{
				$elements[$element->getName()] = $element;
			}
		}

		return $elements;
	}

	public function getElementNames(): array
	{
		return array_keys($this->getElements());
	}

	public function getElement(string $name): ?Element
	{
		return $this->getElements()[$name] ?? null;
	}

	public function hasElement(string $name): bool
	{
		return $this->getElement($name) !== null;
	}

	public function toArray(): array
	{
		$result = array_map(static fn (Section $section) => $section->toArray(), $this->sections);

		return array_values($result);
	}

	/**
	 * @throws InvalidOperationException
	 * @throws ArgumentException
	 */
	public function save(): bool
	{
		if ($this->entityEditorConfig === null)
		{
			throw new InvalidOperationException('Must set entityEditorConfig before saving the configuration');
		}

		return $this->entityEditorConfig->set($this->toArray());
	}

	public function entityEditorConfig(): ?EntityEditorConfig
	{
		return $this->entityEditorConfig;
	}

	public function hasSections(): bool
	{
		return count($this->sections) > 0;
	}
}
