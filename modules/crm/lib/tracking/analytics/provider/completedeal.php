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
 * Class CompleteDeal
 * @package Bitrix\Crm\Tracking\Analytics\Provider
 */
class CompleteDeal extends Base
{
	/**
	 * Get code.
	 *
	 * @return string
	 */
	public function getCode()
	{
		return 'deals-success';
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

	public function getPath()
	{
		return '/crm/deal/list/?STAGE_SEMANTIC_ID=' . Crm\PhaseSemantics::SUCCESS;
	}

	public function query()
	{
		$query = Crm\DealTable::query();
		$query->addFilter('=STAGE_SEMANTIC_ID', Crm\PhaseSemantics::SUCCESS);
		return $this->performQuery($query, \CCrmOwnerType::Deal);
	}
}