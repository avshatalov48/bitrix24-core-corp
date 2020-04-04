<?php
namespace Bitrix\Crm\Agent\Timeline;

use Bitrix\Main;
use Bitrix\Crm\Timeline\ContactController;

class ContactTimelineBuildAgent extends EntityTimelineBuildAgent
{
	const ITERATION_LIMIT = 500;
	/** @var ContactTimelineBuildAgent|null */
	private static $instance = null;
	/**
	 * @return ContactTimelineBuildAgent|null
	 */
	public static function getInstance()
	{
		if(self::$instance === null)
		{
			self::$instance = new ContactTimelineBuildAgent();
		}
		return self::$instance;
	}
	//region EntityTimelineBuildAgent
	public function build(array $itemIDs)
	{
		$itemIDs = $this->filterEntityIDs($itemIDs, \CCrmOwnerType::Contact);
		if(empty($itemIDs))
		{
			return;
		}

		$options = array('EXISTS_CHECK' => false);
		foreach($itemIDs as $itemID)
		{
			ContactController::getInstance()->register($itemID, $options);
		}
	}
	protected function getOptionName()
	{
		return '~CRM_BUILD_CONTACT_TIMELINE';
	}
	protected function getProgressOptionName()
	{
		return '~CRM_BUILD_CONTACT_TIMELINE_PROGRESS';
	}
	protected function getTotalEntityCount()
	{
		return \CCrmContact::GetListEx(array(), array('CHECK_PERMISSIONS' => 'N'), array(), false);
	}
	protected function getEnityIDs($offsetID, $limit)
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
	protected function getIterationLimit()
	{
		return self::ITERATION_LIMIT;
	}
	//endregion
}