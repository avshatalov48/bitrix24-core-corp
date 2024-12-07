<?php

namespace Bitrix\Crm\CallList;

use Bitrix\Crm\CallList\Internals\CallListCreatedTable;
use Bitrix\Crm\CallList\Internals\CallListItemTable;
use Bitrix\Crm\CallList\Internals\CallListTable;
use Bitrix\Crm\CompanyAddress;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;

Loc::loadMessages(__FILE__);

final class CallList
{
	const ITEMS_LIMIT = 500;
	const STATUS_IN_WORK = 'IN_WORK';

	protected $new = true;
	protected $id = 0;
	protected $dateCreate;
	protected $createdBy;
	protected $filtered;
	protected $gridId;
	protected $filterParameters;
	protected $webformId;
	protected $entityTypeId = \CCrmOwnerType::Undefined;
	protected static $statusList = null;

	/** @var Item[] */
	protected $items = array();
	protected $itemsLoaded = false;

	protected function __construct()
	{

	}

	public static function createEmpty()
	{
		return new static;
	}

	/**
	 * @param int $id
	 * @param bool $loadItems
	 * @param array $options
	 * @return static
	 * @throws ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function createWithId(int $id, bool $loadItems = false, array $options = [])
	{
		$row = CallListTable::getById($id)->fetch();
		if (!$row)
		{
			throw new \Bitrix\Main\SystemException('Call list is not found', 404);
		}

		$callList = new static;
		$callList->setFromArray($row);
		$callList->new = false;

		$userId = (int)($options['userId'] ?? Container::getInstance()->getContext()->getUserId());
		$checkPermissions = (bool)($options['checkPermissions'] ?? true);
		$userPermissions = Container::getInstance()->getUserPermissions($userId);

		if ($checkPermissions && !$userPermissions->isAdmin() && (int)$callList->getEntityTypeId() !== 0)
		{
			$canReadType = $userPermissions->canReadType((int)$callList->getEntityTypeId());
			if (!$canReadType)
			{
				throw new \Bitrix\Main\SystemException('Access Denied', 403);
			}
		}

		// todo: add caching
		if($loadItems)
		{
			$callList->loadItems();
			if (empty($callList->items))
			{
				return $callList;
			}

			$itemIds = array();
			/** @var Item $item */
			foreach ($callList->items as $item)
			{
				$itemIds[] = $item->getElementId();
			}

			$itemFields = self::resolveItemFields($callList->getEntityTypeId(), $itemIds);

			foreach ($callList->items as $key => $item)
			{
				if(!isset($itemFields[$item->getElementId()]))
				{
					unset($callList->items[$key]);
				}

				if ($checkPermissions)
				{
					$canReadItem = $userPermissions->checkReadPermissions($callList->entityTypeId, $item->getElementId());
					if (!$canReadItem)
					{
						unset($callList->items[$key]);
						continue;
					}
				}

				$item->setName($itemFields[$item->getElementId()]['NAME']);
				$item->setCompanyTitle($itemFields[$item->getElementId()]['COMPANY_TITLE']);
				$item->setCompanyPost($itemFields[$item->getElementId()]['POST']);
				$item->setEditUrl($itemFields[$item->getElementId()]['EDIT_URL']);

				if (
					isset($itemFields[$item->getElementId()]['PHONES'])
					&& is_array($itemFields[$item->getElementId()]['PHONES'])
				)
				{
					$item->setPhones($itemFields[$item->getElementId()]['PHONES']);
				}

				if (
					isset($itemFields[$item->getElementId()]['ASSOCIATED_ENTITY'])
					&& is_array($itemFields[$item->getElementId()]['ASSOCIATED_ENTITY'])
				)
				{
					$item->setAssociatedEntity($itemFields[$item->getElementId()]['ASSOCIATED_ENTITY']);
				}
			}

