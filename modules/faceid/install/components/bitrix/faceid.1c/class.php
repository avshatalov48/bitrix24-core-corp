<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

if (!\Bitrix\Main\Loader::includeModule("faceid") || !\Bitrix\Main\Loader::includeModule("rest"))
	return;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class FaceId1CComponent extends CBitrixComponent
{
	/**
	 * Start Component
	 */
	public function executeComponent()
	{
		$this->arResult['FACE_KART_AVAILABLE'] = false;

		$app = \Bitrix\Rest\AppTable::getList(array('filter' => array('=CODE' => 'bitrix.1c'), 'select' => array('CLIENT_ID')))->fetch();

		$oauthToken = \Bitrix\Main\Application::getInstance()->getContext()->getRequest()->get('auth');
		$this->arResult['OAUTH_TOKEN'] = $oauthToken;

		$authResult = array();
		$foundToken = \CRestUtil::checkAuth(array('access_token' => $oauthToken), 'crm', $authResult);

		if ($foundToken && $app['CLIENT_ID'] == $authResult['client_id'] && $authResult['user_id'] > 0)
		{
			$this->arResult['FACE_KART_AVAILABLE'] = \Bitrix\FaceId\FaceCard::isAvailableByUser($authResult['user_id']);
		}

		if (!$this->arResult['FACE_KART_AVAILABLE'])
		{
			$this->arResult['ERROR'] = Loc::getMessage("FACEID_1C_TARIFF_ERROR");
		}

		$this->includeComponentTemplate();
	}
}