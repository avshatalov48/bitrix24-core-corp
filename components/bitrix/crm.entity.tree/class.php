<?php

use Bitrix\Crm\Color\PhaseColorScheme;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Order\DeliveryStatus;
use Bitrix\Crm\Order\OrderStatus;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\StatusTable;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

class CrmEntityTreeComponent extends \CBitrixComponent
{
	protected $blockPage = 1;
	protected $blockSize = 100;
	protected $nameTemplate = '';
	protected $notHideEntity = [];
	protected $logoSizes = ['width' => 50, 'height' => 50];
	protected $selectPresets = [
		\CCrmOwnerType::Company => ['ID', 'DATE_CREATE', 'TITLE', 'COMPANY_TYPE', 'LOGO'],
		\CCrmOwnerType::Contact => [
			'ID', 'DATE_CREATE', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'COMPANY_ID',
							'COMPANY_TITLE', 'DATE_CREATE', 'PHOTO'
		],
		\CCrmOwnerType::Deal => [
			'ID', 'DATE_CREATE', 'TITLE', 'OPPORTUNITY', 'CURRENCY_ID', 'STAGE_ID', 'BEGINDATE',
							'CATEGORY_ID', 'ASSIGNED_BY_LOGIN', 'ASSIGNED_BY_NAME', 'ASSIGNED_BY_LAST_NAME',
							'ASSIGNED_BY_SECOND_NAME', 'ASSIGNED_BY_ID'
		],
		\CCrmOwnerType::Quote => [
			'ID', 'BEGINDATE', 'TITLE', 'STATUS_ID', 'ASSIGNED_BY_ID', 'ASSIGNED_BY_LOGIN',
							'ASSIGNED_BY_NAME', 'ASSIGNED_BY_LAST_NAME', 'ASSIGNED_BY_SECOND_NAME', 'CLOSEDATE',
							'DATE_CREATE'
		],
		\CCrmOwnerType::Invoice => [
			'ID', 'DATE_INSERT_FORMAT', 'ORDER_TOPIC', 'ACCOUNT_NUMBER', 'PRICE', 'DATE_BILL',
							'CURRENCY', 'STATUS_ID', 'DATE_PAYED', 'RESPONSIBLE_ID', 'RESPONSIBLE_LOGIN',
							'RESPONSIBLE_NAME', 'RESPONSIBLE_LAST_NAME', 'RESPONSIBLE_SECOND_NAME'
		],
		\CCrmOwnerType::Order => [
			'ID', 'DATE_INSERT_FORMAT', 'DATE_INSERT', 'ORDER_TOPIC', 'ACCOUNT_NUMBER', 'PRICE',
							'CURRENCY', 'STATUS_ID', 'DATE_PAYED', 'RESPONSIBLE_ID', 'RESPONSIBLE_LOGIN' => 'RESPONSIBLE.LOGIN',
							'RESPONSIBLE_NAME' => 'RESPONSIBLE.NAME','RESPONSIBLE_LAST_NAME' => 'RESPONSIBLE.LAST_NAME',
							'RESPONSIBLE_SECOND_NAME' => 'RESPONSIBLE.SECOND_NAME', 'PAY_SYSTEM_ID', 'DATE_BILL'
		],
		\CCrmOwnerType::OrderPayment => [
			'ID', 'ACCOUNT_NUMBER', 'PRICE' => 'SUM', 'DATE_BILL', 'CURRENCY', 'PAID',
							'DATE_PAID', 'RESPONSIBLE_ID', 'RESPONSIBLE_LOGIN' => 'RESPONSIBLE.LOGIN',
							'RESPONSIBLE_NAME' => 'RESPONSIBLE.NAME','RESPONSIBLE_LAST_NAME' => 'RESPONSIBLE.LAST_NAME',
							'RESPONSIBLE_SECOND_NAME' => 'RESPONSIBLE.SECOND_NAME', 'PAY_SYSTEM_ID',
							'ORDER_TOPIC' => 'PAY_SYSTEM.NAME' , 'ORDER_ID'
		],
		\CCrmOwnerType::OrderShipment => [
			'ID','ACCOUNT_NUMBER', 'PRICE' => 'PRICE_DELIVERY', 'DATE_INSERT', 'CURRENCY', 'DEDUCTED',
							'DATE_DEDUCTED', 'DELIVERY_ID', 'RESPONSIBLE_ID', 'RESPONSIBLE_LOGIN' => 'RESPONSIBLE.LOGIN',
							'RESPONSIBLE_NAME' => 'RESPONSIBLE.NAME','RESPONSIBLE_LAST_NAME' => 'RESPONSIBLE.LAST_NAME',
							'RESPONSIBLE_SECOND_NAME' => 'RESPONSIBLE.SECOND_NAME', 'ORDER_TOPIC' => 'DELIVERY.NAME',
							'DATE_INSERT_FORMAT' => 'DATE_INSERT_SHORT', 'STATUS_ID', 'ORDER_ID'
		],
		\CCrmOwnerType::StoreDocument => [
			'ID',
			'DOC_TYPE',
			'RESPONSIBLE_ID',
			'STATUS',
			'DATE_STATUS',
			'TOTAL',
			'CURRENCY',
			'TITLE',
			'RESPONSIBLE_LOGIN' => 'RESPONSIBLE.LOGIN',
			'RESPONSIBLE_NAME' => 'RESPONSIBLE.NAME',
			'RESPONSIBLE_LAST_NAME' => 'RESPONSIBLE.LAST_NAME',
			'RESPONSIBLE_SECOND_NAME' => 'RESPONSIBLE.SECOND_NAME',
		],
	];

