<?php

namespace Bitrix\Crm\Integration\Zoom;

use Bitrix\Crm\Activity\Provider\Base;
use Bitrix\Crm\Activity\Provider\Zoom;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;

class Activity
{
	private $entityId;
	private $entityType;

	public function __construct($entityId, $entityType)
	{
		$this->entityId = $entityId;
		$this->entityType = $entityType;
	}

	/**
	 * Adds Zoom activity.
	 *
	 * @param array $conferenceData
	 *	$conferenceData = [
	 *		'start_time' => (string) Conference start date and time in DATE_ATOM format.
	 *		'duration' => (string) Conference duration in minutes.
	 *		'bitrix_internal_id' => (string) Internal (Bitrix) conference id.
	 *		'id' => (string) External (Zoom) conference id.
	 *	]
	 * @return Result
	 */
	public function addZoom(array $conferenceData): Result
	{
		$result = new Result();

		if (empty($conferenceData))
		{
			return $result->addError(new Error('No conference data'));
		}

		$startTimeStamp = \DateTime::createFromFormat(DATE_ATOM, $conferenceData['start_time'])->getTimestamp();
		$startDateTime = DateTime::createFromTimestamp($startTimeStamp);

		$duration = "T". $conferenceData['duration'] ."M";
		$endDateTime = DateTime::createFromTimestamp($startTimeStamp)->add($duration);

		$fields = [
			'CREATE_TIMESTAMP' => (new DateTime())->getTimestamp(),
			'START_TIME' => $startDateTime,
			'END_TIME' => $endDateTime,
			'ASSOCIATED_ENTITY_ID' => $conferenceData['bitrix_internal_id'],
			'DEADLINE' => $startDateTime,
			'OWNER_ENTITY_ID' => $this->entityId,
			'OWNER_ENTITY_TYPE' => $this->entityType,
			'PROVIDER_TYPE_ID' => Zoom::TYPE_ZOOM_CONF_START,
			'CONFERENCE_EXTERNAL_ID' => $conferenceData['id'],
			'DIRECTION' => \CCrmActivityDirection::Outgoing,
			'COMPLETED' => 'N',
			'SUBJECT' => Loc::getMessage("CRM_ZOOM_ACTIVITY_CONFERENCE_TITLE"),
		];

		$result = $this->saveActivity($fields, \CCrmSecurityHelper::GetCurrentUserID(), SITE_ID);

		return $result;
	}

	public function saveActivity($fields, $userId, $siteId): Result
	{
		$result = new Result();

		$bindings = array();
		if ($fields['OWNER_ENTITY_ID'] > 0 && $fields['OWNER_ENTITY_TYPE'] != '')
		{
			$ownerTypeId = \CCrmOwnerType::ResolveID($fields['OWNER_ENTITY_TYPE']);
			$ownerId = (int)$fields['OWNER_ENTITY_ID'];
		}

		$bindings[] = array(
			'OWNER_TYPE_ID' => $ownerTypeId,
			'OWNER_ID' => $ownerId
		);

		$activityFields = array(
			'TYPE_ID' => \CCrmActivityType::Provider,
			'PROVIDER_ID' => Zoom::PROVIDER_ID,
			'ASSOCIATED_ENTITY_ID' => $fields['ASSOCIATED_ENTITY_ID'],
			'PROVIDER_TYPE_ID' => $fields['PROVIDER_TYPE_ID'],
			'DIRECTION' => $fields['DIRECTION'],
			'START_TIME' => $fields['START_TIME'],
			'END_TIME' => $fields['END_TIME'],
			'DURATION' => $fields['DURATION'],
			'COMPLETED' => $fields['COMPLETED'],
			'PRIORITY' => \CCrmActivityPriority::Medium,
			'SUBJECT' => $fields['SUBJECT'],
			'LOCATION' => '',
			'NOTIFY_TYPE' => \CCrmActivityNotifyType::None,
			'BINDINGS' => $bindings,
			'SETTINGS' => array(),
			'AUTHOR_ID' => $userId,
			'RESPONSIBLE_ID' => $userId,
		);

		$activityId = \CCrmActivity::Add($activityFields, true, true, array('REGISTER_SONET_EVENT' => true));

		if ($activityId == 0)
		{
			return $result->addError(new Main\Error(Loc::getMessage('CRM_ACTIVITY_CREATE_ERROR') . ': ' . \CCrmActivity::GetLastErrorMessage()));
		}

		$communicationsType = \Bitrix\Crm\Activity\Provider\Zoom::getCommunicationType();
		$communications = $this->getCrmEntityCommunications($ownerTypeId, $ownerId, $communicationsType);
		$communications = array_slice($communications, 0, 1);
		\CCrmActivity::SaveCommunications($activityId, $communications, $activityFields, true, false);

		$result->setData(array(
			'ACTIVITY_ID' => $activityId
		));

		return $result;
	}

