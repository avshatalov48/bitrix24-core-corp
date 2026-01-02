<?php

namespace Bitrix\Crm\Service\Router;

use Bitrix\Crm\Feature;
use Bitrix\Crm\Service\Router\Contract;
use Bitrix\Crm\Service\Router\Dto\StaticPageResult;
use Bitrix\Crm\Service\Router\Dto\UserDefinedPageResult;
use Bitrix\Crm\Service\Router\PagePreparation\StaticPagePreparation;
use Bitrix\Crm\Service\Router\PagePreparation\UnsefModePreparation;
use Bitrix\Crm\Service\Router\PagePreparation\UserDefinedPagePreparation;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\IO\Path;
use Bitrix\Main\Routing\Router;
use Bitrix\Main\Routing\RoutingConfigurator;

final class PageResolver implements Contract\PageResolver
{
	private Router $router;
	private RoutingConfigurator $routingConfigurator;
	private array $overridableStaticPageComponents = [];
	private array $overriddenStaticPages = [];

	private readonly string $root;
	private readonly string $siteFolder;

	public function __construct(
		private readonly \Bitrix\Crm\Service\Router $routerService,
		private readonly Contract\PageFactory $pageFactory,
	)
	{
		$this->router = new Router();
		$this->routingConfigurator = new RoutingConfigurator();
		$this->routingConfigurator->setRouter($this->router);

		$this->root = $this->routerService->getRoot();
		$this->siteFolder = $this->routerService->getSiteFolder();

		$this->configureRoutes();
	}

	public function router(): Router
	{
		return $this->router;
	}

	public function resolve(HttpRequest $request): ?Contract\Page
	{
		return $this->doResolve($request)?->prepare();
	}

	protected function configureRoutes(): void
	{
		$this->overridableStaticPageComponents = $this->getOverridableStaticPageComponents();

		$this->configureUserDefinedPagesRoutes();
		$this->configureStaticPagesRoutes();

		$this->router->releaseRoutes();
	}

	protected function getOverridableStaticPageComponents(): array
	{
		return array_keys($this->pageFactory->getStaticPagesComponentUrlMap());
	}

	protected function configureUserDefinedPagesRoutes(): void
	{
		$this->overriddenStaticPages = [];

		foreach ($this->pageFactory->getUserDefinedPages() as $page)
		{
			$route = $page->route();

			if ($this->isRouteWillOverrideStaticPage($route))
			{
				$this->addRouteThatOverrideStaticPage($route);

				continue;
			}

			$url = Path::combine($this->siteFolder, $this->root, $route->baseUrl()) . '/';
			$this->routingConfigurator
				->any($url, static fn () => new UserDefinedPageResult($page))
			;
		}
	}

	protected function configureStaticPagesRoutes(): void
	{
		foreach ($this->pageFactory->getStaticPages() as $page)
		{
			if (!$page::isActive())
			{
				continue;
			}

			foreach ($page::routes() as $route)
			{
				$overriddenBaseUrl = $this->getStaticPageOverriddenBaseUrl($route);
				if ($overriddenBaseUrl !== null)
				{
					$url = Path::combine($this->siteFolder, $this->root, $overriddenBaseUrl) . '/';
					$this->routingConfigurator
						->any($url, static fn () => new StaticPageResult($page, null))
					;

					continue;
				}

				foreach ($page::scopes() as $scope)
				{
					foreach ($scope->roots() as $root)
					{
						$url = Path::combine($this->siteFolder, $root, $route->baseUrl()) . '/';

						$routeConfiguration = $this->routingConfigurator
							->any($url, static fn () => new StaticPageResult($page, $scope))
						;

						$route->applyConfiguration($routeConfiguration);
					}
				}
			}
		}
	}

	protected function doResolve(HttpRequest $request): ?Contract\PagePreparation
	{
		if (!$this->routerService->isSefMode())
		{
			return new UnsefModePreparation($this->routerService, $request);
		}

		$route = $this->router->match($request);
		$controller = $route?->getController();
		if ($route === null || !is_callable($controller))
		{
			return null;
		}

		$result = $controller();

		return match (true) {
			$result instanceof UserDefinedPageResult => new UserDefinedPagePreparation($result, $route),
			$result instanceof StaticPageResult => new StaticPagePreparation($result, $route, $request),
			default => null,
		};
	}

	private function isRouteWillOverrideStaticPage(Route $route): bool
	{
		return in_array($route->getRelatedComponent(), $this->overridableStaticPageComponents, true);
	}

	private function addRouteThatOverrideStaticPage(Route $route): void
	{
		$this->overriddenStaticPages[$route->getRelatedComponent()] = $route->baseUrl();
	}

	private function getStaticPageOverriddenBaseUrl(Route $route): ?string
	{
		if (!$route->hasRelatedComponent())
		{
			return null;
		}

		return $this->overriddenStaticPages[$route->getRelatedComponent()] ?? null;
	}
}