	protected $documentProvidersMap;

	protected function init()
	{
		$this->arParams['STATUSES'] = $this->getStatuses();
		$this->arParams['BLOCK_SIZE'] = $this->blockSize;
		$this->nameTemplate = \CSite::GetNameFormat(false);
		if (isset($this->arParams['BLOCK_PAGE']) && $this->arParams['BLOCK_PAGE'] > 0)
		{
			$this->blockPage = $this->arParams['BLOCK_PAGE'];
		}
	}

	/**
	 * Format user name.
	 * @staticvar array $users
	 * @param array $row
	 * @param string $prefix for ID's key
	 * @return string formatted name
	 */
	protected function getFormattedUserName(array $row, $prefix = 'ASSIGNED_BY'): string
	{
		static $users = [];

		if (empty($row[$prefix . '_ID']))
		{
			return '';
		}

		if (!isset($users[$row[$prefix . '_ID']]))
		{
			if (!isset($row[$prefix . '_NAME']))
			{
				$userData = Container::getInstance()->getUserBroker()->getById((int)$row[$prefix . '_ID']);
				$row[$prefix . '_LOGIN'] = $userData['LOGIN'] ?? '';
				$row[$prefix . '_NAME'] = $userData['NAME'] ?? '';
				$row[$prefix . '_LAST_NAME'] = $userData['LAST_NAME'] ?? '';
				$row[$prefix . '_SECOND_NAME'] = $userData['SECOND_NAME'] ?? '';
			}

			$users[$row[$prefix . '_ID']] = \CUser::FormatName(
				$this->nameTemplate,
				array(
					'LOGIN' => $row[$prefix . '_LOGIN'],
					'NAME' => $row[$prefix . '_NAME'],
					'LAST_NAME' => $row[$prefix . '_LAST_NAME'],
					'SECOND_NAME' => $row[$prefix . '_SECOND_NAME']
				),
				true, false
			);
		}

		return $users[$row[$prefix . '_ID']];
	}

