<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

class CrmEntityTreeComponent extends \CBitrixComponent
{
	protected $blockPage = 1;
	protected $blockSize = 1000;
	protected $nameTemplate = '';
	protected $codes = array();
	protected $notHideEntity = array();
	protected $logoSizes = array('width' => 50, 'height' => 50);
	protected $pathMarkers = array(
		'#lead_id#', '#contact_id#', '#company_id#',
		'#deal_id#', '#quote_id#', '#invoice_id#', '#order_id#',
		'#payment_id#', '#shipment_id#'
	);
	protected $selectPresets = array(
								'company' => array('ID', 'DATE_CREATE', 'TITLE', 'COMPANY_TYPE', 'LOGO'),
								'contact' => array('ID', 'DATE_CREATE', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'COMPANY_ID',
													'COMPANY_TITLE', 'DATE_CREATE', 'PHOTO'),
								'deal' => array('ID', 'DATE_CREATE', 'TITLE', 'OPPORTUNITY', 'CURRENCY_ID', 'STAGE_ID', 'BEGINDATE',
													'CATEGORY_ID', 'ASSIGNED_BY_LOGIN', 'ASSIGNED_BY_NAME', 'ASSIGNED_BY_LAST_NAME',
													'ASSIGNED_BY_SECOND_NAME', 'ASSIGNED_BY_ID'),
								'quote' => array('ID', 'BEGINDATE', 'TITLE', 'STATUS_ID', 'ASSIGNED_BY_ID', 'ASSIGNED_BY_LOGIN',
													'ASSIGNED_BY_NAME', 'ASSIGNED_BY_LAST_NAME', 'ASSIGNED_BY_SECOND_NAME', 'CLOSEDATE',
													'DATE_CREATE'),
								'invoice' => array('ID', 'DATE_INSERT_FORMAT', 'ORDER_TOPIC', 'ACCOUNT_NUMBER', 'PRICE', 'DATE_BILL',
													'CURRENCY', 'STATUS_ID', 'DATE_PAYED', 'RESPONSIBLE_ID', 'RESPONSIBLE_LOGIN',
													'RESPONSIBLE_NAME', 'RESPONSIBLE_LAST_NAME', 'RESPONSIBLE_SECOND_NAME'),
								'order' => array('ID', 'DATE_INSERT_FORMAT', 'DATE_INSERT', 'ORDER_TOPIC', 'ACCOUNT_NUMBER', 'PRICE',
													'CURRENCY', 'STATUS_ID', 'DATE_PAYED', 'RESPONSIBLE_ID', 'RESPONSIBLE_LOGIN' => 'RESPONSIBLE.LOGIN',
													'RESPONSIBLE_NAME' => 'RESPONSIBLE.NAME','RESPONSIBLE_LAST_NAME' => 'RESPONSIBLE.LAST_NAME',
													'RESPONSIBLE_SECOND_NAME' => 'RESPONSIBLE.SECOND_NAME', 'PAY_SYSTEM_ID', 'DATE_BILL'),
								'order_payment' => array('ID', 'ACCOUNT_NUMBER', 'PRICE' => 'SUM', 'DATE_BILL', 'CURRENCY', 'PAID',
													'DATE_PAID', 'RESPONSIBLE_ID', 'RESPONSIBLE_LOGIN' => 'RESPONSIBLE.LOGIN',
													'RESPONSIBLE_NAME' => 'RESPONSIBLE.NAME','RESPONSIBLE_LAST_NAME' => 'RESPONSIBLE.LAST_NAME',
													'RESPONSIBLE_SECOND_NAME' => 'RESPONSIBLE.SECOND_NAME', 'PAY_SYSTEM_ID',
													'ORDER_TOPIC' => 'PAY_SYSTEM.NAME' , 'ORDER_ID'),
								'order_shipment' => array('ID','ACCOUNT_NUMBER', 'PRICE' => 'PRICE_DELIVERY', 'DATE_INSERT', 'CURRENCY', 'DEDUCTED',
													'DATE_DEDUCTED', 'DELIVERY_ID', 'RESPONSIBLE_ID', 'RESPONSIBLE_LOGIN' => 'RESPONSIBLE.LOGIN',
													'RESPONSIBLE_NAME' => 'RESPONSIBLE.NAME','RESPONSIBLE_LAST_NAME' => 'RESPONSIBLE.LAST_NAME',
													'RESPONSIBLE_SECOND_NAME' => 'RESPONSIBLE.SECOND_NAME', 'ORDER_TOPIC' => 'DELIVERY.NAME',
													'DATE_INSERT_FORMAT' => 'DATE_INSERT_SHORT', 'STATUS_ID', 'ORDER_ID'),
	);

