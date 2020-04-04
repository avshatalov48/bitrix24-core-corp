<?php
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Text\Encoding;

define('STOP_STATISTICS', true);
define('BX_SECURITY_SHOW_MESSAGE', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

if (
	!Loader::includeModule('crm') && !Loader::includeModule('seo') &&
	!Loader::includeModule('socialservices')
)
{
	return;
}

use \Bitrix\Crm\Ads\AdsForm;

Loc::loadMessages(__FILE__);

class CrmAdsFormAjaxController extends \Bitrix\Crm\SiteButton\ComponentController
{
	protected function getActions()
	{
		return array(
			'getProvider',
			'getAccounts',
			'getForms',
			'exportForm',
			'unlinkForm',
			'logout',
			'logoutGroup',
			'registerGroup',
		);
	}

	protected function getProvider()
	{
		$type = $this->request->get('type');
		$this->responseData['data'] = static::getAdsProvider($type);
		$this->checkAdsErrors();
	}

	protected function logout()
	{
		$type = $this->request->get('type');
		AdsForm::removeAuth($type);
		$this->responseData['data'] = static::getAdsProvider($type);
		$this->checkAdsErrors();
	}

	protected function logoutGroup()
	{
		$type = $this->request->get('type');
		$groupId = $this->request->get('groupId');

		AdsForm::unRegisterGroup($type, $groupId);
		$this->checkAdsErrors();
	}

	protected function registerGroup()
	{
		$type = $this->request->get('type');
		$groupId = $this->request->get('groupId');

		if (!AdsForm::registerGroup($type, $groupId))
		{
			$this->errors[] = 'Can not register group.';
			return;
		}

		$this->responseData['data'] = [
			'groupAuthUrl' => AdsForm::getService()
				->getForm($type)
				->getGroupAuthAdapter()
				->getAuthUrl()
		];
	}

	protected function getAccounts()
	{
		$type = $this->request->get('type');
		$this->responseData['data'] = AdsForm::getAccounts($type);
		$this->checkAdsErrors();
	}

	protected function getForms()
	{
		$type = $this->request->get('type');
		$accountId = $this->request->get('accountId');
		$this->responseData['data'] = AdsForm::getForms($type, $accountId);
		$this->checkAdsErrors();
	}

	protected function exportForm()
	{
		$type = $this->request->get('type');
		$accountId = $this->request->get('accountId');
		$crmFormId = $this->request->get('crmFormId');
		$accountName = $this->request->get('accountName');
		$crmFormName = $this->request->get('crmFormName');
		$formLocale = $this->request->get('formLocale');
		$crmFormSuccessUrl = $this->request->get('crmFormSuccessUrl');

		$parameters = array();
		if ($crmFormName)
		{
			$parameters['ADS_FORM_NAME'] = Encoding::convertEncodingToCurrent($crmFormName);
		}
		if ($accountName)
		{
			$parameters['ADS_ACCOUNT_NAME'] = Encoding::convertEncodingToCurrent($accountName);
		}
		if ($crmFormSuccessUrl)
		{
			$parameters['ADS_FORM_SUCCESS_URL'] = Encoding::convertEncodingToCurrent($crmFormSuccessUrl);
		}
		if ($formLocale)
		{
			$parameters['LOCALE'] = $formLocale;
		}

		$this->responseData['data'] = AdsForm::exportForm($type, $accountId, $crmFormId, $parameters);
		$this->checkAdsErrors();
	}

	protected function unlinkForm()
	{
		$type = $this->request->get('type');
		$crmFormId = $this->request->get('crmFormId');

		AdsForm::unlinkForm($crmFormId, $type);
		$this->checkAdsErrors();
	}

	protected function checkAdsErrors()
	{
		$this->errors = array_merge($this->errors, AdsForm::getErrors());
	}

	protected static function getAdsProvider($adsType)
	{
		$providers = AdsForm::getProviders();
		$isFound = false;
		$provider = array();
		foreach ($providers as $type => $provider)
		{
			if ($type == $adsType)
			{
				$isFound = true;
				break;
			}
		}

		if (!$isFound)
		{
			return null;
		}

		return $provider;
	}

	protected function checkPermissions()
	{
		/**@var $USER \CAllUser*/
		global $USER;
		return AdsForm::canUserEdit($USER->GetID());
	}

	protected function prepareRequestData()
	{
		$this->requestData = array(

		);
	}
}

$controller = new CrmAdsFormAjaxController();
$controller->exec();