	/**
	 * Get multi-fields for entity (phone, email, etc).
	 * @param array $items
	 * @param int $entityTypeId
	 * @return filled array
	 */
	protected function fillMultiFields(array $items, int $entityTypeId)
	{
		$isOneElement = false;

		if (!empty($items))
		{
			if (isset($items['ID']))
			{
				$isOneElement = true;
				$items = array($items['ID'] => $items);
			}
			$res = \CCrmFieldMulti::GetListEx([], [
				'ENTITY_ID' => \CCrmOwnerType::ResolveName($entityTypeId),
				'ELEMENT_ID' => array_keys($items),
			]);
			while ($row = $res->fetch())
			{
				if (!isset($items[$row['ELEMENT_ID']]['FM']))
				{
					$items[$row['ELEMENT_ID']]['FM'] = [];
					$items[$row['ELEMENT_ID']]['FM_VALUES'] = [];
				}
				if (!isset($items[$row['ELEMENT_ID']]['FM'][$row['TYPE_ID']]))
				{
					$items[$row['ELEMENT_ID']]['FM'][$row['TYPE_ID']] = [];
					$items[$row['ELEMENT_ID']]['FM_VALUES'][$row['TYPE_ID']] = [];
				}
				$items[$row['ELEMENT_ID']]['FM'][$row['TYPE_ID']][] = $row;
				$items[$row['ELEMENT_ID']]['FM_VALUES'][$row['TYPE_ID']][] = $row['VALUE'];
			}
		}

		return $isOneElement ? array_pop($items) : $items;
	}

	/**
	 * Get all CRM statuses, stages, etc.
	 * @return array
	 */
	protected function getStatuses(): array
	{
		$statuses = [];

		$list = StatusTable::getList([
			'order' => [
				'SORT' => 'ASC',
			]
		]);
		while ($status = $list->fetch())
		{
			if (
				!in_array(
					$status['ENTITY_ID'],
					['STATUS', 'QUOTE_STATUS', 'INVOICE_STATUS', 'COMPANY_TYPE', 'DEAL_STAGE', 'SOURCE']
				)
				&& mb_strpos($status['ENTITY_ID'], 'DEAL_STAGE') !== 0
				&& mb_strpos($status['ENTITY_ID'], 'DYNAMIC_') !== 0
				&& mb_strpos($status['ENTITY_ID'], 'SMART_INVOICE_') !== 0
				&& mb_strpos($status['ENTITY_ID'], 'SMART_DOCUMENT_') !== 0
			)
			{
				continue;
			}
			if (!isset($statuses[$status['ENTITY_ID']]))
			{
				$statuses[$status['ENTITY_ID']] = [];
			}
			$statuses[$status['ENTITY_ID']][$status['STATUS_ID']] = $status;
		}

		$orderStatus = OrderStatus::getListInCrmFormat();
		if ($orderStatus)
		{
			foreach ($orderStatus as $status)
			{
				$statuses[$status['ENTITY_ID']][$status['STATUS_ID']] = $status;
			}
		}

		$shipmentStatus = DeliveryStatus::getListInCrmFormat();
		if ($shipmentStatus)
		{
			foreach ($shipmentStatus as $status)
			{
				$statuses[$status['ENTITY_ID']][$status['STATUS_ID']] = $status;
			}
		}

		foreach ($statuses as $entityId => &$statusList)
		{
			$statusList = PhaseColorScheme::fillDefaultColors($statusList);
		}
		unset($statusList);

		//range statuses
		foreach ($statuses as $id => &$entity)
		{
			$count = 1;
			$chunk = 1;
			foreach ($entity as &$status)
			{
				$statusSemantics = $status['SEMANTICS'] ?? null;
				if (
					$statusSemantics !== PhaseSemantics::SUCCESS
					&& $statusSemantics !== PhaseSemantics::FAILURE
				)
				{
					$status['CHUNK'] = $chunk++;
					$count++;
				}
			}
			unset($status);
			$entity['__COUNT'] = $count;
		}
		unset($entity);

		return $statuses;
	}

