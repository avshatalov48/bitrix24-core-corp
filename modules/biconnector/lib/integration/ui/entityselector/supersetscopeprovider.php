<?php

namespace Bitrix\BIConnector\Integration\UI\EntitySelector;

use Bitrix\BIConnector\Superset\Scope\ScopeService;
use Bitrix\UI\EntitySelector\BaseProvider;
use Bitrix\UI\EntitySelector\Dialog;
use Bitrix\UI\EntitySelector\Item;
use Bitrix\UI\EntitySelector\Tab;

class SupersetScopeProvider extends BaseProvider
{
	public const ENTITY_ID = 'biconnector-superset-scope';

	public function __construct(array $options = [])
	{
		parent::__construct();

		$this->options = $options;
	}

	public function isAvailable(): bool
	{
		global $USER;

		return is_object($USER) && $USER->isAuthorized();
	}

	public function fillDialog(Dialog $dialog): void
	{
		$dialog->addItems($this->getItems([]));
		$dialog->addTab(new Tab([
			'id' => 'scopes',
			'title' => 'scopes',
		]));
	}

	public function getItems(array $ids): array
	{
		$result = [];
		$scopes = ScopeService::getInstance()->getScopeList();

		$automatedSolutionSubitems = [];
		$automatedSolutionItem = new Item([
			'id' => 'automated_solution',
			'entityId' => self::ENTITY_ID,
			'title' => ScopeService::getInstance()->getAutomationSolutionsTitle(),
			'tabs' => 'scopes',
			'description' => null,
			'searchable' => false,
		]);
		foreach ($scopes as $scope)
		{
			if (str_starts_with($scope, ScopeService::BIC_SCOPE_AUTOMATED_SOLUTION_PREFIX))
			{
				$automatedSolutionSubitems[] = $this->makeItem($scope);
			}
			else
			{
				$result[] = $this->makeItem($scope);
			}
		}

		if ($automatedSolutionSubitems)
		{
			foreach ($automatedSolutionSubitems as $subitem)
			{
				$automatedSolutionItem->addChild($subitem);
			}
			$result[] = $automatedSolutionItem;
		}

		return $result;
	}

	private function makeItem(string $scopeCode): Item
	{
		$itemParams = [
			'id' => $scopeCode,
			'entityId' => self::ENTITY_ID,
			'title' => ScopeService::getInstance()->getScopeName($scopeCode),
			'description' => null,
			'tabs' => 'scopes',
		];

		return new Item($itemParams);
	}
}
