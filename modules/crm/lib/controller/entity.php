<?php

namespace Bitrix\Crm\Controller;

use Bitrix\Crm\Order\Order;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\UI\EntitySelector;
use Bitrix\Main;
use Bitrix\Crm;
use Bitrix\UI\EntitySelector\Dialog;

class Entity extends Main\Engine\Controller
{
	public const ITEMS_LIMIT = 20;

	public function configureActions()
	{
		return array(
			'search' => array(
				'class' => Crm\Controller\Action\Entity\SearchAction::class,
				'+prefilters' => [new Main\Engine\ActionFilter\CloseSession()]
			),
			'mergeBatch' => array('class' => Crm\Controller\Action\Entity\MergeBatchAction::class),
			'prepareMerge' => array('class' => Crm\Controller\Action\Entity\PrepareMergeAction::class),
			'processMerge' => array('class' => Crm\Controller\Action\Entity\ProcessMergeAction::class),
			'processMergeByMap' => array('class' => Crm\Controller\Action\Entity\ProcessMergeByMapAction::class),
			'prepareDeletion' => array('class' => Crm\Controller\Action\Entity\PrepareDeletionAction::class),
			'cancelDeletion' => array('class' => Crm\Controller\Action\Entity\CancelDeletionAction::class),
			'processDeletion' => array('class' => Crm\Controller\Action\Entity\ProcessDeletionAction::class),
			'fetchPaymentDocuments' => array(
				'class' => Crm\Controller\Action\Entity\FetchPaymentDocumentsAction::class,
				'+prefilters' => [new Main\Engine\ActionFilter\Authentication()]
			),
			'renderImageInput' => [
				'class' =>  Crm\Controller\Action\Entity\RenderImageInputAction::class,
			],
			'canChangeCurrency' => [
				'class' => Crm\Controller\Action\Entity\CanChangeCurrencyAction::class,
			],
		);
	}

	//region LRU
	/**
	 * Add items to LRU items.
	 * @param string $category Category name (it's used for saving user option).
	 * @param string $code Code (it's used for saving user option).
	 * @param array $items Source items.
	 */
	public static function addLastRecentlyUsedItems($category, $code, array $items)
	{
		$values = [];
		foreach($items as $item)
		{
			$entityTypeId = isset($item['ENTITY_TYPE_ID']) ? (int)$item['ENTITY_TYPE_ID'] : 0;
			$entityId = isset($item['ENTITY_ID']) ? (int)$item['ENTITY_ID'] : 0;
			$categoryId = isset($item['CATEGORY_ID']) ? (int)$item['CATEGORY_ID'] : 0;

			if(\CCrmOwnerType::IsDefined($entityTypeId) && $entityId > 0)
			{
				$values[] = "{$entityTypeId}:{$entityId}:{$categoryId}";
			}
		}

		$lastRecentlyUsed = self::getRecentlyUsedItems($category, $code, ['RAW_FORMAT' => true]);
		$values = array_unique(
			array_merge(
				$lastRecentlyUsed,
				array_values($values)
			)
		);

		$qty = count($values);
		if($qty > static::ITEMS_LIMIT)
		{
			$values = array_slice($values, $qty - static::ITEMS_LIMIT);
		}

		$newValues = array_diff($values, $lastRecentlyUsed);
		if (!empty($newValues))
		{
			static::saveRecentItemsInSelector($newValues);
		}

		\CUserOptions::SetOption($category, $code, $values);
	}

	private static function saveRecentItemsInSelector(array $rawItems): void
	{
		$items = [];
		$entities = [];

		foreach ($rawItems as $rawItem)
		{
			[$entityTypeId, $entityId] = explode(':', $rawItem);
			$entityTypeId = (int)$entityTypeId;
			$entityId = (int)$entityId;

			if (\CCrmOwnerType::IsDefined($entityTypeId) && $entityId > 0)
			{
				$entityName = \CCrmOwnerType::ResolveName($entityTypeId);

				$entities[$entityName] = ['id' => $entityName];
				$items[] = [
					'entityId' => $entityName,
					'id' => $entityId,
				];
			}
		}

		if (!empty($entities) && !empty($items))
		{
			$dialog = new Dialog([
				'context' => EntitySelector::CONTEXT,
				'entities' => array_values($entities),
			]);
			$dialog->saveRecentItems($items);
		}
	}