	/**
	 * Replace some field names to common notation.
	 *
	 * @param array $row
	 * @param int $entityTypeId
	 * @return array
	 */
	protected function replaceCommonFields(array $row, int $entityTypeId)
	{
		if (empty($row))
		{
			return $row;
		}
		if (!empty($row['TITLE']))
		{
			$row['NAME'] = $row['TITLE'];
		}
		if (empty($row['NAME']) && !empty($row['HEADING']))
		{
			$row['NAME'] = $row['HEADING'];
		}
		if (isset($row['ASSIGNED_BY_ID']) && $row['ASSIGNED_BY_ID'] > 0)
		{
			$row['ASSIGNED_BY_FORMATTED_NAME'] = $this->getFormattedUserName($row, 'ASSIGNED_BY');
			$row['ASSIGNED_BY_URL'] = Container::getInstance()->getRouter()->getUserPersonalUrl($row['ASSIGNED_BY_ID']);
		}
		elseif (isset($row['RESPONSIBLE_ID']) && $row['RESPONSIBLE_ID'] > 0)
		{
			$row['RESPONSIBLE_FORMATTED_NAME'] = $this->getFormattedUserName($row, 'RESPONSIBLE');
			$row['RESPONSIBLE_URL'] = Container::getInstance()->getRouter()->getUserPersonalUrl($row['RESPONSIBLE_ID']);
		}
		if (array_key_exists('PRICE', $row) && array_key_exists('CURRENCY', $row))
		{
			$row['PRICE_FORMATTED'] = \CCrmCurrency::MoneyToString($row['PRICE'], $row['CURRENCY']);
		}
		if (array_key_exists('OPPORTUNITY', $row) && array_key_exists('CURRENCY_ID', $row) && $row['OPPORTUNITY'] > 0)
		{
			$row['OPPORTUNITY_FORMATTED'] = \CCrmCurrency::MoneyToString($row['OPPORTUNITY'], $row['CURRENCY_ID']);
		}
		if (isset($row['LOGO']) && $row['LOGO']>0)
		{
			$row['LOGO_FILE'] = \CFile::ResizeImageGet(
				$row['LOGO'],
				$this->logoSizes,
				BX_RESIZE_IMAGE_PROPORTIONAL,
				false
			);
		}
		if (isset($row['PHOTO']) && $row['PHOTO']>0)
		{
			$row['PHOTO_FILE'] = \CFile::ResizeImageGet(
				$row['PHOTO'],
				$this->logoSizes,
				BX_RESIZE_IMAGE_PROPORTIONAL,
				false
			);
		}
		if (isset($row['DATE_INSERT']))
		{
			$row['TIMESTAMP'] = $row['DATE_INSERT'] = \makeTimeStamp($row['DATE_INSERT']);
		}
		elseif (isset($row['DATE_CREATE']))
		{
			$row['TIMESTAMP'] = $row['DATE_CREATE'] = \makeTimeStamp($row['DATE_CREATE']);
		}
		elseif (isset($row['DATE_BILL']))
		{
			$row['TIMESTAMP'] = $row['DATE_BILL'] = \makeTimeStamp($row['DATE_BILL']);
		}
		elseif (isset($row['CREATED_TIME']))
		{
			$row['TIMESTAMP'] = \makeTimeStamp($row['CREATED_TIME']);
		}

		//for get activity
		if (isset($row['ID']))
		{
			if (!isset($this->arResult['ACTIVITY'][$entityTypeId]))
			{
				$this->arResult['ACTIVITY'][$entityTypeId] = [];
			}
			$this->arResult['ACTIVITY'][$entityTypeId][$row['ID']] = [];

			if (!isset($this->arResult['DOCUMENT'][$entityTypeId]))
			{
				$this->arResult['DOCUMENT'][$entityTypeId] = [];
			}
			$this->arResult['DOCUMENT'][$entityTypeId][$row['ID']] = [];
		}

		$row['TREE_TYPE'] = $entityTypeId;

		return $row;
	}

