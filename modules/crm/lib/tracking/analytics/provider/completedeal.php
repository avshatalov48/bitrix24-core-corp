<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2018 Bitrix
 */
namespace Bitrix\Crm\Tracking\Analytics\Provider;

use Bitrix\Crm;


class CompleteDeal extends Base
{
	public function getCode()
	{
		return 'deals-success';
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