<?php

namespace Bitrix\Intranet\CustomSection;

use Bitrix\Intranet\CustomSection\DataStructures\Assembler;
use Bitrix\Intranet\CustomSection\DataStructures\CustomSection;
use Bitrix\Intranet\CustomSection\DataStructures\CustomSectionPage;
use Bitrix\Intranet\CustomSection\Entity\CustomSectionTable;
use Bitrix\Intranet\CustomSection\Manager\ResolveResult;
use Bitrix\Main\Application;
use Bitrix\Main\Config\Configuration;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Error;
use Bitrix\Main\InvalidOperationException;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectNotFoundException;
use Bitrix\Main\Web\Uri;

class Manager
{
	public const ERROR_CODE_INVALID_URL = 'CUSTOM_SECTION_MANAGER_INVALID_URL';
	public const ERROR_CODE_SECTION_NOT_FOUND = 'CUSTOM_SECTION_MANAGER_SECTION_NOT_FOUND';
	public const ERROR_CODE_SECTION_NOT_AVAILABLE = 'CUSTOM_SECTION_MANAGER_SECTION_NOT_AVAILABLE';
	public const ERROR_CODE_COMPONENT_NOT_FOUND = 'CUSTOM_SECTION_MANAGER_COMPONENT_NOT_FOUND';

	public const VALID_CODE_REGEX = '|^[a-z0-9_.-]+$|' . BX_UTF_PCRE_MODIFIER;

	public const COUNTER_INFIX = '_custom_section_';

	protected const SECTION_ROOT_URL_TEMPLATE = '/page/#customSectionCode#/';
	protected const PAGE_URL_REGEX = "|^/page/(?'customSectionCode'[\\w]+)/(?'pageCode'[\\w]+)/?|" . BX_UTF_PCRE_MODIFIER;
	protected const PAGE_URL_TEMPLATE = self::SECTION_ROOT_URL_TEMPLATE . '#pageCode#/';

	protected const MODULE_ID = 'intranet';
	protected const LAST_OPENED_OPTION_NAME = 'custom_sections.last_opened_pages';

	/** @var CustomSectionTable */
	protected $dataManager = CustomSectionTable::class;
	/** @var Assembler */
	protected $assembler = Assembler::class;
	/** @var Provider[] */
	// moduleId => Provider
	protected $providers = [];

	/**
	 * Compile url for a custom section page
	 *
	 * @param string $customSectionCode
	 * @param string $pageCode
	 *
	 * @return Uri|null
	 */
	public function getUrlForPage(string $customSectionCode, string $pageCode): ?Uri
	{
		if (!$this->isCodeValid($customSectionCode) || !$this->isCodeValid($pageCode))
		{
			return null;
		}

		$link = str_replace(
			['#customSectionCode#', '#pageCode#'],
			[$customSectionCode, $pageCode],
			static::PAGE_URL_TEMPLATE
		);

		return new Uri($link);
	}

	protected function getSectionRootUrl(CustomSection $customSection): ?Uri
	{
		$customSectionCode = $customSection->getCode();

		if (!$this->isCodeValid($customSectionCode))
		{
			return null;
		}

		$link = str_replace('#customSectionCode#', $customSectionCode, static::SECTION_ROOT_URL_TEMPLATE);

		return new Uri($link);
	}

	protected function isCodeValid(string $code): bool
	{
		return (bool)preg_match(static::VALID_CODE_REGEX, $code);
	}

	/**
	 * Clears left menu cache for the whole site
	 */
	public function clearLeftMenuCache(): void
	{
		$this->clearTaggedCache('bitrix24_left_menu');

		// since composite cache will be cleared for users on their next hit,
		// there is no sense in in clearing whole site composite cache

		// clear cache only for current user
		$this->clearCompositeCache();
	}

	/**
	 * Clears left menu cache for the specified user
	 *
	 * @param int $userId
	 */
	public function clearLeftMenuCacheForUser(int $userId): void
	{
		$this->clearTaggedCache('USER_NAME_' . $userId);

		$this->clearCompositeCache($userId);
	}

	protected function clearTaggedCache(string $tag): void
	{
		if (!defined('BX_COMP_MANAGED_CACHE'))
		{
			return;
		}

		$taggedCache = Application::getInstance()->getTaggedCache();
		$taggedCache->clearByTag($tag);
	}