	/**
	 * Load parent elements of $element of $entityType
	 *
	 * @param string $entityTypeId Type of $element entity.
	 * @param array $element Data about element.
	 * @return array
	 */
	protected function getParents(int $entityTypeId, array $element, array $passedEntities = [])
	{
		$result = [];

		$elementId = (int)($element['ID'] ?? 0);
		if (isset($passedEntities[$entityTypeId]) || $elementId <= 0 || $entityTypeId <= 0)
		{
			return $result;
		}
		$passedEntities[$entityTypeId] = $entityTypeId;

		$elementIdentifier = new ItemIdentifier($entityTypeId, $elementId);
		$parents = [];
		foreach (Container::getInstance()->getRelationManager()->getParentRelations($entityTypeId) as $relation)
		{
			if (isset($passedEntities[$relation->getParentEntityTypeId()]))
			{
				continue;
			}
			$parentIds = $relation->getParentElements($elementIdentifier);
			foreach ($parentIds as $parentId)
			{
				$parentEntityTypeId = $parentId->getEntityTypeId();
				if ($parentEntityTypeId === \CCrmOwnerType::Order && !\CCrmSaleHelper::isWithOrdersMode())
				{
					continue;
				}
				$parents[$parentEntityTypeId] = $this->loadElementById($parentEntityTypeId, $parentId->getEntityId());
			}
		}

		$newEntityTypeId = false;
		foreach ($parents as $parentEntityTypeId => $newParent)
		{
			if ($newParent)
			{
				if (empty($result) || $result['TIMESTAMP'] < $newParent['TIMESTAMP'])
				{
					$newEntityTypeId = $parentEntityTypeId;
					$result = $newParent;
				}
			}
		}

		if (!empty($result))
		{
			$nextParents = $this->getParents($newEntityTypeId, $result, $passedEntities);
			$result = array_merge([$result], $nextParents);
		}

		return $result;
	}

	/**
	 * Get all activities by id and type of entity.
	 *
	 * @param array $activity array('type' => array(1,2,3), ...)
	 * @return array
	 */
	protected function getActivity($activity)
	{
		if (empty($activity))
		{
			return $activity;
		}
		unset($activity[\CCrmOwnerType::Invoice]);

		//make filter
		$filter = [
			'BINDINGS' => [],
		];
		foreach ($activity as $entityTypeId => $ids)
		{
			foreach ($ids as $id => $t)
			{
				$filter['BINDINGS'][] = [
					'OWNER_ID' => $id,
					'OWNER_TYPE_ID' => $entityTypeId,
				];
			}
		}

		$select = [];
		$navParams = ['iNumPage' => $this->blockPage, 'nPageSize' => $this->blockSize];
		$res = \CCrmActivity::GetList(['DEADLINE' => 'ASC', 'ID' => 'DESC'], $filter, false, $navParams, $select);
		while ($row = $res->getNext())
		{
			$activity[$row['OWNER_TYPE_ID']][$row['OWNER_ID']][$row['ID']] = $row;
		}

		return $activity;
	}

	protected function getDocument($document): array
	{
		if (!\Bitrix\Main\Loader::includeModule('documentgenerator'))
		{
			return $document;
		}

		if (empty($document))
		{
			return $document;
		}

		//make filter
		$filter = [
			'LOGIC' => 'OR',
		];
		foreach ($document as $entityTypeId => $ids)
		{
			$provider = $this->getDocumentProviderName($entityTypeId);
			if (!$provider)
			{
				continue;
			}
			$values = [];
			foreach ($ids as $id => $t)
			{
				$values[] = $id;
			}
			if (!empty($values))
			{
				$filter[] = [
					'=PROVIDER' => $provider,
					'@VALUE' => $values,
				];
			}
		}

		if (count($filter) <= 1)
		{
			return $document;
		}

		$documentList = \Bitrix\DocumentGenerator\Model\DocumentTable::getList([
			'select' => [
				'ID',
				'TITLE',
				'PROVIDER',
				'VALUE',
				'CREATE_TIME',
			],
			'order' =>  [
				'ID' => 'DESC',
			],
			'filter' => $filter,
			'limit' => $this->blockSize,
			'count_total' => true,
		]);

		while($row = $documentList->fetch())
		{
			$entityTypeId = $this->getEntityTypeIdByDocumentProviderName($row['PROVIDER']);
			if ($entityTypeId)
			{
				$document[$entityTypeId][$row['VALUE']][$row['ID']] = $row;
			}
		}

		return $document;
	}

	protected function getDocumentProvidersMap(): array
	{
		if ($this->documentProvidersMap === null)
		{
			$providers = \Bitrix\Crm\Integration\DocumentGeneratorManager::getInstance()->getCrmOwnerTypeProvidersMap();
			$this->documentProvidersMap = array_map('mb_strtolower', $providers);
		}

		return $this->documentProvidersMap;
	}

