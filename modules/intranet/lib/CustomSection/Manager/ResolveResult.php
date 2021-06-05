<?php

namespace Bitrix\Intranet\CustomSection\Manager;

use Bitrix\Intranet\CustomSection\DataStructures\CustomSection;
use Bitrix\Intranet\CustomSection\DataStructures\CustomSectionPage;
use Bitrix\Intranet\CustomSection\Provider\Component;
use Bitrix\Main\Result;

class ResolveResult extends Result
{
	/** @var CustomSection|null */
	protected $customSection;
	/** @var CustomSectionPage[] */
	protected $availablePages = [];
	/** @var CustomSectionPage|null */
	protected $activePage;
	/** @var Component|null */
	protected $componentToInclude;

	/**
	 * Returns a resolved custom section
	 *
	 * @return CustomSection
	 */
	public function getCustomSection(): ?CustomSection
	{
		return $this->customSection;
	}

	/**
	 * Set a resolved custom section
	 *
	 * @param CustomSection $customSection
	 *
	 * @return ResolveResult
	 */
	public function setCustomSection(CustomSection $customSection): ResolveResult
	{
		$this->customSection = $customSection;

		return $this;
	}

	/**
	 * Returns pages that should be displayed for the current user
	 *
	 * @return CustomSectionPage[]
	 */
	public function getAvailablePages(): array
	{
		return $this->availablePages;
	}

	/**
	 * Sets pages that should be displayed for the current user
	 *
	 * @param CustomSectionPage[] $availablePages
	 *
	 * @return ResolveResult
	 */
	public function setAvailablePages(array $availablePages): ResolveResult
	{
		$this->availablePages = $availablePages;

		return $this;
	}

	/**
	 * Get a page that is active now
	 *
	 * @return CustomSectionPage|null
	 */
	public function getActivePage(): ?CustomSectionPage
	{
		return $this->activePage;
	}

	/**
	 * Set a page that is active now
	 *
	 * @param CustomSectionPage $activePage
	 *
	 * @return ResolveResult
	 */
	public function setActivePage(CustomSectionPage $activePage): ResolveResult
	{
		$this->activePage = $activePage;

		return $this;
	}

	/**
	 * Get params of component that should be included on page
	 *
	 * @return Component|null
	 */
	public function getComponentToInclude(): ?Component
	{
		return $this->componentToInclude;
	}

	/**
	 * Set params of component that should be included on page
	 *
	 * @param Component|null $componentToInclude
	 *
	 * @return ResolveResult
	 */
	public function setComponentToInclude(?Component $componentToInclude): ResolveResult
	{
		$this->componentToInclude = $componentToInclude;

		return $this;
	}
}
