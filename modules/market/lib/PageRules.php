<?php

namespace Bitrix\Market;

use CBitrixComponent;
use CComponentEngine;
use MarketList;
use MarketMain;

class PageRules
{
	public const MAIN_PAGE = '/market/';

	public const DEFAULT_URL_TEMPLATES = [
		'collection' => 'collection/#collection#/',
		'collectionPage' => 'collection/page/#collection#/',
		'category' => 'category/#category#/',
		'detail' => 'detail/#appCode#/',
		'install' => 'install/#appCode#/',
		'install_version' => 'install/#appCode#/#version#/',
		'install_hash' => 'install/#appCode#/#version#/#installHash#/#checkHash#/',
		'favorites' => 'favorites/',
		'installed' => 'installed/',
		'reviews' => 'reviews/',
		'booklet' => 'booklet/#booklet_code#/',
	];

	private const URL_VARIABLES = [
		'collection' => 'collection',
		'category' => 'category',
	];

	private const COMPONENT_VARIABLES = [
		'collection' => 'COLLECTION',
		'category' => 'CATEGORY',
	];

	private const COMPONENT_PARAMS = [
		'collection' => [
			'IS_COLLECTION' => 'Y',
		],
		'category' => [
			'IS_CATEGORY' => 'Y',
		],
		'favorites' => [
			'IS_FAVORITES' => 'Y'
		],
		'installed' => [
			'IS_INSTALLED' => 'Y'
		],
	];

	private const COMPONENTS_CLASS = [
		'collection' => MarketList::class,
		'category' => MarketList::class,
		'favorites' => MarketList::class,
		'installed' => MarketList::class,
		'main' => MarketMain::class,
	];

	private const COMPONENTS_CLASS_NAME = [
		'collection' => 'bitrix:market.list',
		'category' => 'bitrix:market.list',
		'favorites' => 'bitrix:market.list',
		'installed' => 'bitrix:market.list',
		'main' => 'bitrix:market.main',
	];

	private string $componentPage;

	private array $componentParams = [];

	private ?Loadable $classInstance = null;

	public function __construct($requestUrl, array $queryParams)
	{
		$urlVariables = [];
		$this->componentPage = CComponentEngine::ParseComponentPath(
			PageRules::MAIN_PAGE,
			PageRules::DEFAULT_URL_TEMPLATES,
			$urlVariables,
			$requestUrl
		);

		if (empty($this->componentPage)) {
			$this->componentPage = 'main';
		}

		if (isset(PageRules::COMPONENT_PARAMS[$this->componentPage])) {
			$this->componentParams = PageRules::COMPONENT_PARAMS[$this->componentPage];
		}

		if (isset(PageRules::URL_VARIABLES[$this->componentPage])) {
			$urlVariable = PageRules::URL_VARIABLES[$this->componentPage];
			if (isset($urlVariables[$urlVariable]) && !empty($urlVariables[$urlVariable])) {
				$this->componentParams = array_merge($this->componentParams, [
					PageRules::COMPONENT_VARIABLES[$this->componentPage] => $urlVariables[$urlVariable],
				]);
			}
		}

		$this->componentParams['REQUEST'] = $queryParams;

		if (empty(PageRules::COMPONENTS_CLASS[$this->componentPage])) {
			return;
		}

		CBitrixComponent::includeComponentClass(PageRules::COMPONENTS_CLASS_NAME[$this->componentPage]);

		$class = PageRules::COMPONENTS_CLASS[$this->componentPage];

		$this->classInstance = new $class();
	}

	public function getComponentData(): array
	{
		if (!$this->classInstance instanceof Loadable) {
			return [];
		}

		return $this->classInstance->getAjaxData($this->componentParams);
	}
}