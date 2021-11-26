<?php

namespace Bitrix\Intranet\Binding;

use Bitrix\Intranet\Binding\Map\MapSection;
use Bitrix\Main\ArgumentException;

class Map
{
	/** @var MapSection[] */
	protected $sections = [];

	/**
	 * Map constructor.
	 *
	 * @param MapSection[] $sections
	 */
	public function __construct(array $sections = [])
	{
		foreach ($sections as $section)
		{
			if (!$this->has($section))
			{
				$this->addToInternalStorage($section);
 			}
		}
	}

	protected function addToInternalStorage(MapSection $section): void
	{
		$this->sections[$section->getSimilarHash()] = $section;
	}

	protected function removeFromInternalStorage(MapSection $section): void
	{
		unset($this->sections[$section->getSimilarHash()]);
	}

	public function has(MapSection $section): bool
	{
		return !is_null($this->findSimilarSection($section));
	}

	protected function findSimilarSection(MapSection $section): ?MapSection
	{
		foreach ($this->getSections() as $existingSection)
		{
			if ($existingSection->isSimilarTo($section))
			{
				return $existingSection;
			}
		}

		return null;
	}

	public function add(MapSection $section): self
	{
		if ($this->has($section))
		{
			throw new ArgumentException('The same MapSection already exists in this map');
		}

		$this->addToInternalStorage($section);

		return $this;
	}

	public function remove(MapSection $section): self
	{
		if (!$this->has($section))
		{
			throw new ArgumentException('The MapSection that is being removed does not exist in this map');
		}

		$this->removeFromInternalStorage($section);

		return $this;
	}

	/**
	 * @return MapSection[]
	 */
	public function getSections(): array
	{
		return array_values($this->sections);
	}

	/**
	 * Return a new Map object that contains sections from this and another map. Duplicates are merged
	 *
	 * @param static $anotherMap
	 *
	 * @return static
	 */
	public function merge(self $anotherMap): self
	{
		$mergeResult = new static();

		static::mergeInOneDirection($mergeResult, $this->getSections(), $anotherMap);

		static::mergeInOneDirection($mergeResult, $anotherMap->getSections(), $this);

		return $mergeResult;
	}

	/**
	 * @param static $mergeResult
	 * @param MapSection[] $sectionsToAdd
	 * @param static $mapThatMayContainDuplicates
	 */
	protected static function mergeInOneDirection(
		self $mergeResult,
		array $sectionsToAdd,
		self $mapThatMayContainDuplicates
	): void
	{
		foreach ($sectionsToAdd as $section)
		{
			if ($mergeResult->has($section))
			{
				continue;
			}

			$sectionThatWillBeAdded = $section;

			if ($mapThatMayContainDuplicates->has($section))
			{
				$duplicate = $mapThatMayContainDuplicates->findSimilarSection($section);
				if (!is_null($duplicate))
				{
					$sectionThatWillBeAdded = $section->merge($duplicate);
				}
			}

			$mergeResult->add($sectionThatWillBeAdded);
		}
	}
}