	/**
	 * Init class' vars.
	 */
	protected function init()
	{
		$this->arParams['STATUSES'] = $this->getStatuses();
		$this->arParams['BLOCK_SIZE'] = $this->blockSize;
		$this->arParams['TYPES'] = $this->codes = array(
			'lead' => \CCrmOwnerType::LeadName,
			'contact' => \CCrmOwnerType::ContactName,
			'company' => \CCrmOwnerType::CompanyName,
			'deal' => \CCrmOwnerType::DealName,
			'quote' => \CCrmOwnerType::QuoteName,
			'invoice' => \CCrmOwnerType::InvoiceName,
			'order' => \CCrmOwnerType::OrderName,
			'order_payment' => \CCrmOwnerType::OrderPaymentName,
			'order_shipment' => \CCrmOwnerType::OrderShipmentName
		);
		$this->arParams['PATH_TO_USER_PROFILE'] = \CrmCheckPath('PATH_TO_USER_PROFILE',
																$this->arParams['PATH_TO_USER_PROFILE'],
																'/company/personal/user/#user_id#/');
		$this->nameTemplate = \CSite::GetNameFormat(false);
		if (isset($this->arParams['BLOCK_PAGE']) && $this->arParams['BLOCK_PAGE'] > 0)
		{
			$this->blockPage = $this->arParams['BLOCK_PAGE'];
		}
		$newPresets = array();
		foreach ($this->selectPresets as $code => $preset)
		{
			$newPresets[$this->codes[$code]] = $preset;
		}
		$this->selectPresets = $newPresets;
	}

	/**
	 * Format user name.
	 * @staticvar array $users
	 * @param array $user
	 * @param string $prefix for ID's key
	 * @return string formatted name
	 */
	protected function formatUserName($user, $prefix='ASSIGNED_BY')
	{
		static $users = array();

		if (!$user[$prefix . '_ID'])
		{
			return '';
		}

		if (!isset($users[$user[$prefix . '_ID']]))
		{
			$users[$user[$prefix . '_ID']] = \CUser::FormatName(
				$this->nameTemplate,
				array(
					'LOGIN' => $user[$prefix . '_LOGIN'],
					'NAME' => $user[$prefix . '_NAME'],
					'LAST_NAME' => $user[$prefix . '_LAST_NAME'],
					'SECOND_NAME' => $user[$prefix . '_SECOND_NAME']
				),
				true, false
			);
		}

		return $users[$user[$prefix . '_ID']];
	}

	/**
	 * Get multi-fields for entity (phone, email, etc).
	 * @param array $items
	 * @param string $contragent
	 * @return filled array
	 */
	protected function fillFMfields(array $items, $contragent)
	{
		$isOneElement = false;

		if (!empty($items))
		{
			if (isset($items['ID']))
			{
				$isOneElement = true;
				$items = array($items['ID'] => $items);
			}
			$res = \CCrmFieldMulti::GetListEx(array(), array(
															'ENTITY_ID' => $contragent,
															'ELEMENT_ID' => array_keys($items)));
			while ($row = $res->fetch())
			{
				if (!isset($items[$row['ELEMENT_ID']]['FM']))
				{
					$items[$row['ELEMENT_ID']]['FM'] = array();
					$items[$row['ELEMENT_ID']]['FM_VALUES'] = array();
				}
				if (!isset($items[$row['ELEMENT_ID']]['FM'][$row['TYPE_ID']]))
				{
					$items[$row['ELEMENT_ID']]['FM'][$row['TYPE_ID']] = array();
					$items[$row['ELEMENT_ID']]['FM_VALUES'][$row['TYPE_ID']] = array();
				}
				$items[$row['ELEMENT_ID']]['FM'][$row['TYPE_ID']][] = $row;
				$items[$row['ELEMENT_ID']]['FM_VALUES'][$row['TYPE_ID']][] = htmlspecialcharsbx($row['VALUE']);
			}
		}

		return $isOneElement ? array_pop($items) : $items;
	}

