<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

class CrmKanbanFilterComponent extends \CBitrixComponent
{
	protected $type = '';
	protected $types = array();

	/**
	 * Init class' vars.
	 */
	protected function init()
	{
		if (!\Bitrix\Main\Loader::includeModule('crm'))
		{
			return false;
		}
		$this->types = array(
			'lead' => \CCrmOwnerType::LeadName,
			'deal' => \CCrmOwnerType::DealName,
			'quote' => \CCrmOwnerType::QuoteName,
			'invoice' => \CCrmOwnerType::InvoiceName
		);
		$this->type = strtoupper(isset($this->arParams['ENTITY_TYPE']) ? $this->arParams['ENTITY_TYPE'] : '');
		if (!$this->type || !in_array($this->type, $this->types))
		{
			return false;
		}
		else
		{
			$this->arParams['ENTITY_TYPE'] = $this->type;
		}
		if (!isset($this->arParams['NAVIGATION_BAR']) || !is_array($this->arParams['NAVIGATION_BAR']))
		{
			$this->arParams['NAVIGATION_BAR'] = array();
		}

		return true;
	}

	/**
	 * Base executable method.
	 */
	public function executeComponent()
	{
		if (!$this->init())
		{
			return;
		}

		$this->IncludeComponentTemplate();
	}
}