<?php
namespace Bitrix\Crm\Conversion;
use Bitrix\Main;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Entity\Query;
use Bitrix\Crm\Conversion\Entity\EntityConversionMapTable;
use Bitrix\Crm\UserField\UserFieldHistory;

class EntityConversionMap
{
	/** @var EntityConversionMap[] $items */
	protected static $items = array();
	/** @var int $srcEntityTypeID */
	protected $srcEntityTypeID = 0;
	/** @var int $dstEntityTypeID */
	protected $dstEntityTypeID = 0;
	/** @var EntityConversionMapItem[] $srcIndex */
	protected $srcIndex = null;
	/** @var EntityConversionMapItem[] $dstIndex */
	protected $dstIndex = null;
	/** @var DateTime $time */
	protected $time = null;
	/** @var DateTime $syncTime */
	protected $syncTime = null;
	/** @var boolean $isSynchronized */
	//protected $isSynchronized = false;
	/** @var boolean|null $isOutOfDate */
	protected $isOutOfDate = null;

	public function __construct($srcEntityTypeID = 0, $dstEntityTypeID = 0)
	{
		$this->setSourceEntityTypeID($srcEntityTypeID);
		$this->setDestinationEntityTypeID($dstEntityTypeID);
		$this->time = new DateTime();
	}

	public function getSourceEntityTypeID()
	{
		return $this->srcEntityTypeID;
	}

	public function setSourceEntityTypeID($entityTypeID)
	{
		$this->srcEntityTypeID = $entityTypeID;
	}

	public function getDestinationEntityTypeID()
	{
		return $this->dstEntityTypeID;
	}

	public function setDestinationEntityTypeID($entityTypeID)
	{
		$this->dstEntityTypeID = $entityTypeID;
	}

	public function getTime()
	{
		return $this->time;
	}

	public function setTime(DateTime $time)
	{
		$this->time = $time;
	}

	public function isOutOfDate()
	{
		if($this->isOutOfDate === null)
		{
			$srcLastChanged = UserFieldHistory::getLastChangeTime($this->srcEntityTypeID);
			$dstLastChanged = UserFieldHistory::getLastChangeTime($this->dstEntityTypeID);

			$lastChanged = null;
			if($srcLastChanged !== null && $dstLastChanged !== null)
			{
				$lastChanged = $srcLastChanged->getTimestamp() > $dstLastChanged->getTimestamp()
					? $srcLastChanged : $dstLastChanged;
			}
			elseif($srcLastChanged !== null || $dstLastChanged !== null)
			{
				$lastChanged = $srcLastChanged !== null
					? $srcLastChanged : $dstLastChanged;
			}

			$this->isOutOfDate = $lastChanged !== null && $lastChanged->getTimestamp() > $this->time->getTimestamp();
		}

		return $this->isOutOfDate;
	}

	public function createItem($srcFieldID, $dstFieldID = '', array $options = null)
	{
		$item = new EntityConversionMapItem($srcFieldID, $dstFieldID, $options);
		$this->setItem($item);
		return $item;
	}

	public function findItemBySourceID($srcID)
	{
		return $this->srcIndex !== null && isset($this->srcIndex[$srcID]) ? $this->srcIndex[$srcID] : null;
	}

	public function findItemByDestinationID($dstID)
	{
		return $this->dstIndex !== null && isset($this->dstIndex[$dstID]) ? $this->dstIndex[$dstID] : null;
	}

	/**
	 * Get items
	 * @return  EntityConversionMapItem[]
	*/
	public function getItems()
	{
		return $this->srcIndex !== null ? array_values($this->srcIndex) : array();
	}

	public function setItem(EntityConversionMapItem $item)
	{
		if($this->srcIndex == null)
		{
			$this->srcIndex = array();
		}

		$srcID = $item->getSourceField();
		$this->srcIndex[$srcID] = $item;

		if($this->dstIndex == null)
		{
			$this->dstIndex = array();
		}

		$dstID = $item->getDestinationField();
		if($dstID === '')
		{
			$dstID = $srcID;
		}
		$this->dstIndex[$dstID] = $item;
	}

	public function removeItem(EntityConversionMapItem $item)
	{
		$srcID = $item->getSourceField();
		if($this->srcIndex !== null && isset($this->srcIndex[$srcID]))
		{
			unset($this->srcIndex[$srcID]);
		}

		$dstID = $item->getDestinationField();
		if($dstID === '')
		{
			$dstID = $srcID;
		}

		if($this->dstIndex !== null && isset($this->dstIndex[$dstID]))
		{
			unset($this->dstIndex[$dstID]);
		}
	}