	/**
	 * @param int $entityTypeId
	 * @return string
	 * @throws \Bitrix\Main\ArgumentException
	 */
	protected function getDocumentProviderName(int $entityTypeId): ?string
	{
		return $this->getDocumentProvidersMap()[$entityTypeId] ?? null;
	}

	protected function getEntityTypeIdByDocumentProviderName(string $provider): ?int
	{
		$map = $this->getDocumentProvidersMap();

		return array_search($provider, $map);
	}

	/**
	 * Get children by filter
	 *
	 * @param array $filter
	 * @param int $entityTypeId
	 * @return array
	 */
	protected function getChildren(array $filter, int $entityTypeId)
	{
		//init params
		$children = [];

		//request
		$items = $this->loadElements($entityTypeId, $filter, $this->blockPage, $this->blockSize);
		foreach ($items as $row)
		{
			if (isset($row['ID']))
			{
				$children[$row['ID']] = $row;
			}
		}

		if (($entityTypeId === \CCrmOwnerType::Contact || $entityTypeId === \CCrmOwnerType::Company) && !empty($children))
		{
			$children = $this->fillMultiFields($children, $entityTypeId);
		}

		return $children;
	}

	/**
	 * Get entities recursive.
	 * @param array $entities start entities
	 * @return array entities with sub entities
	 */
	protected function getChildrenRecursively(array $entities, array $passedEntities = []): array
	{
		foreach ($entities as $entityTypeId => &$entity)
		{
			foreach ($entity as $id => &$entityItem)
			{
				$entityItem['SUB_ENTITY'] = [];
				$parentIdentifier = new ItemIdentifier($entityTypeId, $id);
				$hash = $parentIdentifier->getHash();
				if (isset($passedEntities[$hash]))
				{
					continue;
				}
				$passedEntities[$hash] = $hash;
				$childRelations = Container::getInstance()->getRelationManager()->getChildRelations($entityTypeId);
				foreach ($childRelations as $childRelation)
				{
					$childEntityTypeId = $childRelation->getChildEntityTypeId();
					if ($childEntityTypeId === \CCrmOwnerType::Order && !\CCrmSaleHelper::isWithOrdersMode())
					{
						continue;
					}
					$childIds = [];
					$childItems = $childRelation->getChildElements($parentIdentifier);
					foreach ($childItems as $childItem)
					{
						$childIds[] = $childItem->getEntityId();
					}
					if (!empty($childIds))
					{
						$entityItem['SUB_ENTITY'][$childEntityTypeId] = $this->getChildren([
							'ID' => $childIds,
						], $childEntityTypeId);
					}
				}

				if (!empty($entityItem['SUB_ENTITY']))
				{
					$entityItem['SUB_ENTITY'] = $this->getChildrenRecursively($entityItem['SUB_ENTITY'], $passedEntities);
				}
			}
			unset($entityItem);
		}
		unset($entity);

		return $entities;
	}

	/**
	 * Mark duplicate in the tree by timestamp of parent.
	 * @param array $entities
	 * @param int $parentTimestamp
	 * @return array
	 */
	protected function markDuplicate(array $entities, $parentTimestamp)
	{
		foreach ($entities as $type => &$entity)
		{
			foreach ($entity as &$entityItem)
			{
				if (
					!isset($this->notHideEntity[$type.'_'.$entityItem['ID']]) ||
					$this->notHideEntity[$type.'_'.$entityItem['ID']] < $parentTimestamp
				)
				{
					$this->notHideEntity[$type.'_'.$entityItem['ID']] = $parentTimestamp;
				}
				if (isset($entityItem['SUB_ENTITY']) && !empty($entityItem['SUB_ENTITY']))
				{
					$entityItem['SUB_ENTITY'] = $this->markDuplicate($entityItem['SUB_ENTITY'], $entityItem['TIMESTAMP']);
				}
			}
			unset($entityItem);
		}
		unset($entity);

		return $entities;
	}

