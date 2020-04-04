<?php

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

class VoximplantClosingDocumentsRequestAjaxController extends \Bitrix\Main\Engine\Controller
{
	public function __construct(\Bitrix\Main\Request $request = null)
	{
		parent::__construct($request);

		\Bitrix\Main\Loader::includeModule('voximplant');
	}

	public function renderComponentAction()
	{
		return new \Bitrix\Main\Engine\Response\Component(
			'bitrix:voximplant.invoice.request',
			'',
			[

			]
		);
	}

	public function sendRequestAction($period, $index, $address, $email)
	{
		$api = new CVoxImplantHttp();
		$result = $api->sendClosingDocumentsRequest($period, $index, $address, $email);

		if(!$result)
		{
			$this->addError(new \Bitrix\Main\Error($api->GetError()->msg));
			return null;
		}
		if($result->error)
		{
			$this->addError(new \Bitrix\Main\Error($result->error->msg));
			return null;
		}

		CUserOptions::SetOption("voximplant.invoice.request", "default_index", $index);
		CUserOptions::SetOption("voximplant.invoice.request", "default_address", $address);

		return [];
	}
}