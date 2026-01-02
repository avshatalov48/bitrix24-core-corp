<?php

namespace Bitrix\Crm\Service\Router\Page\Item;

use Bitrix\Crm\Service\Router\AbstractPage;
use Bitrix\Crm\Service\Router\Component\Component;
use Bitrix\Crm\Service\Router\Contract;
use Bitrix\Crm\Service\Router\Enum\Scope;
use Bitrix\Crm\Service\Router\PageValidator\EntityTypeAvailabilityValidator;
use Bitrix\Main\HttpRequest;

abstract class AbstractListPage extends AbstractPage
{
	public function __construct(
		protected int $entityTypeId,
		HttpRequest $request,
		?Scope $currentScope,
		protected ?int $categoryId = null,
	)
	{
		parent::__construct($request, $currentScope);
	}

	final public function component(): Contract\Component
	{
		return new Component(
			name: $this->getComponentName(),
			parameters: [
				'entityTypeId' => $this->entityTypeId,
				'categoryId' => $this->categoryId,
			],
		);
	}

	public static function scopes(): array
	{
		return [
			Scope::Crm,
			Scope::AutomatedSolution,
		];
	}

	protected function getPageValidators(): array
	{
		return [
			...parent::getPageValidators(),
			new EntityTypeAvailabilityValidator($this->entityTypeId),
		];
	}

	abstract protected function getComponentName(): string;
}
