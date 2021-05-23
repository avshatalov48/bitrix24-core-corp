<?php
namespace Bitrix\Crm\Agent\Timeline;

use Bitrix\Main;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Entity\Query;
use Bitrix\Crm\Timeline\Entity\TimelineTable;
use Bitrix\Crm\Agent\AgentBase;

abstract class EntityTimelineBuildAgent extends AgentBase
{
	/**
	 * @return EntityTimelineBuildAgent|null
	 */
	public static function getInstance()
	{
		return null;
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
			//Trace('Disabled', 'Y', 1);
			return false;
		}

		/*
		//Disable all timeline agents
		//$instance->enable(false);
		//return false;
		*/

		$progressData = $instance->getProgressData();

		$offsetID = isset($progressData['LAST_ITEM_ID']) ? (int)($progressData['LAST_ITEM_ID']) : 0;
		$processedItemQty = isset($progressData['PROCESSED_ITEMS']) ? (int)($progressData['PROCESSED_ITEMS']) : 0;
		$totalItemQty = isset($progressData['TOTAL_ITEMS']) ? (int)($progressData['TOTAL_ITEMS']) : 0;
		if($totalItemQty <= 0)
		{
			$totalItemQty = $instance->getTotalEntityCount();
		}

		$itemIDs = $instance->getEnityIDs($offsetID, $instance->getIterationLimit());
		$itemQty = count($itemIDs);

		if($itemQty === 0)
		{
			$instance->enable(false);
			//Trace('Completed', $totalItemQty, 1);
			return false;
		}

		$instance->build($itemIDs);

		$processedItemQty += $itemQty;
		$progressData['LAST_ITEM_ID'] = $itemIDs[$itemQty - 1];
		$progressData['PROCESSED_ITEMS'] = $processedItemQty;
		$progressData['TOTAL_ITEMS'] = $totalItemQty;

		$instance->setProgressData($progressData);
		//Trace('Running', "{$processedItemQty} from {$totalItemQty}", 1);
		return true;
	}
	//endregion

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

	public function isActive()
	{
		$dbResult = \CAgent::GetList(
			array('ID' => 'DESC'),
			array('NAME' => get_called_class().'::run(%')
		);
		return is_object($dbResult) && is_array($dbResult->Fetch());
	}

	public function activate($delay = 0)
	{
		if(!is_int($delay))
		{
			$delay = (int)$delay;
		}

		if($delay < 0)
		{
			$delay = 0;
		}

		\CAgent::AddAgent(
			get_called_class().'::run();',
			'crm',
			'N',
			0,
			'',
			'Y',
			ConvertTimeStamp(time() + \CTimeZone::GetOffset() + $delay, 'FULL')
		);
	}

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

	public abstract function build(array $itemIDs);
	protected abstract function getOptionName();
	protected abstract function getProgressOptionName();
	protected abstract function getTotalEntityCount();
	protected abstract function getEnityIDs($offsetID, $limit);

	protected function getIterationLimit()
	{
		return 100;
	}

	protected function filterEntityIDs(array $entityIDs, $entityTypeID)
	{
		if(empty($entityIDs))
		{
			return array();
		}

		//Cast away entities that are already in timeline.
		$query = new Query(TimelineTable::getEntity());
		$query->addFilter('@ASSOCIATED_ENTITY_ID', $entityIDs);
		$query->addFilter('=ASSOCIATED_ENTITY_TYPE_ID', $entityTypeID);
		$query->addSelect('ASSOCIATED_ENTITY_ID');
		$query->addGroup('ASSOCIATED_ENTITY_ID');

		$dbResult = $query->exec();
		$map = array_fill_keys($entityIDs, true);
		while($fields = $dbResult->fetch())
		{
			if(isset($fields['ASSOCIATED_ENTITY_ID']))
			{
				unset($map[$fields['ASSOCIATED_ENTITY_ID']]);
			}
		}

		return array_keys($map);
	}
}