<?php

namespace Bitrix\Crm\Integration\UI\EntitySelector;

use Bitrix\Crm\Integration\AI\AIManager;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Loader;
use Bitrix\UI\EntitySelector\BaseProvider;
use Bitrix\UI\EntitySelector\Dialog;
use Bitrix\UI\EntitySelector\Item;

final class CopilotLanguageProvider extends BaseProvider
{
	public const ENTITY_ID = 'copilot_language';

	private bool $isAiEnabled;
	private int $entityTypeId;
	private ?int $categoryId;

	public function __construct(array $options = [])
	{
		parent::__construct();

		$this->isAiEnabled = Loader::includeModule('ai');

		$this->entityTypeId = (int)($options['entityTypeId'] ?? 0);
		$this->categoryId = isset($options['categoryId']) ? (int)$options['categoryId'] : null;
	}

	public function isAvailable(): bool
	{
		return $this->isAiEnabled
			&& Container::getInstance()
				->getUserPermissions()
				->checkUpdatePermissions($this->entityTypeId, 0, $this->categoryId)
		;
	}

	public function fillDialog(Dialog $dialog): void
	{
		$items = $this->makeItems();

		array_walk(
			$items,
			static function (Item $item, int $index) use ($dialog) {
				if (empty($dialog->getContext()))
				{
					$item->setSort($index);
				}
				$dialog->addRecentItem($item);
			}
		);
	}

	public function getItems(array $ids): array
	{
		return $this->makeItems();
	}

	public function getSelectedItems(array $ids): array
	{
		return $this->makeItems();
	}

	private function makeItems(): array
	{
		if (!$this->isAvailable())
		{
			return [];
		}

		$items = [];
		foreach (AIManager::getAvailableLanguageList() as $code =>$name)
		{
			$items[] = new Item([
				'id' => $code,
				'entityId' => self::ENTITY_ID,
				'title' => $name,
			]);
		}

		return $items;
	}
}
