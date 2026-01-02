<?php

namespace Bitrix\Crm\Service\Router\PagePreparation;

use Bitrix\Crm\Service\Router\Contract\Page;
use Bitrix\Crm\Service\Router\Contract\PagePreparation;
use Bitrix\Crm\Service\Router\Dto\StaticPageResult;
use Bitrix\Crm\Service\Router\Enum\Scope;
use Bitrix\Main\Engine\AutoWire\Binder;
use Bitrix\Main\Engine\AutoWire\Parameter;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\Routing\Route;
use ReflectionClass;
use ReflectionException;
use Throwable;

final class StaticPagePreparation implements PagePreparation
{
	public function __construct(
		private readonly StaticPageResult $result,
		private readonly Route $route,
		private readonly HttpRequest $request,
	)
	{
	}

	/**
	 * @throws ReflectionException
	 */
	public function prepare(): ?Page
	{
		if (!$this->isCorrectPageClass())
		{
			return null;
		}

		$class = new ReflectionClass($this->result->pageClass);

		$constructor = $class->getConstructor();
		if ($constructor !== null && !$constructor->isPublic())
		{
			return null;
		}

		$args = [];
		if ($constructor !== null)
		{
			$binder = new Binder($class->getName(), $constructor->getName());

			array_map([$binder, 'appendAutoWiredParameter'], $this->getBindParameters());

			$routeValues = $this->route->getParametersValues()->getValues();
			$binder->setSourcesParametersToMap([ $routeValues ]);

			try
			{
				$args = $binder->getArgs();
			}
			catch (Throwable)
			{
				return null;
			}
		}

		try {
			return $class->newInstanceArgs($args);
		}
		catch (Throwable)
		{
			return null;
		}
	}

	private function getBindParameters(): array
	{
		return [
			new Parameter(Route::class, fn () => $this->route),
			new Parameter(HttpRequest::class, fn () => $this->request),
			new Parameter(Scope::class, fn () => $this->result->scope),
		];
	}

	private function isCorrectPageClass(): bool
	{
		return class_exists($this->result->pageClass)
			&& is_subclass_of($this->result->pageClass, Page::class)
		;
	}
}
