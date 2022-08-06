<?php
namespace Bitrix\Crm\Agent\Duplicate;

use Bitrix\Crm\Integrity\Volatile\FieldCategory;
use Bitrix\Main\Config\Option;
use Bitrix\Crm\Integrity\DuplicateRequisiteCriterion;
use Bitrix\Crm\Integrity\DuplicateBankDetailCriterion;
use Bitrix\Crm\Integrity\DuplicateVolatileCriterion;
use CCrmOwnerType;

class CompanyDuplicateIndexRebuildAgent extends EntityDuplicateIndexRebuildAgent
{
	/** @var CompanyDuplicateIndexRebuildAgent|null */
	private static $instance = null;

	/**
	 * @return EntityDuplicateIndexRebuildAgent|null
	 */
	public static function getInstance()
	{
		if(self::$instance === null)
		{
			self::$instance = new CompanyDuplicateIndexRebuildAgent();
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
		return Option::get('crm', '~CRM_REBUILD_COMPANY_DUPLICATE_INDEX', 'N') === 'Y';
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
			Option::set('crm', '~CRM_REBUILD_COMPANY_DUPLICATE_INDEX', 'Y');
		}
		else
		{
			Option::delete('crm', array('name' => '~CRM_REBUILD_COMPANY_DUPLICATE_INDEX'));
		}
		Option::delete('crm', array('name' => '~CRM_REBUILD_COMPANY_DUPLICATE_INDEX_PROGRESS'));
	}
	public function getProgressData()
	{
		$s = Option::get('crm', '~CRM_REBUILD_COMPANY_DUPLICATE_INDEX_PROGRESS',  '');
		$data = $s !== '' ? unserialize($s, ['allowed_classes' => false]) : null;
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
		Option::set('crm', '~CRM_REBUILD_COMPANY_DUPLICATE_INDEX_PROGRESS', serialize($data));
	}
	public function getTotalCount()
	{
		return \CCrmCompany::GetListEx(array(), array('CHECK_PERMISSIONS' => 'N'), array(), false);
	}
	public function prepareItemIDs($offsetID, $limit)
	{
		$filter = array('CHECK_PERMISSIONS' => 'N');
		if($offsetID > 0)
		{
			$filter['>ID'] = $offsetID;
		}

		$dbResult = \CCrmCompany::GetListEx(
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
		// \CCrmCompany::RebuildDuplicateIndex($itemIDs);

		foreach ($itemIDs as $ID)
		{
			DuplicateRequisiteCriterion::registerByEntity(CCrmOwnerType::Company, $ID);
			DuplicateBankDetailCriterion::registerByEntity(CCrmOwnerType::Company, $ID);

			//region Register volatile duplicate criterion fields
			DuplicateVolatileCriterion::register(
				CCrmOwnerType::Company,
				$ID,
				[FieldCategory::REQUISITE, FieldCategory::BANK_DETAIL]
			);
			//endregion Register volatile duplicate criterion fields
		}
	}
}