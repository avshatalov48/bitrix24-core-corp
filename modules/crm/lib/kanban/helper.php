<?php
namespace Bitrix\Crm\Kanban;

use \Bitrix\Main\Localization\Loc;
use \Bitrix\Crm\Counter\EntityCounterType;
use \Bitrix\Crm\PhaseSemantics;
use \Bitrix\Crm\Order;

Loc::loadMessages(__FILE__);

class Helper
{
	/**
	 * UI Filter prefix.
	 */
	const FILTER_PREFIX = 'KANBAN_V11_';

	/**
	 * Current category of entities.
	 * @var int
	 */
	protected static $categoryId = 0;

	/**
	 * Get instance of grid.
	 * @param string $type Type of entity.
	 * @return \CGridOptions
	 */
	public static function getGrid($type)
	{
		static $grid = array();

		if (!array_key_exists($type, $grid))
		{
			$grid[$type] = new \Bitrix\Main\UI\Filter\Options(
				self::getGridId($type),
				self::getPresets($type)
			);
		}
		return $grid[$type];
	}

	/**
	 * Set new category id.
	 * @param int $id New category id.
	 * @return void
	 */
	public static function setCategoryId($id)
	{
		self::$categoryId = $id;
	}

	/**
	 * Get category id.
	 * @return int
	 */
	public static function getCategoryId()
	{
		return self::$categoryId;
	}

	/**
	 * Get id of grid.
	 * @param string $type Type of entity.
	 * @return string
	 */
	public static function getGridId($type)
	{
		$gridId = 'CRM_' . $type . '_LIST_V12';

		if ($type === \CCrmOwnerType::DealName && self::$categoryId >= 0)
		{
			return $gridId . '_C_' . self::$categoryId;
		}

		if (in_array(
			$type,
			[
				\CCrmOwnerType::DealName,
				\CCrmOwnerType::LeadName,
				\CCrmOwnerType::QuoteName,
				\CCrmOwnerType::OrderName,
			],
			true
		))
		{
			return $gridId;
		}

		return self::FILTER_PREFIX . $type;
	}

	/**
	 * Get owner types.
	 * @return array
	 */
	private static function getTypes()
	{
		static $types = null;

		if ($types === null)
		{
			$types = array(
				'lead' => \CCrmOwnerType::LeadName,
				'deal' => \CCrmOwnerType::DealName,
				'quote' => \CCrmOwnerType::QuoteName,
				'invoice' => \CCrmOwnerType::InvoiceName,
				'order' => \CCrmOwnerType::OrderName,
			);
		}

		return $types;
	}

