<?php
namespace Bitrix\Crm\Color;

use Bitrix\Main;
use Bitrix\Crm;

/**
 * @deprecated
 */
class QuoteStatusColorScheme extends PhaseColorScheme
{
	/** @var QuoteStatusColorScheme|null  */
	private static $current = null;
	/** @var array|null  */
	private static $names = null;

	public function __construct()
	{
		parent::__construct(self::getName());
	}

	public static function getName()
	{
		return 'CONFIG_STATUS_QUOTE_STATUS';
	}

	/**
	 * Get Status Names
	 * @return array
	 */
	public static function getStatusNames()
	{
		if(self::$names === null)
		{
			self::$names = array_keys(\CCrmQuote::GetStatuses());
		}
		return self::$names;
	}

	/**
	 * Get default element color by semantic ID.
	 * @param string $statusID Quote status ID.
	 * @param int $index Quote Status Index.
	 * @return string
	 */
	public static function getDefaultColorByStatus($statusID, $index = -1)
	{
		$options = array();
		$semanticID = \CCrmQuote::GetSemanticID($statusID);
		if($semanticID === Crm\PhaseSemantics::PROCESS)
		{
			if($index < 0)
			{
				$index = array_search($statusID, self::getStatusNames(), true);
			}
			$options['offset'] = $index;
		}
		return self::getDefaultColorBySemantics($semanticID, $options);
	}
	/**
	 * Get default color for element.
	 * @param string $name Element Name.
	 * @param int $index Element Index.
	 * @return string
	 */
	public function getDefaultColor($name, $index = -1)
	{
		return self::getDefaultColorByStatus($name, $index);
	}
	/**
	 * Get Element Names
	 * @return array
	 */
	public function getElementNames()
	{
		return self::getStatusNames();
	}
	/**
	 * Get current scheme
	 * @return QuoteStatusColorScheme
	 * @throws Main\ArgumentNullException
	 */
	public static function getCurrent()
	{
		if(self::$current === null)
		{
			self::$current = new QuoteStatusColorScheme();
			if(!self::$current->load())
			{
				self::$current->setupByDefault();
			}
		}
		return self::$current;
	}
}