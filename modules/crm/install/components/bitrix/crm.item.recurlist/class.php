<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm\Service\Container;

class CrmItemRecurListComponent extends CBitrixComponent
{
	public function executeComponent()
	{
		$this->arResult = $this->arParams;
		$this->arResult['isRecurring'] = true;

		$entityTypeId = $this->arParams['entityTypeId'] ?? null;
		$templatePage = '';
		if (!Container::getInstance()->getFactory($entityTypeId)?->isRecurringEnabled())
		{
			$templatePage = 'error';
		}

		$this->includeComponentTemplate($templatePage);
	}
}
