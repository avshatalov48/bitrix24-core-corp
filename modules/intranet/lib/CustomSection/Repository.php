<?php

namespace Bitrix\Intranet\CustomSection;

use Bitrix\Intranet\CustomSection\DataStructures\Assembler;
use Bitrix\Intranet\CustomSection\DataStructures\CustomSection;
use Bitrix\Intranet\CustomSection\DataStructures\CustomSectionPage;
use Bitrix\Intranet\CustomSection\Entity\CustomSectionTable;
use Bitrix\Main\DI\ServiceLocator;

/**
 * @internal Not covered by backwards compatibility
 */
class Repository
{
	// deps
	/** @var CustomSectionTable */
	protected string $dataManager = CustomSectionTable::class;
	/** @var Assembler */
	private string $assembler = Assembler::class;
	protected Provider\Registry $providerRegistry;

	private ?array $cache = null;

	final public function __construct()
	{
		$this->providerRegistry = ServiceLocator::getInstance()->get('intranet.customSection.provider.registry');
	}

	final public function clearCache(): void
	{
		$this->cache = null;
		$this->dataManager::cleanCache();
	}

	/**
	 * @return CustomSection[]
	 */
	final public function getCustomSections(): array
	{
		if (is_array($this->cache))
		{
			return array_values($this->cache);
		}

		$collection = $this->dataManager::getList([
			'select' => ['*', 'PAGES']
		])->fetchCollection();

		$sections = [];
		foreach ($collection as $entityObject)
		{
			$section = $this->assembler::constructCustomSectionFromEntityObject($entityObject);
			$this->loadSystemPagesIntoSection($section);

			foreach ($section->getPages() as $page)
			{
				$this->loadAnalyticsIntoPage($page);
			}

			$sections[$section->getCode()] = $section;
		}

		$this->cache = $sections;

		return array_values($sections);
	}

	final public function getCustomSection(string $customSectionCode): ?CustomSection
	{
		if (is_array($this->cache) && array_key_exists($customSectionCode, $this->cache))
		{
			return $this->cache[$customSectionCode];
		}

		$object = $this->dataManager::getList([
			'select' => ['*', 'PAGES'],
			'filter' => [
				'=CODE' => $customSectionCode,
			],
			'cache' => [
				'ttl' => 3600,
				'cache_joins' => true,
			],
		])->fetchObject();

		$section = ($object ? $this->assembler::constructCustomSectionFromEntityObject($object) : null);
		if (!is_null($section))
		{
			$this->loadSystemPagesIntoSection($section);

			foreach ($section->getPages() as $page)
			{
				$this->loadAnalyticsIntoPage($page);
			}
		}

		$this->cache[$customSectionCode] = $section;

		return $section;
	}

	final public function getCustomSectionPage(string $customSectionCode, string $pageCode): ?CustomSectionPage
	{
		$section = $this->getCustomSection($customSectionCode);
		if (!$section)
		{
			return null;
		}

		foreach ($section->getPages() as $page)
		{
			if ($page->getCode() === $pageCode)
			{
				return $page;
			}
		}

		return null;
	}

	/**
	 * Set in $section system pages
	 *
	 * @param CustomSection $section
	 *
	 * @return void
	 */
	private function loadSystemPagesIntoSection(CustomSection $section): void
	{
		$sectionModuleId = $section->getModuleId();
		if (is_null($sectionModuleId))
		{
			return;
		}

		$provider = $this->providerRegistry->getProvider($sectionModuleId);
		if (is_null($provider))
		{
			return;
		}

		$systemPages = $provider->getSystemPages($section);
		$pages = $section->getPages();

		$section->setPages(array_merge($pages, $systemPages));
	}

	private function loadAnalyticsIntoPage(CustomSectionPage $page): void
	{
		$provider = $this->providerRegistry->getProvider($page->getModuleId());
		if ($provider === null)
		{
			return;
		}

		$page->setAnalytics($provider->getAnalytics($page->getSettings()));
	}
}
