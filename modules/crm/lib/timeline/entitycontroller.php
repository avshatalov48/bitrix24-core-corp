<?php
namespace Bitrix\Crm\Timeline;

use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Localization\Loc;

class EntityController extends Controller
{
	/**
	 * @return int
	 */
	public function getEntityTypeID()
	{
		return \CCrmOwnerType::Undefined;
	}

	/**
	 * Register create event in a timeline
	 *
	 * @param $entityID
	 * @param array $params
	 */
	public function onCreate($entityID, array $params)
	{
	}

	/**
	 * Register update event in a timeline
	 *
	 * @param $entityID
	 * @param array $params
	 */
	public function onModify($entityID, array $params)
	{
	}

	/**
	 * Register delete event in a timeline
	 *
	 * @param $entityID
	 * @param array $params
	 */
	public function onDelete($entityID, array $params)
	{
	}

	/**
	 * Register restore event in a timeline
	 *
	 * @param $entityID
	 * @param array $params
	 */
	public function onRestore($entityID, array $params)
	{
	}

	/**
	 * Register conversion event in a timeline of a source entity
	 * (This entity is a source)
	 *
	 * @param $ownerID - id of a source entity, which this controller is associated with
	 * @param array $params
	 */
	public function onConvert($ownerID, array $params)
	{
	}

	/**
	 * This method was create in order to reuse the code without copy-paste
	 *
	 * @param $ownerID
	 * @param array $params
	 *
	 * @throws ArgumentException
	 */
	protected function onConvertImplementation($ownerID, array $params): void
	{
		$ownerID = (int)$ownerID;
		if ($ownerID <= 0)
		{
			throw new ArgumentException('Owner ID must be greater than zero.', 'ownerID');
		}

		$entities = isset($params['ENTITIES']) && is_array($params['ENTITIES']) ? $params['ENTITIES'] : [];
		if (empty($entities))
		{
			return;
		}

		$settings = ['ENTITIES' => []];
		foreach ($entities as $entityTypeName => $entityID)
		{
			$entityTypeID = \CCrmOwnerType::ResolveID($entityTypeName);
			if ($entityTypeID === \CCrmOwnerType::Undefined)
			{
				continue;
			}

			$settings['ENTITIES'][] = ['ENTITY_TYPE_ID' => $entityTypeID, 'ENTITY_ID' => $entityID];
		}

		$authorID = isset($params['USER_ID'])
			? (int)$params['USER_ID']
			: \CCrmSecurityHelper::GetCurrentUserID();
		if ($authorID <= 0)
		{
			$authorID = static::getDefaultAuthorId();
		}

		$historyEntryID = ConversionEntry::create(
			[
				'ENTITY_TYPE_ID' => $this->getEntityTypeID(),
				'ENTITY_ID' => $ownerID,
				'AUTHOR_ID' => $authorID,
				'SETTINGS' => $settings,
			]
		);

		$this->sendPullEventOnAdd(
			new \Bitrix\Crm\ItemIdentifier($this->getEntityTypeID(), $ownerID),
			$historyEntryID
		);
	}

	/**
	 * Returns associative array of supported pull commands
	 *
	 * @return string[]
	 */
	public function getSupportedPullCommands()
	{
		return array();
	}
	public function prepareSearchContent(array $params)
	{
		return '';
	}

	public function prepareEntityPushTag($entityID)
	{
		return TimelineEntry::prepareEntityPushTag($this->getEntityTypeID(), $entityID);
	}

	/**
	 * Register existed entity in retrospect mode.
	 * @param int $ownerID Entity ID
	 * @return void
	 */
	public function register($ownerID, array $options = null)
	{
	}

	public static function loadCommunicationsAndMultifields(array &$items, \CCrmPerms $userPermissions = null, array $options = []): void
	{
		if (!isset($options['ENABLE_PERMISSION_CHECK']))
		{
			$options['ENABLE_PERMISSION_CHECK'] = true;
		}
		if (!isset($options['USER_PERMISSIONS']))
		{
			$options['USER_PERMISSIONS'] = $userPermissions;
		}
		$communications = \CCrmActivity::PrepareCommunicationInfos(
			array_keys($items),
			$options
		);
		foreach ($communications as $ID => $info)
		{
			$items[$ID]['COMMUNICATION'] = $info;
		}

		\Bitrix\Crm\Timeline\EntityController::prepareMultiFieldInfoBulk($items);
	}