	/**
	 * Get LRU items.
	 *
	 * @param string $category Category name (it's used for saving user option).
	 * @param string $code Code (it's used for saving user option).
	 * @param array|null $options Options.
	 *
	 * @return array|bool
	 */
	public static function getRecentlyUsedItems($category, $code, array $options = null)
	{
		if (!is_array($options))
		{
			$options = [];
		}

		$values = \CUserOptions::GetOption($category, $code, []);
		if (!is_array($values))
		{
			$values = [];
		}

		if (isset($options['RAW_FORMAT']) && $options['RAW_FORMAT'] === true)
		{
			return $values;
		}

		$actualEntityTypeId = isset($options['EXPAND_ENTITY_TYPE_ID'])
			? (int)$options['EXPAND_ENTITY_TYPE_ID']
			: 0;
		$actualCategoryId = isset($options['EXPAND_CATEGORY_ID'])
			? (int)$options['EXPAND_CATEGORY_ID']
			: 0;

		$items = [];
		foreach ($values as $value)
		{
			if (!is_string($value))
			{
				continue;
			}

			$parts = explode(':', $value);
			if (count($parts) <= 1)
			{
				continue;
			}

			$storedEntityTypeId = (int)$parts[0];
			$storedCategoryId = isset($parts[2]) ? (int)$parts[2] : 0;

			if (
				$actualEntityTypeId !== $storedEntityTypeId
				|| $actualCategoryId !== $storedCategoryId
			)
			{
				continue;
			}

			$items[] = [
				'ENTITY_TYPE_ID' => $storedEntityTypeId,
				'ENTITY_ID' => (int)$parts[1],
				'CATEGORY_ID' => $storedCategoryId,
			];
		}

		$qty = count($items);
		if ($qty < static::ITEMS_LIMIT && isset($options['EXPAND_ENTITY_TYPE_ID']))
		{

			self::expandItems(
				$items,
				(int)$options['EXPAND_ENTITY_TYPE_ID'],
				$actualCategoryId,
				static::ITEMS_LIMIT - $qty
			);
		}

		return $items;
	}

	/**
	 * Expand source items by recently created items of specified entity type.
	 *
	 * @param array $items Source items.
	 * @param int $entityTypeId Entity Type ID.
	 * @param int $categoryId Entity Type ID.
	 * @param int $limit Limit of new items.
	 */
	protected static function expandItems(
		array &$items,
		int $entityTypeId,
		int $categoryId,
		int $limit = self::ITEMS_LIMIT
	): void
	{
		$map = [];
		foreach ($items as $item)
		{
			$storedEntityTypeId = isset($item['ENTITY_TYPE_ID']) ? (int)$item['ENTITY_TYPE_ID'] : 0;
			$storedEntityId = isset($item['ENTITY_ID']) ? (int)$item['ENTITY_ID'] : 0;
			$storedCategoryId = isset($item['CATEGORY_ID']) ? (int)$item['CATEGORY_ID'] : 0;

			if (
				 $storedEntityId <= 0
				|| $entityTypeId !== $storedEntityTypeId
				|| $categoryId !== $storedCategoryId
				|| !\CCrmOwnerType::IsDefined($storedEntityTypeId)
			)
			{
				continue;
			}

			$map["{$storedEntityTypeId}:{$storedEntityId}:{$storedCategoryId}"] = $item;
		}

		$userPermissions = \CCrmPerms::GetCurrentUserPermissions();
		$entityIDs = null;
		if($entityTypeId === \CCrmOwnerType::Lead)
		{
			$entityIDs = \CCrmLead::GetTopIDs($limit, 'DESC', $userPermissions);
		}
		elseif($entityTypeId === \CCrmOwnerType::Contact)
		{
			$entityIDs = \CCrmContact::GetTopIDsInCategory($categoryId, $limit, 'DESC', $userPermissions);
		}
		elseif($entityTypeId === \CCrmOwnerType::Company)
		{
			$entityIDs = \CCrmCompany::GetTopIDsInCategory($categoryId, $limit, 'DESC', $userPermissions);
		}
		elseif($entityTypeId === \CCrmOwnerType::Deal)
		{
			$entityIDs = \CCrmDeal::GetTopIDs($limit, 'DESC', $userPermissions);
		}
		elseif($entityTypeId === \CCrmOwnerType::Order)
		{
			$orders = Order::getList([
				'select' => ['ID'],
				'limit' => $limit
			])->fetchCollection();

			$entityIDs = $orders->getIdList();
		}
		elseif(\CCrmOwnerType::isUseFactoryBasedApproach($entityTypeId))
		{
			$factory = Container::getInstance()->getFactory($entityTypeId);
			if ($factory)
			{
				$list = $factory->getItemsFilteredByPermissions([
					'order' => ['ID' => 'DESC'],
					'limit' => $limit
				]);

				foreach ($list as $item)
				{
					$entityIDs[] = $item->getId();
				}
			}
		}

		if(!is_array($entityIDs))
		{
			return;
		}

		foreach($entityIDs as $entityId)
		{
			$key = "{$entityTypeId}:{$entityId}:{$categoryId}";
			if(isset($map[$key]))
			{
				continue;
			}

			$map[$key] = [
				'ENTITY_TYPE_ID' => $entityTypeId,
				'ENTITY_ID' => (int)$entityId,
				'CATEGORY_ID' => $categoryId,
			];
		}

		$items = array_values($map);
	}
	//endregion
}
