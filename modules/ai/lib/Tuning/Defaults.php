<?php

namespace Bitrix\AI\Tuning;

use Bitrix\AI\Engine;
use Bitrix\AI\Facade\Bitrix24;
use Bitrix\AI\Facade\User;
use Bitrix\AI\Quality;
use Bitrix\Main\Localization\Loc;

/**
 * Predefined data for settings
 */
class Defaults
{
	/**
	 * Default groups for all modules
	 */
	public const GROUP_TEXT = 'text';
	public const GROUP_IMAGE = 'image';
	public const GROUP_DEFAULT = 'default';
	public const DEFAULT_GROUPS = [
		self::GROUP_TEXT,
		self::GROUP_IMAGE,
		self::GROUP_DEFAULT,
	];

	public const GROUP_SORT_MIN = 100;
	public const GROUP_SORT_MAX = 10000;

	/**
	 * Groups for AI module
	 */
	private const GROUP_COPILOT = 'copilot';
	private const INTERNAL_GROUPS = [
		self::GROUP_COPILOT,
	];

	private const INTERNAL_LANG_PREFIX = 'AI_SETTINGS_INTERNAL_';

	/**
	 * Create groups for all modules
	 * @return array
	 */
	public static function getDefaultGroups(): array
	{
		$groups = [];
		foreach (self::getDefaultGroupsData() as $code => $groupData)
		{
			$group = Group::create($code, $groupData);
			if ($group)
			{
				$groups[$code] = Group::create($code, $groupData);
			}
		}

		return $groups;
	}

	protected static function getDefaultGroupsData(): array
	{
		$icons = [
			self::GROUP_IMAGE => [
				'code' => '--magic-image',
			],
			self::GROUP_TEXT => [
				'code' => '--ai',
			],
		];

		$data = [];
		$sort = 1;
		foreach (self::DEFAULT_GROUPS as $group)
		{
			$langCode = 'AI_SETTINGS_DEFAULT_GROUP_' . mb_strtoupper($group);
			$data[$group] = [
				'title' => Loc::getMessage($langCode),
				'description' => Loc::getMessage($langCode . '_DESCRIPTION') ?? null,
				'icon' => $icons[$group] ?? null,
				'sort' => $sort++,
				'helpdesk' => self::getDefaultGroupsHelpdesk($group),
			];
		}

		return $data;
	}

	protected static function getDefaultGroupsHelpdesk(string $group): ?string
	{
		// now - one article for all languages
		$helpdesk = [
			self::GROUP_IMAGE => [
				'en' => '19092894',
			],
			self::GROUP_TEXT => [
				'en' => '19092894',
			],
		];

		if (isset($helpdesk[$group]))
		{
			$zone = Bitrix24::getPortalZone();
			$defaultZone = 'en';

			return $helpdesk[$group][$zone] ?? $helpdesk[$group][$defaultZone];
		}

		return null;
	}

	/**
	 * Create groups for AI module
	 * @return Group[]
	 */
	public static function getInternalGroups(): array
	{
		$groups = [];
		foreach (self::getInternalGroupsData() as $code => $groupData)
		{
			$group = Group::create($code, $groupData);
			if ($group)
			{
				$groups[$code] = Group::create($code, $groupData);
			}
		}

		return $groups;
	}

	protected static function getInternalGroupsData(): array
	{
		$data = [];
		$sort = self::GROUP_SORT_MAX + self::GROUP_SORT_MIN;
		foreach (self::INTERNAL_GROUPS as $group)
		{
			$langCode = 'AI_SETTINGS_INTERNAL_GROUP_' . mb_strtoupper($group);
			// todo: add helps
			// todo: add  helps
			$data[$group] = [
				'title' => Loc::getMessage($langCode),
				'description' => Loc::getMessage($langCode . '_DESCRIPTION'),
				'sort' => $sort++,
				// 'helpdesk' => 123,
			];
		}

		return $data;
	}

	/**
	 * Return internal Item object, separated by groups
	 *
	 * @return Array<string, Array<string,Item>> - [groupCode => [itemCode => Item]]
	 */
	public static function getInternalItems(): array
	{
		$items = [];
		foreach (Engine::getCategories() as $category)
		{
			$params = self::getProviderSelectFieldParams($category);
			if (empty($params))
			{
				continue;
			}

			$langCode = self::INTERNAL_LANG_PREFIX . mb_strtoupper($category) . '_';
			$params = array_merge($params, [
				'title' => Loc::getMessage($langCode . 'TITLE'),
				'header' => Loc::getMessage($langCode . 'HEADER') ?? null,
				'onSave' => [
					'callback' => function () use ($category) {
						User::clearLastUsedEngineCodeForAll($category);
					},
					'switcher' => Loc::getMessage('AI_SETTINGS_INTERNAL_ON_SAVE_TITLE'),
				],
			]);

			$code = Engine::getConfigCode($category);
			$item = Item::create($code, $params);

			if ($item)
			{
				$groupConst = 'self::GROUP_' . mb_strtoupper($category);
				if (defined($groupConst) && !empty($params['options']))
				{
					$item->setValue($params['default'] ?? null);
					$items[constant($groupConst)][$code] = $item;
				}
			}
		}

		return $items;
	}

	/**
	 * Format params for provider selector field.
	 * @param string $category
	 * @param Quality|null $quality
	 * @return array
	 */
	public static function getProviderSelectFieldParams(string $category, ?Quality $quality = null): array
	{
		if (!in_array($category, Engine::CATEGORIES))
		{
			return [];
		}

		$engines = Engine::getListAvailable($category, $quality);
		$options = [];
		$recommended = [];
		$default = null;
		foreach ($engines as $engine)
		{
			$default = $default ?: $engine->getCode();
			$options[$engine->getCode()] = $engine->getName();
			if ($engine->isPreferredForQuality($quality))
			{
				$recommended[] = $engine->getCode();
			}
		}

		return 	[
			'type' => Type::LIST,
			'options' => $options,
			'default' => $default,
			'recommended' => $recommended,
			'additional' => [
				'isProviderSelector' => true,
			],
		];
	}

	/**
	 * Set group sorting to correctly value
	 * @param Group $group
	 * @return void
	 */
	public static function normalizeGroupSort(Group $group): void
	{
		if (!$group->getSort())
		{
			$group->setSort(Defaults::GROUP_SORT_MAX);
		}
		elseif (
			!Defaults::isGroupDefault($group)
			&& $group->getSort() < Defaults::GROUP_SORT_MIN
		)
		{
			$group->setSort(Defaults::GROUP_SORT_MIN);
		}
		elseif(
			!Defaults::isGroupInternal($group)
			&& $group->getSort() > Defaults::GROUP_SORT_MAX
		)
		{
			$group->setSort(Defaults::GROUP_SORT_MAX);
		}
	}

	/**
	 * Check is group are internal
	 * @param Group $group
	 * @return bool
	 */
	public static function isGroupDefault(Group $group): bool
	{
		return in_array($group->getCode(), self::DEFAULT_GROUPS);
	}

	/**
	 * Check is group are internal
	 * @param Group $group
	 * @return bool
	 */
	public static function isGroupInternal(Group $group): bool
	{
		return in_array($group->getCode(), self::INTERNAL_GROUPS);
	}

	/**
	 * * Check is item are internal
	 * @param Item $item
	 * @return bool
	 */
	public static function isItemInternal(Item $item): bool
	{
		$internalCodes = [];
		foreach (Engine::getCategories() as $category)
		{
			$internalCodes[] = Engine::getConfigCode($category);
		}

		return in_array($item->getCode(), $internalCodes);
	}
}