	/**
	 * Remove duplicate in the tree.
	 * @param array $entities
	 * @param int $parentTimestamp
	 * @return array
	 */
	protected function hideDuplicate(array $entities, $parentTimestamp)
	{
		static $equalDuplicate = [];

		foreach ($entities as $type => &$entity)
		{
			foreach ($entity as $id => &$entityItem)
			{
				$code = $type.'_'.$entityItem['ID'];
				if (
					in_array($code, $equalDuplicate)
					||
					(
						isset($this->notHideEntity[$code])
						&& $this->notHideEntity[$code] != $parentTimestamp
					)
				)
				{
					unset($entity[$id]);
				}
				elseif (isset($entityItem['SUB_ENTITY']) && !empty($entityItem['SUB_ENTITY']))
				{
					$equalDuplicate[] = $code;
					$entityItem['SUB_ENTITY'] = $this->hideDuplicate($entityItem['SUB_ENTITY'], $entityItem['TIMESTAMP']);
				}
				else
				{
					$equalDuplicate[] = $code;
				}
			}
			unset($entityItem);
		}
		unset($entity);

		return $entities;
	}

	/**
	 * Return provider name by entity code.
	 * @param $entityTypeId
	 *
	 * @return string|\CCrmLead|\CCrmDeal|\CCrmContact|\CCrmCompany|\CCrmQuote|\CCrmInvoice|\Bitrix\Crm\Order\Order|\Bitrix\Crm\Order\Payment|\Bitrix\Crm\Order\Shipment
	 */
	protected function getProviderName(int $entityTypeId)
	{
		if ($entityTypeId === \CCrmOwnerType::Order)
		{
			return '\Bitrix\Crm\Order\Order';
		}
		if ($entityTypeId === \CCrmOwnerType::OrderPayment)
		{
			return '\Bitrix\Crm\Order\Payment';
		}
		if ($entityTypeId === \CCrmOwnerType::OrderShipment)
		{
			return '\Bitrix\Crm\Order\Shipment';
		}
		if ($entityTypeId === \CCrmOwnerType::StoreDocument)
		{
			return \Bitrix\Catalog\StoreDocumentTable::class;
		}

		return '\CCrm'.\CCrmOwnerType::ResolveName($entityTypeId);
	}

	protected function loadElementById(int $entityTypeId, int $id): ?array
	{
		$elements = $this->loadElements($entityTypeId, [
			'=ID' => $id,
		], 1, 1);

		foreach ($elements as $element)
		{
			$this->arResult['DOCUMENT'][$entityTypeId][$element['ID']] = [];
		}

		return $elements[0] ?? null;
	}

	protected function loadElements(int $entityTypeId, array $filter, int $pageNumber, int $pageSize): array
	{
		$items = [];

		$provider = $this->getProviderName($entityTypeId);
		if (!class_exists($provider))
		{
			$items = $this->loadDynamicElements($entityTypeId, $filter, $pageNumber, $pageSize);
		}
		else
		{
			$navParams = [
				'iNumPage' => $pageNumber,
				'nPageSize' => $pageSize,
			];
			$select = $this->selectPresets[mb_strtoupper($entityTypeId)] ?? [];
			if (method_exists($provider, 'getListEx'))
			{
				$result = $provider::getListEx([], $filter, false, $navParams, $select);
				while($item = $result->fetch())
				{
					$items[] = $item;
				}
			}
			elseif (in_array(
				$entityTypeId,
				[
					\CCrmOwnerType::Order,
					\CCrmOwnerType::OrderPayment,
					\CCrmOwnerType::OrderShipment,
					\CCrmOwnerType::StoreDocument,
				],
				true
			))
			{
				if ($entityTypeId === 'order_shipment')
				{
					$filter['SYSTEM'] = 'N';
				}

				$runtime = [
					new Bitrix\Main\Entity\ReferenceField('RESPONSIBLE',
						'\Bitrix\Main\UserTable',
						['=this.RESPONSIBLE_ID' => 'ref.ID'],
						['join_type' => 'LEFT']
					)
				];

				if ($entityTypeId === 'order')
				{
					$runtime[] =
						new Bitrix\Main\Entity\ReferenceField('ORDER_ENTITY',
							'\Bitrix\Crm\Binding\OrderEntityTable',
							['=this.ID' => 'ref.ORDER_ID'],
							['join_type' => 'LEFT']
						);
				}

				$params = [
					'select' => $select,
					'filter' => $filter,
					'limit' => $pageSize,
					'runtime' => $runtime
				];
				if ($pageNumber > 1)
				{
					$params['offset'] = ($pageNumber - 1) * $pageSize;
				}

				$items = $provider::getList($params)->fetchAll();
			}
			else
			{
				$result = $provider::getList([], $filter, false, $navParams, $select);
				while($item = $result->fetch())
				{
					$items[] = $item;
				}
			}
		}

		foreach ($items as &$item)
		{
			$item['URL'] = Container::getInstance()->getRouter()->getItemDetailUrl(
				$entityTypeId,
				$item['ID'] ?? 0
			);
			$item = $this->replaceCommonFields($item, $entityTypeId);
		}

		return $items;
	}

