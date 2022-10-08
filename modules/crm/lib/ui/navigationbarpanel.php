<?php

namespace Bitrix\Crm\UI;

use Bitrix\Crm\Automation\Helper;
use Bitrix\Crm\Integration\Calendar;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Router;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Uri;
use CCrmOwnerType;

class NavigationBarPanel
{
	public const ID_AUTOMATION = 'automation';
	public const ID_KANBAN = 'kanban';
	public const ID_LIST = 'list';
	public const ID_CALENDAR = 'calendar';

	private const LANG_MAP = [
		self::ID_KANBAN => 'CRM_COMMON_KANBAN',
		self::ID_LIST => 'CRM_COMMON_LIST',
		self::ID_CALENDAR => 'CRM_COMMON_CALENDAR',
	];
	private const ID_BINDING_CATEGORY = 'crm.navigation';
	private const ID_BINDING_NAME = 'index';

	/**
	 * @var Router
	 */
	private Router $router;

	/**
	 * Entity type ID.
	 *
	 * @var int
	 */
	private int $entityTypeId;

	/**
	 * Entity category ID.
	 *
	 * @var null|int
	 */
	private ?int $categoryId;

	/**
	 * Navigation bar items.
	 *
	 * @var array
	 */
	private array $items = [];

	/**
	 * Navigation bar binding.
	 *
	 * @var array
	 */
	private array $binding = [];

	private array $supportedEntities = [
		CCrmOwnerType::Lead,
		CCrmOwnerType::Deal,
		CCrmOwnerType::Contact,
		CCrmOwnerType::Company,
		CCrmOwnerType::Invoice,
		CCrmOwnerType::Quote,
		CCrmOwnerType::Order,
	];

	public function __construct(int $entityTypeId, int $categoryId = null)
	{
		$this->validate($entityTypeId);

		$this->entityTypeId = $entityTypeId;
		$this->categoryId = $categoryId === -1 ? null : $categoryId;
		$this->router = Container::getInstance()->getRouter();
	}

	public function setItems(array $ids, string $activeId = ''): self
	{
		if (empty($activeId) || !in_array($activeId, [self::ID_KANBAN, self::ID_LIST, self::ID_CALENDAR], true))
		{
			$activeId = self::ID_LIST;
		}

		foreach ($ids as $id)
		{
			if (!in_array($id, [self::ID_AUTOMATION, self::ID_KANBAN, self::ID_LIST, self::ID_CALENDAR], true))
			{
				continue;
			}

			if ($id === self::ID_CALENDAR && !Calendar::isResourceBookingEnabled())
			{
				continue;
			}

			if ($id === self::ID_AUTOMATION)
			{
				$automationButton = Helper::getNavigationBarItems($this->entityTypeId, $this->categoryId);
				if (!empty($automationButton))
				{
					$this->items[] = $automationButton[0];
				}
			}
			else
			{
				$this->items[] = [
					'id' => $id,
					'name' => Loc::getMessage(self::LANG_MAP[$id]),
					'active' => $id === $activeId,
					'url' => $this->getUrl($id),
				];
			}
		}

		return $this;
	}

	public function setBinding(string $key): self
	{
		$this->binding = [
			'category' => self::ID_BINDING_CATEGORY,
			'name' => self::ID_BINDING_NAME,
			'key' => mb_strtolower($key),
		];

		return $this;
	}

	public function get(): array
	{
		return [
			'ITEMS' => $this->items,
			'BINDING' => $this->binding,
		];
	}

	private function validate(int $entityTypeId): void
	{
		if (!in_array($entityTypeId, $this->supportedEntities, true))
		{
			throw new \InvalidArgumentException(
				sprintf(
					'Specified entity type "%s" is not supported',
					CCrmOwnerType::ResolveName($entityTypeId)
				)
			);
		}
	}

	private function getUrl(string $id): ?Uri
	{
		switch($id)
		{
			case self::ID_KANBAN:
				return $this->router->getKanbanUrl($this->entityTypeId, $this->categoryId);
			case self::ID_LIST:
				return $this->router->getItemListUrl($this->entityTypeId, $this->categoryId);
			case self::ID_CALENDAR:
				return $this->router->getCalendarUrl($this->entityTypeId, $this->categoryId);
		}

		return null;
	}
}
