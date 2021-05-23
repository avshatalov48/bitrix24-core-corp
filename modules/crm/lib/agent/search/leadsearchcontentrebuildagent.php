<?php
namespace Bitrix\Crm\Agent\Search;

use Bitrix\Main\Config\Option;
use Bitrix\Crm\Search\SearchContentBuilderFactory;

class LeadSearchContentRebuildAgent extends EntitySearchContentRebuildAgent
{
	/** @var LeadSearchContentRebuildAgent|null */
	private static $instance = null;
	/**
	 * @return EntitySearchContentRebuildAgent|null
	 */
	public static function getInstance()
	{
		if(self::$instance === null)
		{
			self::$instance = new LeadSearchContentRebuildAgent();
		}
		return self::$instance;
	}

	public function isEnabled()
	{
		return Option::get('crm', '~CRM_REBUILD_LEAD_SEARCH_CONTENT', 'N') === 'Y';
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
			Option::set('crm', '~CRM_REBUILD_LEAD_SEARCH_CONTENT', 'Y');
		}
		else
		{
			Option::delete('crm', array('name' => '~CRM_REBUILD_LEAD_SEARCH_CONTENT'));
		}
		Option::delete('crm', array('name' => '~CRM_REBUILD_LEAD_SEARCH_CONTENT_PROGRESS'));
	}
	public function getProgressData()
	{
		$s = Option::get('crm', '~CRM_REBUILD_LEAD_SEARCH_CONTENT_PROGRESS',  '');
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
		Option::set('crm', '~CRM_REBUILD_LEAD_SEARCH_CONTENT_PROGRESS', serialize($data));
	}
	public function getTotalCount()
	{
		return \CCrmLead::GetListEx(array(), array('CHECK_PERMISSIONS' => 'N'), array(), false);
	}
	public function prepareItemIDs($offsetID, $limit)
	{
		$filter = array('CHECK_PERMISSIONS' => 'N');
		if($offsetID > 0)
		{
			$filter['>ID'] = $offsetID;
		}

		$dbResult = \CCrmLead::GetListEx(
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
		$builder = SearchContentBuilderFactory::create(\CCrmOwnerType::Lead);
		$builder->bulkBuild($itemIDs);
	}
}