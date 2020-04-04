<?

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection;

use Bitrix\Crm\Tracking;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

Loc::loadMessages(__FILE__);

class CrmTrackingListComponent extends CBitrixComponent
{
	/** @var ErrorCollection $errors */
	protected $errors;

	protected function checkRequiredParams()
	{
		return true;
	}

	protected function initParams()
	{
		$this->arParams['PATH_TO_LIST'] = isset($this->arParams['PATH_TO_LIST']) ? $this->arParams['PATH_TO_LIST'] : '';
		$this->arParams['SET_TITLE'] = isset($this->arParams['SET_TITLE']) ? $this->arParams['SET_TITLE'] == 'Y' : true;
	}

	protected function preparePost()
	{

	}

	protected function prepareResult()
	{
		/* Set title */
		if ($this->arParams['SET_TITLE'])
		{
			/**@var \CAllMain*/
			$GLOBALS['APPLICATION']->SetTitle(Loc::getMessage('CRM_TRACKING_LIST_TITLE'));
		}

		$this->arResult['ERRORS'] = [];
		$this->arResult['SOURCES'] = $this->getSources();
		$this->arResult['CHANNELS'] = $this->getChannels();

		$this->arResult['ACTION_URI'] = $this->getPath() . '/ajax.php';


		return true;
	}

	protected function getChannels()
	{
		$url = $this->arParams['PATH_TO_CHANNEL'];
		$siteUrl = $this->arParams['PATH_TO_SITE'];
		return array_map(
			function ($channel) use ($url, $siteUrl)
			{
				if ($channel['CODE'] == 'site')
				{
					$url = $siteUrl;
				}
				else
				{
					$url = str_replace(['#id#'], [$channel['CODE']], $url);
				}

				$items = isset($channel['ITEMS']) ? $channel['ITEMS'] : [];
				return [
					'id' => $channel['CODE'],
					'name' => $channel['NAME'],
					'iconClass' => $channel['ICON_CLASS'],
					'selected' => $channel['CONFIGURED'],
					'data' => [
						'url' => str_replace(['#id#'], ['0'], $url),
						'items' => [
							'addUrl' => str_replace(['#id#'], ['0'], $url),
							'addText' => Loc::getMessage('CRM_TRACKING_LIST_BTN_ADD_SITE'),
							'list' => array_map(
								function ($item) use ($url)
								{
									return [
										'text' => $item['NAME'],
										'href' => str_replace(['#id#'], [$item['ID']], $url),
										'active' => isset($item['ACTIVE'])
											? $item['ACTIVE'] === 'Y'
											: null
									];
								},
								$items
							)
						]
					]
				];
			},
			Tracking\Provider::getChannels()
		);
	}

	protected function getSources()
	{
		$sources = [];
		foreach (Tracking\Provider::getAvailableSources() as $row)
		{
			if (!$row['CODE'] || !$row['ID'] || !$row['CONFIGURABLE'])
			{
				$sources[] = $row;
				continue;
			}

			if (!is_array($sources[$row['CODE']]))
			{
				$sources[$row['CODE']] = $row;
				$sources[$row['CODE']]['ITEMS'] = [];

			}

			$sources[$row['CODE']]['ITEMS'][] = $row;
			if (!$sources[$row['CODE']]['CONFIGURED'])
			{
				$sources[$row['CODE']]['CONFIGURED'] = $row['CONFIGURED'];
			}
		}
		$sources = array_values($sources);

		$url = $this->arParams['PATH_TO_EDIT'];
		return array_map(
			function ($row) use ($url)
			{
				$id = $row['CODE'] ?: $row['ID'];
				$items = isset($row['ITEMS']) ? $row['ITEMS'] : [];
				return [
					'id' => $id,
					'name' => !empty($row['SHORT_NAME']) ? $row['SHORT_NAME'] : $row['NAME'],
					'iconClass' => $row['ICON_CLASS'],
					'iconColor' => $row['ICON_COLOR'],
					'selected' => $row['CONFIGURED'],
					'data' => [
						'url' => str_replace('#id#', $id, $url),
						'items' => [
							'addUrl' => str_replace(['#id#'], [$id ?: '0'], $url),
							'addText' => Loc::getMessage('CRM_TRACKING_LIST_BTN_ADD_SOURCE'),
							'list' => array_map(
								function ($item) use ($url)
								{
									return [
										'text' => $item['NAME'],
										'href' => str_replace(['#id#'], [$item['ID']], $url),
										'active' => isset($item['CONFIGURED'])
											? $item['CONFIGURED']
											: null
									];
								},
								$items
							)
						]
					]
				];
			},
			$sources
		);
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

		$this->includeComponentTemplate();
	}
}