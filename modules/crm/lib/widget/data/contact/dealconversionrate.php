<?php
namespace Bitrix\Crm\Widget\Data\Contact;

use Bitrix\Crm\Widget\Data\DataContext;
use Bitrix\Crm\Widget\Data\DealDataSource;
use Bitrix\Main;
use Bitrix\Main\Type\Collection;

class DealConversionRate extends DealDataSource
{
	const TYPE_NAME = 'CONTACT_DEAL_CONV_RATE';
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
			$name = 'ACTIVITY';
		}

		if($name == 'ACTIVITY')
		{
			$numeratorSrc = new DealSumStatistics($this->settings, $this->userID, $this->enablePermissionCheck);
			$numeratorItems = $numeratorSrc->getList(array(
				'group' => $group,
				'filter' => $params['filter'],
				'select' => array(array('name' => 'SUCCESS_SUM', 'aggregate' => 'COUNT'))
			));

			$denominatorSrc = new ActivityStatistics($this->settings, $this->userID, $this->enablePermissionCheck);
			$denominatorItems = $denominatorSrc->getList(array(
				'group' => $group,
				'filter' => $params['filter'],
				'select' => array(array('name' => 'TOTAL_QTY', 'aggregate' => 'SUM'))
			));
		}
		else
		{
			throw new Main\NotSupportedException("The field '{$name}' is not supported in current context");
		}

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

					$qty = isset($item['TOTAL_QTY']) ? (int)$item['TOTAL_QTY'] : 0;
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

					$qty = isset($item['TOTAL_QTY']) ? (int)$item['TOTAL_QTY'] : 0;
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
			$numerator = count($numeratorItems) !== 0 && isset($numeratorItems[0]['SUCCESS_SUM'])
				? (int)$numeratorItems[0]['SUCCESS_SUM'] : 0;

			$denominator = count($denominatorItems) !== 0 && isset($denominatorItems[0]['TOTAL_QTY'])
				? (int)$denominatorItems[0]['TOTAL_QTY'] : 0;

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
				'entity' => \CCrmOwnerType::ContactName,
				'title' => GetMessage('CRM_CONTACT_DEAL_CONVERSION_RATE_PRESET_ACTIVITY'),
				'name' => self::TYPE_NAME.'::ACTIVITY',
				'source' => self::TYPE_NAME,
				'select' => array('name' => 'ACTIVITY'),
				'context' => DataContext::PERCENT,
			),
		);

		return $result;
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
