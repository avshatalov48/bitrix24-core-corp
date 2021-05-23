<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Loader;
use Bitrix\Main\Error;

use Bitrix\Crm\Tracking;
use Bitrix\Crm\UI\Webpack;


/**
 * Class CrmTrackingChannelComponent
 */
class CrmTrackingB24SiteComponent extends \CBitrixComponent
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
		$this->arParams['IS_SHOP'] = isset($this->arParams['IS_SHOP']) ? (bool) $this->arParams['IS_SHOP'] : true;
	}

	protected function preparePost()
	{
		$sites = $this->request->get('SITE');
		$sites = is_array($sites) ? $sites : [];

		$isShop = $this->arParams['IS_SHOP'] ? 'Y' : 'N';
		Tracking\Internals\SiteB24Table::delete([
			'IS_SHOP' => $isShop
		]);
		foreach ($sites as $siteId => $enabled)
		{
			$enabled = $enabled !== 'N';
			if ($enabled || !$siteId)
			{
				continue;
			}

			Tracking\Internals\SiteB24Table::add([
				'IS_SHOP' => $isShop,
				'LANDING_SITE_ID' => $siteId
			]);
		}

		Webpack\CallTracker::rebuildEnabled();

		$uri = $this->arParams['IS_SHOP'] ?
			$this->arParams['PATH_TO_SITE24']
			:
			$this->arParams['PATH_TO_SHOP24'];

		$uri = (new \Bitrix\Main\Web\Uri($uri));
		if ($this->arParams['IFRAME'])
		{
			$uri->addParams(['IFRAME' => 'Y']);
		}
		LocalRedirect($uri->getLocator());
	}

	protected function prepareResult()
	{
		$channels = Tracking\Provider::getChannels();
		$channels = array_combine(array_column($channels, 'CODE'), $channels);
		$channel = isset($channels[$this->arParams['ID']]) ? $channels[$this->arParams['ID']] : [];
		$this->arResult['ROW'] = $channel;

		$this->arResult['SITES'] = Tracking\Provider::getB24Sites($this->arParams['IS_SHOP']);

		if ($this->request->isPost() && check_bitrix_sessid())
		{
			$this->preparePost();
		}


		$this->arResult['SOURCES'] = Webpack\CallTracker::getSources(true)
			?: Webpack\CallTracker::getDemoSources();

		if ($this->arParams['SET_TITLE'] == 'Y')
		{
			$GLOBALS['APPLICATION']->SetTitle($channel['NAME']);
		}

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
		$this->errors = new \Bitrix\Main\ErrorCollection();
		if (!Loader::includeModule('crm'))
		{
			$this->errors->setError(new Error('Module `crm` is not installed.'));
			$this->printErrors();
			return;
		}
		if (!Loader::includeModule('landing'))
		{
			$this->errors->setError(new Error('Module `landing` is not installed.'));
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