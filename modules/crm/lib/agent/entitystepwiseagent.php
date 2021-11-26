<?php
namespace Bitrix\Crm\Agent;

use Bitrix\Main\Config\Option;

abstract class EntityStepwiseAgent extends AgentBase
{
	/**
	 * @return EntityStepwiseAgent|null
	 */
	public static function getInstance()
	{
		return null;
	}

	public function isRegistered()
	{
		$dbResult = \CAgent::GetList(
			array('ID' => 'DESC'),
			array('MODULE_ID' => 'crm', 'NAME' => get_called_class().'::run(%')
		);
		return is_object($dbResult) && is_array($dbResult->Fetch());
	}

	public function register($delay = 0)
	{
		if(!is_int($delay))
		{
			$delay = (int)$delay;
		}

		if($delay < 0)
		{
			$delay = 0;
		}

		return \CAgent::AddAgent(
			get_called_class().'::run();',
			'crm',
			'N',
			0,
			'',
			'Y',
			ConvertTimeStamp(time() + \CTimeZone::GetOffset() + $delay, 'FULL')
		);
	}

	public function isEnabled()
	{
		$name = $this->getOptionName();
		return $name !== '' && Option::get('crm', $name, 'N') === 'Y';
	}

	public function enable($enable)
	{
		$name = $this->getOptionName();
		if($name === '')
		{
			return;
		}

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
			Option::set('crm', $name, 'Y');
		}
		else
		{
			Option::delete('crm', array('name' => $name));
		}

		$progressName = $this->getProgressOptionName();
		if($progressName !== '')
		{
			Option::delete('crm', array('name' => $progressName));
		}
	}

	//region AgentBase
	public static function doRun()
	{
		$instance = static::getInstance();
		if($instance === null)
		{
			return false;
		}

		if(!$instance->isEnabled())
		{
			return false;
		}

		$progressData = $instance->getProgressData();

		$offsetID = isset($progressData['LAST_ITEM_ID']) ? (int)($progressData['LAST_ITEM_ID']) : 0;
		$processedItemQty = isset($progressData['PROCESSED_ITEMS']) ? (int)($progressData['PROCESSED_ITEMS']) : 0;

		$limit = $instance->getIterationLimit();
		if($limit <= 0)
		{
			$instance->enable(false);
			return false;
		}

		$itemIDs = $instance->getEntityIDs($offsetID, $limit);
		$itemQty = count($itemIDs);

		if($itemQty === 0)
		{
			$instance->enable(false);
			return false;
		}

		$instance->process($itemIDs);

		$processedItemQty += $itemQty;

		$progressData['LAST_ITEM_ID'] = $itemIDs[$itemQty - 1];
		if (is_array($progressData['LAST_ITEM_ID']) && isset($progressData['LAST_ITEM_ID']['ID']))
		{
			$progressData['LAST_ITEM_ID'] = $progressData['LAST_ITEM_ID']['ID'];
		}
		$progressData['PROCESSED_ITEMS'] = $processedItemQty;

		if (!isset($progressData['TOTAL_ITEMS']) || !isset($progressData['TOTAL_ITEMS_CALCULATED']))
		{
			$progressData['TOTAL_ITEMS'] = $instance->getTotalEntityCount(); // calculate total can be complicated, so do it once
			$progressData['TOTAL_ITEMS_CALCULATED'] = true;
		}
		if ($progressData['TOTAL_ITEMS'] < $progressData['PROCESSED_ITEMS'])
		{
			$progressData['TOTAL_ITEMS'] = $progressData['PROCESSED_ITEMS'];
		}

		$instance->setProgressData($progressData);
		return true;
	}
	//endregion

	public function getProgressData()
	{
		$progressName = $this->getProgressOptionName();
		if($progressName === '')
		{
			return null;
		}

		$s = Option::get('crm', $progressName,  '');
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
		$progressName = $this->getProgressOptionName();
		if($progressName !== '')
		{
			Option::set('crm', $progressName, serialize($data));
		}
	}

	public abstract function process(array $itemIDs);
	protected abstract function getOptionName();
	protected abstract function getProgressOptionName();
	protected abstract function getTotalEntityCount();
	protected abstract function getEntityIDs($offsetID, $limit);
	protected function getIterationLimit()
	{
		return 100;
	}
}