	/**
	 * Clears left menu composite cache for a user.
	 *
	 * @param int|null $userId - if no $userId is provided, cache is cleared for the current user
	 */
	protected function clearCompositeCache(int $userId = null): void
	{
		\Bitrix\Intranet\Composite\CacheProvider::deleteUserCache(is_int($userId) ? $userId : false);
	}

	/**
	 * Appends available custom sections to super left menu
	 *
	 * @param array $superLeftMenuSections
	 */
	public function appendSuperLeftMenuSections(array &$superLeftMenuSections): void
	{
		foreach ($this->getCustomSections() as $customSection)
		{
			if ($this->isCustomSectionAvailable($customSection))
			{
				$superLeftMenuSections[] = $this->compileLeftMenuSectionDescription($customSection);
			}
		}
	}

	/**
	 * @return CustomSection[]
	 */
	protected function getCustomSections(): array
	{
		$collection = $this->dataManager::getList([
			'select' => ['*', 'PAGES']
		])->fetchCollection();

		$sections = [];
		foreach ($collection as $entityObject)
		{
			$sections[] = $this->assembler::constructCustomSectionFromEntityObject($entityObject);
		}

		return $sections;
	}

	protected function getCustomSection(string $customSectionCode): ?CustomSection
	{
		$object = $this->dataManager::getList([
			'select' => ['*', 'PAGES'],
			'filter' => [
				'=CODE' => $customSectionCode,
			],
		])->fetchObject();

		return ($object ? $this->assembler::constructCustomSectionFromEntityObject($object) : null);
	}

	protected function isCustomSectionAvailable(CustomSection $customSection): bool
	{
		return !empty($this->getAvailablePages($customSection));
	}

	/**
	 * @param CustomSection $customSection
	 *
	 * @return CustomSectionPage[]
	 */
	protected function getAvailablePages(CustomSection $customSection): array
	{
		$pages = $customSection->getPages();
		if (is_null($pages))
		{
			return [];
		}

		$availablePages = [];
		foreach ($pages as $page)
		{
			if ($this->isPageAvailable($page))
			{
				$availablePages[] = $page;
			}
		}

		return $availablePages;
	}

	protected function isPageAvailable(CustomSectionPage $page): bool
	{
		$provider = $this->getProvider($page->getModuleId());
		if (!$provider)
		{
			return false;
		}

		return $provider->isAvailable($page->getSettings(), $this->getCurrentUserId());
	}

	protected function getCurrentUserId(): int
	{
		global $USER;
		if (is_object($USER) && ($USER instanceof \CAllUser))
		{
			return (int)CurrentUser::get()->getId();
		}

		return 0;
	}

	protected function getProvider(string $moduleId): ?Provider
	{
		if (isset($this->providers[$moduleId]))
		{
			return $this->providers[$moduleId];
		}

		if (!Loader::includeModule($moduleId))
		{
			return null;
		}

		$providerClass = $this->getProviderClass($moduleId);
		if (!$providerClass)
		{
			return null;
		}

		$provider = new $providerClass();
		$this->providers[$moduleId] = $provider;

		return $provider;
	}

	protected function getProviderClass(string $moduleId): ?string
	{
		$config = Configuration::getInstance($moduleId)->get('intranet.customSection');
		if (empty($config))
		{
			return null;
		}

		$providerClass = $config['provider'] ?? null;
		if (empty($providerClass) || !is_a($providerClass, Provider::class, true))
		{
			return null;
		}

		return $providerClass;
	}

	protected function compileLeftMenuSectionDescription(CustomSection $customSection): array
	{
		$availablePages = $this->getAvailablePages($customSection);

		$page = $this->getUserSelectedFirstPage($customSection->getCode(), $availablePages);
		if (is_null($page))
		{
			$page = $this->getLastOpenedPage($customSection->getCode(), $availablePages);
		}
		if (is_null($page))
		{
			$page = $this->getPageWithMinSort($availablePages);
		}

		return [
			htmlspecialcharsbx($customSection->getTitle()),
			$this->getUrlForPage($customSection->getCode(), $page->getCode()),
			[
				$this->getSectionRootUrl($customSection),
			],
			[
				'menu_item_id' => $this->getCustomSectionMenuId($customSection->getCode()),
				'is_custom_section' => true,
				'counter_id' => self::buildCustomSectionCounterId($customSection->getModuleId(), $customSection->getId())
			],
			''
		];
	}

