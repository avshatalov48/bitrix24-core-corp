<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Engine\Response\Component;

class Item extends \Bitrix\Main\Engine\Controller
{
	public function getComponentAction($params)
	{
		$params = \Bitrix\Main\Component\ParameterSigner::unsignParameters(
			'bitrix:crm.deal_category.panel',
			$params
		);
		$params['USE_VIEW_TARGET'] = 'N';

		return new Component('bitrix:crm.deal_category.panel', 'tiny', $params);
	}
}