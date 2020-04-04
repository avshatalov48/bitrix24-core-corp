<?php
namespace Bitrix\Crm\Filter;
class DealSettings extends EntitySettings
{
	const FLAG_RECURRING = 1;

	/** @var int */
	protected $categoryID = -1;
	/** @var array|null */
	protected $categoryAccess = null;

	function __construct(array $params)
	{
		parent::__construct($params);

		$this->categoryID = isset($params['categoryID'])
			? (int)$params['categoryID'] : -1;

		$this->categoryAccess = isset($params['categoryAccess']) && is_array($params['categoryAccess'])
			? $params['categoryAccess'] : array();
	}

	/**
	 * Get Entity Type ID.
	 * @return int
	 */
	public function getEntityTypeID()
	{
		return \CCrmOwnerType::Deal;
	}

	/**
	 * Get User Field Entity ID.
	 * @return string
	 */
	public function getUserFieldEntityID()
	{
		return \CCrmDeal::GetUserFieldEntityID();
	}

	/**
	 * Get Deal Category ID.
	 * @return int
	 */
	public function getCategoryID()
	{
		return $this->categoryID;
	}

	/**
	 * Get Deal Category Access Data.
	 * @return array
	 */
	public function getCategoryAccessData()
	{
		return $this->categoryAccess;
	}
}