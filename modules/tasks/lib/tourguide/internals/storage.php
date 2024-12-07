<?php

namespace Bitrix\Tasks\TourGuide\Internals;

use Bitrix\Tasks\TourGuide\TourGuide;

class Storage
{
	/** @var TourGuide[]  */
	private array $items = [];

	public function __construct(TourGuide ...$guides)
	{
		foreach ($guides as $guide)
		{
			$this->add($guide);
		}
	}

	public function add(TourGuide $guide): static
	{
		$this->items[$guide::class] = $guide;
		return $this;
	}

	public function remove(TourGuide $guide): static
	{
		unset($this->items[$guide::class]);
		return $this;
	}

	public function hasActiveGuides(TourGuide $excludedGuide): bool
	{
		foreach ($this->items as $guide)
		{
			if ($guide::class === $excludedGuide::class)
			{
				continue;
			}

			if ($guide->proceed())
			{
				return true;
			}
		}

		return false;
	}

	public function hasActiveFirstExperienceGuides(TourGuide $excludedGuide): bool
	{
		foreach ($this->items as $guide)
		{
			if (
				$guide::class === $excludedGuide::class
				|| !$guide->isFirstExperience()
			)
			{
				continue;
			}

			if ($guide->proceed())
			{
				return true;
			}
		}

		return false;
	}
}
