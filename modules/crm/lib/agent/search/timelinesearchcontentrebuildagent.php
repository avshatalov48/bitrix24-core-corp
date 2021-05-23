<?php
namespace Bitrix\Crm\Agent\Search;

use Bitrix\Main\Config\Option;
use Bitrix\Crm;

class TimelineSearchContentRebuildAgent extends EntitySearchContentRebuildAgent
{
	/** @var TimelineSearchContentRebuildAgent|null */
	private static $instance = null;

	/**
	 * @return EntitySearchContentRebuildAgent|null
	 */
	public static function getInstance()
	{
		if(self::$instance === null)
		{
			self::$instance = new TimelineSearchContentRebuildAgent();
		}
		return self::$instance;
	}

	public function isEnabled()
	{
		return Option::get('crm', '~CRM_REBUILD_TIMELINE_SEARCH_CONTENT', 'N') === 'Y';
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
			Option::set('crm', '~CRM_REBUILD_TIMELINE_SEARCH_CONTENT', 'Y');
		}
		else
		{
			Option::delete('crm', array('name' => '~CRM_REBUILD_TIMELINE_SEARCH_CONTENT'));
		}
		Option::delete('crm', array('name' => '~CRM_REBUILD_TIMELINE_SEARCH_CONTENT_PROGRESS'));
	}
	public function getProgressData()
	{
		$s = Option::get('crm', '~CRM_REBUILD_TIMELINE_SEARCH_CONTENT_PROGRESS',  '');
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
		Option::set('crm', '~CRM_REBUILD_TIMELINE_SEARCH_CONTENT_PROGRESS', serialize($data));
	}
	public function getTotalCount()
	{
		return Crm\Timeline\Entity\TimelineTable::getCount();
	}
	public function prepareItemIDs($offsetID, $limit)
	{
		$filter = array();
		if($offsetID > 0)
		{
			$filter['>ID'] = $offsetID;
		}

		$dbResult = Crm\Timeline\Entity\TimelineTable::getList(
			array(
				'select' => array('ID'),
				'filter' => $filter,
				'order' => array('ID' => 'ASC'),
				'limit' => $limit
			)
		);

		$results = array();
		while($fields = $dbResult->fetch())
		{
			$results[] = (int)$fields['ID'];
		}
		return $results;
	}
	public function rebuild(array $itemIDs)
	{
		$builder = new Crm\Search\TimelineSearchContentBuilder();
		$builder->bulkBuild($itemIDs);
	}
}