<?php

namespace Bitrix\Crm\Integration\UI\EntityEditor\Configuration;

use Bitrix\AiProxy\Enum\Provider;

class Section
{
	/** @var array<string, Element>  */
	private array $elements = [];

	public function __construct(
		private string $name,
		private string $title,
		array $elements = [],
	)
	{
		foreach ($elements as $element)
		{
			if ($element instanceof Element)
			{
				$this->addElement($element);
			}
		}
	}

	public static function fromArray(array $section): ?self
	{
		$name = $section['name'] ?? null;
		$title = $section['title'] ?? null;
		$type = $section['type'] ?? null;
		$elements = $section['elements'] ?? [];

		if (
			$type !== 'section'
			|| empty($name)
			|| empty($title)
		)
		{
			return null;
		}

		$elements = array_map(Element::fromArray(...), $elements);
		$elements = array_filter($elements, static fn (?Element $element) => $element !== null);

		return new self($name, $title, $elements);
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function setName(string $name): self
	{
		if (empty($name))
		{
			return $this;
		}

		$this->name = $name;

		return $this;
	}

	public function getTitle(): string
	{
		return $this->title;
	}

	public function setTitle(string $title): Section
	{
		if (empty($title))
		{
			return $this;
		}

		$this->title = $title;

		return $this;
	}

	public function getElements(): array
	{
		return $this->elements;
	}

	public function clearElements(): self
	{
		$this->elements = [];

		return $this;
	}

	public function addElement(Element $element): self
	{
		$this->elements[$element->getName()] = $element;

		return $this;
	}

	public function removeElement(string $elementName): self
	{
		unset($this->elements[$elementName]);

		return $this;
	}

	public function getElement(string $elementName): ?Element
	{
		return $this->elements[$elementName] ?? null;
	}

	public function getElementNames(): array
	{
		$names = [];
		foreach ($this->elements as $element)
		{
			$names[] = $element->getName();
		}

		return $names;
	}

	public function getOrCreateElement(string $elementName): Element
	{
		$element = $this->getElement($elementName);
		if ($element !== null)
		{
			return $element;
		}

		$element = new Element($elementName);
		$this->addElement($element);

		return $element;
	}

	public function toArray(): array
	{
		$elements = array_map(static fn (Element $element) => $element->toArray(), $this->elements);
		$elements = array_values($elements);

		return [
			'name' => $this->name,
			'title' => $this->title,
			'type' => 'section',
			'elements' => $elements,
		];
	}
}
