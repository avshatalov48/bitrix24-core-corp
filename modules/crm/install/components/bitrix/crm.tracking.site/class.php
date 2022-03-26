<?

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Loader;
use Bitrix\Main\Error;

use Bitrix\Crm\UI\Webpack;
use Bitrix\Crm\Tracking;


if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

Loc::loadMessages(__FILE__);

class CrmTrackingChannelSiteComponent extends \CBitrixComponent
{
	/** @var ErrorCollection $errors */
	protected $errors;
	protected $trackingSiteId;

	protected function checkRequiredParams()
	{
		return $this->errors->count() == 0;
	}

	protected function initParams()
	{
		$this->arParams['ID'] = isset($this->arParams['ID']) ? (int) $this->arParams['ID'] : null;
		$this->arParams['SET_TITLE'] = isset($this->arParams['SET_TITLE']) ? (bool) $this->arParams['SET_TITLE'] : true;
	}

	protected function preparePost()
	{
		if ($this->request->get('remove') === 'Y' && $this->arResult['ROW']['ID'])
		{
			$result = Tracking\Internals\SiteTable::delete($this->arResult['ROW']['ID']);
			if ($result->isSuccess())
			{
				$this->finalizePost();
			}
			else
			{
				$this->errors->add($result->getErrors());
			}

			return;
		}


		$phones = [];
		$list = $this->request->get('PHONES');
		$list = is_array($list) ? $list : [];
		foreach ($list as $item)
		{
			if (!is_string($item) || mb_strlen($item) > 30)
			{
				continue;
			}
			$item = trim($item);
			$phones[] = $item;
		}


		$emails = [];
		$list = $this->request->get('EMAILS');
		$list = is_array($list) ? $list : [];
		foreach ($list as $item)
		{
			if (!is_string($item))
			{
				continue;
			}
			$item = trim($item);
			if (!check_email($item))
			{
				continue;
			}

			$emails[] = $item;
		}

		$address = (new \Bitrix\Main\Web\Uri($this->request->get('ADDRESS')));
		$name = $address->getHost();
		$address = $address->getLocator();

		$data = [
			'HOST' => $name,
			'ADDRESS' => $address,
			'ACTIVE' => $this->request->get('deactivate') === 'Y' ? 'N' : 'Y',
			'IS_INSTALLED' => $this->request->get('IS_INSTALLED') === 'Y' ? 'Y' : 'N',
			'PHONES' => $phones,
			'EMAILS' => $emails,
			'REPLACE_TEXT' => $this->request->get('REPLACE_TEXT') === 'Y' ? 'Y' : 'N',
			'ENRICH_TEXT' => $this->request->get('ENRICH_TEXT') === 'Y' ? 'Y' : 'N',
			'RESOLVE_DUPLICATES' => $this->request->get('RESOLVE_DUPLICATES') === 'Y' ? 'Y' : 'N',
		];
		if ($this->arResult['ROW']['ID'])
		{
			$result = Tracking\Internals\SiteTable::update($this->arResult['ROW']['ID'], $data);
		}
		else
		{
			$result = Tracking\Internals\SiteTable::add($data);
		}

		if ($result->isSuccess())
		{
			$this->finalizePost();
		}
		else
		{
			$this->errors->add($result->getErrors());
		}
	}

	protected function finalizePost()
	{
		Webpack\CallTracker::rebuildEnabled();
		LocalRedirect($this->request->getRequestUri());
	}

	protected function prepareResult()
	{
		if ($this->arParams['SET_TITLE'] == 'Y')
		{
			$GLOBALS['APPLICATION']->SetTitle(
				Loc::getMessage('CRM_ANALYTICS_CHANNEL_SITE_TITLE')
			);
		}

		$row = $this->arParams['ID']
			? Tracking\Internals\SiteTable::getRow(['filter' => ['=ID' => $this->arParams['ID']]])
			: null;
		$this->arResult['ROW'] = $row ?: [
			'ACTIVE' => 'Y',
			'REPLACE_TEXT' => 'Y',
			'ENRICH_TEXT' => 'Y',
			'RESOLVE_DUPLICATES' => 'Y',
			'PHONES' => [],
			'EMAILS' => [],
		];

		if ($this->request->isPost() && check_bitrix_sessid())
		{
			$this->preparePost();
		}

		$this->arResult['PHONES'] = array_map(
			function ($item)
			{
				return ['id' => $item, 'data' => [], 'name' => $item];
			},
			$this->arResult['ROW']['PHONES']
		);
		$this->arResult['EMAILS'] = array_map(
			function ($item)
			{
				return ['id' => $item, 'data' => [], 'name' => $item];
			},
			$this->arResult['ROW']['EMAILS']
		);

		$this->arResult['SOURCES'] = Webpack\CallTracker::getSources(true)
			?: Webpack\CallTracker::getDemoSources()
		;

		$callTrackerWebpack = Webpack\CallTracker::instance();
		if (!$callTrackerWebpack->isBuilt())
		{
			$callTrackerWebpack->build();
		}

		$callTrackerEditorWebpack = Webpack\CallTrackerEditor::instance();
		if (!$callTrackerEditorWebpack->isBuilt())
		{
			$callTrackerEditorWebpack->build();
		}
		$this->arResult['SCRIPT_LOADER'] = $callTrackerWebpack->getEmbeddedScript();

		return true;
	}

	protected function printErrors()
	{
		$list = [];
		foreach ($this->errors as $error)
		{
			/** @var Error $error */
			$list[] = $error->getMessage();
		}

		$list = array_unique($list);
		foreach ($list as $error)
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