<?php

namespace Bitrix\Crm\Service\Router\Page\ItemDetails;

use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Router\AbstractPage;
use Bitrix\Crm\Service\Router\Component\Component;
use Bitrix\Crm\Service\Router\Component\SidePanelWrapper;
use Bitrix\Crm\Service\Router\Contract;
use Bitrix\Crm\Service\Router\DetailsFrameScriptTarget;
use Bitrix\Crm\Service\Router\Enum\Scope;
use Bitrix\Crm\Service\Router\PageValidator\EntityTypeAvailabilityValidator;
use Bitrix\Crm\Service\Router\Route;
use Bitrix\Main\HttpRequest;

class DynamicDetailsPage extends AbstractPage
{
	protected const COMPONENT_NAME = 'bitrix:crm.item.details';
	protected const ENTITY_TYPE_ID = null;
	protected const ENTITY_TYPE_ID_PLACEHOLDER = '{ENTITY_TYPE_ID}';
	protected const DEFAULT_ENTITY_ID = 0;

	protected int $entityTypeId;
	protected int $entityId;

	public function __construct(
		HttpRequest $request,
		?Scope $currentScope,
		$ENTITY_ID,
		$ENTITY_TYPE_ID = null,
	)
	{
		parent::__construct($request, $currentScope);

		$entityTypeId = static::ENTITY_TYPE_ID ?? $ENTITY_TYPE_ID;
		$this->entityTypeId = \CCrmOwnerType::IsDefined($entityTypeId) ? $entityTypeId : \CCrmOwnerType::Undefined;
		$this->entityId = is_numeric($ENTITY_ID) ? (int)$ENTITY_ID : static::DEFAULT_ENTITY_ID;
	}

	public function component(): Contract\Component
	{
		return new Component(
			name: $this->getRenderComponentName(),
			parameters: [
				'ENTITY_TYPE_ID' => $this->entityTypeId,
				'ENTITY_ID' => $this->entityId,
			],
		);
	}

	public static function routes(): array
	{
		$entityTypeId = static::ENTITY_TYPE_ID ?? static::ENTITY_TYPE_ID_PLACEHOLDER;

		return [
			(new Route("type/{$entityTypeId}/details/{ENTITY_ID}/"))
				->setRelatedComponent(static::COMPONENT_NAME)
			,
		];
	}

	public static function scopes(): array
	{
		return [
			Scope::Crm,
			Scope::AutomatedSolution,
		];
	}

	protected function getDetailsFrameWrapperTarget(): ?DetailsFrameScriptTarget
	{
		return new DetailsFrameScriptTarget($this->entityTypeId, $this->entityId);
	}

	protected function getPageValidators(): array
	{
		return [
			...parent::getPageValidators(),
			new EntityTypeAvailabilityValidator($this->entityTypeId),
		];
	}

	protected function getRenderComponentName(): string
	{
		// find the required component at the Page: crm.item.details, crm.document.details have the same template url
		return Container::getInstance()->getRouter()->getItemDetailComponentName($this->entityTypeId)
			?? static::COMPONENT_NAME;
	}

	protected function configureSidePanel(SidePanelWrapper $sidePanel): void
	{
		$sidePanel->isUsePadding = false;
	}
}
