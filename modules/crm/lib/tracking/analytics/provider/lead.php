<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2018 Bitrix
 */
namespace Bitrix\Crm\Tracking\Analytics\Provider;

use Bitrix\Crm;


class Lead extends Base
{
	public function getCode()
	{
		return 'leads';
	}

	public function getPath()
	{
		return '/crm/lead/list/';
	}

	public function query()
	{
		$query = Crm\LeadTable::query();
		return $this->performQuery($query, \CCrmOwnerType::Lead);
	}
}