	/**
	 * @param string $sectionCode
	 * @param CustomSectionPage[] $pages
	 *
	 * @return CustomSectionPage|null
	 */
	protected function getUserSelectedFirstPage(string $sectionCode, array $pages): ?CustomSectionPage
	{
		$option = $this->loadInterfaceButtonsSettings($sectionCode);
		$firstPageLink = empty($option['firstPageLink']) ? null : (string)$option['firstPageLink'];
		if (!$firstPageLink)
		{
			return null;
		}

		$url = new Uri($firstPageLink);

		if (!preg_match(static::PAGE_URL_REGEX, $url->getPath(), $matches))
		{
			return null;
		}

		$pageCode = (string)$matches['pageCode'];

		foreach ($pages as $page)
		{
			if ($page->getCode() === $pageCode)
			{
				return $page;
			}
		}

		return null;
	}

	protected function loadInterfaceButtonsSettings(string $sectionCode): array
	{
		/** @see \CMainInterfaceButtons::getUserOptions */
		return \CUserOptions::GetOption('ui', $this->getCustomSectionMenuId($sectionCode), []);
	}

	/**
	 * Returns menu id for the custom section
	 *
	 * @param string $customSectionCode
	 *
	 * @return string
	 */
	public function getCustomSectionMenuId(string $customSectionCode): string
	{
		return 'menu_custom_section_' . htmlspecialcharsbx($customSectionCode);
	}

	/**
	 * Select a last opened page of a custom section from the provided pages
	 *
	 * @param string $customSectionCode
	 * @param array $pages
	 *
	 * @return CustomSectionPage|null
	 */
	protected function getLastOpenedPage(string $customSectionCode, array $pages): ?CustomSectionPage
	{
		$lastOpenedPageCode = $this->getLastOpenedPageCode($customSectionCode);
		if (empty($lastOpenedPageCode))
		{
			return null;
		}

		foreach ($pages as $page)
		{
			if ($page->getCode() === $lastOpenedPageCode)
			{
				return $page;
			}
		}

		return null;
	}

	/**
	 * Returns pageCode of a page that was opened last by the current user in the custom section with $customSectionCode
	 * If there is no info about the last opened page, returns null
	 *
	 * @param string $customSectionCode
	 *
	 * @return string|null
	 */
	protected function getLastOpenedPageCode(string $customSectionCode): ?string
	{
		return $this->loadLastOpenedPages()[$customSectionCode] ?? null;
	}

	/**
	 * Set last opened page for a custom section for the current user
	 *
	 * @param string $customSectionCode
	 * @param string $pageCode
	 */
	protected function setLastOpenedPageCode(string $customSectionCode, string $pageCode): void
	{
		$currentOption = $this->loadLastOpenedPages();

		$currentOption[$customSectionCode] = $pageCode;

		$this->saveLastOpenedPages($currentOption);
	}

	/**
	 * Get array of last opened pages for each custom section for current user
	 *
	 * @return array [(string)sectionCode => (string)pageCode]
	 */
	protected function loadLastOpenedPages(): array
	{
		return \CUserOptions::GetOption(static::MODULE_ID, static::LAST_OPENED_OPTION_NAME, []);
	}

	/**
	 * Set last opened pages for each custom section for current user
	 *
	 * @param array $lastOpenedPages [(string)sectionCode => (string)pageCode]
	 *
	 * @return void
	 */
	protected function saveLastOpenedPages(array $lastOpenedPages): void
	{
		\CUserOptions::SetOption(static::MODULE_ID, static::LAST_OPENED_OPTION_NAME, $lastOpenedPages);
	}

	/**
	 * @param CustomSectionPage[] $pages
	 *
	 * @return CustomSectionPage
	 */
	protected function getPageWithMinSort(array $pages): CustomSectionPage
	{
		$pageWithMinSort = reset($pages);

		foreach ($pages as $page)
		{
			if ($page->getSort() < $pageWithMinSort->getSort())
			{
				$pageWithMinSort = $page;
			}
		}

		return $pageWithMinSort;
	}

	/**
	 * Resolves the custom section and its pages by url
	 *
	 * @param Uri $url
	 *
	 * @return ResolveResult
	 */
	public function resolveCustomSection(Uri $url): ResolveResult
	{
		$result = new ResolveResult();

		$pageCode = $this->prepareCustomSection($result, $url);
		if (!$result->isSuccess() || is_null($pageCode))
		{
			return $result;
		}

		$this->preparePages($result, $pageCode);
		if (!$result->isSuccess())
		{
			return $result;
		}

		$this->prepareComponent($result, $url);

		return $result;
	}