	private function getCrmEntityCommunications($entityTypeId, $entityId, $communicationType): array
	{
		$communications = array();

		$result = static function (&$data)
		{
			$communications = array();
			foreach ($data as $item)
			{
				$id = 'CRM'.$item['ENTITY_TYPE'].$item['ENTITY_ID'].':'.hash('crc32b', $item['TYPE'].':'.$item['VALUE']);
				if (!array_key_exists($id, $communications))
				{
					$communications[$id] = $item;
				}
			}

			return array_values($communications);
		};

		if (in_array($entityTypeId, array(\CCrmOwnerType::Lead, \CCrmOwnerType::Contact, \CCrmOwnerType::Company)))
		{
			$communications = array_merge(
				$communications,
				$this->getCommunicationsFromFM($entityTypeId, $entityId, $communicationType)
			);

			if (\CCrmOwnerType::Lead == $entityTypeId)
			{
				$entity = \CCrmLead::getById($entityId);
				if (empty($entity))
				{
					return $result($communications);
				}

				$entityCompanyId = isset($entity['COMPANY_ID']) ? (int)$entity['COMPANY_ID'] : 0;
				if ($entityCompanyId > 0)
				{
					$communications = array_merge(
						$communications,
						$this->getCrmEntityCommunications(\CCrmOwnerType::Company, $entityCompanyId, $communicationType)
					);
				}

				$entityContactsIds = \Bitrix\Crm\Binding\LeadContactTable::getLeadContactIds($entityId);
				if (!empty($entityContactsIds))
				{
					$communications = array_merge(
						$communications,
						$this->getCrmEntityCommunications(\CCrmOwnerType::Contact, $entityContactsIds, $communicationType)
					);
				}
			}
			else if (\CCrmOwnerType::Company == $entityTypeId)
			{
				$communications = array_merge(
					$communications,
					\CCrmActivity::getCompanyCommunications($entityId, $communicationType)
				);
			}
		}
		else if (\CCrmOwnerType::Deal == $entityTypeId || \CCrmOwnerType::DealRecurring == $entityTypeId)
		{
			$entity = \CCrmDeal::getById($entityId);
			if (empty($entity))
			{
				return $result($communications);
			}

			$entityCompanyId = isset($entity['COMPANY_ID']) ? intval($entity['COMPANY_ID']) : 0;
			if ($entityCompanyId > 0)
			{
				$communications = array_merge(
					$communications,
					$this->getCrmEntityCommunications(\CCrmOwnerType::Company, $entityCompanyId, $communicationType)
				);
			}

			$entityContactsIds = \Bitrix\Crm\Binding\DealContactTable::getDealContactIds($entityId);
			if (!empty($entityContactsIds))
			{
				$communications = array_merge(
					$communications,
					$this->getCrmEntityCommunications(\CCrmOwnerType::Contact, $entityContactsIds, $communicationType)
				);
			}

			$communications = array_merge(
				$communications,
				\CCrmActivity::getCommunicationsByOwner(\CCrmOwnerType::DealName, $entityId, $communicationType)
			);
		}
		else if (\CCrmOwnerType::Invoice == $entityTypeId)
		{
			$entity = \CCrmInvoice::getById($entityId);
			if (empty($entity))
				return $result($communications);

			$entityContactId = isset($entity['UF_CONTACT_ID']) ? (int) $entity['UF_CONTACT_ID'] : 0;
			if ($entityContactId > 0)
			{
				$communications = array_merge(
					$communications,
					$this->getCrmEntityCommunications(\CCrmOwnerType::Contact, $entityContactId, $communicationType)
				);
			}

			$entityCompanyId = isset($entity['UF_COMPANY_ID']) ? (int) $entity['UF_COMPANY_ID'] : 0;
			if ($entityCompanyId > 0)
			{
				$communications = array_merge(
					$communications,
					$this->getCrmEntityCommunications(\CCrmOwnerType::Company, $entityCompanyId, $communicationType)
				);
			}

			$entityDealId = isset($entity['UF_DEAL_ID']) ? (int) $entity['UF_DEAL_ID'] : 0;
			if ($entityDealId > 0)
			{
				$communications = array_merge(
					$communications,
					$this->getCrmEntityCommunications(\CCrmOwnerType::Deal, $entityDealId, $communicationType)
				);
			}
		}
		else if (\CCrmOwnerType::Order == $entityTypeId)
		{
			$entity = \Bitrix\Crm\Order\Order::load((int)$entityId);
			if (empty($entity))
			{
				return $result($communications);
			}

			$ccCollection = $entity->getContactCompanyCollection();
			if ($primaryCompany = $ccCollection->getPrimaryCompany())
			{
				$communications = array_merge(
					$communications,
					$this->getCrmEntityCommunications(\CCrmOwnerType::Company, $primaryCompany->getField('ENTITY_ID'), $communicationType)
				);
			}

			if ($primaryContact = $ccCollection->getPrimaryContact())
			{
				$communications = array_merge(
					$communications,
					$this->getCrmEntityCommunications(\CCrmOwnerType::Contact, $primaryContact->getField('ENTITY_ID'), $communicationType)
				);
			}
		}
		else if (\CCrmOwnerType::Quote == $entityTypeId)
		{
			$entity = \CCrmQuote::getById($entityId);
			if (empty($entity))
			{
				return $result($communications);
			}

			$entityContactId = isset($entity['CONTACT_ID']) ? (int) $entity['CONTACT_ID'] : 0;
			if ($entityContactId > 0)
			{
				$communications = array_merge(
					$communications,
					$this->getCrmEntityCommunications(\CCrmOwnerType::Contact, $entityContactId, $communicationType)
				);
			}

			$entityCompanyId = isset($entity['COMPANY_ID']) ? (int) $entity['COMPANY_ID'] : 0;
			if ($entityCompanyId > 0)
			{
				$communications = array_merge(
					$communications,
					$this->getCrmEntityCommunications(\CCrmOwnerType::Company, $entityCompanyId, $communicationType)
				);
			}

			$entityDealId = isset($entity['DEAL_ID']) ? (int) $entity['DEAL_ID'] : 0;
			if ($entityDealId > 0)
			{
				$communications = array_merge(
					$communications,
					$this->getCrmEntityCommunications(\CCrmOwnerType::Deal, $entityDealId, $communicationType)
				);
			}

			$entityLeadId = isset($entity['LEAD_ID']) ? (int) $entity['LEAD_ID'] : 0;
			if ($entityLeadId > 0)
			{
				$communications = array_merge(
					$communications,
					$this->getCrmEntityCommunications(\CCrmOwnerType::Lead, $entityLeadId, $communicationType)
				);
			}
		}

		return $result($communications);
	}