	/**
	 * Get filter for Kanban.
	 * @param string $entity Type of entity.
	 * @return array
	 */
	public static function getFilter($entity)
	{
		static $filter = array();
		$types = self::getTypes();

		if (!array_key_exists($entity, $filter))
		{
			$filter[$entity] = array();
			// lead
			if ($entity == $types['lead'])
			{
				$entityFilter = \Bitrix\Crm\Filter\Factory::createEntityFilter(
					new \Bitrix\Crm\Filter\LeadSettings(
						array(
							'ID' => self::getGridId($entity)
						)
					)
				);
			}
			// deal
			elseif ($entity == $types['deal'])
			{
				$userPermissions = \CCrmPerms::getCurrentUserPermissions();
				$entityFilter = \Bitrix\Crm\Filter\Factory::createEntityFilter(
					new \Bitrix\Crm\Filter\DealSettings(
						array(
							'ID' => self::getGridId($entity),
							'categoryID' => self::$categoryId,
							'categoryAccess' => array(
								'CREATE' => \CCrmDeal::getPermittedToCreateCategoryIDs($userPermissions),
								'READ' => \CCrmDeal::getPermittedToReadCategoryIDs($userPermissions),
								'UPDATE' => \CCrmDeal::getPermittedToUpdateCategoryIDs($userPermissions)
							),
							'flags' => \Bitrix\Crm\Filter\DealSettings::FLAG_NONE
						)
					)
				);
			}
			// quote
			elseif ($entity == $types['quote'])
			{
				$entityFilter = \Bitrix\Crm\Filter\Factory::createEntityFilter(
					new \Bitrix\Crm\Filter\QuoteSettings(
						array(
							'ID' => self::getGridId($entity)
						)
					)
				);
			}
			// invoice
			elseif ($entity == $types['invoice'])
			{
				$filter[$entity]['OVERDUE'] = array(
					'id' => 'OVERDUE',
					'name' => Loc::getMessage('CRM_KANBAN_HELPER_FLT_OVERDUE_INVOICE'),
					'default' => true,
					'type' => 'list',
					'items' => array(
						'Y' => Loc::getMessage('CRM_KANBAN_HELPER_FLT_YES'),
						'N' => Loc::getMessage('CRM_KANBAN_HELPER_FLT_NO')
					)
				);
				$filter[$entity]['DATE_PAY_BEFORE'] = array(
					'id' => 'DATE_PAY_BEFORE',
					'name' => Loc::getMessage('CRM_KANBAN_HELPER_FLT_DATE_PAY_INVOICE'),
					'default' => true,
					'type' => 'date'
				);
				$filter[$entity]['PRICE'] = array(
					'id' => 'PRICE',
					'name' => Loc::getMessage('CRM_KANBAN_HELPER_FLT_PRICE'),
					'default' => true,
					'type' => 'number'
				);
				$filter[$entity]['RESPONSIBLE_ID'] = array(
					'id' => 'RESPONSIBLE_ID',
					'name' => Loc::getMessage('CRM_KANBAN_HELPER_FLT_ASSIGNED_BY_ID'),
					'default' => true,
					'type' => 'dest_selector',
					'params' => array(
						'context' => 'CRM_KANBAN_FILTER_RESPONSIBLE_ID',
						'multiple' => 'Y',
						'contextCode' => 'U',
						'enableAll' => 'N',
						'enableSonetgroups' => 'N',
						'allowEmailInvitation' => 'N',
						'allowSearchEmailUsers' => 'N',
						'departmentSelectDisable' => 'Y',
						'isNumeric' => 'Y',
						'prefix' => 'U',
					)
				);
				// add new columns
				$columns = PhaseSemantics::getListFilterInfo(false, null, true);
				$id = 'STATUS_SEMANTIC_ID';
				$filter[$entity][$id] = array_merge(
					array(
						'id' => $id,
						'name' => Loc::getMessage('CRM_KANBAN_HELPER_FLT_STATUS_SEMANTIC_ID'),
						'default' => false,
						'params' => array(
							'multiple' => 'Y'
						)
					),
					$columns
				);
			}
			// order
			elseif ($entity == $types['order'])
			{
				$entityFilter = \Bitrix\Crm\Filter\Factory::createEntityFilter(
					new \Bitrix\Crm\Filter\OrderSettings(
						array(
							'ID' => self::getGridId($entity)
						)
					)
				);
			}
			// new common filter
			if ($entity != $types['invoice'])
			{
				$grid = self::getGrid($entity);
				$effectiveFilterFieldIDs = $grid->getUsedFields();
				// add some additional fields
				if ($entity != $types['quote'])
				{

					$addCodes = array(
						'ASSIGNED_BY_ID', 'ACTIVITY_COUNTER', 'STAGE_ID'
					);
					if (self::$categoryId)
					{
						$addCodes[] = 'STAGE_ID';
					}
					foreach ($addCodes as $code)
					{
						if (!in_array($code, $effectiveFilterFieldIDs))
						{
							$effectiveFilterFieldIDs[] = $code;
						}
					}
				}
				// compile filter
				if (empty($effectiveFilterFieldIDs))
				{
					$effectiveFilterFieldIDs = $grid->getDefaultFieldIDs();
				}
				foreach ($effectiveFilterFieldIDs as $filterFieldID)
				{
					$filterField = $entityFilter->getField($filterFieldID);
					if ($filterField)
					{
						$filter[$entity][$filterFieldID] = $filterField->toArray();
					}
				}
			}
		}

		return $filter[$entity];
	}

