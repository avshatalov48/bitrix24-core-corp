<?php

use Bitrix\Intranet\Enum\UserAgentType;
use Bitrix\Main\Config\Option;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die;
}

class IntranetMobilePopup extends \CBitrixComponent
{
	private bool $available;

	public function __construct($component = null)
	{
		parent::__construct($component);
		$this->available = Option::get('intranet', 'showPopupLeadingFromMobileToWeb', 'Y') === 'Y';
	}

	public function executeComponent()
	{
		if ($this->available !== true)
		{
			return;
		}

		//only mobile
		$clientType = UserAgentType::fromRequest($this->request);
		$this->arResult['isMobile'] = $clientType === UserAgentType::MOBILE_APP;

		//installation will be from the controller side
		$this->includeComponentTemplate();
	}
}