	protected function prepareCustomSection(ResolveResult $result, Uri $url): ?string
	{
		if (!preg_match(static::PAGE_URL_REGEX, $url->getPath(), $matches))
		{
			$result->addError(new Error(
				Loc::getMessage('INTRANET_CUSTOM_SECTION_MANAGER_INVALID_URL'),
				static::ERROR_CODE_INVALID_URL
			));
			return null;
		}

		$customSection = $this->getCustomSection((string)$matches['customSectionCode']);

		if (!$customSection)
		{
			$result->addError(new Error(
				Loc::getMessage('INTRANET_CUSTOM_SECTION_MANAGER_SECTION_NOT_FOUND'),
				static::ERROR_CODE_SECTION_NOT_FOUND
			));
			return null;
		}

		if (!$this->isCustomSectionAvailable($customSection))
		{
			$result->addError(new Error(
				Loc::getMessage('INTRANET_CUSTOM_SECTION_MANAGER_SECTION_NOT_AVAILABLE'),
				static::ERROR_CODE_SECTION_NOT_AVAILABLE
			));
			return null;
		}

		$result->setCustomSection($customSection);

		return (string)$matches['pageCode'];
	}

	protected function preparePages(ResolveResult $result, string $pageCode): void
	{
		$customSection = $result->getCustomSection();
		if (is_null($customSection))
		{
			throw new ObjectNotFoundException('Custom section is required for further operations');
		}

		$availablePages = $this->getAvailablePages($customSection);
		if (empty($availablePages))
		{
			throw new InvalidOperationException('Custom section has no available pages');
		}

		$this->fillCountersInfo($availablePages);

		$activePage = null;
		foreach ($availablePages as $page)
		{
			if ($page->getCode() === $pageCode)
			{
				$activePage = $page;
			}
		}

		if (is_null($activePage))
		{
			$activePage = $this->getLastOpenedPage($customSection->getCode(), $availablePages);
		}
		if (is_null($activePage))
		{
			$activePage = $this->getPageWithMinSort($availablePages);
		}

		$result->setAvailablePages($availablePages);
		$result->setActivePage($activePage);

		if ($activePage->getCode() !== $this->getLastOpenedPageCode($customSection->getCode()))
		{
			$this->setLastOpenedPageCode($customSection->getCode(), $activePage->getCode());
		}
	}

	/**
	 * @param CustomSectionPage[] $pages
	 */
	protected function fillCountersInfo(array $pages): void
	{
		foreach ($pages as $page)
		{
			$provider = $this->getProvider($page->getModuleId());
			if (!$provider)
			{
				continue;
			}

			$counterId = $provider->getCounterId($page->getSettings());
			if (!is_null($counterId))
			{
				$page->setCounterId($counterId);
			}

			$counterValue = $provider->getCounterValue($page->getSettings());
			if (!is_null($counterValue))
			{
				$page->setCounterValue($counterValue);
			}
		}
	}

	protected function prepareComponent(ResolveResult $result, Uri $url): void
	{
		$activePage = $result->getActivePage();
		if (is_null($activePage))
		{
			throw new ObjectNotFoundException('An active page is required for further operations');
		}

		$provider = $this->getProvider($activePage->getModuleId());
		$component = $provider ? $provider->resolveComponent($activePage->getSettings(), $url) : null;

		if (is_null($component))
		{
			$result->addError(new Error(
				Loc::getMessage('INTRANET_CUSTOM_SECTION_MANAGER_COMPONENT_NOT_FOUND'),
				static::ERROR_CODE_COMPONENT_NOT_FOUND
			));
			return;
		}

		$result->setComponentToInclude($component);
	}

	public static function isCustomSectionCounter(string $counterId, string $moduleId): bool {
		return preg_match(sprintf('#^%s%s#', $moduleId, self::COUNTER_INFIX), $counterId);
	}

	public static function getCustomSectionIdByCounterId(string $counterId, string $moduleId): int
	{
		return (int)preg_replace(sprintf('#^%s%s#', $moduleId, self::COUNTER_INFIX), '', $counterId);
	}

	/**
	 * Builds the counter code for the custom section menu. Like crm_custom_section_1 etc...
	 * @param string $moduleId
	 * @param int|null $customSectionId
	 * @return string
	 */
	public static function buildCustomSectionCounterId(string $moduleId, ?int $customSectionId): string
	{
		return $moduleId . self::COUNTER_INFIX . $customSectionId;
	}
}
