<?php
namespace Bitrix\Crm\Agent\Requisite;

use Bitrix\Main\Config\Option;
use Bitrix\Crm\Agent\AgentBase;
use Bitrix\Main\SystemException;

abstract class EntityAddressConvertAgent extends AgentBase
{
	const STEP_TTL = 10;
	const ITEM_LIMIT = 20;
	
	protected static $optionName = '';

	/**
	 * @return EntityAddressConvertAgent|null
	 */
	public static function getInstance()
	{
		return null;
	}

	public function isActive()
	{
		$result = false;

		$res = \CAgent::GetList(
			["ID" => "DESC"],
			['=NAME' => get_called_class().'::run();', 'ACTIVE' => 'Y']
		);
		if (is_object($res))
		{
			$row = $res->Fetch();
			if (is_array($row) && !empty($row))
			{
				$result = true;
			}
		}
		if (!$result)
		{
			$res = \CAgent::GetList(
				["ID" => "DESC"],
				['=NAME' => '\\'.get_called_class().'::run();', 'ACTIVE' => 'Y']
			);
			if (is_object($res))
			{
				$row = $res->Fetch();
				if (is_array($row) && !empty($row))
				{
					$result = true;
				}
			}
		}

		return $result;
	}
	public function activate($delay = 0, array $options = [])
	{
		if(!is_int($delay))
		{
			$delay = (int)$delay;
		}

		if($delay < 0)
		{
			$delay = 0;
		}

		if (is_array($options) && !empty($options))
		{
			$this->setProgressData(['OPTIONS' => $options]);
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
	public function isEnabled()
	{
		return Option::get('crm', static::$optionName, 'N') === 'Y';
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
			Option::set('crm', static::$optionName, 'Y');
			$this->enableErrorFlag(false);
		}
		else
		{
			Option::delete('crm', array('name' => static::$optionName));
		}
		Option::delete('crm', array('name' => static::$optionName.'_PROGRESS'));
	}
	public function getProgressData()
	{
		$s = Option::get('crm', static::$optionName.'_PROGRESS',  '');
		$data = $s !== '' ? unserialize($s, ['allowed_classes' => false]) : null;
		if(!is_array($data))
		{
			$data = array();
		}

		$data['OPTIONS'] = isset($data['OPTIONS']) ? $data['OPTIONS'] : 0;
		$data['LAST_ITEM_ID'] = isset($data['LAST_ITEM_ID']) ? (int)($data['LAST_ITEM_ID']) : 0;
		$data['PROCESSED_ITEMS'] = isset($data['PROCESSED_ITEMS']) ? (int)($data['PROCESSED_ITEMS']) : 0;
		$data['TOTAL_ITEMS'] = isset($data['TOTAL_ITEMS']) ? (int)($data['TOTAL_ITEMS']) : 0;

		return $data;
	}
	public function isErrorFlagEnabled()
	{
		return Option::get('crm', static::$optionName.'_ERROR', 'N') === 'Y';
	}
	public function enableErrorFlag(bool $enable)
	{
		if($enable === self::isErrorFlagEnabled())
		{
			return;
		}

		if($enable)
		{
			Option::set('crm', static::$optionName.'_ERROR', 'Y');
		}
		else
		{
			Option::delete('crm', array('name' => static::$optionName.'_ERROR'));
		}
	}
	public function setProgressData(array $data)
	{
		Option::set('crm', static::$optionName.'_PROGRESS', serialize($data));
	}

	public abstract function getTotalCount();
	public abstract function prepareItemIds($offsetId, $limit);
	protected abstract function getConverterInstance();

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

		$isStart = false;

		// Prevent multiple steps
		$timestamp = time();
		if (isset($progressData['TIMESTAMP']))
		{
			$prevTimeStamp = (int)$progressData['TIMESTAMP'];
			if ($timestamp >= $prevTimeStamp && $timestamp - $prevTimeStamp < self::STEP_TTL)
			{
				return true;
			}
		}
		else
		{
			if (!isset($progressData['LAST_ITEM_ID']) || $progressData['LAST_ITEM_ID'] <= 0)
			{
				$isStart = true;
			}
		}
		$progressData['TIMESTAMP'] = $timestamp;
		$instance->setProgressData($progressData);

		$success = true;
		if ($isStart)
		{
			try
			{
				$instance->start();
			}
			catch (SystemException $e)
			{
				$success = false;
			}
		}

		if ($success)
		{
			$offsetId = isset($progressData['LAST_ITEM_ID']) ? (int)($progressData['LAST_ITEM_ID']) : 0;
			$processedItemQty = isset($progressData['PROCESSED_ITEMS']) ? (int)($progressData['PROCESSED_ITEMS']) : 0;
			$totalItemQty = isset($progressData['TOTAL_ITEMS']) ? (int)($progressData['TOTAL_ITEMS']) : 0;
			if($totalItemQty <= 0)
			{
				$totalItemQty = $instance->getTotalCount();
			}

			$limit = (int)Option::get('crm', '~CRM_ENTITY_ADDRESS_CONVERT_STEP_LIMIT',  self::ITEM_LIMIT);
			if($limit <= 0)
			{
				$instance->enable(false);
				return false;
			}

			$itemIds = $instance->prepareItemIds($offsetId, $limit);
			$itemQty = count($itemIds);

			if($itemQty === 0)
			{
				$instance->complete();
				$instance->enable(false);
				return false;
			}

			try
			{
				$instance->convert($itemIds);
			}
			catch(SystemException $e)
			{
				$success = false;
			}
		}

		if (!$success)
		{
			$instance->complete();
			$instance->enable(false);
			$instance->enableErrorFlag(true);
			return false;
		}

		$processedItemQty += $itemQty;
		if($totalItemQty < $processedItemQty)
		{
			$totalItemQty = $instance->getTotalCount();
		}

		unset($progressData['TIMESTAMP']);
		$progressData['LAST_ITEM_ID'] = $itemIds[$itemQty - 1];
		$progressData['PROCESSED_ITEMS'] = $processedItemQty;
		$progressData['TOTAL_ITEMS'] = $totalItemQty;

		$instance->setProgressData($progressData);
		return true;
	}
	public function setAllowedEntityTypes(array $entityTypes = [])
	{
		$map = [];

		foreach ($entityTypes as $entityTypeId)
		{
			$entityTypeId = (int)$entityTypeId;
			if (\CCrmOwnerType::IsDefined($entityTypeId))
			{
				$map[$entityTypeId] = true;
			}
		}
		if (!empty($map) && $this->isEnabled())
		{
			$progressData = $this->getProgressData();
			if (!is_array($progressData))
			{
				$progressData = [];
			}
			if (!is_array($progressData['OPTIONS']))
			{
				$progressData['OPTIONS'] = [];
			}
			$progressData['OPTIONS']['ALLOWED_ENTITY_TYPES'] = array_keys($map);
			$this->setProgressData($progressData);
		}
	}
	public function getAllowedEntityTypes()
	{
		$resultMap = [];

		$progressData = $this->getProgressData();
		if (is_array($progressData) && is_array($progressData['OPTIONS'])
			&& is_array($progressData['OPTIONS']['ALLOWED_ENTITY_TYPES']))
		{
			foreach ($progressData['OPTIONS']['ALLOWED_ENTITY_TYPES'] as $entityTypeId)
			{
				$entityTypeId = (int)$entityTypeId;
				if (\CCrmOwnerType::IsDefined($entityTypeId))
				{
					$resultMap[$entityTypeId] = true;
				}
			}
		}

		return array_keys($resultMap);
	}
	public function start()
	{
		$this->getConverterInstance()->start();
	}
	public function convert(array $itemIds)
	{
		$this->getConverterInstance()->convert($itemIds);
	}
	public function complete()
	{
		$this->getConverterInstance()->complete();
	}
}
