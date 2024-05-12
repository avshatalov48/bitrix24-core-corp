<?php

namespace Bitrix\Crm\Integration\UI\EntitySelector;

use Bitrix\Crm\Integration\DocumentGeneratorManager;
use Bitrix\Crm\Service\Container;
use Bitrix\DocumentGenerator\DataProviderManager;
use Bitrix\DocumentGenerator\Document;
use Bitrix\DocumentGenerator\Template;
use Bitrix\UI\EntitySelector\BaseProvider;
use Bitrix\UI\EntitySelector\Dialog;
use Bitrix\UI\EntitySelector\Item;
use CCrmOwnerType;

final class PlaceholderProvider extends BaseProvider
{
	public const ENTITY_ID = 'placeholder';

	private const ITEMS_VIEW_TYPE_LIST = 'list';
	private const ITEMS_VIEW_TYPE_TREE = 'tree';
	private const ITEM_ID_WHITE_LIST = [
		'BANK_DETAIL',
		'COMPANY',
		'CONTACT',
		'PRODUCTS_PRODUCT',
		'MY_COMPANY',
		'ASSIGNED',
		'REQUISITE',
	];
	private const ITEM_ID_BLACK_LIST = [
		'OPENED',
		'COMMENTS',
		'REVENUE',
		'WEBFORM_ID',
		'ORIGINATOR_ID',
		'ORIGIN_ID',
		'ORIGIN_VERSION',
		'OBSERVER_IDS',
		'OBSERVERS',
		'OPPORTUNITY_ACCOUNT',
		'ORDER_BINDING',
		'SOURCE_ID',
		'SOURCE_BY',
		'SOURCE_DESCRIPTION',
		'UTM_SOURCE',
		'UTM_MEDIUM',
		'UTM_CAMPAIGN',
		'UTM_CONTENT',
		'UTM_TERM',
		'PRODUCT_ID',
		'QUOTE_ID',
		'BIRTHDAY_SORT',
		'IS_MANUAL_OPPORTUNITY',
		'IS_REPEATED_APPROACH',
		'IS_NEW',
		'MOVED_BY_ID',
		'HISTORY',
		'FULL_HISTORY',
		'EXPORT',
		'BINDINGS',
		'CURRENCY_ID',
		'XML_ID',
		'CLIENT_PHONE',
		'CLIENT_EMAIL',
		'REQUISITE.PRESET_ID',
		'REQUISITE.RQ_COMPANY_ID',
		'CRM_DEAL_RECURRING',
		'.ID', // TODO: temporary disable show ID field
	];

	private int $entityTypeId;
	private int $entityId;
	private ?int $categoryId;
	private string $viewType;

	public function __construct(array $options = [])
	{
		parent::__construct();

		$this->entityTypeId = (int)($options['entityTypeId'] ?? 0);
		$this->entityId = (int)($options['entityId'] ?? 0);
		$this->categoryId = isset($options['categoryId']) ? (int)$options['categoryId'] : null;
		$this->viewType = $options['viewType'] ?? self::ITEMS_VIEW_TYPE_TREE;
	}

	public function isAvailable(): bool
	{
		return Container::getInstance()
			->getUserPermissions()
			->checkReadPermissions($this->entityTypeId, $this->entityId, $this->categoryId)
		;
	}

	public function fillDialog(Dialog $dialog): void
	{
		$dialog->addRecentItems($this->makeItems());
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
		$providerClassName = DocumentGeneratorManager::getInstance()->getCrmOwnerTypeProvider($this->entityTypeId, false);
		if ($providerClassName === null)
		{
			return [];
		}

		$placeholders = DataProviderManager::getInstance()->getDefaultTemplateFields(
			$providerClassName,
			[],
			[],
			false
		);

		$placeholders = $this->filterItems($placeholders);
		if ($this->viewType === self::ITEMS_VIEW_TYPE_LIST)
		{
			return $this->makeItemsAsFlatList($placeholders);
		}

		if ($this->viewType === self::ITEMS_VIEW_TYPE_TREE)
		{
			return $this->makeItemsAsTree($placeholders);
		}

		return [];
	}

	private function makeItemsAsFlatList(array $input): array
	{
		if (empty($input))
		{
			return [];
		}

		$result = [];
		foreach ($input as $placeholder => $row)
		{
			$result[] = new Item([
				'id' => $placeholder,
				'entityId' => self::ENTITY_ID,
				'title' => $row['TITLE'] ?? $placeholder,
				'customData' => [
					'value' =>  $row['VALUE'] ?? $placeholder,
					'entityTypeName' => self::ENTITY_ID,
				],
			]);
		}

		return $result;
	}

	private function makeItemsAsTree(array $input): array
	{
		if (empty($input))
		{
			return [];
		}

		$rootBranches = $this->makeTreeRootItems($input);
		$groupedItems = $this->groupTreeItems($input);

		$result = [];
		foreach ($rootBranches as $id => $title)
		{
			$result[] = new Item([
				'id' => $id,
				'entityId' => self::ENTITY_ID,
				'title' => $title,
				'tabs' => 'recents',
				'searchable' => false,
				'children' => $this->makeItemChildren($groupedItems[$id] ?? $groupedItems, $id, $title),
			]);
		}

		return $result;
	}