	public function removeAllItems()
	{
		if($this->srcIndex !== null)
		{
			$this->srcIndex = null;
		}

		if($this->dstIndex !== null)
		{
			$this->dstIndex = null;
		}
	}

	public function resolveDestinationID($srcID, $default = '')
	{
		return $this->srcIndex !== null && isset($this->srcIndex[$srcID])
			? $this->srcIndex[$srcID]->getSourceField() : $default;
	}

	public function resolveSourceID($dstID, $default = '')
	{
		return $this->dstIndex !== null && isset($this->dstIndex[$dstID])
			? $this->dstIndex[$dstID]->getSourceField() : $default;
	}

	public function externalize()
	{
		$result = array(
			'srcEntityTypeID' => $this->srcEntityTypeID,
			'dstEntityTypeID' => $this->dstEntityTypeID,
			'time' => $this->time->format(\DateTime::ISO8601),
			//'sync' => $this->isSynchronized,
			'items' => array()
		);

		if($this->srcIndex !== null)
		{
			foreach($this->srcIndex as $item)
			{
				$result['items'][] = $item->externalize();
			}
		}

		return $result;
	}

	public function internalize(array $params)
	{
		$this->srcEntityTypeID = isset($params['srcEntityTypeID']) ? $params['srcEntityTypeID'] : '';
		$this->dstEntityTypeID = isset($params['dstEntityTypeID']) ? $params['dstEntityTypeID'] : '';

		if(isset($params['time']))
		{
			$this->time = new DateTime($params['time'], \DateTime::ISO8601);
		}

		//if(isset($params['sync']))
		//{
		//	$this->isSynchronized = $params['sync'];
		//}

		if(isset($params['items']) && is_array($params['items']))
		{
			foreach($params['items'] as $itemParams)
			{
				$item = new EntityConversionMapItem();
				$item->internalize($itemParams);
				$this->setItem($item);
			}
		}
	}

	/**
	 * Save conversion map
	 * @return void
	 * @throws Main\NotSupportedException
	 */
	public function save()
	{
		EntityConversionMapTable::upsert(
			array(
				'SRC_TYPE_ID' => $this->srcEntityTypeID,
				'DST_TYPE_ID' => $this->dstEntityTypeID,
				'DATA' => serialize($this->externalize())
			)
		);

		$key = "{$this->srcEntityTypeID}_{$this->dstEntityTypeID}";
		if(isset(self::$items[$key]))
		{
			unset(self::$items[$key]);
		}
	}

	/**
	 * Load conversion map
	 * @static
	 * @param int $srcEntityTypeID Source Entity Type ID
	 * @param int $dstEntityTypeID Destination Entity Type ID
	 * @return EntityConversionMap|null
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function load($srcEntityTypeID, $dstEntityTypeID)
	{
		if(!is_int($srcEntityTypeID))
		{
			$srcEntityTypeID = (int)$srcEntityTypeID;
		}

		if(!is_int($dstEntityTypeID))
		{
			$dstEntityTypeID = (int)$dstEntityTypeID;
		}

		$key = "{$srcEntityTypeID}_{$dstEntityTypeID}";
		if(isset(self::$items[$key]))
		{
			return self::$items[$key];
		}

		$query = new Query(EntityConversionMapTable::getEntity());
		$query->addSelect('DATA');
		$query->addFilter('=SRC_TYPE_ID', $srcEntityTypeID);
		$query->addFilter('=DST_TYPE_ID', $dstEntityTypeID);

		$dbResult = $query->exec();
		$result = $dbResult->fetch();
		if(!is_array($result))
		{
			return null;
		}

		$params = isset($result['DATA']) ? unserialize($result['DATA'], ['allowed_classes' => false]) : null;
		if(!is_array($params))
		{
			return null;
		}

		$item = new EntityConversionMap();
		$item->internalize($params);

		return (self::$items[$key] = $item);
	}

	/**
	 * Remove conversion map
	 * @static
	 * @param int $srcEntityTypeID Source Entity Type ID
	 * @param int $dstEntityTypeID Destination Entity Type ID
	 * @return void
	 * @throws \Exception
	 */
	public static function remove($srcEntityTypeID, $dstEntityTypeID)
	{
		if(!is_int($srcEntityTypeID))
		{
			$srcEntityTypeID = (int)$srcEntityTypeID;
		}

		if(!is_int($dstEntityTypeID))
		{
			$dstEntityTypeID = (int)$dstEntityTypeID;
		}

		EntityConversionMapTable::delete(array('SRC_TYPE_ID' => $srcEntityTypeID, 'DST_TYPE_ID' => $dstEntityTypeID));

		$key = "{$srcEntityTypeID}_{$dstEntityTypeID}";
		if(self::$items[$key])
		{
			unset(self::$items[$key]);
		}
	}
}