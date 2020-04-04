<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Loader;
use Bitrix\Main\Error;
use Bitrix\Main\Engine\Contract\Controllerable;

use Bitrix\Seo;
use Bitrix\Crm\Tracking;
use Bitrix\Crm\UI\Webpack;

Loc::loadMessages(__FILE__);

class CrmTrackingSourceEditComponent  extends \CBitrixComponent implements Controllerable
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
		$this->arParams['IS_ADDED'] = isset($this->arParams['IS_ADDED']) ? $this->arParams['IS_ADDED'] === 'Y' : false;

		if (!isset($this->arParams['ID']))
		{
			$this->arParams['ID'] = 0;
		}
		$this->arParams['CODE'] = null;
		if (!is_numeric($this->arParams['ID']))
		{
			$staticSourceCodes = array_column(Tracking\Provider::getStaticSources(), 'CODE');
			if (!in_array($this->arParams['ID'], $staticSourceCodes))
			{
				$this->arParams['ID'] = null;
			}

			$this->arParams['CODE'] = $this->arParams['ID'];
			$this->arParams['ID'] = null;
		}
	}

	protected function preparePostRefDomains($domains)
	{
		$list = [];
		$domains = is_array($domains) ? $domains : [];
		foreach ($domains as $domain)
		{
			$domain = trim($domain);
			if (!$domain)
			{
				continue;
			}

			$list[] = $domain;
		}

		return $list;
	}

	protected function preparePost()
	{
		$name = $this->request->get('NAME');
		if ($this->arParams['CODE'])
		{
			$name = $this->arResult['ROW']['NAME'];
		}

		$data = [
			'CODE' => $this->arParams['CODE'],
			'NAME' => $name,
			'ICON_COLOR' => $this->arParams['CODE'] ? '' : $this->request->get('ICON_COLOR'),
			'UTM_SOURCE' => $this->request->get('UTM_SOURCE'),
			'TAGS' => [],
		];
		if ($this->arResult['ROW']['ID'])
		{
			if ($this->request->get('archive') === 'Y')
			{
				$result = Tracking\Internals\SourceTable::update($this->arResult['ROW']['ID'], ['ACTIVE' => 'N']);
			}
			else
			{
				$result = Tracking\Internals\SourceTable::update($this->arResult['ROW']['ID'], $data);
			}
		}
		else
		{
			$result = Tracking\Internals\SourceTable::add($data);
			$this->arResult['ROW']['ID'] = $result->getId() ?: 0;
		}

		if ($result->isSuccess())
		{
			if ($this->arParams['CODE'] && $this->request->get('AD_ACCOUNT_ID'))
			{
				Tracking\Analytics\Ad::setAccountIdByCode(
					$this->arParams['CODE'],
					$this->request->get('AD_ACCOUNT_ID')
				);
			}

			Tracking\Internals\SourceFieldTable::setSourceField(
				$this->arResult['ROW']['ID'],
				Tracking\Internals\SourceFieldTable::FIELD_REF_DOMAIN,
				$this->preparePostRefDomains($this->request->get('REF_DOMAIN'))
			);

			Webpack\CallTracker::rebuildEnabled();

			$uri = str_replace(
				'#id#',
				$this->arResult['ROW']['CODE'] ?: $this->arResult['ROW']['ID'],
				$this->arParams['PATH_TO_EDIT']
			);
			$uri = (new \Bitrix\Main\Web\Uri($uri));
			if ($this->arParams['IFRAME'])
			{
				$uri->addParams(['IFRAME' => 'Y']);
			}
			if (!$this->arResult['ROW']['ID'])
			{
				$uri->addParams(['IS_ADDED' => 'Y']);
			}

			LocalRedirect($uri->getLocator());
		}
		else
		{
			$this->errors->add($result->getErrors());
		}
	}

	public function configureActions()
	{
		return [];
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
		$GLOBALS['APPLICATION']->SetTitle(Loc::getMessage('CRM_ANALYTICS_SOURCE_EDIT_TITLE'));

		$row = null;
		if ($this->arParams['CODE'] || $this->arParams['ID'])
		{
			$rowFilter = [];
			if ($this->arParams['CODE'])
			{
				$rowFilter['=CODE'] = $this->arParams['CODE'];
			}
			if ($this->arParams['ID'])
			{
				$rowFilter['=ID'] = $this->arParams['ID'];
			}
			$row = Tracking\Internals\SourceTable::getRow(['filter' => $rowFilter]);
		}

		$this->arResult['ROW'] = $row ?: [
			'ID' => $this->arParams['ID'],
			'CODE' => $this->arParams['CODE'],
			'NAME' => $this->arParams['CODE'],
			'UTM_SOURCE' => null,
		];

		$this->arResult['ROW'] += [
			'ICON_CLASS' => null,
			'CONFIGURABLE' => true,
			'AD_ACCOUNT_ID' => Tracking\Analytics\Ad::getAccountIdByCode($this->arParams['CODE'])
		];

		if ($this->arResult['ROW']['CODE'])
		{
			$code = $this->arResult['ROW']['CODE'];
			$adsSources = Tracking\Provider::getStaticSources();
			$adsSources = array_combine(array_column($adsSources, 'CODE'), $adsSources);
			if (isset($adsSources[$code]))
			{
				$this->arResult['ROW']['NAME'] = $adsSources[$code]['NAME'];
				$this->arResult['ROW']['ICON_CLASS'] = $adsSources[$code]['ICON_CLASS'];
				$this->arResult['ROW']['CONFIGURABLE'] = $adsSources[$code]['CONFIGURABLE'];
			}
		}

		if ($this->request->isPost() && check_bitrix_sessid() && $this->arResult['ROW']['CONFIGURABLE'])
		{
			$this->preparePost();
		}

		if (!$this->arResult['ROW']['CONFIGURABLE'])
		{
			$GLOBALS['APPLICATION']->SetTitle($this->arResult['ROW']['NAME']);
		}
		elseif ($this->arResult['ROW']['CODE'])
		{
			$GLOBALS['APPLICATION']->SetTitle(Loc::getMessage(
				'CRM_ANALYTICS_SOURCE_EDIT_TITLE_ADS',
				['%name%' => $this->arResult['ROW']['NAME']]
			));
		}

		$this->arResult['ROW']['REF_DOMAIN'] = Tracking\Internals\SourceFieldTable::getSourceField(
			$this->arResult['ROW']['ID'],
			Tracking\Internals\SourceFieldTable::FIELD_REF_DOMAIN
		);
		$this->arResult['ROW']['REF_DOMAIN'] = array_map(
			function ($item)
			{
				return [
					'id' => $item,
					'name' => $item,
					'data' => []
				];
			},
			$this->arResult['ROW']['REF_DOMAIN']
		);

		$adType = Tracking\Analytics\Ad::getSeoCodeByCode($this->arResult['ROW']['CODE']);
		$this->arResult['AD_UPDATE_ACCESSIBLE'] = Tracking\Manager::isAdUpdateAccessible();
		$this->arResult['AD_ACCESSIBLE'] = Tracking\Manager::isAdAccessible();
		$this->arResult['PROVIDER'] = $this->arResult['AD_ACCESSIBLE'] ? self::getAdProvider($adType) : null;
		$this->arResult['HAS_AUTH'] = $this->arResult['AD_ACCESSIBLE'] && !empty($this->arResult['PROVIDER']['HAS_AUTH']);
		if (!$this->arResult['PROVIDER'])
		{
			$this->arResult['AD_ACCESSIBLE'] = false;
		}

		$this->arResult['PATH_TO_EXPENSES'] = str_replace('#id#', $this->arResult['ROW']['ID'], $this->arParams['PATH_TO_EXPENSES']);

		return true;
	}

	protected function printErrors()
	{
		foreach ($this->errors as $error)
		{
			ShowError($error->getMessage());
		}
	}

	public function onPrepareComponentParams($arParams)
	{
		$this->errors = new \Bitrix\Main\ErrorCollection();
		if (!Loader::includeModule('crm'))
		{
			$this->errors->setError(new Error('Module `crm` is not installed.'));
			return $arParams;
		}

		$this->arParams = $arParams;
		$this->initParams();
		if (!$this->checkRequiredParams())
		{
			$this->printErrors();
		}

		return $this->arParams;
	}

	public function executeComponent()
	{
		if (!$this->errors->isEmpty())
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

	protected function prepareAjaxAnswer(array $data)
	{
		$errorTexts = Seo\Analytics\Service::getErrors();
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

	protected static function getAdProvider($adType)
	{
		$providers = Seo\Analytics\Service::getProviders();
		$isFound = false;
		$provider = array();
		foreach ($providers as $type => $provider)
		{
			if ($type == $adType)
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

	public function getProviderAction($type)
	{
		$data = self::getAdProvider($type);

		return $this->prepareAjaxAnswer($data);
	}

	public function getAccountsAction($type)
	{
		$data = Seo\Analytics\Service::getAccounts($type);

		return $this->prepareAjaxAnswer($data);
	}

	public function disconnectAction($type)
	{
		Seo\Analytics\Service::removeAuth($type);
		$data = self::getAdProvider($type);

		return $this->prepareAjaxAnswer($data);
	}
}