			if (empty($callList->items))
			{
				throw new \Bitrix\Main\SystemException('Call list is empty or access denied', 403);
			}
		}

		return $callList;
	}

	/**
	 * @param string $entityType
	 * @param string $gridId
	 * @return CallList
	 */
	public static function createWithGridId($entityType, $gridId)
	{
		$entityIds = static::getEntitiesFromGridId($entityType, $gridId);
		$filterParameters = static::getGridFilter($gridId, \CCrmOwnerType::ResolveID($entityType));

		$callList = static::createWithEntities($entityType, $entityIds);
		$callList->setFiltered(true);
		$callList->setGridId($gridId);
		$callList->setFilterParameters($filterParameters);
		return $callList;
	}

	/**
	 * @param string $entityType Type of the entities used to build the call list (LEAD|CONTACT|COMPANY).
	 * @param int[] $entityIds Array of the entity's ids.
	 * @return CallList
	 */
	public static function createWithEntities($entityType, $entityIds)
	{
		$callList = new static;
		$callList->filtered = false;
		$callList->filterParameters = null;
		$callList->entityTypeId = \CCrmOwnerType::resolveId($entityType);

		$rank = 0;
		foreach ($entityIds as $entityId)
		{
			$rank = $rank + 10;
			$callList->addItem(Item::createFromArray(
				array(
					'ENTITY_TYPE_ID' => $callList->entityTypeId,
					'ELEMENT_ID' => $entityId,
					'RANK' => $rank
				),
				true
			));
		}
		return $callList;
	}

	/**
	 * @param int[] $entityIds Array of entity ids to be added to the call list
	 */
	public function addEntities($entityIds)
	{
		$lastItem = end($this->items);
		$rank = ($lastItem instanceof Item) ? $lastItem->getRank() : 0;
		reset($this->items);

		foreach ($entityIds as $entityId)
		{
			if(isset($this->items[$entityId]))
				continue;

			$rank = $rank + 10;
			$this->addItem(Item::createFromArray(
				array(
					'ENTITY_TYPE_ID' => $this->entityTypeId,
					'ELEMENT_ID' => $entityId,
					'RANK' => $rank
				),
				true
			));
		}

		return $this;
	}

	public function addEntitiesFromGrid($gridId)
	{
		$this->setFiltered(true);
		$this->setGridId($gridId);
		$this->setFilterParameters(static::getGridFilter($gridId, $this->entityTypeId));
		$entitiesIds = static::getEntitiesFromGridId(\CCrmOwnerType::ResolveName($this->entityTypeId), $gridId);
		$this->addEntities($entitiesIds);
	}

	public function persist()
	{
		$new = $this->id == 0;

		$record = array(
			'FILTERED' => $this->filtered ? 'Y' : 'N',
			'FILTER_PARAMS' => $this->filterParameters,
			'GRID_ID' => $this->gridId,
			'WEBFORM_ID' => $this->webformId,
			'ENTITY_TYPE_ID' => $this->entityTypeId
		);

		if($new)
		{
			$record['DATE_CREATE'] = new DateTime();
			$record['CREATED_BY_ID'] = self::getCurrentUserId();
			$insertResult = CallListTable::add($record);
			$this->id = $insertResult->getId();
		}
		else
		{
			CallListTable::update($this->id, $record);
		}

		$rank = 0;
		foreach ($this->items as $item)
		{
			$rank = $rank + 10;
			$item->setListId($this->id);
			$item->setRank($rank);
			$item->persist();
		}
		return $this;
	}

	/**
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @return mixed
	 */
	public function getDateCreate()
	{
		return $this->dateCreate;
	}

	/**
	 * @return mixed
	 */
	public function getCreatedBy()
	{
		return $this->createdBy;
	}

	/**
	 * @return Item[]
	 */
	public function getItems()
	{
		return $this->items;
	}

	/**
	 * @return mixed
	 */
	public function getFilterParameters()
	{
		return $this->filterParameters;
	}

	/**
	 * @param mixed $filterParameters
	 */
	public function setFilterParameters($filterParameters)
	{
		$this->filterParameters = $filterParameters;
	}

	/**
	 * @return mixed
	 */
	public function getWebformId()
	{
		return $this->webformId;
	}

	/**
	 * @param mixed $webformId
	 */
	public function setWebformId($webformId)
	{
		$this->webformId = $webformId;
	}

	/**
	 * @return mixed
	 */
	public function getEntityTypeId()
	{
		return $this->entityTypeId;
	}

	/**
	 * @param mixed $entityTypeId
	 */
	public function setEntityTypeId($entityTypeId)
	{
		$this->entityTypeId = $entityTypeId;
	}

	public function addItem(Item $item)
	{
		$this->items[$item->getElementId()] = $item;
	}

	/**
	 * @return mixed
	 */
	public function isFiltered()
	{
		return $this->filtered;
	}

	/**
	 * @param mixed $filtered
	 */
	public function setFiltered($filtered)
	{
		$this->filtered = $filtered;
	}

	/**
	 * @return mixed
	 */
	public function getGridId()
	{
		return $this->gridId;
	}

	/**
	 * @param mixed $gridId
	 */
	public function setGridId($gridId)
	{
		$this->gridId = $gridId;
	}

	/**
	 * Returns count of the list items.
	 * @return int
	 */
	public function getItemsCount($statusId = null)
	{
		if($this->itemsLoaded && is_null($statusId))
		{
			$result = count($this->items);
		}
		else
		{
			$filter = array(
				'LIST_ID' => $this->id
			);
			if(!is_null($statusId))
			{
				$filter['STATUS_ID'] = $statusId;
			}

			$row = CallListItemTable::getList(array(
				'select' => array('CNT'),
				'filter' => $filter
			))->fetch();
			$result = $row['CNT'];
		}
		return $result;
	}

	public function setElementStatus($elementId, $statusId)
	{
		$item = $this->getItem($elementId);
		if($item === false)
			return false;

		$item->setStatusId($statusId);
		$item->setRank($item->getRank() + 5500);
		usort($this->items, function($a, $b)
		{
			/** @var Item $a */
			/** @var Item $b */
			return $b->compare($a);
		});
		return true;
	}

	/**
	 * Sets call reference for the element of the list.
	 * @param int $elementId Id of the crm entity.
	 * @param string $callId Id of th call.
	 * @return bool Returns true if call list has element with the specified id and false otherwise.
	 */
	public function setElementCall($elementId, $callId)
	{
		$item = $this->getItem($elementId);
		if($item === false)
			return false;

		$item->setCallId($callId);
		return true;
	}

	/**
	 * @param $elementId
	 * @param $createdEntityType
	 * @param $createdEntityId
	 */
	public function addCreatedEntity($elementId, $createdEntityType, $createdEntityId)
	{
		$cursor = CallListCreatedTable::getList(array(
			'select' => array('ENTITY_ID'),
			'filter' => array(
				'LIST_ID' => $this->id,
				'ELEMENT_ID' => $elementId,
				'ENTITY_TYPE' => $createdEntityType,
				'ENTITY_ID' => $createdEntityId
			)
		));
		if($cursor->fetch())
			return false;

		$insertResult = CallListCreatedTable::add(array(
			'LIST_ID' => $this->id,
			'ELEMENT_ID' => $elementId,
			'ENTITY_TYPE' => $createdEntityType,
			'ENTITY_ID' => $createdEntityId
		));
		return $insertResult->isSuccess();
	}

	/**
	 *
	 * @param $elementId
	 * @param $rank
	 */
	public function setElementRank($elementId, $rank)
	{
		$item = $this->getItem($elementId);
		if($item === false)
			return false;

		$item->setRank($rank);
		usort($this->items, function($a, $b)
		{
			/** @var Item $a */
			/** @var Item $b */
			return $b->compare($a);
		});
		return true;
	}

	/**
	 * Returns items, converted to the array of activity bindings.
	 * @return array
	 */
	public function convertItemsToBindings()
	{
		$result = array();
		foreach ($this->getItems() as $item)
		{
			$result[] = array(
				'OWNER_ID' => $item->getElementId(),
				'OWNER_TYPE_ID' => $this->getEntityTypeId()
			);
		}
		return $result;
	}

	/**
	 * Finishes activity, associated with the call list.
	 */
	public function completeAssociatedActivity()
	{
		$filter = array(
			'TYPE_ID' => \CCrmActivityType::Provider,
			'PROVIDER_ID' => \Bitrix\Crm\Activity\Provider\CallList::getId(),
			'PROVIDER_TYPE_ID' => \Bitrix\Crm\Activity\Provider\CallList::TYPE_CALL_LIST,
			'ASSOCIATED_ENTITY_ID' => $this->getId(),
		);
		$cursor = \CCrmActivity::GetList(array(), $filter);
		$activity = $cursor->Fetch();
		if(!$activity)
		{
			return false;
		}
		\CCrmActivity::Update($activity['ID'], array(
			'COMPLETED' => true
		));
	}

	public static function getStatusList(): array
	{
		return array_values(\CCrmStatus::GetStatus('CALL_LIST'));
	}

	/**
	 * Returns call list converted to an array.
	 * @return array
	 */
	public function toArray()
	{
		$result = array(
			'ID' => $this->id,
			'DATE_CREATE' => $this->dateCreate,
			'CREATED_BY_ID' => $this->createdBy,
			'FILTERED' => $this->filtered ? 'Y' : 'N',
			'FILTER_PARAMS' => $this->filterParameters,
			'GRID_ID' => $this->gridId,
			'WEBFORM_ID' => $this->webformId,
			'ENTITY_TYPE_ID' => $this->entityTypeId,
			'ENTITY_TYPE' => \CCrmOwnerType::ResolveName($this->entityTypeId),
			'ITEMS' => array(),
		);
		foreach ($this->items as $item)
		{
			$result['ITEMS'][] = $item->toArray();
		}
		return $result;
	}

	/**
	 * Kinda workaround functions that applies original filter settings, which were used to build this call list.
	 */
	public function applyOriginalFilter()
	{
		$gridFilter = is_array($this->filterParameters) ? $this->filterParameters : array();
		$gridId = $this->gridId;

		if($gridId == '')
			return;

		$filterOptions = new \Bitrix\Main\UI\Filter\Options($gridId);
		$filterOptions->setFilterSettings(
			'default_filter',
			array(
				'fields' => $gridFilter,
				//'rows' => array_keys($arResult['FILTER_ROWS'])
			)
		);
		$filterOptions->save();
	}

	/**
	 * Creates new Activity, associatied with the current call list.
	 * @param string $subject Subject of the activity.
	 * @param string $description Description of the activity.
	 * @return Result
	 */
	public function createActivity($subject = '', $description = '')
	{
		$result = new Result();
		$activityFields = array(
			'TYPE_ID' => \CCrmActivityType::Provider,
			'PROVIDER_ID' => \Bitrix\Crm\Activity\Provider\CallList::getId(),
			'PROVIDER_TYPE_ID' => \Bitrix\Crm\Activity\Provider\CallList::TYPE_CALL_LIST,
			'ASSOCIATED_ENTITY_ID' => $this->getId(),
			'START_TIME' => new DateTime(),
			'COMPLETED' => 'N',
			'PRIORITY' => \CCrmActivityPriority::Medium,
			'SUBJECT' => $subject ?: $this->getDefaultSubject(),
			'DESCRIPTION' => $description,
			'DESCRIPTION_TYPE' => \CCrmContentType::PlainText,
			'LOCATION' => '',
			'NOTIFY_TYPE' => \CCrmActivityNotifyType::None,
			'BINDINGS' => array(),
			'SETTINGS' => array(),
			'AUTHOR_ID' => \CCrmSecurityHelper::getCurrentUserId(),
			'RESPONSIBLE_ID' => \CCrmSecurityHelper::getCurrentUserId(),
			'OWNER_TYPE_ID' => \CCrmOwnerType::CallList,
			'OWNER_ID' => $this->getId()
		);

		$activityId = \CCrmActivity::Add($activityFields, false, true, array('REGISTER_SONET_EVENT' => true));
		if ($activityId > 0)
		{
			$activityFields['ID'] = $activityId;
			$result->setData($activityFields);
		}
		else
		{
			$result->addError(new Error(\CCrmActivity::GetLastErrorMessage()));
		}

		return $result;
	}

	/**
	 * Returns default call list activity subject.
	 * @return string
	 */
	public function getDefaultSubject()
	{
		return Loc::getMessage("CRM_CALL_LIST_SUBJECT") . " #" . $this->id;
	}

	/**
	 * Delete elements from call list.
	 * @param array $elements Array of element ids to be deleted.
	 */
	public function deleteItems(array $elements)
	{
		foreach ($elements as $elementId)
		{
			CallListItemTable::delete(array(
				'LIST_ID' => $this->id,
				'ENTITY_TYPE_ID' => $this->getEntityTypeId(),
				'ELEMENT_ID' => $elementId
			));
		}
	}

	protected function setFromArray(array $fields)
	{
		$this->id = $fields['ID'];
		$this->dateCreate = $fields['DATE_CREATE'];
		$this->createdBy = $fields['CREATED_BY_ID'];
		$this->filtered = $fields['FILTERED'] == 'Y';
		$this->filterParameters = $fields['FILTER_PARAMS'];
		$this->gridId = $fields['GRID_ID'];
		$this->webformId = $fields['WEBFORM_ID'];
		$this->entityTypeId = $fields['ENTITY_TYPE_ID'];
	}

	protected function loadItems()
	{
		$this->items = array();
		$cursor = CallListItemTable::getList(array(
			'filter' => array(
				'=LIST_ID' => $this->id
			),
			'order' => array(
				'LIST_ID' => 'ASC',
				'STATUS_ID' => 'ASC',
				'RANK' => 'ASC'
			)
		));

		while($row = $cursor->fetch())
		{
			$this->items[$row['ELEMENT_ID']] = Item::createFromArray($row, false);
		}
		$this->itemsLoaded = true;
	}

	/**
	 * @param int $entityTypeId
	 * @param array $itemIds
	 * @return array
	 */
	protected static function resolveItemFields($entityTypeId, array $itemIds)
	{
		$filter = array(
			'=ID' => $itemIds,
			'CHECK_PERMISSIONS' => 'Y'
		);

		switch ($entityTypeId)
		{
			case \CCrmOwnerType::Lead:
				$cursor = \CCrmLead::getListEx(array(), $filter, false, false, array('ID', 'HONORIFIC', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'COMPANY_TITLE', 'POST', 'TITLE'));
				break;
			case \CCrmOwnerType::Contact:
				$cursor = \CCrmContact::getListEx(array(), $filter, false, false, array('ID', 'HONORIFIC', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'COMPANY_TITLE', 'POST'));
				break;
			case \CCrmOwnerType::Company:
				$cursor = \CCrmCompany::getListEx(array(), $filter, false, false, array('ID', 'TITLE', 'ADDRESS', 'COMMENTS'));
				break;
			case \CCrmOwnerType::Deal:
				$cursor = \CCrmDeal::getListEx(array(), $filter, false, false, array('ID', 'TITLE', 'CONTACT_ID', 'COMPANY_ID'));
				break;
			case \CCrmOwnerType::Quote:
				$cursor = \CCrmQuote::getList(array(), $filter, false, false, array('ID', 'TITLE', 'CONTACT_ID', 'COMPANY_ID'));
				break;
			case \CCrmOwnerType::Invoice:
				$cursor = \CCrmInvoice::getList(array(), $filter, false, false, array('ID', 'ORDER_TOPIC', 'UF_CONTACT_ID', 'UF_COMPANY_ID'));
				break;
			default:
				return array();
		}

		$result = array();
		$companyIds = array();
		$contactIds = array();
		while($row = $cursor->Fetch())
		{
			$formattedName = '';
			switch ($entityTypeId)
			{
				case \CCrmOwnerType::Lead:
					if(isset($row['NAME']) || isset($row['SECOND_NAME']) || isset($row['LAST_NAME']))
						$formattedName = \CCrmLead::PrepareFormattedName(
							array(
								'HONORIFIC' => isset($row['HONORIFIC']) ? $row['HONORIFIC'] : '',
								'NAME' => isset($row['NAME']) ? $row['NAME'] : '',
								'SECOND_NAME' => isset($row['SECOND_NAME']) ? $row['SECOND_NAME'] : '',
								'LAST_NAME' => isset($row['LAST_NAME']) ? $row['LAST_NAME'] : ''
							)
						);
					else
						$formattedName = $row['TITLE'];

					$result[$row['ID']]['COMPANY_TITLE'] = $row['COMPANY_TITLE'];
					$result[$row['ID']]['POST'] = $row['POST'];

					break;
				case \CCrmOwnerType::Contact:
					$formattedName = \CCrmContact::PrepareFormattedName(array(
						'HONORIFIC' => isset($row['HONORIFIC']) ? $row['HONORIFIC'] : '',
						'NAME' => isset($row['NAME']) ? $row['NAME'] : '',
						'SECOND_NAME' => isset($row['SECOND_NAME']) ? $row['SECOND_NAME'] : '',
						'LAST_NAME' => isset($row['LAST_NAME']) ? $row['LAST_NAME'] : ''
					));

					$result[$row['ID']]['COMPANY_TITLE'] = $row['COMPANY_TITLE'];
					$result[$row['ID']]['POST'] = $row['POST'];

					break;
				case \CCrmOwnerType::Company:
					$formattedName = $row['TITLE'];
					break;
				case \CCrmOwnerType::Deal:
					$formattedName = $row['TITLE'];
					if($row['CONTACT_ID'] > 0)
					{
						$result[$row['ID']]['CONTACT_ID'] = (int)$row['CONTACT_ID'];
						$contactIds[$row['CONTACT_ID']] = true;
					}
					else if($row['COMPANY_ID'] > 0)
					{
						$result[$row['ID']]['COMPANY_ID'] = (int)$row['COMPANY_ID'];
						$companyIds[$row['COMPANY_ID']] = true;
					}

					break;
				case \CCrmOwnerType::Quote:
					$formattedName = $row['TITLE'];
					if($row['CONTACT_ID'] > 0)
					{
						$result[$row['ID']]['CONTACT_ID'] = (int)$row['CONTACT_ID'];
						$contactIds[$row['CONTACT_ID']] = true;
					}
					else if($row['COMPANY_ID'] > 0)
					{
						$result[$row['ID']]['COMPANY_ID'] = (int)$row['COMPANY_ID'];
						$companyIds[$row['COMPANY_ID']] = true;
					}

					break;
				case \CCrmOwnerType::Invoice:
					$formattedName = $row['ORDER_TOPIC'];
					if($row['UF_CONTACT_ID'] > 0)
					{
						$result[$row['ID']]['CONTACT_ID'] = (int)$row['UF_CONTACT_ID'];
						$contactIds[$row['UF_CONTACT_ID']] = true;
					}
					else if($row['UF_COMPANY_ID'] > 0)
					{
						$result[$row['ID']]['COMPANY_ID'] = (int)$row['UF_COMPANY_ID'];
						$companyIds[$row['UF_COMPANY_ID']] = true;
					}

					break;
			}

			$result[$row['ID']]['NAME'] = $formattedName;
			$result[$row['ID']]['PHONES'] = array();
			$result[$row['ID']]['EDIT_URL'] = \CCrmOwnerType::GetEditUrl($entityTypeId, $row['ID']);
		}

		// filling up phones
		$cursor = \CCrmFieldMulti::GetListEx(
			array(),
			array('ENTITY_ID' => \CCrmOwnerType::ResolveName($entityTypeId), '@ELEMENT_ID' => $itemIds, '@TYPE_ID' => array('PHONE')),
			false,
			false,
			array('ENTITY_ID', 'ELEMENT_ID', 'TYPE_ID', 'VALUE_TYPE', 'VALUE')
		);

		while ($row = $cursor->Fetch())
		{
			$result[$row['ELEMENT_ID']]['PHONES'][] = array(
				'TYPE' => $row['VALUE_TYPE'],
				'VALUE' => $row['VALUE']
			);
		}

		$contactFields = count($contactIds) > 0 ? self::resolveItemFields(\CCrmOwnerType::Contact, array_keys($contactIds)) : array();
		$companyFields = count($companyIds) > 0 ? self::resolveItemFields(\CCrmOwnerType::Company, array_keys($companyIds)) : array();

		if(count($companyFields) > 0 || count($contactFields) > 0)
		{
			foreach ($result as $itemId => $itemFields)
			{
				if(isset($result[$itemId]['CONTACT_ID']) && isset($contactFields[$result[$itemId]['CONTACT_ID']]))
				{
					$result[$itemId]['ASSOCIATED_ENTITY'] = array(
						'ID' => $result[$itemId]['CONTACT_ID'],
						'TYPE' => \CCrmOwnerType::ContactName
					);
					$result[$itemId]['ASSOCIATED_ENTITY'] += $contactFields[$result[$itemId]['CONTACT_ID']];
				}
				elseif(isset($result[$itemId]['COMPANY_ID']) && isset($companyFields[$result[$itemId]['COMPANY_ID']]))
				{
					$result[$itemId]['ASSOCIATED_ENTITY'] = array(
						'ID' => $result[$itemId]['COMPANY_ID'],
						'TYPE' => \CCrmOwnerType::CompanyName
					);
					$result[$itemId]['ASSOCIATED_ENTITY'] += $companyFields[$result[$itemId]['COMPANY_ID']];
				}
			}
		}

		return $result;
	}

	/**
	 * @param int $elementId
	 */
	protected function getItem($elementId)
	{
		$foundItem = null;
		foreach ($this->items as $item)
		{
			if($item->getElementId() == $elementId)
			{
				$foundItem = $item;
				break;
			}
		}

		return (is_null($foundItem) ? false: $foundItem);
	}

	protected static function getCurrentUserId()
	{
		global $USER;
		return $USER->getId();
	}

	protected static function getEntitiesFromGridId($entityType, $gridId)
	{
		$gridFilter = static::getGridFilter($gridId, \CCrmOwnerType::ResolveID($entityType));

		$gridFilter['CHECK_PERMISSIONS'] = 'Y';

		$cursor = null;
		if($entityType === \CCrmOwnerType::LeadName)
		{
			$cursor = \CCrmLead::getListEx(array(), $gridFilter, false, false, array('ID'));
		}
		elseif($entityType === \CCrmOwnerType::CompanyName)
		{
			$cursor = \CCrmCompany::getListEx(array(), $gridFilter, false, false, array('ID'));
		}
		elseif($entityType === \CCrmOwnerType::ContactName)
		{
			$cursor = \CCrmContact::getListEx(array(), $gridFilter, false, false, array('ID'));
		}
		elseif($entityType === \CCrmOwnerType::DealName)
		{
			$cursor = \CCrmDeal::getListEx(array(), $gridFilter, false, false, array('ID'));
		}
		elseif($entityType === \CCrmOwnerType::QuoteName)
		{
			$cursor = \CCrmQuote::getList(array(), $gridFilter, false, false, array('ID'));
		}
		elseif($entityType === \CCrmOwnerType::InvoiceName)
		{
			$cursor = \CCrmInvoice::getList(array(), $gridFilter, false, false, array('ID'));
		}

		if(!$cursor)
			throw new \Bitrix\Main\SystemException('Database error');

		$entityIds = array();

		$count = 0;
		while($row = $cursor->fetch())
		{
			$count++;
			if($count > self::ITEMS_LIMIT)
			{
				throw new \Bitrix\Main\SystemException(Loc::getMessage('CRM_CALL_LIST_LIMIT_ERROR', array("#LIMIT#" => self::ITEMS_LIMIT)));
			}

			$entityIds[] = $row['ID'];
		}
		return $entityIds;
	}

	protected static function getGridFilter($gridId, int $entityTypeId = 0)
	{
		$filterFactory = Container::getInstance()->getFilterFactory();

		$filter = $filterFactory->getFilter(
			\Bitrix\Crm\Filter\Factory::getSettingsByGridId($entityTypeId, (string)$gridId)
		);

		return $filterFactory->getFilterValue($filter);
	}

	/**
	 * Handler for the event onCrmContactListItemBuildMenu.
	 * Rewrites contact's actions menu.
	 * @param $restPlacement
	 * @param $contactId
	 * @param array $menu
	 */
	public static function handleOnCrmContactListItemBuildMenu($restPlacement, $params, array &$menu)
	{
		static::handleOnEntityListBuildMenu($restPlacement, $params, $menu, \CCrmOwnerType::ContactName);
	}

	/**
	 * Handler for the event onCrmCompanyListItemBuildMenu.
	 * Rewrites contact's actions menu.
	 * @param $restPlacement
	 * @param $contactId
	 * @param array $menu
	 */
	public static function handleOnCrmCompanyListItemBuildMenu($restPlacement, $params, array &$menu)
	{
		static::handleOnEntityListBuildMenu($restPlacement, $params, $menu, \CCrmOwnerType::CompanyName);
	}

	/**
	 * Handler for the event onCrmLeadListItemBuildMenu.
	 * Rewrites contact's actions menu.
	 * @param $restPlacement
	 * @param $contactId
	 * @param array $menu
	 */
	public static function handleOnCrmLeadListItemBuildMenu($restPlacement, $params, array &$menu)
	{
		static::handleOnEntityListBuildMenu($restPlacement, $params, $menu, \CCrmOwnerType::LeadName);
	}

	/**
	 * Handler for the event onCrmDealListItemBuildMenu.
	 * Rewrites deal's actions menu.
	 * @param $restPlacement
	 * @param $contactId
	 * @param array $menu
	 */
	public static function handleOnCrmDealListItemBuildMenu($restPlacement, $params, array &$menu)
	{
		static::handleOnEntityListBuildMenu($restPlacement, $params, $menu, \CCrmOwnerType::DealName);
	}

	/**
	 * Handler for the event onCrmQuoteListItemBuildMenu.
	 * Rewrites quote's actions menu.
	 * @param $restPlacement
	 * @param $contactId
	 * @param array $menu
	 */
	public static function handleOnCrmQuoteListItemBuildMenu($restPlacement, $params, array &$menu)
	{
		static::handleOnEntityListBuildMenu($restPlacement, $params, $menu, \CCrmOwnerType::QuoteName);
	}

	/**
	 * Handler for the event onCrmQuoteListItemBuildMenu.
	 * Rewrites quote's actions menu.
	 * @param $restPlacement
	 * @param $contactId
	 * @param array $menu
	 */
	public static function handleOnCrmOrderListItemBuildMenu($restPlacement, $params, array &$menu)
	{
		static::handleOnEntityListBuildMenu($restPlacement, $params, $menu, \CCrmOwnerType::OrderName);
	}

	/**
	 * Handler for the event onCrmInvoiceListItemBuildMenu.
	 * Rewrites invoice's actions menu.
	 * @param $restPlacement
	 * @param $contactId
	 * @param array $menu
	 */
	public static function handleOnCrmInvoiceListItemBuildMenu($restPlacement, $params, array &$menu)
	{
		static::handleOnEntityListBuildMenu($restPlacement, $params, $menu, \CCrmOwnerType::InvoiceName);
	}

	/**
	 * Generalized menu build event handler.
	 * Rewrites entity context menu.
	 * @param $restPlacement
	 * @param $params
	 * @param array $menu
	 * @param $entityTypeName
	 */
	protected static function handleOnEntityListBuildMenu($restPlacement, $params, array &$menu, $entityTypeName)
	{
		$menu = array(
			array(
				'ICONCLASS' => '',
				'TITLE' => GetMessage('CRM_CALL_LIST_UPDATE'),
				'TEXT' => GetMessage('CRM_CALL_LIST_UPDATE'),
				'ONCLICK' => "BX.CrmCallListHelper.addToCallList({
						'entityType': '".$entityTypeName."', 
						'id': '".(int)$params['ID']."',
						'callListId': {$params['CALL_LIST_ID']},
						'context': '{$params['CALL_LIST_CONTEXT']}',
						'gridId': '{$params['GRID_ID']}'
					});",
				'DEFAULT' => true
			)
		);
	}

	public static function isAvailable()
	{
		if(!Loader::includeModule('bitrix24'))
			return true;

		return \Bitrix\Bitrix24\Feature::isFeatureEnabled('crm_call_list');
	}

	public static function getLicensePopupHeader()
	{
		return Loc::getMessage('CRM_CALL_LIST_LICENSE_POPUP_HEADER');
	}

	public static function getLicensePopupContent()
	{

		$text = '<p>'.Loc::getMessage('CRM_CALL_LIST_LICENSE_POPUP_CONTENT').'</p> 
			 <ul class="hide-features-list">
			 	<li class="hide-features-list-item">'.GetMessage("CRM_CALL_LIST_LICENSE_POPUP_ITEM_1").'</li>
			 	<li class="hide-features-list-item">'.GetMessage("CRM_CALL_LIST_LICENSE_POPUP_ITEM_2").'</li>
			 	<li class="hide-features-list-item">'.GetMessage("CRM_CALL_LIST_LICENSE_POPUP_ITEM_3").'</li>
			</ul>
				<a href="'.static::getProLink().'" target="_blank" class="hide-features-more">'.GetMessage('CRM_CALL_LIST_LICENSE_POPUP_SHOW_MORE').'</a>
			<strong>'.GetMessage('CRM_CALL_LIST_LICENSE_POPUP_FOOTER_2').'</strong>';
		return $text;
	}

	public static function getProLink()
	{
		if(LANGUAGE_ID == "ru" || LANGUAGE_ID == "kz" || LANGUAGE_ID == "by")
			return "https://www.bitrix24.ru/pro/crm.php";
		else if(LANGUAGE_ID == "de")
			return "https://www.bitrix24.de/pro/crm.php";
		else if(LANGUAGE_ID == "ua")
			return "https://www.bitrix24.ua/pro/crm.php";
		else
			return "https://www.bitrix24.com/pro/crm.php";
	}

	public static function transferOwnership($oldEntityTypeId, $oldEntityId, $newEntityTypeId, $newEntityId)
	{
		//Waiting for ENTITY_TYPE column in b_crm_call_list_item
		/*
		if($oldEntityTypeId <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero.', 'oldEntityTypeID');
		}

		if($oldEntityId <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero.', 'oldEntityID');
		}

		if($newEntityTypeId <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero.', 'newEntityTypeID');
		}

		if($newEntityId <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero.', 'newEntityID');
		}

		$oldEntityTypeName = \CCrmOwnerType::ResolveName($oldEntityTypeId);
		if($oldEntityTypeName === '')
		{
			throw new Main\ArgumentException("Could not resolve Entity Type Name: {$oldEntityTypeId}.", 'oldEntityTypeId');
		}

		$newEntityTypeName = \CCrmOwnerType::ResolveName($newEntityTypeId);
		if($newEntityTypeName === '')
		{
			throw new Main\ArgumentException("Could not resolve Entity Type Name: {$newEntityTypeId}.", 'newEntityTypeId');
		}

		$connection = Main\Application::getConnection();
		$helper = $connection->getSqlHelper();

		$oldEntityTypeName = $helper->forSql($oldEntityTypeName);
		$newEntityTypeName = $helper->forSql($newEntityTypeName);

		Main\Application::getConnection()->queryExecute(
			"UPDATE b_crm_call_list_created SET ENTITY_TYPE = '{$newEntityTypeName}', ENTITY_ID = {$newEntityId} WHERE ENTITY_TYPE = '{$oldEntityTypeName}' AND ENTITY_ID = {$oldEntityId}"
		);

		Main\Application::getConnection()->queryExecute(
			"UPDATE b_crm_call_list_item SET ENTITY_TYPE = '{$newEntityTypeName}', ELEMENT_ID = {$newEntityId} WHERE ENTITY_TYPE = '{$oldEntityTypeName}' AND ELEMENT_ID = {$oldEntityId}"
		);
		*/
	}

	public static function deleteByOwner($entityTypeId, $entityId)
	{
		//Waiting for ENTITY_TYPE column in b_crm_call_list_item
		/*
		if($entityTypeId <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero.', 'oldEntityTypeID');
		}

		if($entityId <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero.', 'oldEntityID');
		}

		$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeId);
		if($entityTypeName === '')
		{
			throw new Main\ArgumentException("Could not resolve Entity Type Name: {$entityTypeId}.", 'entityTypeId');
		}

		$connection = Main\Application::getConnection();
		$helper = $connection->getSqlHelper();

		$entityTypeName = $helper->forSql($entityTypeName);

		Main\Application::getConnection()->queryExecute(
			"DELETE FROM b_crm_call_list_created WHERE ENTITY_TYPE = '{$entityTypeName}' AND ENTITY_ID = {$entityId}"
		);

		Main\Application::getConnection()->queryExecute(
			"DELETE FROM b_crm_call_list_item WHERE ENTITY_TYPE = '{$entityTypeName}' AND ELEMENT_ID = {$entityId}"
		);
		*/
	}

	final public static function isEntityTypeSupported(int $entityTypeId): bool
	{
		static $supportedEntityTypes = [
			\CCrmOwnerType::Lead,
			\CCrmOwnerType::Contact,
			\CCrmOwnerType::Company,
			\CCrmOwnerType::Deal,
			\CCrmOwnerType::Quote,
			\CCrmOwnerType::Invoice,
		];

		return in_array($entityTypeId, $supportedEntityTypes, true);
	}
}
