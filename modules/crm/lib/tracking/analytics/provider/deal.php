<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2018 Bitrix
 */
namespace Bitrix\Crm\Tracking\Analytics\Provider;

use Bitrix\Crm;

/**
 * Class Deal
 * @package Bitrix\Crm\Tracking\Analytics\Provider
 */
class Deal extends Base
{
	/**
	 * Get code.
	 *
	 * @return string
	 */
	public function getCode()
	{
		return 'deals';
	}

	/**
	 * Get entity ID.
	 *
	 * @return int|null
	 */
	public function getEntityId()
	{
		return \CCrmOwnerType::Deal;
	}

	/**
	 * Get entity name.
	 *
	 * @return string|null
	 */
	public function getEntityName()
	{
		return \CCrmOwnerType::getCategoryCaption($this->getEntityId());
	}

	/**
	 * Get path.
	 *
	 * @return string
	 */
	public function getPath()
	{
		return '/crm/deal/list/';
	}

	/**
	 * Query data.
	 *
	 * @return array
	 */
	public function query()
	{
		$query = Crm\DealTable::query();
		return $this->performQuery($query, \CCrmOwnerType::Deal);
	}
}