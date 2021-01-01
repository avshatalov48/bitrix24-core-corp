<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Loader;
use Bitrix\Voximplant\Limits;

class CVoxImplantComponentConfigRent extends \CBitrixComponent
{
	protected $showTemplate = true;

	protected function init()
	{
		$request = Bitrix\Main\Context::getCurrent()->getRequest();

		if (isset($request['AJAX_CALL']) && $request['AJAX_CALL'] == 'Y')
		{
			$this->showTemplate = false;
			return;
		}

		if(isset($this->arParams['TEMPLATE_HIDE']) && $this->arParams['TEMPLATE_HIDE'] === 'Y')
			$this->showTemplate = false;
	}

	protected function prepareData()
	{
		$this->arResult['CAN_RENT_NUMBER'] = Limits::canRentNumber();
		$this->arResult['RENT_PACKET_SIZE'] = (int)$_REQUEST['PACKET_SIZE'] ?: 1;

		$account = new CVoxImplantAccount();
		$this->arResult['CURRENT_BALANCE'] = $account->GetAccountBalance();

		$this->arResult['IFRAME'] = $this->request['IFRAME'] === 'Y';
	}

	/**
	 * Executes component
	 */
	public function executeComponent()
	{
		if (!Loader::includeModule('voximplant'))
			return false;

		$permissions = \Bitrix\Voximplant\Security\Permissions::createWithCurrentUser();
		if(!$permissions->canPerform(\Bitrix\Voximplant\Security\Permissions::ENTITY_LINE, \Bitrix\Voximplant\Security\Permissions::ACTION_MODIFY))
			return false;

		if(!Limits::canManageTelephony())
			return false;

		$this->init();
		$this->prepareData();
		if ($this->showTemplate)
			$this->includeComponentTemplate();

		return $this->arResult;
	}
}


?>