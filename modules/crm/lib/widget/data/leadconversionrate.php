<?php
namespace Bitrix\Crm\Widget\Data;

use Bitrix\Main;
use Bitrix\Main\Type\Collection;

class LeadConversionRate extends LeadDataSource
{
	const TYPE_NAME = 'LEAD_CONV_RATE';
	const GROUP_BY_DATE = 'DATE';
	const GROUP_BY_USER = 'USER';
	private static $messagesLoaded = false;

	/**
	 * @return string
	 */
	public function getTypeName()
	{
		return self::TYPE_NAME;
	}


	/** @return array */
	public function getList(array $params)
	{
		$group = isset($params['group']) ? strtoupper($params['group']) : '';
		if($group !== '' && $group !== self::GROUP_BY_USER && $group !== self::GROUP_BY_DATE)
		{
			$group = '';
		}

		/** @var array $select */
		$select = isset($params['select']) && is_array($params['select']) ? $params['select'] : array();
		$name = '';
		if(!empty($select))
		{
			$selectItem = $select[0];
			if(isset($selectItem['name']))
			{
				$name = $selectItem['name'];
			}
		}

		if($name === '')
		{
			$name = 'SUCCESS';
		}

		$listParams = array(
			'group' => $group,
			'filter' => $params['filter'],
			'select' => array(array('name' => 'COUNT', 'aggregate' => 'COUNT'))
		);

		if($name == 'SUCCESS')
		{
			$numeratorSrc = new LeadConversionStatistics($this->settings, $this->userID, $this->enablePermissionCheck);
			$numeratorItems = $numeratorSrc->getList($listParams);
		}
		elseif($name == 'FAIL')
		{
			$numeratorSrc = new LeadJunk($this->settings, $this->userID, $this->enablePermissionCheck);
			$numeratorItems = $numeratorSrc->getList($listParams);
		}
		else
		{
			throw new Main\NotSupportedException("The field '{$name}' is not supported in current context");
		}

		$denominatorSrc = new LeadInWork($this->settings, $this->userID, $this->enablePermissionCheck);
		$denominatorItems = $denominatorSrc->getList($listParams);

		$results = array();
		if($group === self::GROUP_BY_DATE || $group === self::GROUP_BY_USER)
		{
			$items = array();
			foreach($denominatorItems as $item)
			{
				if($group === self::GROUP_BY_USER)
				{
					$userID = isset($item['USER_ID']) ? (int)$item['USER_ID'] : 0;
					if($userID <= 0)
					{
						continue;
					}

					$qty = isset($item['COUNT']) ? (int)$item['COUNT'] : 0;
					$items[$userID] = array(
						'USER_ID' => $userID,
						'USER' => isset($item['USER']) ? $item['USER'] : $userID,
						'DENOMINATOR' => $qty,
						'NUMERATOR' => 0
					);
				}
				else//if($group === self::GROUP_BY_DATE)
				{
					$date = isset($item['DATE']) ? $item['DATE'] : '';
					if($date === '')
					{
						continue;
					}

					$qty = isset($item['COUNT']) ? (int)$item['COUNT'] : 0;
					$items[$date] = array('DATE' => $date, 'DENOMINATOR' => $qty, 'NUMERATOR' => 0);
				}
			}

			foreach($numeratorItems as $item)
			{
				if($group === self::GROUP_BY_USER)
				{
					$userID = isset($item['USER_ID']) ? (int)$item['USER_ID'] : 0;
					if($userID <= 0)
					{
						continue;
					}

					$currentItem = isset($items[$userID])
						? $items[$userID]
						: array(
							'USER_ID' => $userID,
							'USER' => isset($item['USER']) ? $item['USER'] : $userID,
							'DENOMINATOR' => 0,
							'NUMERATOR' => 0
						);

					if(isset($item['COUNT']))
					{
						$currentItem['NUMERATOR'] = (int)$item['COUNT'];
					}
					$items[$userID] = $currentItem;
				}
				else//if($group === self::GROUP_BY_DATE)
				{
					$date = isset($item['DATE']) ? $item['DATE'] : '';
					if($date === '')
					{
						continue;
					}

					$currentItem = isset($items[$date])
						? $items[$date]
						: array('DATE' => $date, 'DENOMINATOR' => 0, 'NUMERATOR' => 0);

					if(isset($item['COUNT']))
					{
						$currentItem['NUMERATOR'] = (int)$item['COUNT'];
					}
					$items[$date] = $currentItem;
				}
			}

			foreach($items as $item)
			{
				$resultItem = array(
					$name => $item['NUMERATOR'] > 0 && $item['DENOMINATOR'] > 0
						? round(100 * ($item['NUMERATOR'] / $item['DENOMINATOR']), 2) : 0.0
				);

				if($group === self::GROUP_BY_USER)
				{
					$resultItem['USER_ID'] = $item['USER_ID'];
					$resultItem['USER'] = $item['USER'];
				}
				else//if($group === self::GROUP_BY_DATE)
				{
					$resultItem['DATE'] = $item['DATE'];
				}
				$results[] = $resultItem;
			}
		}
		else
		{
			$numerator = count($numeratorItems) !== 0 && isset($numeratorItems[0]['COUNT'])
				? (int)$numeratorItems[0]['COUNT'] : 0;

			$denominator = count($denominatorItems) !== 0 && isset($denominatorItems[0]['COUNT'])
				? (int)$denominatorItems[0]['COUNT'] : 0;

			$value = 0;
			if($numerator > 0 && $denominator > 0)
			{
				$value = round(100 * ($numerator / $denominator), 2);
			}
			$results[] = array($name => $value);
		}

		$results = array_values($results);

		if(isset($params['sort']) && is_array($params['sort']))
		{
			foreach($params['sort'] as $sortItem)
			{
				if(isset($sortItem['name']) && $sortItem['name'] === $name)
				{
					$order = isset($sortItem['order']) && strtolower($sortItem['order']) === 'desc'
						? SORT_DESC : SORT_ASC;
					Collection::sortByColumn($results, array($name => $order));
					break;
				}
			}
		}
		return $results;
	}
	/**
	 * Get current data context
	 * @return DataContext
	 */
	public function getDataContext()
	{
		return DataContext::PERCENT;
	}
	/**
	 * @return array Array of arrays
	 */
	public static function getPresets()
	{
		self::includeModuleFile();
		$result = array(
			array(
				'entity' => \CCrmOwnerType::LeadName,
				'title' => GetMessage('LEAD_CONV_RATE_PRESET_SUCCESS'),
				'name' => self::TYPE_NAME.'::SUCCESS',
				'source' => self::TYPE_NAME,
				'select' => array('name' => 'SUCCESS'),
				'context' => DataContext::PERCENT,
				'category' => 'CONVERTED'
			),
			array(
				'entity' => \CCrmOwnerType::LeadName,
				'title' => GetMessage('LEAD_CONV_RATE_PRESET_FAIL'),
				'name' => self::TYPE_NAME.'::FAIL',
				'source' => self::TYPE_NAME,
				'select' => array('name' => 'FAIL'),
				'context' => DataContext::PERCENT,
				'category' => 'JUNK'
			)
		);

		return $result;
	}

	/**
	 * @return array Array of arrays
	 */
	public static function prepareCategories(array &$categories)
	{
		if(isset($categories['CONVERTED']) && isset($categories['JUNK']))
		{
			return;
		}

		self::includeModuleFile();

		if(!isset($categories['CONVERTED']))
		{
			$categories['CONVERTED'] = array(
				'entity' => \CCrmOwnerType::LeadName,
				'title' => GetMessage('CRM_LEAD_CONV_CATEGORY'),
				'name' => 'CONVERTED',
				'enableSemantics' => false
			);
		}

		if(!isset($categories['JUNK']))
		{
			$categories['JUNK'] = array(
				'entity' => \CCrmOwnerType::LeadName,
				'title' => GetMessage('CRM_LEAD_JUNK_CATEGORY'),
				'name' => 'JUNK',
				'enableSemantics' => false
			);
		}
	}

	/**
	 * @return string
	 */
	public function getDetailsPageUrl(array $params)
	{
		return '';
	}
	/**
	 * @return void
	 */
	protected static function includeModuleFile()
	{
		if(self::$messagesLoaded)
		{
			return;
		}

		Main\Localization\Loc::loadMessages(__FILE__);
		self::$messagesLoaded = true;
	}
}
