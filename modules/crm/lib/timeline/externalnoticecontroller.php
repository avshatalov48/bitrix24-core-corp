<?php
namespace Bitrix\Crm\Timeline;

use Bitrix\Crm\Order\OrderShipmentStatus;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * @deprecated No longer in use
 */
class ExternalNoticeController extends EntityController
{
	//region EntityController

	/**
	 * @param $ownerID
	 * @param $ownerTypeID
	 * @param array $params
	 *
	 * @throws Main\ArgumentException
	 */
	public function onReceive($ownerID, $ownerTypeID, array $params)
	{
		if(!is_int($ownerID))
		{
			$ownerID = (int)$ownerID;
		}
		if($ownerID <= 0)
		{
			throw new Main\ArgumentException('Owner ID must be greater than zero.', 'ownerID');
		}

		if(!\CCrmOwnerType::IsDefined((int)$ownerTypeID))
		{
			throw new Main\ArgumentException('Owner Type ID is not defined.', 'ownerTypeID');
		}

		$messageType = $params['TYPE_CATEGORY_ID'] ?? TimelineType::MODIFICATION;
		$messageType = (int)$messageType;

		if($messageType <= 0)
		{
			throw new Main\ArgumentException('Type category ID must be greater than zero.', '$messageType');
		}

		$fields = isset($params['ENTITY_FIELDS']) && is_array($params['ENTITY_FIELDS']) ? $params['ENTITY_FIELDS'] : [];

		$settings = [
			'ENTITY_TYPE_ID' => (int)$params['ENTITY_TYPE_ID'],
			'LEGEND' => $params['LEGEND'] ?? '',
		];

		if ($messageType === TimelineType::MODIFICATION)
		{
			$settings['FIELD_NAME'] = isset($params['FIELD_NAME']) ? (string)$params['FIELD_NAME'] : '';
			$settings['CURRENT_VALUE'] = isset($params['CURRENT_VALUE']) ? (string)$params['CURRENT_VALUE'] : '';
			$settings['PREVIOUS_VALUE'] = isset($params['PREVIOUS_VALUE']) ? (string)$params['PREVIOUS_VALUE'] : '';
			$settings['MODIFIED_FIELD'] = $settings['FIELD_NAME'];
		}

		if ($fields['DATE_INSERT'] instanceof Main\Type\Date)
		{
			$settings['DATE_INSERT_TIMESTAMP'] = $fields['DATE_INSERT']->getTimestamp();
		}

		$authorID = self::resolveCreatorID($params);

		$tag = TimelineEntry::prepareEntityPushTag($ownerTypeID, $ownerID);
		$historyEntryID = ExternalNoticeEntry::create(
			array(
				'ENTITY_TYPE_ID' => $ownerTypeID,
				'ENTITY_ID' => $ownerID,
				'TYPE_CATEGORY_ID' => $messageType,
				'AUTHOR_ID' => $authorID,
				'SETTINGS' => $settings,
			)
		);

		if($historyEntryID > 0)
		{
			self::pushHistoryEntry($historyEntryID, $tag,'timeline_rest_notification_add');
		}
	}

	protected static function resolveCreatorID(array $fields)
	{
		$authorID = 0;

		if ($authorID <= 0 && isset($fields['CREATED_BY']))
		{
			$authorID = (int)$fields['CREATED_BY'];
		}

		if ($authorID <= 0 && isset($fields['RESPONSIBLE_ID']))
		{
			$authorID = (int)$fields['RESPONSIBLE_ID'];
		}

		if($authorID <= 0)
		{
			//Set portal admin as default creator
			$authorID = 1;
		}

		return $authorID;
	}

	public function prepareHistoryDataModel(array $data, array $options = null)
	{
		$typeControllerID = isset($data['TYPE_CATEGORY_ID']) ? (int)$data['TYPE_CATEGORY_ID'] : TimelineType::UNDEFINED;
		$settings = is_array($data['SETTINGS']) ? $data['SETTINGS'] : [];
		$entityTypeID = (int)($settings['ENTITY_TYPE_ID'] ?? 0);
		if($typeControllerID === TimelineType::MODIFICATION)
		{
			$fieldName = $settings['FIELD_NAME'] ?? '';
			$data['CHANGED_FIELD_NAME'] = $fieldName;
			$data['ASSOCIATED_ENTITY'] = ['TYPE_ID' => $entityTypeID];
			$entityName = \CCrmOwnerType::ResolveName($entityTypeID);
			if ($fieldName === 'STATUS_ID' && ($entityTypeID === \CCrmOwnerType::Order || $entityTypeID === \CCrmOwnerType::OrderShipment))
			{
				$data['TITLE'] =  Loc::getMessage("CRM_{$entityName}_MODIFICATION_STATUS_ID");
			}
			else
			{
				$data['TITLE'] = \CCrmOwnerType::GetDescription($entityTypeID);
			}

			if($fieldName === 'STATUS_ID')
			{
				$data['START_NAME'] = $settings['PREVIOUS_VALUE'] ?? '';
				$data['FINISH_NAME'] = $settings['CURRENT_VALUE'] ?? '';
			}
			elseif (
				($fieldName === 'PAID' && $entityTypeID === \CCrmOwnerType::OrderPayment)
				|| ($fieldName === 'DEDUCTED' && $entityTypeID === \CCrmOwnerType::OrderShipment)
				|| ($fieldName === 'ALLOW_DELIVERY' && $entityTypeID === \CCrmOwnerType::OrderShipment)
				|| ($fieldName === 'CANCELED' && $entityTypeID === \CCrmOwnerType::Order)
			)
			{
				$fieldCode = 'ORDER_'.$fieldName;
				$data['FIELDS'] = [
					$fieldCode => (($settings['CURRENT_VALUE'] === 'Y') ? 'Y' : 'N')
				];
				$data['ASSOCIATED_ENTITY']['TITLE'] = $settings['LEGEND'];
				$data['ASSOCIATED_ENTITY']['LEGEND'] = '';
				$data['CHANGED_ENTITY'] = $entityName;
			}

			unset($data['SETTINGS']);
		}
		return parent::prepareHistoryDataModel($data, $options);
	}
}
