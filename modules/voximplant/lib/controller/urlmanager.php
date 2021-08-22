<?php

namespace Bitrix\Voximplant\Controller;

use Bitrix\Main\Engine;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Voximplant\Limits;

class UrlManager extends Engine\Controller
{
	public function getBillingUrlAction()
	{
		if (!Limits::canManageTelephony())
		{
			$this->addError(new \Bitrix\Main\Error(Loc::getMessage("VOX_URLMANAGER_PAID_PLAN_REQUIRED"), "paid_plan_required"));
			return null;
		}

		$apiClient = new \CVoxImplantHttp();
		$result = $apiClient->getBillingUrl();

		if(isset($result['error']))
		{
			$this->addError(new \Bitrix\Main\Error($result['error']['msg'], $result['error']['code']));
			return null;
		}

		$consentRequired = $result['consentRequired'];

		$isDemo = Loader::includeModule('bitrix24') && \CBitrix24::IsDemoLicense();

		return [
			'billingUrl' => $result['billingUrl'],
			'disclaimerText' => $consentRequired ? \CVoxImplantMain::GetTOS() : '',
			'demoWarningTitle' => $isDemo ? \CVoxImplantMain::GetDemoTopUpWarningTitle() : '',
			'demoWarning' => $isDemo ? \CVoxImplantMain::GetDemoTopUpWarning() : '',
		];
	}

	public function getAdditionalDocumentsUploadUrlAction(int $verificationId)
	{
		$docManager = new \CVoxImplantDocuments();
		$url = $docManager->GetAdditionalUploadUrl($verificationId);
		if (!$url)
		{
			$err = $docManager->GetError();
			$this->addError(new Error($err->msg, $err->code));
			return null;
		}

		return [
			'url' => $url
		];
	}
}