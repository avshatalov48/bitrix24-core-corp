<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Loader;
use Bitrix\Main\Error;

use Bitrix\Crm\Tracking;
use Bitrix\Crm\UI\Webpack;


/**
 * Class CrmTrackingChannelComponent
 */
class CrmTrackingSettingsComponent extends \CBitrixComponent
{
	/** @var ErrorCollection $errors */
	protected $errors;

	protected function checkRequiredParams()
	{
		return $this->errors->count() == 0;
	}

	protected function initParams()
	{
		$this->arParams['SET_TITLE'] = isset($this->arParams['SET_TITLE']) ? (bool) $this->arParams['SET_TITLE'] : true;
	}

	protected function preparePost()
	{
		Tracking\Settings::setAttrWindow($this->request->get('ATTR_WINDOW'));
		Tracking\Settings::setAttrWindowOffline($this->yn2Bool($this->request->get('ATTR_WINDOW_OFFLINE')));
		Tracking\Settings::setSocialRefDomain($this->yn2Bool($this->request->get('SOCIAL_REF_DOMAIN_USED')));

		if ($this->arResult['DATA']['ATTR_WINDOW'] !== Tracking\Settings::getAttrWindow())
		{
			Webpack\Guest::instance()->build();
			Webpack\CallTracker::rebuildEnabled();
			\CAgent::AddAgent(
				'\\Bitrix\\Crm\\SiteButton\\Manager::updateScriptCacheAgent();',
				"crm",
				"N",
				60,
				"",
				"Y",
				\ConvertTimeStamp(time()+\CTimeZone::GetOffset(), "FULL")
			);
		}

		LocalRedirect($this->request->getRequestUri());
	}

	protected function prepareResult()
	{
		$this->arResult['DATA'] = [
			'ATTR_WINDOW' => Tracking\Settings::getAttrWindow(),
			'ATTR_WINDOW_OFFLINE' => $this->bool2Yn(Tracking\Settings::isAttrWindowOffline()),
			'SOCIAL_REF_DOMAIN_USED' => $this->bool2Yn(Tracking\Settings::isSocialRefDomainUsed()),
		];

		if ($this->request->isPost() && check_bitrix_sessid())
		{
			$this->preparePost();
		}

		if ($this->arParams['SET_TITLE'] == 'Y')
		{
			$GLOBALS['APPLICATION']->SetTitle(Loc::getMessage('CRM_TRACKING_SETTINGS_TITLE'));
		}


		return true;
	}

	protected function bool2Yn($value)
	{
		return $value ? 'Y' : 'N';
	}

	protected function yn2Bool($value)
	{
		return $value === 'Y';
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
		$this->errors = new \Bitrix\Main\ErrorCollection();
		if (!Loader::includeModule('crm'))
		{
			$this->errors->setError(new Error('Module `crm` is not installed.'));
			$this->printErrors();
			return;
		}

		$this->initParams();
		if (!$this->checkRequiredParams())
		{
			$this->printErrors();
			return;
		}

		if (!$this->prepareResult())
		{
			$this->printErrors();
			return;
		}

		$this->printErrors();
		$this->includeComponentTemplate();
	}
}