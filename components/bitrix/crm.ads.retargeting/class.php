<?

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Loader;
use Bitrix\Main\Error;
use Bitrix\Main\Engine\Contract\Controllerable;

use \Bitrix\Crm\Ads\AdsAudience;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

Loc::loadMessages(__FILE__);

class CrmAdsRetargetingComponent extends CBitrixComponent implements Controllerable
{
	/** @var ErrorCollection $errors */
	protected $errors;

	protected function checkRequiredParams()
	{
		if (!Loader::includeModule('crm'))
		{
			$this->errors->setError(new Error('Module `crm` is not installed.'));
			return false;
		}

		return true;
	}

	protected function initParams()
	{
		$this->arParams['INPUT_NAME_PREFIX'] = isset($this->arParams['INPUT_NAME_PREFIX']) ? $this->arParams['INPUT_NAME_PREFIX'] : '';
		if (!isset($this->arParams['HAS_ACCESS']))
		{
			/**@var $USER \CAllUser*/
			global $USER;
			$crmPerms = new CCrmPerms($USER->GetID());
			$this->arParams['HAS_ACCESS'] = $crmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE');
		}

		return $this->arParams;
	}

	protected function listKeysSignedParameters()
	{
		return [
			'INPUT_NAME_PREFIX',
			'HAS_ACCESS',
		];
	}

	protected function prepareResult()
	{
		$this->arResult['ERRORS'] = array();

		return true;
	}

	protected function printErrors()
	{
		foreach ($this->errors as $error)
		{
			ShowError($error);
		}
	}

	public function executeComponent()
	{
		if (!$this->errors->isEmpty())
		{
			return;
		}

		if (!$this->prepareResult())
		{
			$this->printErrors();
			return;
		}

		$this->includeComponentTemplate();
	}

	public function onPrepareComponentParams($arParams)
	{
		$this->errors = new \Bitrix\Main\ErrorCollection();
		$this->arParams = $arParams;

		if (!$this->checkRequiredParams())
		{
			$this->printErrors();
		}

		$this->initParams();

		return $this->arParams;
	}

	protected function checkAccess()
	{
		if (!$this->arParams['HAS_ACCESS'])
		{
			$this->errors->setError(new Error('Access denied.'));
			return false;
		}

		return true;
	}

	protected function prepareAjaxAnswer(array $data)
	{
		$errorTexts = AdsAudience::getErrors();
		foreach ($errorTexts as $errorText)
		{
			$this->errors->setError(new Error($errorText));
		}

		/** @var Error $error */
		$error = $this->errors->current();

		return [
			'data' => $data,
			'error' => !$this->errors->isEmpty(),
			'text' => $error ? $error->getMessage() : ''
		];
	}

	public function configureActions()
	{
		return [];
	}

	public function getAccountsAction($type)
	{
		$data = [];
		if ($this->checkAccess())
		{
			$data = AdsAudience::getAccounts($type);
		}

		return $this->prepareAjaxAnswer($data);
	}

	public function getProviderAction($type)
	{
		$data = [];
		if ($this->checkAccess())
		{
			$data = static::getAdsProvider($type);
		}

		return $this->prepareAjaxAnswer($data);
	}

	public function logoutAction($type)
	{
		$data = [];
		if ($this->checkAccess())
		{
			AdsAudience::removeAuth($type);
			$data = static::getAdsProvider($type);
		}

		return $this->prepareAjaxAnswer($data);
	}

	public function getAudiencesAction($type, $accountId = null)
	{
		$data = [];
		if ($this->checkAccess())
		{
			$data = AdsAudience::getAudiences($type, $accountId);
		}

		return $this->prepareAjaxAnswer($data);
	}

	protected static function getAdsProvider($adsType)
	{
		$providers = AdsAudience::getProviders();
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
}