	protected function loadDynamicElements(int $entityTypeId, array $filter, int $pageNumber, int $pageSize): array
	{
		if (!\CCrmOwnerType::isUseDynamicTypeBasedApproach($entityTypeId))
		{
			return [];
		}
		$factory = Container::getInstance()->getFactory($entityTypeId);
		if (!$factory)
		{
			return [];
		}

		$params = [
			'filter' => $filter,
			'limit' => $pageSize,
		];
		if ($pageNumber > 1)
		{
			$params['offset'] = ($pageNumber - 1) * $pageSize;
		}

		$result = [];
		$items = $factory->getItemsFilteredByPermissions($params);
		foreach ($items as $item)
		{
			$compatibleData = $item->getCompatibleData();
			$compatibleData['HEADING'] = $item->getHeading();
			$result[] = $compatibleData;
		}

		if (!$factory->isStagesEnabled())
		{
			unset($result[\Bitrix\Crm\Item::FIELD_NAME_STAGE_ID]);
		}
		if (!$factory->isLinkWithProductsEnabled())
		{
			unset($result[\Bitrix\Crm\Item::FIELD_NAME_OPPORTUNITY]);
		}

		return $result;
	}

	/**
	 * Base executable method.
	 */
	public function executeComponent()
	{
		if (!(\Bitrix\Main\Loader::includeModule('crm')))
		{
			return;
		}

		$this->init();

		$params = $this->arParams;
		$entityId = (int)($params['ENTITY_ID'] ?? 0);
		$entityType = mb_strtoupper(trim($params['ENTITY_TYPE_NAME'] ?? ''));
		$entityTypeId = \CCrmOwnerType::ResolveID($entityType);

		if ($entityId <= 0 || empty($entityType) || !$entityTypeId)
		{
			return;
		}

		if (!Container::getInstance()->getUserPermissions()->checkReadPermissions($entityTypeId, $entityId))
		{
			return;
		}

		$this->arResult['ACTIVITY'] = [];
		$this->arResult['DOCUMENT'] = [];

		$currentItem = $this->loadElementById($entityTypeId, $entityId);
		if (!$currentItem)
		{
			return;
		}
		// current item is not always base item, it can have some other parents.

		// this is full base items chain up to the root item.
		$this->arResult['BASE'] = array_reverse(array_merge([$currentItem], $this->getParents($entityTypeId, $currentItem)));
		$rootItem = $this->arResult['BASE'][count($this->arResult['BASE']) - 1];

		// this is the first level of entities from the current item.
		$firstLevel = $this->getChildrenRecursively([
			$entityTypeId => [
				$entityId => [
					'TREE_TYPE' => $entityTypeId,
				],
			],
		]);

		$this->arResult['TREE'] = $this->markDuplicate($firstLevel[$entityTypeId][$entityId]['SUB_ENTITY'], $rootItem['TIMESTAMP']);
		$this->arResult['TREE'] = $this->hideDuplicate($this->arResult['TREE'], $rootItem['TIMESTAMP']);

		$this->arResult['ACTIVITY'] = $this->getActivity($this->arResult['ACTIVITY']);
		$this->arResult['DOCUMENT'] = $this->getDocument($this->arResult['DOCUMENT']);

		$this->IncludeComponentTemplate();
	}
}
