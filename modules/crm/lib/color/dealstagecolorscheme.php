<?php
namespace Bitrix\Crm\Color;

use Bitrix\Main;
use Bitrix\Crm;

/**
 * @deprecated
 */
class DealStageColorScheme extends PhaseColorScheme
{
	/** @var DealStageColorScheme[]  */
	private static $items = array();
	/** @var array|null  */
	private static $names = null;
	/** @var int */
	private $categoryID = 0;
	/**
	 * @param int $categoryID Deal category ID.
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentTypeException
	 */
	public function __construct($categoryID = 0)
	{
		if(!is_int($categoryID))
		{
			$categoryID = (int)$categoryID;
		}
		$categoryID = max($categoryID, 0);

		$this->categoryID = $categoryID;
		parent::__construct(self::prepareCategoryOptionName($categoryID));
	}
	public static function getName($categoryID)
	{
		return self::prepareCategoryOptionName($categoryID);
	}
	/**
	 * Get Element Names
	 * @return array
	 */
	public function getElementNames()
	{
		return self::getStageNames($this->categoryID);
	}
	/**
	 * Get Stage Names for specified category.
	 * @param int $categoryID Deal Category ID.
	 * @return array
	 */
	public static function getStageNames($categoryID)
	{
		if(self::$names === null)
		{
			self::$names = array();
		}

		if(!isset(self::$names[$categoryID]))
		{
			self::$names[$categoryID] = array_keys(\CCrmDeal::GetStageNames($categoryID));
		}
		return self::$names[$categoryID];
	}
	/**
	 * Get default element color by semantic ID.
	 * @param string $stageID Deal stage ID.
	 * @param int $categoryID Deal category ID.
	 * @param int $index Deal Stage Index.
	 * @return string
	 */
	public static function getDefaultColorByStage($stageID, $categoryID = 0, $index = -1)
	{
		$options = array();
		$semanticID = \CCrmDeal::GetSemanticID($stageID);
		if($semanticID === Crm\PhaseSemantics::PROCESS)
		{
			if($index < 0)
			{
				$index = array_search($stageID, self::getStageNames($categoryID), true);
			}
			$options['offset'] = $index;
		}
		return self::getDefaultColorBySemantics($semanticID, $options);
	}
	/**
	 * Get Deal category ID
	 * @return int
	 */
	public function getCategoryId()
	{
		return $this->categoryID;
	}
	/**
	 * Get default color for element.
	 * @param string $name Element Name.
	 * @param int $index Element Index.
	 * @return string
	 */
	public function getDefaultColor($name, $index = -1)
	{
		return self::getDefaultColorByStage($name, $this->categoryID, $index);
	}

	/**
	 * Get scheme by category
	 * @param int $categoryID Deal category ID.
	 * @return DealStageColorScheme
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentTypeException
	 * @throws Main\NotImplementedException
	 */
	public static function getByCategory($categoryID = 0)
	{
		if(!is_int($categoryID))
		{
			$categoryID = (int)$categoryID;
		}
		$categoryID = max($categoryID, 0);

		if(!self::$items[$categoryID])
		{
			self::$items[$categoryID] = new DealStageColorScheme($categoryID);
			if(!self::$items[$categoryID]->load())
			{
				self::$items[$categoryID]->setupByDefault();
			}
		}
		return self::$items[$categoryID];
	}
	public static function getByName($name)
	{
		$categoryID = self::resolveCategoryByName($name);
		return $categoryID >= 0 ? self::getByCategory($categoryID) : null;
	}
	public static function resolveCategoryByName($name)
	{
		if($name === 'CONFIG_STATUS_DEAL_STAGE')
		{
			return 0;
		}

		if(preg_match('/CONFIG_STATUS_DEAL_STAGE_(\d+)/', $name, $m) === 1 && count($m) > 1)
		{
			return (int)$m[1];
		}
		return -1;
	}
	/**
	 * Prepare option name for specified category.
	 * @param int $categoryID Deal category ID.
	 * @return string
	 */
	protected static function prepareCategoryOptionName($categoryID)
	{
		return $categoryID > 0 ? "CONFIG_STATUS_DEAL_STAGE_{$categoryID}" : 'CONFIG_STATUS_DEAL_STAGE';
	}
	/**
	 * Remove scheme by category.
	 * @param int $categoryID Deal category ID.
	 * @return bool
	 * @throws Main\ArgumentNullException
	 */
	public static function removeByCategory($categoryID)
	{
		if(!is_int($categoryID))
		{
			$categoryID = (int)$categoryID;
		}

		if($categoryID < 0)
		{
			return false;
		}

		self::removeByName(self::prepareCategoryOptionName($categoryID));
		return true;
	}
	/**
	 * Get current scheme (for default category)
	 * @return DealStageColorScheme
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentTypeException
	 * @throws Main\NotImplementedException
	 */
	public static function getCurrent()
	{
		return self::getByCategory(0);
	}
}