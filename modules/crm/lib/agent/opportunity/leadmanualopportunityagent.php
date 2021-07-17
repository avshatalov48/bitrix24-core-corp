<?php

namespace Bitrix\Crm\Agent\Opportunity;

use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;

class LeadManualOpportunityAgent extends \Bitrix\Crm\Agent\AgentBase
{
	private static $instance = null;

	public static function getInstance()
	{
		if (self::$instance === null)
		{
			self::$instance = new static();
		}
		return self::$instance;
	}

	public static function doRun()
	{
		$instance = static::getInstance();
		if ($instance === null)
		{
			return false;
		}

		if (!$instance->isEnabled())
		{
			return false;
		}

		$progressData = $instance->getProgressData();

		$offsetId = isset($progressData['LAST_ITEM_ID']) ? (int)($progressData['LAST_ITEM_ID']) : 0;
		$limit = (int)Option::get('crm', '~CRM_LEAD_MANUAL_OPPORTUNITY_INITIATED_LIMIT', 50);
		if ($limit <= 0)
		{
			$instance->markAsDisabled();
			return false;
		}
		$items = $instance->getItems($offsetId, $limit);
		if (!count($items))
		{
			$instance->markAsDisabled();
			return false;
		}

		$leadId = $offsetId;

		$connection = Application::getConnection();
		$tableName = \CCrmLead::TABLE_NAME;
		foreach ($items as $item)
		{
			$leadId = (int)$item['ID'];
			if ($item['IS_MANUAL_OPPORTUNITY'] === 'N' && $item['OPPORTUNITY'] > 0)
			{
				$productCount = \CCrmProductRow::GetRowQuantity('L', $leadId);
				if (!$productCount)
				{
					$connection->query("UPDATE {$tableName} SET IS_MANUAL_OPPORTUNITY='Y' WHERE ID={$leadId}");
				}
			}
		}
		$instance->setProgressData(['LAST_ITEM_ID' => $leadId]);
		return true;
	}

	public function isEnabled()
	{
		return Option::get('crm', '~CRM_LEAD_MANUAL_OPPORTUNITY_INITIATED', 'N') !== 'Y';
	}

	public function markAsDisabled()
	{
		Option::set('crm', '~CRM_LEAD_MANUAL_OPPORTUNITY_INITIATED', 'Y');
	}

	public function getProgressData()
	{
		$s = Option::get('crm', '~CRM_LEAD_MANUAL_OPPORTUNITY_INITIATED_PROGRESS', '');
		$data = $s !== '' ? unserialize($s, ['allowed_classes' => false]) : null;
		if (!is_array($data))
		{
			$data = array();
		}
		$data['LAST_ITEM_ID'] = isset($data['LAST_ITEM_ID']) ? (int)($data['LAST_ITEM_ID']) : 0;

		return $data;
	}

	public function setProgressData(array $data)
	{
		Option::set('crm', '~CRM_LEAD_MANUAL_OPPORTUNITY_INITIATED_PROGRESS', serialize($data));
	}

	public function getItems($offsetId, $limit)
	{
		$filter = [
			'CHECK_PERMISSIONS' => 'N',
			'>OPPORTUNITY' => 0
		];
		if ($offsetId > 0)
		{
			$filter['>ID'] = $offsetId;
		}

		$dbResult = \CCrmLead::GetListEx(
			array('ID' => 'ASC'),
			$filter,
			false,
			array('nTopCount' => $limit),
			array('ID', 'OPPORTUNITY', 'IS_MANUAL_OPPORTUNITY')
		);

		$results = array();

		if (is_object($dbResult))
		{
			while ($fields = $dbResult->Fetch())
			{
				$results[] = $fields;
			}
		}

		return $results;
	}
}