	private function getCommunicationsFromFM($entityTypeId, $entityId, $communicationType): array
	{
		$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeId);
		$communications = array();

		if ($communicationType !== '')
		{
			$iterator = \CCrmFieldMulti::GetList(
				array('ID' => 'asc'),
				array('ENTITY_ID' => $entityTypeName,
					'ELEMENT_ID' => $entityId,
					'TYPE_ID' => $communicationType
				)
			);

			while ($row = $iterator->fetch())
			{
				if (empty($row['VALUE']))
				{
					continue;
				}

				$communications[] = array(
					'ENTITY_ID' => $row['ELEMENT_ID'],
					'ENTITY_TYPE_ID' => $entityTypeId,
					'ENTITY_TYPE' => $entityTypeName,
					'TYPE' => $communicationType,
					'VALUE' => $row['VALUE'],
					'VALUE_TYPE' => $row['VALUE_TYPE']
				);
			}

			if (is_array($entityId))
			{
				usort(
					$communications,
					static function ($a, $b) use (&$entityId)
					{
						return array_search($a['ENTITY_ID'], $entityId) - array_search($b['ENTITY_ID'], $entityId);
					}
				);
			}
		}
		else
		{
			foreach ((array) $entityId as $item)
			{
				$communications[] = array(
					'ENTITY_ID' => $item,
					'ENTITY_TYPE_ID' => $entityTypeId,
					'ENTITY_TYPE' => $entityTypeName,
					'TYPE' => $communicationType
				);
			}
		}

		return $communications;
	}
}