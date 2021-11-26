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
use Bitrix\Bitrix24\Feature;

use Bitrix\Seo;
use Bitrix\Intranet;
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

	protected function preparePostUtmSource($utmSources)
	{
		$list = [];
		$utmSources = is_array($utmSources) ? $utmSources : [];
		foreach ($utmSources as $utmSource)
		{
			$utmSource = trim($utmSource);
			if (!$utmSource)
			{
				continue;
			}

			$list[] = $utmSource;
		}

		return $list;
	}

	protected function preparePost()
	{
		$name = $this->request->get('NAME');
		$data = [
			'NAME' => $name,
			'ICON_COLOR' => $this->arParams['CODE'] ? '' : $this->request->get('ICON_COLOR'),
			//'UTM_SOURCE' => trim($this->request->get('UTM_SOURCE')),
			'TAGS' => [],
			'AD_CLIENT_ID' => $this->arParams['CODE'] ? $this->request->get('AD_CLIENT_ID') : null,
			'AD_ACCOUNT_ID' => $this->arParams['CODE'] ? $this->request->get('AD_ACCOUNT_ID') : null,
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
			$data['CODE'] = $this->arParams['CODE'];
			$result = Tracking\Internals\SourceTable::add($data);
			$this->arResult['ROW']['ID'] = $result->getId() ?: 0;
		}

		if ($result->isSuccess())
		{
			Tracking\Internals\SourceFieldTable::setSourceField(
				$this->arResult['ROW']['ID'],
				Tracking\Internals\SourceFieldTable::FIELD_REF_DOMAIN,
				$this->preparePostRefDomains($this->request->get('REF_DOMAIN'))
			);

			Tracking\Internals\SourceFieldTable::setSourceField(
				$this->arResult['ROW']['ID'],
				Tracking\Internals\SourceFieldTable::FIELD_UTM_SOURCE,
				$this->preparePostUtmSource($this->request->get('UTM_SOURCE'))
			);

			Webpack\CallTracker::rebuildEnabled();

			$uri = str_replace(
				'#id#',
				$this->arResult['ROW']['ID'],
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
		return [
			'disconnect' => [
				'+prefilters' => [
					new Intranet\ActionFilter\IntranetUser(),
				]
			],
			'getAccounts' => [
				'+prefilters' => [
					new Intranet\ActionFilter\IntranetUser(),
				]
			],
			'getProvider' => [
				'+prefilters' => [
					new Intranet\ActionFilter\IntranetUser(),
				]
			],
		];
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
		if ($this->arParams['ID'])
		{
			$rowFilter = [];
			$rowFilter['=ID'] = $this->arParams['ID'];
			$row = Tracking\Internals\SourceTable::getRow(['filter' => $rowFilter]);
		}

		$this->arResult['ROW'] = $row ?: [
			'ID' => $this->arParams['ID'],
			'CODE' => $this->arParams['CODE'],
			'NAME' => '',
			'AD_CLIENT_ID' => null,
			'AD_ACCOUNT_ID' => null,
			//'UTM_SOURCE' => null,
		];

		$this->arResult['ROW'] += [
			'ICON_CLASS' => null,
			'CONFIGURABLE' => true,
			'ADVERTISABLE' => false,
			'UTM_CONTENT' => null,
		];

		$hasCode = !empty($this->arResult['ROW']['CODE']);
		if ($hasCode)
		{
			$code = $this->arResult['ROW']['CODE'];
			$this->arParams['CODE'] = $code;
			$adsSources = Tracking\Provider::getStaticSources();
			$adsSources = array_combine(array_column($adsSources, 'CODE'), $adsSources);
			if (isset($adsSources[$code]))
			{
				if (!$this->arResult['ROW']['NAME'])
				{
					$this->arResult['ROW']['NAME'] = $adsSources[$code]['NAME'];
				}
				$this->arResult['ROW']['ICON_CLASS'] = $adsSources[$code]['ICON_CLASS'];
				$this->arResult['ROW']['CONFIGURABLE'] = $adsSources[$code]['CONFIGURABLE'];
				$this->arResult['ROW']['ADVERTISABLE'] = $adsSources[$code]['ADVERTISABLE'];
				$this->arResult['ROW']['UTM_CONTENT'] = $adsSources[$code]['UTM_CONTENT'] ?? null;
			}
		}

		$this->arResult['FEATURE_CODE'] = (
			!$hasCode
			&& Loader::includeModule('bitrix24')
			&& !Feature::isFeatureEnabled("crm_tracking_sources_own")
		)
			? "crm_tracking_sources_own"
			: null
		;

		if ($this->request->isPost() && check_bitrix_sessid() && $this->arResult['ROW']['CONFIGURABLE'] && !$this->arResult['FEATURE_CODE'])
		{
			$this->preparePost();
		}

		if (!$this->arResult['ROW']['CONFIGURABLE'])
		{
			$GLOBALS['APPLICATION']->SetTitle($this->arResult['ROW']['NAME']);
		}
		elseif ($this->arResult['ROW']['ADVERTISABLE'])
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

		$this->arResult['ROW']['UTM_SOURCE'] = Tracking\Internals\SourceFieldTable::getSourceField(
			$this->arResult['ROW']['ID'],
			Tracking\Internals\SourceFieldTable::FIELD_UTM_SOURCE
		);
		$this->arResult['ROW']['UTM_SOURCE'] = array_map(
			function ($item)
			{
				return [
					'id' => $item,
					'name' => $item,
					'data' => []
				];
			},
			$this->arResult['ROW']['UTM_SOURCE']
		);

		Tracking\Analytics\Ad::updateAccountIdCompatible();
		$adType = Tracking\Analytics\Ad::getSeoCodeByCode($this->arResult['ROW']['CODE']);
		$this->arResult['AD_UPDATE_ACCESSIBLE'] = $hasCode && Tracking\Manager::isAdUpdateAccessible();
		$this->arResult['AD_ACCESSIBLE'] = $hasCode && $this->arResult['ROW']['ADVERTISABLE'] && Tracking\Manager::isAdAccessible();
		$this->arResult['PROVIDER'] = $this->arResult['AD_ACCESSIBLE']
			? self::getAdProvider($adType, $this->arResult['ROW']['AD_CLIENT_ID'])
			: null;
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
		$errorTexts = [];
		if (Loader::includeModule('seo'))
		{
			$errorTexts = Seo\Analytics\Service::getErrors();
		}

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

	protected static function getAdProvider($adType, $clientId = null)
	{
		if (!Loader::includeModule('seo'))
		{
			return null;
		}

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

		$provider['PROFILE'] = current(array_filter(
			$provider['CLIENTS'],
			function ($item) use ($clientId)
			{
				return $item['CLIENT_ID'] == $clientId;
			}
		)) ?: null;

		return $provider;
	}

	public function getProviderAction($type, $clientId = null)
	{
		$data = self::getAdProvider($type, $clientId);

		return $this->prepareAjaxAnswer($data);
	}

	public function getAccountsAction($type, $clientId)
	{
		$data = [];
		if (Loader::includeModule('seo'))
		{
			$data = Seo\Analytics\Service::getInstance()
				->setClientId($clientId)
				->getAccounts($type);
		}

		return $this->prepareAjaxAnswer($data);
	}

	public function disconnectAction($type, $clientId)
	{
		if (Loader::includeModule('seo'))
		{
			Seo\Analytics\Service::getInstance()
				->setClientId($clientId)
				->removeAuth($type);
		}

		$data = self::getAdProvider($type);

		return $this->prepareAjaxAnswer($data);
	}
}