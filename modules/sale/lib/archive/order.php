<?php
namespace Bitrix\Sale\Archive;

use Bitrix\Sale,
	Bitrix\Main;

class Order extends Sale\Order
{
	protected $discountResult = array();
	protected $dateArchived = null;
	protected $archiveVersion = null;

	/**
	 * Restrict recalculation discounts
	 *
	 * @param array $data			Order data.
	 * @return Sale\Result
	 */
	public function applyDiscount(array $data)
	{
		return new Sale\Result();
	}

	/**
	 * @return string
	 */
	public static function getRegistryType()
	{
		return Sale\Registry::REGISTRY_TYPE_ARCHIVE_ORDER;
	}

	/**
	 * Set discount array for detail page.
	 * 
	 * @param array $resultData
	 */
	public function setDiscountData($resultData = array())
	{
		$this->discountResult = $resultData;
	}

	/**
	 * @return array
	 */
	public function getDiscountData()
	{
		return $this->discountResult;
	}

	/**
	 * @return Main\Type\DateTime
	 */
	public function getDateArchived()
	{
		return $this->dateArchived;
	}

	/**
	 * @param Main\Type\DateTime $date
	 * 
	 * @return void
	 */
	public function setDateArchived($date)
	{
		$this->dateArchived = $date;
	}

	/**
	 * @return int
	 */
	public function getVersion()
	{
		return $this->archiveVersion;
	}

	/**
	 * @param int $version		Version of archived entity.
	 *
	 * @return void
	 */
	public function setVersion($version)
	{
		$this->archiveVersion = (int)$version;
	}
}