	/**
	 * Get filter presets for Kanban.
	 * @param string $entity Type of entity.
	 * @return array
	 */
	public static function getPresets($entity)
	{
		static $presets = array();
		$types = self::getTypes();

		if (!array_key_exists($entity, $presets))
		{
			$presets[$entity] = array();
			$uid = \CCrmSecurityHelper::GetCurrentUserID();
			if ($uid)
			{
				if ($uname = \Cuser::getById($uid)->fetch())
				{
					$uname = \CUser::FormatName(\CSite::GetNameFormat(false), $uname);
				}
			}
			// lead
			if ($entity == $types['lead'])
			{
				$defaultFilter = array(
					'SOURCE_ID' => array(),
					'STATUS_ID' => array(),
					'COMMUNICATION_TYPE' => array(),
					'DATE_CREATE' => '',
					'ASSIGNED_BY_ID' => ''
				);
				$presets[$entity]['filter_my_in_work'] = array(
					'name' => Loc::getMessage('CRM_KANBAN_HELPER_LPR_MY_WORK'),
					'disallow_for_all' => true,
					'fields' => array_merge(
							$defaultFilter,
							array(
								'ASSIGNED_BY_ID_name' => $uname,
								'ASSIGNED_BY_ID' => $uid,
								'STATUS_SEMANTIC_ID' => array(
									\Bitrix\Crm\PhaseSemantics::PROCESS
								)
							)
						)
				);
				$presets[$entity]['filter_in_work'] = array(
					'name' => Loc::getMessage('CRM_KANBAN_HELPER_LPR_WORK'),
					'default' => true,
					'fields' => array_merge(
						$defaultFilter,
						array(
							'STATUS_SEMANTIC_ID' => array(
								\Bitrix\Crm\PhaseSemantics::PROCESS
							)
						)
					)
				);
				$presets[$entity]['filter_closed'] = array(
					'name' => Loc::getMessage('CRM_KANBAN_HELPER_LPR_CLOSED'),
					'fields' => array_merge(
						$defaultFilter,
						array(
							'STATUS_SEMANTIC_ID' => array(
								\Bitrix\Crm\PhaseSemantics::SUCCESS,
								\Bitrix\Crm\PhaseSemantics::FAILURE
							)
						)
					)
				);
			}
			// deal
			elseif ($entity == $types['deal'])
			{
				$presets[$entity]['filter_in_work'] = array(
					'name' => Loc::getMessage('CRM_KANBAN_HELPER_DPR_WORK'),
					'default' => true,
					'fields' => array(
						'STAGE_SEMANTIC_ID' => array(
							\Bitrix\Crm\PhaseSemantics::PROCESS
						)
					)
				);
				$presets[$entity]['filter_my'] = array(
					'name' => Loc::getMessage('CRM_KANBAN_HELPER_DPR_WORK_MY'),
					'disallow_for_all' => true,
					'fields' => array(
						'ASSIGNED_BY_ID_name' => $uname,
						'ASSIGNED_BY_ID' => $uid,
						'STAGE_SEMANTIC_ID' => array(
							\Bitrix\Crm\PhaseSemantics::PROCESS
						)
					)
				);
				$presets[$entity]['filter_closed'] = array(
					'name' => Loc::getMessage('CRM_KANBAN_HELPER_DPR_WON'),
					'fields' => array(
						'STAGE_SEMANTIC_ID' => array(
							[\Bitrix\Crm\PhaseSemantics::SUCCESS, \Bitrix\Crm\PhaseSemantics::FAILURE]
						)
					)
				);
			}
			// quote
			elseif ($entity == $types['quote'])
			{
				$processStatusIDs = array();
				foreach (array_keys(\CCrmQuote::getStatuses()) as $statusID)
				{
					if (\CCrmQuote::getSemanticID($statusID) === \Bitrix\Crm\PhaseSemantics::PROCESS)
					{
						$processStatusIDs[] = $statusID;
					}
				}
				$presets[$entity]['filter_new'] = array(
					'name' => Loc::getMessage('CRM_KANBAN_HELPER_QT_NEW'),
					'fields' => array(
						'STATUS_ID' => array(
							'selDRAFT' => 'DRAFT'
						)
					)
				);
				$presets[$entity]['filter_my'] = array(
					'name' => Loc::getMessage('CRM_KANBAN_HELPER_QT_MY'),
					'disallow_for_all' => true,
					'fields' => array(
						'ASSIGNED_BY_ID_name' => $uname,
						'ASSIGNED_BY_ID' => $uid
					)
				);
				$presets[$entity]['filter_my_in_work'] = array(
					'name' => Loc::getMessage('CRM_KANBAN_HELPER_QT_MY_WORK'),
					'disallow_for_all' => true,
					'default' => true,
					'fields' => array(
						'ASSIGNED_BY_ID_name' => $uname,
						'ASSIGNED_BY_ID' => $uid,
						'STATUS_ID' => $processStatusIDs
					)
				);
			}
			// invoice
			elseif ($entity == $types['invoice'])
			{
				$presets[$entity]['filter_inv1_my'] = array(
					'name' => Loc::getMessage('CRM_KANBAN_HELPER_INV_MY'),
					'disallow_for_all' => true,
					'default' => true,
					'fields' => array(
						'RESPONSIBLE_ID' => $uid,
						'RESPONSIBLE_ID_name' => $uname,
						'OVERDUE' => '',
						'DATE_PAY_BEFORE' => '',
						'PRICE' => ''
					)
				);
				$presets[$entity]['filter_inv2_overdue'] = array(
					'name' => Loc::getMessage('CRM_KANBAN_HELPER_INV_OVERDUE'),
					'disallow_for_all' => true,
					'fields' => array(
						'RESPONSIBLE_ID' => $uid,
						'RESPONSIBLE_ID_name' => $uname,
						'OVERDUE' => 'Y',
						'DATE_PAY_BEFORE' => '',
						'PRICE' => ''
					)
				);
			}
			// order
			elseif ($entity == $types['order'])
			{
				$presets[$entity]['filter_in_work'] = [
					'name' => Loc::getMessage('CRM_KANBAN_HELPER_ORDER_PRESET_MY_WORK'),
					'default' => true,
					'fields' => ['STATUS_ID' => Order\OrderStatus::getSemanticProcessStatuses()]
				];
				$presets[$entity]['filter_my'] = array(
					'name' => Loc::getMessage('CRM_KANBAN_HELPER_ORDER_PRESET_MY'),
					'fields' => array(
						'RESPONSIBLE_ID_name' => $uname,
						'RESPONSIBLE_ID' => $uid,
						'STATUS_ID' => Order\OrderStatus::getSemanticProcessStatuses()
					)
				);
				$presets[$entity]['filter_won'] = array(
					'name' => Loc::getMessage('CRM_KANBAN_HELPER_ORDER_PRESET_WON'),
					'fields' => array('STATUS_ID' =>  array(Order\OrderStatus::getFinalStatus()))
				);
			}
		}

		return $presets[$entity];
	}

	/**
	 * Get default key of filter for Kanban.
	 * @param string $entity Type of entity.
	 * @return array
	 */
	public static function getDefaultFilterKey($entity)
	{
		$keys = array();
		foreach (self::getFilter($entity) as $key => $item)
		{
			$keys[$key] = $item['default']===true;
		}
		return $keys;
	}
}
