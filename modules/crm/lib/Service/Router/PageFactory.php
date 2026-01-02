<?php

namespace Bitrix\Crm\Service\Router;

use Bitrix\Crm\Service\Router;

final class PageFactory implements Router\Contract\PageFactory
{
	private ?array $staticPageComponentUrlMap = null;

	public function __construct(
		private readonly Router $routerService,
	)
	{
	}

	/**
	 * @return array<Router\Contract\Page\StaticPage|string>
	 */
	public function getStaticPages(): array
	{
		return [
			Page\ItemDetails\QuoteDetailsPage::class,
			Page\ItemDetails\SmartInvoiceDetailsPage::class,
			Page\ItemDetails\SmartDocumentDetailsPage::class,
			Page\ItemDetails\DynamicDetailsPage::class,
			Page\AutomatedSolution\DetailsPage::class,
			Page\AutomatedSolution\ListPage::class,
			Page\AutomatedSolution\PermissionsPage::class,
			Page\Copilot\CallAssessment\ListPage::class,
			Page\Copilot\CallAssessment\DetailsPage::class,
			Page\RepeatSale\Segment\ListPage::class,
			Page\RepeatSale\Segment\DetailsPage::class,
			Page\RepeatSale\Segment\ResultPage::class,
			Page\Item\ListPage::class,
			Page\Item\RecurListPage::class,
			Page\Item\KanbanPage::class,
			Page\Item\DeadlinesPage::class,
			Page\Item\AutomationPage::class,
			Page\Type\ListPage::class,
			Page\Type\DetailsPage::class,
			Page\Type\MergeResolverPage::class,
			Page\PermissionsPage::class,
			Page\SalesTunnelsPage::class,
		];
	}

	/**
	 * @return Contract\Page\UserDefinedPage[]
	 */
	public function getUserDefinedPages(): array
	{
		$pages = [];

		$templateUrls = $this->routerService->getCustomUrlTemplates();
		foreach ($templateUrls as $componentName => $url)
		{
			$pages[] = new Page\UserDefinedPage($componentName, $url);
		}

		return $pages;
	}

	public function getStaticPagesComponentUrlMap(): array
	{
		if ($this->staticPageComponentUrlMap !== null)
		{
			return $this->staticPageComponentUrlMap;
		}

		$this->staticPageComponentUrlMap = [];

		$routes = array_map(static fn (string $page) => $page::routes(), $this->getStaticPages());
		$routes = array_merge(...$routes);

		/** @var Route[] $routes */
		foreach ($routes as $route)
		{
			if ($route->hasRelatedComponent())
			{
				$this->staticPageComponentUrlMap[$route->getRelatedComponent()] = $route->oldBaseUrl();
			}
		}

		return $this->staticPageComponentUrlMap;
	}
}
