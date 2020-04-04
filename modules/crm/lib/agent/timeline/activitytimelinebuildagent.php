<?php
namespace Bitrix\Crm\Agent\Timeline;

use Bitrix\Main;
use Bitrix\Main\Config\Option;
use Bitrix\Crm\Agent\AgentBase;
use Bitrix\Crm\Timeline\ActivityController;

class ActivityTimelineBuildAgent extends EntityTimelineBuildAgent
{
	const ITERATION_LIMIT = 500;
	/** @var ActivityTimelineBuildAgent|null */
	private static $instance = null;
	/**
	 * @return ActivityTimelineBuildAgent|null
	 */
	public static function getInstance()
	{
		if(self::$instance === null)
		{
			self::$instance = new ActivityTimelineBuildAgent();
		}
		return self::$instance;
	}
	//region EntityTimelineBuildAgent
	public function build(array $itemIDs)
	{
		$itemIDs = $this->filterEntityIDs($itemIDs, \CCrmOwnerType::Activity);
		if(empty($itemIDs))
		{
			return;
		}

		$options = array('EXISTS_CHECK' => false);
		foreach($itemIDs as $itemID)
		{
			ActivityController::getInstance()->register($itemID, $options);
		}
	}
	protected function getOptionName()
	{
		return '~CRM_BUILD_ACTIVITY_TIMELINE';
	}
	protected function getProgressOptionName()
	{
		return '~CRM_BUILD_ACTIVITY_TIMELINE_PROGRESS';
	}
	protected function getTotalEntityCount()
	{
		return \CCrmActivity::GetList(array(), array('CHECK_PERMISSIONS' => 'N'), array(), false);
	}
	protected function getEnityIDs($offsetID, $limit)
	{
		$filter = array('CHECK_PERMISSIONS' => 'N');
		if($offsetID > 0)
		{
			$filter['>ID'] = $offsetID;
		}

		$dbResult = \CCrmActivity::GetList(
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
	protected function getIterationLimit()
	{
		return self::ITERATION_LIMIT;
	}
	//endregion
}