	public static function prepareMultiFieldInfo(array &$item)
	{
		$items = array($item);
		self::prepareMultiFieldInfoBulk($items);
		$item = $items[0];
	}
	public static function prepareMultiFieldInfoBulk(array &$items)
	{
		$map = array();
		foreach($items as $ID => $item)
		{
			if(!isset($item['ASSOCIATED_ENTITY']) || !isset($item['ASSOCIATED_ENTITY']['COMMUNICATION']))
			{
				continue;
			}

			$communication = $item['ASSOCIATED_ENTITY']['COMMUNICATION'];
			$typeName = $communication['TYPE'] ? $communication['TYPE'] : '';
			$entityID = $communication['ENTITY_ID'] ? $communication['ENTITY_ID'] : 0;
			$entityTypeID = $communication['ENTITY_TYPE_ID'] ? $communication['ENTITY_TYPE_ID'] : \CCrmOwnerType::Undefined;

			if($typeName === '' || $entityID <= 0 || !\CCrmOwnerType::IsDefined($entityTypeID))
			{
				continue;
			}

			$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeID);
			if(!isset($map[$typeName]))
			{
				$map[$typeName] = array();
			}

			if(!isset($map[$typeName][$entityTypeName]))
			{
				$map[$typeName][$entityTypeName] = array();
			}

			if(!isset($map[$typeName][$entityTypeName][$entityID]))
			{
				$map[$typeName][$entityTypeName][$entityID] = array();
			}

			$map[$typeName][$entityTypeName][$entityID][] = $ID;
		}

		$multifields = array();
		foreach($map as $typeName => $item)
		{
			$entityTypeNames = array_keys($item);
			foreach($entityTypeNames as $entityTypeName)
			{
				if(!isset($multifields[$typeName]))
				{
					$multifields[$typeName] = array();
				}

				if(!isset($multifields[$typeName][$entityTypeName]))
				{
					$multifields[$typeName][$entityTypeName] = array();
				}

				$multifields[$typeName][$entityTypeName] = \CCrmFieldMulti::PrepareEntityDataBulk(
					$typeName,
					$entityTypeName,
					array_keys($item[$entityTypeName]),
					array('ENABLE_COMPLEX_NAME' => true, 'LIMIT' => 5)
				);
			}
		}

		foreach($multifields as $typeName => $item)
		{
			if(!isset($map[$typeName]))
			{
				continue;
			}

			$entityTypeNames = array_keys($item);
			foreach($entityTypeNames as $entityTypeName)
			{
				if(!isset($map[$typeName][$entityTypeName]))
				{
					continue;
				}

				$entityTypeData = $item[$entityTypeName];
				$entityIDs = array_keys($entityTypeData);
				foreach($entityIDs as $entityID)
				{
					if(!isset($map[$typeName][$entityTypeName][$entityID]))
					{
						continue;
					}

					$entityData = $entityTypeData[$entityID];
					foreach($map[$typeName][$entityTypeName][$entityID] as $ID)
					{
						$items[$ID][$typeName] = $entityData;
					}
				}
			}
		}
	}

	protected function getItemIdentifier(int $entityId): ItemIdentifier
	{
		return new ItemIdentifier($this->getEntityTypeID(), $entityId);
	}

	/**
	 * @deprecated
	 */
	final protected function createManualOpportunityModificationEntryIfNeeded(
		int $ownerId,
		int $authorId,
		array $currentFields,
		?array $previousFields = null
	): void
	{
		$prevIsManualOpportunity = 'N';
		if (is_array($previousFields))
		{
			$prevIsManualOpportunity = $previousFields['IS_MANUAL_OPPORTUNITY'] ?? 'N';
			if (is_bool($prevIsManualOpportunity))
			{
				$prevIsManualOpportunity = $prevIsManualOpportunity ? 'Y' : 'N';
			}
		}

		$curIsManualOpportunity = $currentFields['IS_MANUAL_OPPORTUNITY'] ?? $prevIsManualOpportunity;
		if (is_bool($curIsManualOpportunity))
		{
			$curIsManualOpportunity = $curIsManualOpportunity ? 'Y' : 'N';
		}

		if ($prevIsManualOpportunity !== $curIsManualOpportunity)
		{
			$this->createManualOpportunityModificationEntry(
				$ownerId,
				$authorId,
				$prevIsManualOpportunity,
				$curIsManualOpportunity,
			);
		}
	}

	/**
	 * @deprecated
	 */
	protected function createManualOpportunityModificationEntry($ownerId, $authorId, $prevValue, $curValue)
	{
		Container::getInstance()->getLocalization()->loadMessages();

		$names = [
			'N' => Loc::getMessage('CRM_COMMON_IS_MANUAL_OPPORTUNITY_FALSE'),
			'Y' => Loc::getMessage('CRM_COMMON_IS_MANUAL_OPPORTUNITY_TRUE'),
		];
		$historyEntryID = ModificationEntry::create(
			[
				'ENTITY_TYPE_ID' => $this->getEntityTypeID(),
				'ENTITY_ID' => $ownerId,
				'AUTHOR_ID' => $authorId,
				'SETTINGS' => [
					'FIELD' => 'IS_MANUAL_OPPORTUNITY',
					'START' => $prevValue,
					'FINISH' => $curValue,
					'START_NAME' => isset($names[$prevValue]) ? $names[$prevValue] : $prevValue,
					'FINISH_NAME' => isset($names[$curValue]) ? $names[$curValue] : $curValue
				]
			]
		);
		$this->sendPullEventOnAdd(new \Bitrix\Crm\ItemIdentifier($this->getEntityTypeID(), $ownerId), $historyEntryID);
	}
}
