<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

\Bitrix\Main\Loader::includeModule('intranet');

use Bitrix\Intranet\CustomSection\DataStructures\CustomSectionPage;
use Bitrix\Main\Error;
use Bitrix\Crm\Integration\IntranetManager;
use Bitrix\Main\Web\Uri;

class IntranetCustomSectionComponent extends \CBitrixComponent implements \Bitrix\Main\Errorable
{
	/** @var \Bitrix\Main\ErrorCollection */
	protected $errorCollection;
	/** @var \Bitrix\Intranet\CustomSection\Manager */
	protected $manager;

	public function __construct($component = null)
	{
		parent::__construct($component);

		$this->errorCollection = new \Bitrix\Main\ErrorCollection();
		$this->manager = \Bitrix\Main\DI\ServiceLocator::getInstance()->get('intranet.customSection.manager');
	}

	public function executeComponent()
	{
		$currentUrl = new Uri($this->request->getRequestUri());
		$resolveResult = $this->manager->resolveCustomSection($currentUrl);
		if (!$resolveResult->isSuccess())
		{
			$this->addErrors($resolveResult->getErrors());
			$this->includeComponentTemplate();

			return;
		}

		$this->getApplication()->SetTitle(htmlspecialcharsbx($resolveResult->getCustomSection()->getTitle()));

		$this->arResult['interfaceButtonsParams'] = [
			'ID' => $this->generateInterfaceId($resolveResult->getCustomSection()->getCode()),
			'ITEMS' => $this->prepareInterfaceItems(
				$resolveResult->getCustomSection()->getCode(),
				$resolveResult->getActivePage(),
				$resolveResult->getAvailablePages()
			),
		];
		$this->arResult['componentToInclude'] = $resolveResult->getComponentToInclude();

		$this->includeComponentTemplate();
	}

	protected function getApplication(): CMain
	{
		global $APPLICATION;

		return $APPLICATION;
	}

	protected function generateInterfaceId(string $customSectionCode): string
	{
		return $this->manager->getCustomSectionMenuId($customSectionCode);
	}

	/**
	 * @param string $sectionCode
	 * @param CustomSectionPage $activePage
	 * @param CustomSectionPage[] $pages
	 *
	 * @return array[]
	 */
	protected function prepareInterfaceItems(string $sectionCode, CustomSectionPage $activePage, array $pages): array
	{
		$sortedPages = $this->sortPages($pages);

		$items = [];
		foreach ($sortedPages as $page)
		{
			$items[] = $this->prepareInterfaceItem($sectionCode, $page, ($page->getCode() === $activePage->getCode()));
		}

		if (
			Bitrix\Main\Loader::includeModule('biconnector')
			&& class_exists('\Bitrix\BIConnector\Superset\Scope\ScopeService')
		)
		{
			/** @see \Bitrix\BIConnector\Superset\Scope\MenuItem\MenuItemCreatorAutomatedSolution::getMenuItemData */
			$menuItem = \Bitrix\BIConnector\Superset\Scope\ScopeService::getInstance()->prepareAutomatedSolutionMenuItem($sectionCode);
			if ($menuItem)
			{
				$items[] = $menuItem;
			}
		}

		return $items;
	}

	/**
	 * @param CustomSectionPage[] $pages
	 *
	 * @return CustomSectionPage[]
	 */
	protected function sortPages(array $pages): array
	{
		usort(
			$pages,
			static function (CustomSectionPage $pageOne, CustomSectionPage $pageTwo): int {
				return ($pageOne->getSort() - $pageTwo->getSort());
			}
		);

		return $pages;
	}

	protected function prepareInterfaceItem(string $sectionCode, CustomSectionPage $page, bool $isActive): array
	{
		return [
			'ID' => $page->getCode(),
			'TEXT' => $page->getTitle(),
			'URL' => $this->manager->getUrlForPage($sectionCode, $page->getCode()),
			'IS_ACTIVE' => $isActive,
			'COUNTER_ID' => $page->getCounterId(),
			'COUNTER' => $page->getCounterValue(),
			'IS_DISABLED' => $page->getDisabledInCtrlPanel(),
		];
	}

	/**
	 * Returns true if this component is opened in an IFRAME
	 *
	 * @return bool
	 */
	public function isIframe(): bool
	{
		return ($this->request->get('IFRAME') === 'Y');
	}

	// region Error handling
	/**
	 * @inheritDoc
	 */
	public function getErrors(): array
	{
		return $this->errorCollection->toArray();
	}

	/**
	 * @inheritDoc
	 */
	public function getErrorByCode($code): ?Error
	{
		return $this->errorCollection->getErrorByCode($code);
	}

	/**
	 * Add an error
	 *
	 * @param Error $error
	 */
	public function addError(Error $error): void
	{
		$this->errorCollection[] = $error;
	}

	/**
	 * Add multiple errors
	 *
	 * @param Error[] $errors
	 */
	public function addErrors(array $errors): void
	{
		$this->errorCollection->add($errors);
	}

	/**
	 * Returns true if at least one error has occurred
	 *
	 * @return bool
	 */
	public function hasErrors(): bool
	{
		return !empty($this->getErrors());
	}
	//endregion
}
