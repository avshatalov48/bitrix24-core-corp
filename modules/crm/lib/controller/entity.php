<?php
namespace Bitrix\Crm\Controller;
use Bitrix\Main;
use Bitrix\Crm;

class Entity extends Main\Engine\Controller
{
	public function configureActions()
	{
		return array(
			'search' => array(
				'class' => Crm\Controller\Action\Entity\SearchAction::class,
				'+prefilters' => [new Main\Engine\ActionFilter\CloseSession()]
			),
			'prepareMerge' => array('class' => Crm\Controller\Action\Entity\PrepareMergeAction::class),
			'processMerge' => array('class' => Crm\Controller\Action\Entity\ProcessMergeAction::class),
			'prepareDeletion' => array('class' => Crm\Controller\Action\Entity\PrepareDeletionAction::class),
			'cancelDeletion' => array('class' => Crm\Controller\Action\Entity\CancelDeletionAction::class),
			'processDeletion' => array('class' => Crm\Controller\Action\Entity\ProcessDeletionAction::class)
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
		$values = array();
		foreach($items as $item)
		{
			$entityTypeID = isset($item['ENTITY_TYPE_ID']) ? (int)$item['ENTITY_TYPE_ID'] : 0;
			$entityID = isset($item['ENTITY_ID']) ? (int)$item['ENTITY_ID'] : 0;

			if(\CCrmOwnerType::IsDefined($entityTypeID) && $entityID > 0)
			{
				$values[] = "{$entityTypeID}:{$entityID}";
			}
		}

		$values = array_unique(
			array_merge(
				self::getRecentlyUsedItems($category, $code, array('RAW_FORMAT' => true)),
				array_values($values)
			)
		);

		$qty = count($values);
		if($qty > 20)
		{
			$values = array_slice($values, $qty - 20);
		}

		\CUserOptions::SetOption($category, $code, $values);
	}

	/**
	 * Get LRU items.
	 * @param string $category Category name (it's used for saving user option).
	 * @param string $code Code (it's used for saving user option).
	 * @param array|null $options Options.
	 * @return array|bool
	 */
	public static function getRecentlyUsedItems($category, $code, array $options = null)
	{
		if(!is_array($options))
		{
			$options = array();
		}

		$values = \CUserOptions::GetOption($category, $code, array());
		if(!is_array($values))
		{
			$values = array();
		}

		if(isset($options['RAW_FORMAT']) && $options['RAW_FORMAT'] === true)
		{
			return $values;
		}

		$items = array();
		foreach($values as $value)
		{
			if(!is_string($value))
			{
				continue;
			}

			$parts = explode(':', $value);
			if(count($parts) > 1)
			{
				$items[] = array('ENTITY_TYPE_ID' => (int)$parts[0], 'ENTITY_ID' => (int)$parts[1]);
			}
		}

		$qty = count($items);
		if($qty < 20 && isset($options['EXPAND_ENTITY_TYPE_ID']))
		{
			self::expandItems($items, $options['EXPAND_ENTITY_TYPE_ID'], 20 - $qty);
		}

		return $items;
	}
	/**
	 * Expand source items by recently created items of specified entity type.
	 * @param array $items Source items.
	 * @param int $entityTypeID Entity Type ID.
	 * @param int $limit Limit of new items.
	 */
	protected static function expandItems(array &$items, $entityTypeID, $limit = 20)
	{
		$map = array();
		foreach($items as $item)
		{
			$entityTypeID = isset($item['ENTITY_TYPE_ID']) ? (int)$item['ENTITY_TYPE_ID'] : 0;
			$entityID = isset($item['ENTITY_ID']) ? (int)$item['ENTITY_ID'] : 0;

			if(!\CCrmOwnerType::IsDefined($entityTypeID) || $entityID <= 0)
			{
				continue;
			}

			$map["{$entityTypeID}:{$entityID}"] = $item;
		}

		$userPermissions = \CCrmPerms::GetCurrentUserPermissions();
		$entityIDs = null;
		if($entityTypeID === \CCrmOwnerType::Lead)
		{
			$entityIDs = \CCrmLead::GetTopIDs($limit, 'DESC', $userPermissions);
		}
		elseif($entityTypeID === \CCrmOwnerType::Contact)
		{
			$entityIDs = \CCrmContact::GetTopIDs($limit, 'DESC', $userPermissions);
		}
		elseif($entityTypeID === \CCrmOwnerType::Company)
		{
			$entityIDs = \CCrmCompany::GetTopIDs($limit, 'DESC', $userPermissions);
		}
		elseif($entityTypeID === \CCrmOwnerType::Deal)
		{
			$entityIDs = \CCrmDeal::GetTopIDs($limit, 'DESC', $userPermissions);
		}

		if(!is_array($entityIDs))
		{
			return;
		}

		foreach($entityIDs as $entityID)
		{
			$key = "{$entityTypeID}:{$entityID}";
			if(isset($map[$key]))
			{
				continue;
			}

			$map[$key] = array('ENTITY_TYPE_ID' => $entityTypeID, 'ENTITY_ID' => (int)$entityID);
		}

		$items = array_values($map);
	}
	//endregion
}