	private function makeTreeRootItems(array $input): array
	{
		$result = [];

		foreach ($input as $row)
		{
			$valueArr = array_slice(explode('.', $row['VALUE'] ?? ''), 2, -1);
			$cnt = count($valueArr);
			if ($cnt === 0)
			{
				$result[CCrmOwnerType::ResolveName($this->entityTypeId)] = CCrmOwnerType::GetDescription($this->entityTypeId);
			}
			elseif (
				$cnt === 1
				|| (str_starts_with($valueArr[0], 'UF_CRM') && $valueArr[1] === 'ITEM')
			)
			{
				$result[$valueArr[0]] = $row['GROUP'][0];
			}
		}

		return $result;
	}

	private function groupTreeItems(array $input): array
	{
		foreach ($input as $placeholder => &$row)
		{
			$row['placeholder'] = $placeholder;
		}
		unset($row);

		$result = array_reduce($input, static function($carry, $item)
		{
			$uidParts = explode('.', $item['VALUE']);
			$lastUidPart = array_pop($uidParts);

			$currentArray = &$carry;
			foreach ($uidParts as $uidPart)
			{
				if (!isset($currentArray[$uidPart]))
				{
					$currentArray[$uidPart] = [];
				}
				$currentArray = &$currentArray[$uidPart];
			}

			$currentArray[$lastUidPart] = $item;

			return $carry;
		}, []);

		return $result[Document::THIS_PLACEHOLDER][Template::MAIN_PROVIDER_PLACEHOLDER] ?? [];
	}

	private function makeItemChildren(array $input, string $parentId, string $parentTitle): array
	{
		if (empty($input))
		{
			return [];
		}

		if (array_key_exists('ITEM', $input) && str_starts_with($parentId, 'UF_CRM'))
		{
			$input = $input['ITEM'];
		}

		$childrenItems = [];

		$leaves = array_filter(
			$input,
			static function(array $row): bool {
				return array_key_exists('TITLE', $row)
					&& array_key_exists('VALUE', $row)
					&& array_key_exists('GROUP', $row)
				;
			}
		);
		if (!empty($leaves))
		{
			foreach ($leaves as $placeholder => $leave)
			{
				if (str_ends_with($leave['VALUE'], $leave['TITLE']))
				{
					continue; // skip not localized items
				}

				$childrenItems[] = [
					'id' => $leave['placeholder'],
					'entityId' => $parentId,
					'supertitle' => $parentTitle,
					'title' => $leave['TITLE'] ,
					'customData' => [
						'value' => $leave['VALUE'] ?? $placeholder,
						'entityTypeName' => $parentId,
						'fieldName' => str_replace(
							Document::THIS_PLACEHOLDER . '.' . Template::MAIN_PROVIDER_PLACEHOLDER . $parentId,
							'',
							$leave['VALUE']
						),
					],
				];
			}
		}

		return $childrenItems;
	}

	private function filterItems(array $input): array
	{
		$duplicatingItems = [];

		// modify 'PRODUCTS.PRODUCT' key
		array_walk($input, static function (array &$row) use (&$duplicatingItems) {
			static $productProps = [];
			if (str_contains($row['VALUE'], 'PRODUCTS.PRODUCT'))
			{
				$row['VALUE'] = str_replace('PRODUCTS.PRODUCT', 'PRODUCTS_PRODUCT', $row['VALUE']);

				if (str_contains($row['VALUE'], 'PROPERTY_'))
				{
					if (in_array($row['TITLE'], $productProps, true))
					{
						$duplicatingItems[] = $row['VALUE'];
					}
					else
					{
						$productProps[] = $row['TITLE'];
					}

				}
			}
		});

		return array_filter(
			$input,
			static function(array $row) use ($duplicatingItems)
			{
				$value = $row['VALUE'] ?? '';
				$isImageType = isset($row['TYPE']) && $row['TYPE'] === 'IMAGE';
				$isInBlackList = count(
					array_filter(
						self::ITEM_ID_BLACK_LIST,
						static fn(string $word) => str_ends_with($value, $word)
					)
				) > 0;
				if (
					$isImageType
					|| $isInBlackList
					|| in_array($value, $duplicatingItems, true)
				)
				{
					return false;
				}

				$valueArr = array_slice(explode('.', $value), 2, -1);
				$val = implode('.', $valueArr);
				$isUfField = str_starts_with($val, 'UF_CRM')
					&& (
						str_contains($value, 'TITLE')
						|| str_contains($value, 'ITEM.VALUE')
					)
				;

				return
					empty($valueArr)											// entity placeholders
					|| in_array($val, self::ITEM_ID_WHITE_LIST)	// white list placeholders
					|| $isUfField
				;
			}
		);
	}
}
