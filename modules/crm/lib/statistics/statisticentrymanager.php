<?php
namespace Bitrix\Crm\Statistics;
use Bitrix\Main;

abstract class StatisticEntryManager
{
	/** @var StatisticEntry[]|null */
	private static $entries = null;
	private static $messagesLoaded = false;
	/**
	* Get entries list
	* @return StatisticEntry[]
	*/
	private static function getEntries()
	{
		if(self::$entries === null)
		{
			self::$entries = array();

			self::addEntry(DealSumStatisticEntry::getCurrent());
			self::addEntry(LeadSumStatisticEntry::getCurrent());
			self::addEntry(InvoiceSumStatisticEntry::getCurrent());
		}
		return self::$entries;
	}
	/**
	* Get entries list by entity type ID
	* @param string $entityTypeID Entity type ID.
	* @return StatisticEntry[]
	*/
	private static function getEntriesByEntityTypeID($entityTypeID)
	{
		$result = array();
		if(self::$entries !== null)
		{
			foreach(self::$entries as $entry)
			{
				if($entry->getEntityTypeID() === $entityTypeID)
				{
					$result[$entry->getTypeName()] = $entry;
				}
			}
		}
		return $result;
	}
	/**
	 * Add entry
	 * @param StatisticEntry $entry Entry to add.
	 * @return void
	 */
	public static function addEntry(StatisticEntry $entry)
	{
		if(self::$entries === null)
		{
			self::$entries = array();
		}

		self::$entries[$entry->getTypeName()] = $entry;
	}
	/**
	 * Remove entry
	 * @param StatisticEntry $entry Entry to remove.
	 * @return void
	 */
	public static function removeEntry(StatisticEntry $entry)
	{
		$key = $entry->getTypeName();
		if(self::$entries !== null && isset(self::$entries[$key]))
		{
			unset(self::$entries[$key]);
		}
	}
	/**
	 * Get overall limit
	 * @return int
	 */
	public static function getOverallSlotLimit()
	{
		return 10;
	}
	/**
	 * Get slot limit
	 * @return int
	 */
	public static function getSlotLimit()
	{
		return 5;
	}
	/**
	* Check if entry is valid
	* @param string $typeName Statistic entity type name.
	* @return boolean
	*/
	public static function isValid($typeName)
	{
		$entries = self::getEntries();
		if(!isset($entries[$typeName]))
		{
			throw new Main\NotSupportedException("The type '{$typeName}' is not supported in current context.");
		}
		return $entries[$typeName]->isValid();
	}
	/**
	* Get count of busy slots
	* @param string $typeName Statistic entity type name.
	* @return integer
	*/
	public static function getBusySlotCount($typeName)
	{
		$entries = self::getEntries();
		if(!isset($entries[$typeName]))
		{
			throw new Main\NotSupportedException("The type '{$typeName}' is not supported in current context.");
		}
		return $entries[$typeName]->getBusySlotCount();
	}
	/**
	* Get slots data
	* @param string $typeName Statistic entity type name.
	* @return array
	*/
	public static function getSlotInfos($typeName)
	{
		$entries = self::getEntries();
		if(!isset($entries[$typeName]))
		{
			throw new Main\NotSupportedException("The type '{$typeName}' is not supported in current context.");
		}
		return $entries[$typeName]->getSlotInfos();
	}
	/**
	* Get fields data
	* @param string $typeName Statistic entity type name.
	* @param string $langID Language ID.
	* @return array
	*/
	public static function getSlotFieldInfos($typeName, $langID = '')
	{
		$entries = self::getEntries();
		if(!isset($entries[$typeName]))
		{
			throw new Main\NotSupportedException("The type '{$typeName}' is not supported in current context.");
		}
		return $entries[$typeName]->getSlotFieldInfos($langID);
	}
	/**
	* Get binding map
	* @param string $typeName Statistic entity type name.
	* @return StatisticFieldBindingMap
	*/
	public static function getSlotBindingMap($typeName)
	{
		$entries = self::getEntries();
		if(!isset($entries[$typeName]))
		{
			throw new Main\NotSupportedException("The type '{$typeName}' is not supported in current context.");
		}
		return $entries[$typeName]->getSlotBindingMap();
	}
	/**
	* Set binding map
	* @param string $typeName Statistic entity type name.
	* @param StatisticFieldBindingMap $srcBindingMap Binding map.
	* @return void
	*/
	public static function setSlotBindingMap($typeName, StatisticFieldBindingMap $srcBindingMap)
	{
		$entries = self::getEntries();
		if(!isset($entries[$typeName]))
		{
			throw new Main\NotSupportedException("The type '{$typeName}' is not supported in current context.");
		}
		$entries[$typeName]->setSlotBindingMap($srcBindingMap);
	}
	/**
	* Prepare binging data
	* @param string $typeName Statistic entity type name.
	* @return array
	*/
	public static function prepareSlotBingingData($typeName)
	{
		$entries = self::getEntries();
		if(!isset($entries[$typeName]))
		{
			throw new Main\NotSupportedException("The type '{$typeName}' is not supported in current context.");
		}
		return $entries[$typeName]->prepareSlotBingingData();
	}
	/**
	* Prepare builder data
	* @param int $ownerTypeID Owner type ID (see: \CCrmOwnerType).
	* @return array
	*/
	public static function prepareBuilderData($ownerTypeID)
	{
		if($ownerTypeID === \CCrmOwnerType::Deal
			&& Main\Config\Option::get('crm', '~CRM_REBUILD_DEAL_STATISTICS', 'N', false) === 'Y')
		{
			self::includeModuleFile();
			return array(
				array(
					'ID' => 'DEAL_ALL',
					'ACTIVE' => true,
					'MESSAGE' => GetMessage('CRM_STAT_MGR_REBUILD_STATISTICS'),
					'SETTINGS' => array(
						'TITLE' => GetMessage('CRM_STAT_MGR_REBUILD_STATISTICS_DLG_TITLE'),
						'SUMMARY' => GetMessage('CRM_STAT_MGR_REBUILD_STATISTICS_DLG_SUMMARY'),
						'ACTION' => 'REBUILD_STATISTICS',
						'URL' => '/bitrix/components/bitrix/crm.deal.list/list.ajax.php?'.bitrix_sessid_get()
					)
				)
			);
		}
		elseif($ownerTypeID === \CCrmOwnerType::Lead
			&& Main\Config\Option::get('crm', '~CRM_REBUILD_LEAD_STATISTICS', 'N', false) === 'Y')
		{
			self::includeModuleFile();
			return array(
				array(
					'ID' => 'LEAD_ALL',
					'ACTIVE' => true,
					'MESSAGE' => GetMessage('CRM_STAT_MGR_REBUILD_STATISTICS'),
					'SETTINGS' => array(
						'TITLE' => GetMessage('CRM_STAT_MGR_REBUILD_STATISTICS_DLG_TITLE'),
						'SUMMARY' => GetMessage('CRM_STAT_MGR_REBUILD_STATISTICS_DLG_SUMMARY'),
						'ACTION' => 'REBUILD_STATISTICS',
						'URL' => '/bitrix/components/bitrix/crm.lead.list/list.ajax.php?'.bitrix_sessid_get()
					)
				)
			);
		}
		elseif($ownerTypeID === \CCrmOwnerType::Invoice
			&& Main\Config\Option::get('crm', '~CRM_REBUILD_INVOICE_STATISTICS', 'N', false) === 'Y')
		{
			self::includeModuleFile();
			return array(
				array(
					'ID' => 'INVOICE_ALL',
					'ACTIVE' => true,
					'MESSAGE' => GetMessage('CRM_STAT_MGR_REBUILD_STATISTICS'),
					'SETTINGS' => array(
						'TITLE' => GetMessage('CRM_STAT_MGR_REBUILD_STATISTICS_DLG_TITLE'),
						'SUMMARY' => GetMessage('CRM_STAT_MGR_REBUILD_STATISTICS_DLG_SUMMARY'),
						'ACTION' => 'REBUILD_STATISTICS',
						'URL' => '/bitrix/components/bitrix/crm.invoice.list/list.ajax.php?'.bitrix_sessid_get()
					)
				)
			);
		}
		elseif($ownerTypeID === \CCrmOwnerType::Company
			&& Main\Config\Option::get('crm', '~CRM_REBUILD_COMPANY_ACT_STATISTICS', 'N', false) === 'Y'
		)
		{
			self::includeModuleFile();
			return array(
				array(
					'ID' => 'COMPANY_ACT_STAT',
					'ACTIVE' => true,
					'MESSAGE' => GetMessage('CRM_STAT_MGR_REBUILD_STATISTICS'),
					'SETTINGS' => array(
						'TITLE' => GetMessage('CRM_STAT_MGR_REBUILD_STATISTICS_DLG_TITLE'),
						'SUMMARY' => GetMessage('CRM_STAT_MGR_REBUILD_STATISTICS_DLG_SUMMARY'),
						'ACTION' => 'REBUILD_ACT_STATISTICS',
						'URL' => '/bitrix/components/bitrix/crm.company.list/list.ajax.php?'.bitrix_sessid_get()
					)
				)
			);
		}
		elseif($ownerTypeID === \CCrmOwnerType::Contact
			&& Main\Config\Option::get('crm', '~CRM_REBUILD_CONTACT_ACT_STATISTICS', 'N', false) === 'Y'
		)
		{
			self::includeModuleFile();
			return array(
				array(
					'ID' => 'CONTACT_ACT_STAT',
					'ACTIVE' => true,
					'MESSAGE' => GetMessage('CRM_STAT_MGR_REBUILD_STATISTICS'),
					'SETTINGS' => array(
						'TITLE' => GetMessage('CRM_STAT_MGR_REBUILD_STATISTICS_DLG_TITLE'),
						'SUMMARY' => GetMessage('CRM_STAT_MGR_REBUILD_STATISTICS_DLG_SUMMARY'),
						'ACTION' => 'REBUILD_ACT_STATISTICS',
						'URL' => '/bitrix/components/bitrix/crm.contact.list/list.ajax.php?'.bitrix_sessid_get()
					)
				)
			);
		}
		elseif($ownerTypeID === \CCrmOwnerType::Activity
			&& Main\Config\Option::get('crm', '~CRM_REBUILD_ACTIVITY_STATISTICS', 'N', false) === 'Y'
		)
		{
			self::includeModuleFile();
			return array(
				array(
					'ID' => 'ACT_STAT',
					'ACTIVE' => true,
					'MESSAGE' => GetMessage('CRM_STAT_MGR_REBUILD_STATISTICS'),
					'SETTINGS' => array(
						'TITLE' => GetMessage('CRM_STAT_MGR_REBUILD_STATISTICS_DLG_TITLE'),
						'SUMMARY' => GetMessage('CRM_STAT_MGR_REBUILD_STATISTICS_DLG_SUMMARY'),
						'ACTION' => 'REBUILD_ACT_STATISTICS',
						'URL' => '/bitrix/components/bitrix/crm.activity.list/list.ajax.php?'.bitrix_sessid_get()
					)
				)
			);
		}

		$result = array();
		$entries = self::getEntriesByEntityTypeID($ownerTypeID);
		foreach($entries as $entry)
		{
			$result[] = $entry->prepareBuilderData();
		}
		return $result;
	}
	/**
	 * Include language file
	 * @return void
	 */
	protected static function includeModuleFile()
	{
		if(self::$messagesLoaded)
		{
			return;
		}

		Main\Localization\Loc::loadMessages(__FILE__);
		self::$messagesLoaded = true;
	}
}