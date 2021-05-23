<?

use Bitrix\Main\Config\Option;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Error;
use Bitrix\Socialnetwork\UserWelltoryTable;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

class CIntranetStressLevelComponent extends \CBitrixComponent implements \Bitrix\Main\Errorable
{
	/** @var ErrorCollection errorCollection */
	protected $errorCollection;

	public function getErrorByCode($code)
	{
		return $this->errorCollection->getErrorByCode($code);
	}

	/**
	 * Getting array of errors.
	 * @return Error[]
	 */
	public function getErrors()
	{
		return $this->errorCollection->toArray();
	}

	public function onPrepareComponentParams($params)
	{
		global $USER;

		$this->errorCollection = new ErrorCollection();

		if (
			!isset($params['PAGE'])
			|| !in_array($params['PAGE'], ['result', 'disclaimer'])
		)
		{
			$params['PAGE'] = '';
		}

		if (
			!isset($params['USER_ID'])
			|| intval($params['USER_ID']) <= 0
		)
		{
			$params['USER_ID'] = $USER->getId();
		}
		else
		{
			$params['USER_ID'] = intval($params['USER_ID']);
		}

		if (
			!isset($params['LAST_RESULTS_COUNT'])
			|| intval($params['LAST_RESULTS_COUNT']) <= 0
			|| intval($params['LAST_RESULTS_COUNT']) >= 10
		)
		{
			$params['LAST_RESULTS_COUNT'] = 4;
		}
		else
		{
			$params['LAST_RESULTS_COUNT'] = intval($params['LAST_RESULTS_COUNT']);
		}

		return $params;
	}

	public function getData()
	{
		global $USER;
		$this->arResult['HISTORIC_DATA'] = \Bitrix\Socialnetwork\Item\UserWelltory::getHistoricData([
			'userId' => $this->arParams['USER_ID'],
			'limit' => $this->arParams['LAST_RESULTS_COUNT']
		]);

		$this->arResult['LAST_DATA'] = (
			!empty($this->arResult['HISTORIC_DATA'])
				? $this->arResult['HISTORIC_DATA'][0]
				: []
		);

		$this->arResult['HISTORIC_DATA'] = array_reverse($this->arResult['HISTORIC_DATA']);
		if ($this->arParams['USER_ID'] != $USER->getId())
		{
			unset($this->arResult['HISTORIC_DATA']);
		}
	}

	protected function getDisclaimerType()
	{
		$lang = (Loader::includeModule('bitrix24') ? \CBitrix24::getLicensePrefix() : LANGUAGE_ID);
		switch($lang)
		{
			case 'ru':
			case 'kz':
			case 'by':
			case 'ua':
				$type = 'ru';
				break;
			default:
				$type = 'en';
		}
		$this->arResult['DISCLAIMER_TYPE'] = $type;
	}

	public function executeComponent()
	{
		global $USER;

		if ($this->arParams['PAGE'] == 'result')
		{
			if (
				Option::get('intranet', 'stresslevel_available', 'Y') != 'Y'
				|| (
					Loader::includeModule('bitrix24')
					&& !\Bitrix\Bitrix24\Release::isAvailable('stresslevel')
				)
				|| (
					$this->arParams['USER_ID'] != $USER->getId()
					&& \Bitrix\Socialnetwork\Item\UserWelltory::getAccess([
						'userId' => $this->arParams["USER_ID"]
					]) != 'Y'
				)
			)
			{
				$this->errorCollection->setError(new Error(Loc::getMessage("INTRANET_STRESSLEVEL_NO_PERMISSIONS")));
				return;
			}
			$this->getData();
		}
		elseif ($this->arParams['PAGE'] == 'disclaimer')
		{
			$this->getDisclaimerType();
		}

		$this->IncludeComponentTemplate($this->arParams['PAGE']);
	}
}