	/**
	 * Get all CRM statuses, stages, etc.
	 * @return array
	 */
	protected function getStatuses()
	{
		$statuses = array();
		$semantic = \CCrmStatus::GetEntityTypes();

		$semantic[\Bitrix\Crm\Order\OrderStatus::NAME] = \Bitrix\Crm\Order\OrderStatus::getSemanticInfo();
		$semantic[\Bitrix\Crm\Order\DeliveryStatus::NAME] = \Bitrix\Crm\Order\DeliveryStatus::getSemanticInfo();

		$colors = array(
			'QUOTE_STATUS' => (array)unserialize(\Bitrix\Main\Config\Option::get('crm', 'CONFIG_STATUS_QUOTE_STATUS')),
			'INVOICE_STATUS' => (array)unserialize(\Bitrix\Main\Config\Option::get('crm', 'CONFIG_STATUS_INVOICE_STATUS')),
			'ORDER_STATUS' => (array)unserialize(\Bitrix\Main\Config\Option::get('crm', 'CONFIG_STATUS_ORDER_STATUS')),
			'ORDER_SHIPMENT_STATUS' => (array)unserialize(\Bitrix\Main\Config\Option::get('crm', 'CONFIG_STATUS_ORDER_SHIPMENT_STATUS')),
			'STATUS' => (array)unserialize(\Bitrix\Main\Config\Option::get('crm', 'CONFIG_STATUS_STATUS')),
			'DEAL_STAGE' => (array)unserialize(\Bitrix\Main\Config\Option::get('crm', 'CONFIG_STATUS_DEAL_STAGE')),
		);
		foreach (\Bitrix\Crm\Category\DealCategory::getList(array())->fetchAll() as $cat)
		{
			$colors['DEAL_STAGE_' . $cat['ID']] = (array)unserialize(\Bitrix\Main\Config\Option::get('crm', 'CONFIG_STATUS_DEAL_STAGE_' . $cat['ID']));
		}


		$res = \CCrmStatus::GetList(array('SORT' => 'ASC'));
		while ($row = $res->getNext())
		{
			if (!in_array($row['ENTITY_ID'], array('STATUS', 'QUOTE_STATUS', 'INVOICE_STATUS',
													'COMPANY_TYPE', 'DEAL_STAGE', 'SOURCE'))
				&& !isset($colors[$row['ENTITY_ID']])
			)
			{
				continue;
			}
			if (!isset($statuses[$row['ENTITY_ID']]))
			{
				$statuses[$row['ENTITY_ID']] = array();
			}
			$row['COLOR'] = !empty($colors[$row['ENTITY_ID']]) && isset($colors[$row['ENTITY_ID']][$row['STATUS_ID']])
							? $colors[$row['ENTITY_ID']][$row['STATUS_ID']]['COLOR']
							: '';
			if (isset($semantic[$row['ENTITY_ID']]) && isset($semantic[$row['ENTITY_ID']]['SEMANTIC_INFO'])
				&& $semantic[$row['ENTITY_ID']]['SEMANTIC_INFO']['FINAL_SORT'] == 0 &&
				(
					$semantic[$row['ENTITY_ID']]['SEMANTIC_INFO']['FINAL_SUCCESS_FIELD'] == $row['STATUS_ID'] ||
					$semantic[$row['ENTITY_ID']]['SEMANTIC_INFO']['FINAL_UNSUCCESS_FIELD'] == $row['STATUS_ID']
				)
			)
			{
				$semantic[$row['ENTITY_ID']]['SEMANTIC_INFO']['FINAL_SORT'] = $row['SORT'];
			}
			$statuses[$row['ENTITY_ID']][$row['STATUS_ID']] = $row;
		}

		$orderStatus = \Bitrix\Crm\Order\OrderStatus::getListInCrmFormat();
		if ($orderStatus)
		{
			foreach ($orderStatus as $row)
			{
				$row['COLOR'] = !empty($colors[$row['ENTITY_ID']]) && isset($colors[$row['ENTITY_ID']][$row['STATUS_ID']])
					? $colors[$row['ENTITY_ID']][$row['STATUS_ID']]['COLOR']
					: '';

				$statuses[$row['ENTITY_ID']][$row['STATUS_ID']] = $row;
			}
		}

		$shipmentStatus = \Bitrix\Crm\Order\DeliveryStatus::getListInCrmFormat();
		if ($shipmentStatus)
		{
			foreach ($shipmentStatus as $row)
			{
				$row['COLOR'] = !empty($colors[$row['ENTITY_ID']]) && isset($colors[$row['ENTITY_ID']][$row['STATUS_ID']])
					? $colors[$row['ENTITY_ID']][$row['STATUS_ID']]['COLOR']
					: '';

				$statuses[$row['ENTITY_ID']][$row['STATUS_ID']] = $row;
			}
		}

		//range statuses
		foreach ($statuses as $id => &$entity)
		{
			$count = 1;
			$chunk = 1;
			$finalSort = isset($semantic[$id]) && isset($semantic[$id]['SEMANTIC_INFO'])
						? $semantic[$id]['SEMANTIC_INFO']['FINAL_SORT']
						: 0;
			foreach ($entity as &$status)
			{
				if ($finalSort > $status['SORT'])
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
	 * Get path for entity from params or module settings.
	 * @param string $type
	 * @return string
	 */
	protected function getEntityPath($type)
	{
		$params = $this->arParams;
		$enableSlider = \CCrmOwnerType::IsSliderEnabled(\CCrmOwnerType::ResolveID($type));

		$pathKey = 'PATH_TO_'.strtoupper($type).($enableSlider ? '_DETAILS' : '_SHOW');
		$url = !array_key_exists($pathKey, $params) || $params[$pathKey] == ''
				? \CrmCheckPath($pathKey, '', '')
				: $params[$pathKey];
		if (isset($params['ADDITIONAL_PARAMS']) && $params['ADDITIONAL_PARAMS'])
		{
			$url .= ((strpos($url, '?') === false) ? '?' : '&') . $params['ADDITIONAL_PARAMS'];
		}

		return $url;
	}

	/**
	 * Build additional fields for each item.
	 * @param array $row
	 * @param string $entityCode
	 * @return array
	 */
	protected function buildItemExtra(array $row, $entityCode)
	{
		if (empty($row))
		{
			return $row;
		}
		if (isset($row['URL']))
		{
			$row['URL'] = str_replace($this->pathMarkers, $row['ID'], $row['URL']);
		}
		if (isset($row['ASSIGNED_BY_ID']) && $row['ASSIGNED_BY_ID'] > 0)
		{
			$row['ASSIGNED_BY_FORMATTED_NAME'] = $this->formatUserName($row, 'ASSIGNED_BY');
			$row['ASSIGNED_BY_URL'] = \CComponentEngine::MakePathFromTemplate(
				$this->arParams['PATH_TO_USER_PROFILE'],
				array('user_id' => $row['ASSIGNED_BY'])
			);
		}
		elseif (isset($row['RESPONSIBLE_ID']) && $row['RESPONSIBLE_ID'] > 0)
		{
			$row['RESPONSIBLE_FORMATTED_NAME'] = $this->formatUserName($row, 'RESPONSIBLE');
			$row['RESPONSIBLE_URL'] = \CComponentEngine::MakePathFromTemplate(
				$this->arParams['PATH_TO_USER_PROFILE'],
				array('user_id' => $row['RESPONSIBLE'])
			);
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

		//for get activity
		if (isset($row['ID']))
		{
			if (!isset($this->arResult['ACTIVITY'][$entityCode]))
			{
				$this->arResult['ACTIVITY'][$entityCode] = array();
			}
			$this->arResult['ACTIVITY'][$entityCode][$row['ID']] = array();
		}

		$row['TREE_TYPE'] = $entityCode;

		return $row;
	}

	/**
	 * Get parent entity.
	 * @param array $parent array of current entity
	 * @param string $entityCode
	 * @param string $key key of parrent with ID
	 * @return array|boolean false if no exsists
	 */
	protected function addChainItem(array $parent, $entityCode, $key=false)
	{
		$entityCode = strtolower($entityCode);
		$provider = $this->getProviderName($entityCode);
		if ($key === false)
		{
			$key = strtoupper($entityCode).'_ID';
		}

		if (!$parent[$key] || !class_exists($provider))
		{
			return false;
		}


		if (method_exists($provider, 'getListEx'))
		{
			$parent = $provider::getListEx(array(), array('=ID' => $parent[$key]))->getNext();
		}
		elseif (in_array($entityCode, array('order', 'order_payment', 'order_shipment')))
		{
			$params = array(
				'filter' => array('=ID' => $parent[$key]),
				'runtime' => array(
					new Bitrix\Main\Entity\ReferenceField('RESPONSIBLE',
						'\Bitrix\Main\UserTable',
						array('=this.RESPONSIBLE_ID' => 'ref.ID'),
						array('join_type' => 'LEFT')
					)
				),
				'limit' => 1
			);
			if (isset($this->selectPresets[strtoupper($entityCode)]))
			{
				$params['select'] = $this->selectPresets[strtoupper($entityCode)];
			}
			$parent = $provider::getList($params)->fetch(\Bitrix\Main\Text\Converter::getHtmlConverter());
		}
		else
		{
			$parent = $provider::getList(array(), array('=ID' => $parent[$key]))->getNext();
		}
		if ($parent)
		{
			$parent = $this->buildItemExtra($parent, $this->codes[$entityCode]);
			$parent = $this->fillFMfields($parent, $entityCode);
			$parent['URL'] = str_replace($this->pathMarkers, $parent['ID'], $this->getEntityPath($entityCode));

			return $parent;
		}

		return false;
	}


	/**
	 * Get nav chain with base and parents entities.
	 * @param string $type Type of entity.
	 * @param array $parent Parent entity.
	 * @return array
	 */
	protected function getBaseChainEx($type, array $parent)
	{
		$resultChain = array();
		$parents = array(
			'lead' => false,
			'deal' => false,
			'contact' => false,
			'company' => false,
			'quote' => false,
			'invoice' => false,
			'order' => false
		);

		switch ($type)
		{
			case $this->codes['lead']:
				break;
			case $this->codes['deal']:
				$parents['lead'] = $this->addChainItem($parent, $this->codes['lead']);
				$parents['quote'] = $this->addChainItem($parent, $this->codes['quote']);
				$parents['contact'] = $this->addChainItem($parent, $this->codes['contact']);
				$parents['company'] = $this->addChainItem($parent, $this->codes['company']);
				break;
			case $this->codes['company']:
				$parents['lead'] = $this->addChainItem($parent, $this->codes['lead']);
				$parents['deal'] = $this->addChainItem($parent, $this->codes['deal']);
				break;
			case $this->codes['contact']:
				$parents['lead'] = $this->addChainItem($parent, $this->codes['lead']);
				$parents['deal'] = $this->addChainItem($parent, $this->codes['deal']);
				$parents['company'] = $this->addChainItem($parent, $this->codes['company']);
				break;
			case $this->codes['invoice']:
				$parents['deal'] = $this->addChainItem($parent, $this->codes['deal'], 'UF_DEAL_ID');
				$parents['quote'] = $this->addChainItem($parent, $this->codes['quote'], 'UF_QUOTE_ID');
				$parents['contact'] = $this->addChainItem($parent, $this->codes['contact'], 'UF_CONTACT_ID');
				$parents['company'] = $this->addChainItem($parent, $this->codes['company'], 'UF_COMPANY_ID');
				break;
			case $this->codes['quote']:
				$parents['lead'] = $this->addChainItem($parent, $this->codes['lead']);
				$parents['deal'] = $this->addChainItem($parent, $this->codes['deal']);
				$parents['contact'] = $this->addChainItem($parent, $this->codes['contact']);
				$parents['company'] = $this->addChainItem($parent, $this->codes['company']);
				break;
			case $this->codes['order']:
				$parents['order_shipment'] = $this->addChainItem($parent, $this->codes['order_shipment']);
				$parents['order_payment'] = $this->addChainItem($parent, $this->codes['order_payment']);
				break;
			case $this->codes['order_payment']:
				$parents['order'] = $this->addChainItem($parent, $this->codes['order']);
				$parents['order_payment'] = $this->addChainItem($parent, $this->codes['order_payment']);
				break;
			case $this->codes['order_shipment']:
				$parents['order'] = $this->addChainItem($parent, $this->codes['order']);
				$parents['order_shipment'] = $this->addChainItem($parent, $this->codes['order_shipment']);
				break;
		}

		$newType = false;
		foreach ($parents as $t => $newParent)
		{
			if ($newParent)
			{
				if (empty($resultChain) || $resultChain['TIMESTAMP'] < $newParent['TIMESTAMP'])
				{
					$newType = $t;
					$resultChain = $newParent;
				}
			}
		}

		if (!empty($resultChain))
		{
			$newResultChain = $this->getBaseChainEx($this->codes[$newType], $resultChain);
			$resultChain = array_merge(array($resultChain), $newResultChain);
		}


		return $resultChain;
	}

	/**
	 * Get all activities by id and type of entity.
	 * @param array $activity array('type' => array(1,2,3), ...)
	 * @return array
	 */
	protected function getActivity($activity)
	{
		if (empty($activity))
		{
			return $activity;
		}
		if (isset($activity[$this->codes['invoice']]))
		{
			unset($activity[$this->codes['invoice']]);
		}
		if (isset($activity[$this->codes['quote']]))
		{
			unset($activity[$this->codes['quote']]);
		}

		$resolves = array();

		//make filter
		$filter = array(
				'BINDINGS' => array()
			);
		foreach ($activity as $type => $ids)
		{
			$typeId = \CCrmOwnerType::ResolveID($type);
			$resolves[$typeId] = $type;
			foreach ($ids as $id => $t)
			{
				$filter['BINDINGS'][] = array(
					'OWNER_ID' => $id,
					'OWNER_TYPE_ID' => $typeId,
				);
			}
		}

		$select = array();
		$navParams = false;//array('iNumPage' => $this->blockPage, 'nPageSize' => $this->blockSize);
		$res = \CCrmActivity::GetList(array('DEADLINE' => 'ASC', 'ID' => 'DESC'), $filter, false, $navParams, $select, array());
		while ($row = $res->getNext())
		{
			$activity[$resolves[$row['OWNER_TYPE_ID']]][$row['OWNER_ID']][$row['ID']] = $row;
		}

		return $activity;
	}

	/**
	 * Get subentity for entity.
	 * @param array $filter
	 * @param string $entityCode
	 * @return array
	 */
	protected function getSubEntity(array $filter, $entityCode)
	{
		//init params
		$contragents = array();
		$entityCode = strtolower($entityCode);
		$contragent = strtoupper($entityCode);
		$provider = $this->getProviderName($entityCode);
		if (isset($this->selectPresets[$contragent]))
		{
			$select = $this->selectPresets[$contragent];
		}
		if (!class_exists($provider))
		{
			return $contragents;
		}
		//for replace url
		$url = $this->getEntityPath($entityCode);
		//request
		$navParams = array('iNumPage' => $this->blockPage, 'nPageSize' => $this->blockSize);
		if (method_exists($provider, 'getListEx'))
		{
			$res = $provider::getListEx(array(), $filter, false, $navParams, $select);
		}
		elseif (in_array($entityCode, array('order', 'order_payment', 'order_shipment')))
		{
			if ($entityCode === 'order_shipment')
			{
				$filter['SYSTEM'] = 'N';
			}
			$params = array(
				'filter' => $filter,
				'limit' => $this->blockSize,
				'runtime' => array(
					new Bitrix\Main\Entity\ReferenceField('RESPONSIBLE',
						'\Bitrix\Main\UserTable',
						array('=this.RESPONSIBLE_ID' => 'ref.ID'),
						array('join_type' => 'LEFT')
					)
				)
			);
			if ($this->blockPage > 1)
			{
				$params['offset'] = ($this->blockPage - 1) * $this->blockSize;
			}
			if (!empty($select))
			{
				$params['select'] = $select;
			}
			$res = $provider::getList($params);
			$res = new \CDBResult($res);
		}
		else
		{
			$res = $provider::getList(array(), $filter, false, $navParams, $select);
		}
		$this->arParams[$contragent.'_COUNT'] = $res->NavRecordCount;
		$this->arParams[$contragent.'_PAGE'] = $res->NavPageNomer;
		$this->arParams[$contragent.'_PAGE_COUNT'] = $res->NavPageCount;
		while ($row = $res->getNext())
		{
			$row['URL'] = $url;
			$row = $this->buildItemExtra($row, $contragent);
			$contragents[$row['ID']] = $row;
		}

		if (($entityCode == 'contact' || $entityCode == 'company') && !empty($contragents))
		{
			$contragents = $this->fillFMfields($contragents, $contragent);
		}

		return $contragents;
	}

	/**
	 * Get id of associated entities.
	 * @param int $entityTypeID
	 * @param array $filter
	 * @return array
	 */
	protected function getAssociatedEntity($entityTypeID, array $filter)
	{
		static $uid = null;
		if ($uid === null)
		{
			$uid = \CCrmSecurityHelper::GetCurrentUserID();
		}

		$items = array();
		$res = \CCrmActivity::GetEntityList($entityTypeID, $uid, 'ASC', $filter);
		while ($row = $res->getNext())
		{
			$items[] = $row['ID'];
		}

		return $items;
	}

	/**
	 * Get entities recursive.
	 * @param array $entities start entities
	 * @return array entities with sub entities
	 */
	protected function subEntityRecur(array $entities)
	{
		foreach ($entities as $type => &$entity)
		{
			foreach ($entity as $id => &$entityItem)
			{
				$entityItem['SUB_ENTITY'] = array();
				switch ($entityItem['TREE_TYPE'])
				{
					case $this->codes['lead']:
						$entityItem['SUB_ENTITY'][$this->codes['contact']] = $this->getSubEntity(array('LEAD_ID' => $id), $this->codes['contact']);
						$entityItem['SUB_ENTITY'][$this->codes['company']] = $this->getSubEntity(array('LEAD_ID' => $id), $this->codes['company']);
						$entityItem['SUB_ENTITY'][$this->codes['deal']] = $this->getSubEntity(array('LEAD_ID' => $id), $this->codes['deal']);
						$entityItem['SUB_ENTITY'][$this->codes['quote']] = $this->getSubEntity(array('LEAD_ID' => $id), $this->codes['quote']);
						break;
					case $this->codes['contact']:
						$deals = $this->getAssociatedEntity(\CCrmOwnerType::Deal, array('ASSOCIATED_CONTACT_ID' => $id));
						$entityItem['SUB_ENTITY'][$this->codes['deal']] = !empty($deals) ? $this->getSubEntity(array('@ID' => $deals), $this->codes['deal']) : array();
						$entityItem['SUB_ENTITY'][$this->codes['quote']] = $this->getSubEntity(array('CONTACT_ID' => $id), $this->codes['quote']);
						$entityItem['SUB_ENTITY'][$this->codes['invoice']] = $this->getSubEntity(array('UF_CONTACT_ID' => $id), 'invoice');
						break;
					case $this->codes['company']:
						$contacts = $this->getAssociatedEntity(\CCrmOwnerType::Contact, array('ASSOCIATED_COMPANY_ID' => $id));
						$entityItem['SUB_ENTITY'][$this->codes['contact']] = !empty($contacts) ? $this->getSubEntity(array('@ID' => $contacts), $this->codes['contact']) : array();
						$entityItem['SUB_ENTITY'][$this->codes['deal']] = $this->getSubEntity(array('COMPANY_ID' => $id), $this->codes['deal']);
						$entityItem['SUB_ENTITY'][$this->codes['quote']] = $this->getSubEntity(array('COMPANY_ID' => $id), $this->codes['quote']);
						$entityItem['SUB_ENTITY'][$this->codes['invoice']] = $this->getSubEntity(array('UF_COMPANY_ID' => $id), $this->codes['invoice']);
						break;
					case $this->codes['deal']:
						$entityItem['SUB_ENTITY'][$this->codes['quote']] = $this->getSubEntity(array('DEAL_ID' => $id), $this->codes['quote']);
						$entityItem['SUB_ENTITY'][$this->codes['invoice']] = $this->getSubEntity(array('UF_DEAL_ID' => $id), $this->codes['invoice']);
						break;
					case $this->codes['quote']:
						$entityItem['SUB_ENTITY'][$this->codes['invoice']] = $this->getSubEntity(array('UF_QUOTE_ID' => $id), $this->codes['invoice']);
						break;
					case $this->codes['order']:
						$entityItem['SUB_ENTITY'][$this->codes['order_payment']] = $this->getSubEntity(array('ORDER_ID' => $id), $this->codes['order_payment']);
						$entityItem['SUB_ENTITY'][$this->codes['order_shipment']] = $this->getSubEntity(array('ORDER_ID' => $id), $this->codes['order_shipment']);
						break;
					case $this->codes['invoice']:
						break;
				}
				if (!empty($entityItem['SUB_ENTITY']))
				{
					$entityItem['SUB_ENTITY'] = $this->subEntityRecur($entityItem['SUB_ENTITY']);
				}
			}
			unset($entityItem);
		}
		unset($entity);
		return $entities;

		/*
		//$this->arResult[$this->codes['contact']] = $this->base['UF_CONTACT_ID'] > 0 ?  $this->getSubEntity(array('ID' => $this->base['UF_CONTACT_ID']), 'contact') : array();
		//$this->arResult[$this->codes['company']] = $this->base['UF_COMPANY_ID'] > 0 ?  $this->getSubEntity(array('ID' => $this->base['UF_COMPANY_ID']), 'company') : array();
		//$this->arResult[$this->codes['deal']] = $this->base['UF_DEAL_ID'] > 0 ?  $this->getSubEntity(array('ID' => $this->base['UF_DEAL_ID']), 'deal') : array();
		//$this->arResult[$this->codes['quote']] = $this->base['UF_QUOTE_ID'] > 0 ?  $this->getSubEntity(array('ID' => $this->base['UF_QUOTE_ID']), 'quote') : array();
		 */
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
		static $equalDuplicate = array();

		foreach ($entities as $type => &$entity)
		{
			foreach ($entity as $id => &$entityItem)
			{
				$code = $type.'_'.$entityItem['ID'];
				if (
					in_array($code, $equalDuplicate) ||
					isset($this->notHideEntity[$code]) &&
					$this->notHideEntity[$code] != $parentTimestamp
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
	 * @param $entityCode
	 *
	 * @return string
	 */
	protected function getProviderName($entityCode)
	{
		switch ($entityCode)
		{
			case 'order':
				return '\Bitrix\Crm\Order\Order';
			case 'order_payment':
				return '\Bitrix\Crm\Order\Payment';
			case 'order_shipment':
				return '\Bitrix\Crm\Order\Shipment';
			default:
				return '\CCrm'.$entityCode;
		}
	}

	/**
	 * Base executable method.
	 */
	public function executeComponent()
	{
		if (\Bitrix\Main\Loader::includeModule('crm'))
		{
			$this->init();
		}
		else
		{
			return;
		}

		$params = $this->arParams;
		$id = trim($params['ENTITY_ID']);
		$type = strtoupper(trim($params['ENTITY_TYPE_NAME']));

		//get base items
		if ($id > 0 && $type != '')
		{
			$this->arResult['ACTIVITY'] = array();
			if (!($base = $this->addChainItem(array('ID' => $id), $type, 'ID')))
			{
				return;
			}
			$this->arResult['BASE'] = array_reverse(array_merge(array($base), $this->getBaseChainEx($type, $base)));
			$baseLast = $this->arResult['BASE'][count($this->arResult['BASE']) - 1];

			//build tree (first level and the next)
			$firstLevel = $this->subEntityRecur(array(
					$type => array($id => array(
						'TREE_TYPE' => $type
					))
				));
			$this->arResult['TREE'] = $this->markDuplicate($firstLevel[$type][$id]['SUB_ENTITY'], $baseLast['TIMESTAMP']);
			$this->arResult['TREE'] = $this->hideDuplicate($this->arResult['TREE'], $baseLast['TIMESTAMP']);

			$this->arResult['ACTIVITY'] = $this->getActivity($this->arResult['ACTIVITY']);
		}
		else
		{
			return;
		}

		$this->IncludeComponentTemplate();
	}
}