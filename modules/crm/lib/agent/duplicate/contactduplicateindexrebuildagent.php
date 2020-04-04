<?php
namespace Bitrix\Crm\Agent\Duplicate;

use Bitrix\Main\Config\Option;
use Bitrix\Crm\Integrity\DuplicateRequisiteCriterion;
use Bitrix\Crm\Integrity\DuplicateBankDetailCriterion;

class ContactDuplicateIndexRebuildAgent extends EntityDuplicateIndexRebuildAgent
{
	/** @var ContactDuplicateIndexRebuildAgent|null */
	private static $instance = null;

	/**
	 * @return EntityDuplicateIndexRebuildAgent|null
	 */
	public static function getInstance()
	{
		if(self::$instance === null)
		{
			self::$instance = new ContactDuplicateIndexRebuildAgent();
		}
		return self::$instance;
	}

	public function isActive()
	{
		$result = false;

		$res = \CAgent::GetList(array("ID" => "DESC"), array('=NAME' => __CLASS__.'::run();', 'ACTIVE' => 'Y'));
		if (is_object($res))
		{
			$row = $res->Fetch();
			if (is_array($row) && !empty($row))
				$result = true;
		}
		if (!$result)
		{
			$res = \CAgent::GetList(array("ID" => "DESC"), array('=NAME' => '\\'.__CLASS__.'::run();', 'ACTIVE' => 'Y'));
			if (is_object($res))
			{
				$row = $res->Fetch();
				if (is_array($row) && !empty($row))
					$result = true;
			}
		}

		return $result;
	}

	public static function activate()
	{
		\CAgent::AddAgent('\\'.__CLASS__.'::run();', 'crm', 'Y', 0, '', 'Y', ConvertTimeStamp(time() + \CTimeZone::GetOffset() + 60, 'FULL'));
	}

	public function isEnabled()
	{
		return Option::get('crm', '~CRM_REBUILD_CONTACT_DUPLICATE_INDEX', 'N') === 'Y';
	}
	public function enable($enable)
	{
		if(!is_bool($enable))
		{
			$enable = (bool)$enable;
		}

		if($enable === self::isEnabled())
		{
			return;
		}

		if($enable)
		{
			Option::set('crm', '~CRM_REBUILD_CONTACT_DUPLICATE_INDEX', 'Y');
		}
		else
		{
			Option::delete('crm', array('name' => '~CRM_REBUILD_CONTACT_DUPLICATE_INDEX'));
		}
		Option::delete('crm', array('name' => '~CRM_REBUILD_CONTACT_DUPLICATE_INDEX_PROGRESS'));
	}
	public function getProgressData()
	{
		$s = Option::get('crm', '~CRM_REBUILD_CONTACT_DUPLICATE_INDEX_PROGRESS',  '');
		$data = $s !== '' ? unserialize($s) : null;
		if(!is_array($data))
		{
			$data = array();
		}

		$data['LAST_ITEM_ID'] = isset($data['LAST_ITEM_ID']) ? (int)($data['LAST_ITEM_ID']) : 0;
		$data['PROCESSED_ITEMS'] = isset($data['PROCESSED_ITEMS']) ? (int)($data['PROCESSED_ITEMS']) : 0;
		$data['TOTAL_ITEMS'] = isset($data['TOTAL_ITEMS']) ? (int)($data['TOTAL_ITEMS']) : 0;

		return $data;
	}
	public function setProgressData(array $data)
	{
		Option::set('crm', '~CRM_REBUILD_CONTACT_DUPLICATE_INDEX_PROGRESS', serialize($data));
	}
	public function getTotalCount()
	{
		return \CCrmContact::GetListEx(array(), array('CHECK_PERMISSIONS' => 'N'), array(), false);
	}
	public function prepareItemIDs($offsetID, $limit)
	{
		$filter = array('CHECK_PERMISSIONS' => 'N');
		if($offsetID > 0)
		{
			$filter['>ID'] = $offsetID;
		}

		$dbResult = \CCrmContact::GetListEx(
			array('ID' => 'ASC'),
			$filter,
			false,
			array('nTopCount' => $limit),
			array('ID')
		);

		$results = array();

		if(is_object($dbResult))
		{
			while($fields = $dbResult->Fetch())
			{
				$results[] = (int)$fields['ID'];
			}
		}

		return $results;
	}
	public function rebuild(array $itemIDs)
	{
		// If you need to rebuild the entire index, you can use
		// \CCrmContact::RebuildDuplicateIndex($itemIDs);

		foreach ($itemIDs as $ID)
		{
			DuplicateRequisiteCriterion::registerByEntity(\CCrmOwnerType::Contact, $ID);
			DuplicateBankDetailCriterion::registerByEntity(\CCrmOwnerType::Contact, $ID);
		}
	}
}