<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)
{
	die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\Web\Uri;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;

use Bitrix\ImConnector\Limit;
use Bitrix\ImConnector\Output;
use Bitrix\ImConnector\Status;
use Bitrix\ImConnector\Library;
use Bitrix\ImConnector\Connector;

class ImConnectorImessage extends CBitrixComponent
{
	private $cacheId;
	private $connector = 'imessage';
	private $error = [];
	private $messages = [];
	/** @var Output */
	private $connectorOutput;
	/** @var Status */
	private $status;

	protected $pageId = 'page_imess';

	private $listOptions = ['business_id', 'business_name'];
	public $helpDeskParams = 'redirect=detail&code=10798618';
	public $helpLimitDeskParams = 'redirect=detail&code=11735970';
	public $cookieNameIdLine = 'IMESSAGE_ID_LINE';
	public $urlNetwork = 'https://bitrix24.net/oauth/select/imessage/?domain=';

	/**
	 * Check the connection of the necessary modules.
	 *
	 * @return bool
	 * @throws LoaderException
	 */
	protected function checkModules()
	{
		if (Loader::includeModule('imconnector'))
		{
			return true;
		}
		else
		{
			ShowError(Loc::getMessage('IMCONNECTOR_COMPONENT_IMESSAGE_MODULE_NOT_INSTALLED_MSGVER_1'));
			return false;
		}
	}

	/**
	 * Reset cache
	 */
	protected function cleanCache()
	{
		Connector::cleanCacheConnector($this->arParams['LINE'], $this->cacheId);
	}

	/**
	 * @return string
	 */
	protected function getJsLangMessageSetting()
	{
		$cookieNameIdLine = $this->cookieNameIdLine;

		$cookiePrefix = Option::get('main', 'cookie_name', 'BITRIX_SM');

		if(!empty($cookiePrefix))
		{
			$cookieNameIdLine = $cookiePrefix . '_' . $cookieNameIdLine;
		}

		return '<script type="text/javascript">
			BX.message({
				IMCONNECTOR_COMPONENT_IMESSAGE_CONFIRM_TITLE: \'' . GetMessageJS('IMCONNECTOR_COMPONENT_IMESSAGE_CONFIRM_TITLE') . '\',
				IMCONNECTOR_COMPONENT_IMESSAGE_CONFIRM_DESCRIPTION: \'' . GetMessageJS('IMCONNECTOR_COMPONENT_IMESSAGE_CONFIRM_DESCRIPTION_NEW_2', ['#ID#' => $this->helpDeskParams, '#ID_LIMIT#' => $this->helpLimitDeskParams]) . '\',
				IMCONNECTOR_COMPONENT_IMESSAGE_CONFIRM_BUTTON_OK: \'' . GetMessageJS('IMCONNECTOR_COMPONENT_IMESSAGE_CONFIRM_BUTTON_OK') . '\',
				IMCONNECTOR_COMPONENT_IMESSAGE_CONFIRM_BUTTON_CANCEL: \'' . GetMessageJS('IMCONNECTOR_COMPONENT_IMESSAGE_CONFIRM_BUTTON_CANCEL') . '\',
				IMCONNECTOR_COMPONENT_IMESSAGE_LINE_ID: \'' . $this->arParams['LINE'] . '\',
				IMCONNECTOR_COMPONENT_IMESSAGE_COOKIE_NAME: \'' . $cookieNameIdLine . '\',
				IMCONNECTOR_COMPONENT_IMESSAGE_URL_NETWORK: \'' . $this->urlNetwork. Connector::getDomainDefault() . '\',
			});
		</script>';
	}

	/**
	 * Initialize connector before starting actions.
	 */
	protected function initialization()
	{
		global $APPLICATION;

		if(
			!empty($this->arResult['PAGE']) &&
			$this->arResult['PAGE'] == 'connection' &&
			empty($this->request['LINE'])
		)
		{
			$cookieNameIdLine = (int)$this->request->getCookie($this->cookieNameIdLine);
			if(
				!empty($cookieNameIdLine) &&
				$cookieNameIdLine > 0
			)
			{
				$cookie = new \Bitrix\Main\Web\Cookie($this->cookieNameIdLine, '');
				$cookie->setSecure(false);
				$context = \Bitrix\Main\Context::getCurrent();
				$context->getResponse()->addCookie($cookie);
				LocalRedirect($APPLICATION->GetCurPageParam('LINE=' . $cookieNameIdLine, ['LINE']));
			}
		}

		$this->connectorOutput = new Output($this->connector, (int)$this->arParams['LINE']);

		$this->status = Status::getInstance($this->connector, (int)$this->arParams['LINE']);

		$this->arResult['STATUS'] = $this->status->isStatus();
		$this->arResult['ACTIVE_STATUS'] = $this->status->getActive();
		$this->arResult['CONNECTION_STATUS'] = $this->status->getConnection();
		$this->arResult['REGISTER_STATUS'] = $this->status->getRegister();
		$this->arResult['ERROR_STATUS'] = $this->status->getError();
		$this->arResult['CAN_USE_CONNECTION'] = Limit::canUseConnector($this->connector);
		$this->arResult['INFO_HELPER_LIMIT'] = Limit::getIdInfoHelperConnector($this->connector);
		$this->arResult['HELP_LIMIT_DESK_PARAMS'] = $this->helpLimitDeskParams;

		$this->cacheId = Connector::getCacheIdConnector($this->arParams['LINE'], $this->connector);
	}

	protected function registerConnector(): void
	{
		$register = $this->connectorOutput->register();

		if($register->isSuccess())
		{
			$this->messages[] = Loc::getMessage('IMCONNECTOR_COMPONENT_IMESSAGE_OK_REGISTER');

			$this->status->setRegister(true);
			$this->arResult['REGISTER_STATUS'] = true;
			$this->arResult['STATUS'] = true;

			$this->status->setError(false);
			$this->arResult['ERROR_STATUS'] = false;
		}
		else
		{
			$this->error[] = Loc::getMessage('IMCONNECTOR_COMPONENT_IMESSAGE_NO_REGISTER');

			$this->status->setRegister(false);
			$this->arResult['REGISTER_STATUS'] = false;

			$this->arResult['STATUS'] = false;
		}
	}

	protected function saveConnectorSettings(): void
	{
		if(empty($this->arResult['REGISTER_STATUS']))
		{
			$this->connectorOutput->unregister();
		}

		$saved = $this->connectorOutput->saveSettings($this->arResult['FORM']);

		if($saved->isSuccess())
		{
			$this->messages[] = Loc::getMessage('IMCONNECTOR_COMPONENT_IMESSAGE_OK_SAVE');
			$this->arResult['SAVE_STATUS'] = true;
			$this->status->setConnection(true);
			$this->arResult['CONNECTION_STATUS'] = true;

			$this->status->setError(false);
			$this->arResult['ERROR_STATUS'] = false;
		}
		else
		{
			$this->error[] = Loc::getMessage('IMCONNECTOR_COMPONENT_IMESSAGE_NO_SAVE');
			$this->arResult['SAVE_STATUS'] = false;

			$this->status->setConnection(false);
			$this->arResult['CONNECTION_STATUS'] = false;
			$this->status->setRegister(false);
			$this->arResult['REGISTER_STATUS'] = false;

			$this->arResult['STATUS'] = false;
		}
	}

	protected function saveSettingsFromNetwork(): void
	{
		if ($this->request['page_imess'] === 'connection' && $this->request['LINE'] > 0 && !$this->arResult['STATUS'])
		{
			if (empty($this->arResult['ACTIVE_STATUS']))
			{
				$this->status->setActive(true);
				$this->arResult['ACTIVE_STATUS'] = true;

				//Reset cache
				$this->cleanCache();
			}

			if (!empty($this->arResult['ACTIVE_STATUS']))
			{
				foreach ($this->listOptions as $value)
				{
					if (!empty($this->request[$value]))
					{
						$this->arResult['FORM'][$value] = $this->request[$value];
					}
				}

				if (!empty($this->arResult['FORM']))
				{
					$this->saveConnectorSettings();
				}

				//Reset cache
				$this->cleanCache();

				if (isset($this->arResult['SAVE_STATUS']) && $this->arResult['SAVE_STATUS'] === true)
				{
					$this->registerConnector();

					$request = Application::getInstance()->getContext()->getRequest();
					$uri = $request->getRequestUri();
					$uri = new Uri($uri);
					$uri->deleteParams([$this->pageId]);
					$uri->deleteParams($this->listOptions);
					$redirect = $uri->getUri();

					LocalRedirect($redirect);
				}
			}
		}
	}

	public function saveForm(): void
	{
		//If been sent the current form
		if ($this->request->isPost() && !empty($this->request[$this->connector. '_form']))
		{
			//If the session actual
			if (check_bitrix_sessid())
			{
				if($this->request[$this->connector. '_active'] && empty($this->arResult['ACTIVE_STATUS']))
				{
					$this->status->setActive(true);
					$this->arResult['ACTIVE_STATUS'] = true;

					//Reset cache
					$this->cleanCache();
				}

				if(!empty($this->arResult['ACTIVE_STATUS']))
				{
					//If saving
					if($this->request[$this->connector. '_save'])
					{
						foreach ($this->listOptions as $value)
						{
							if(!empty($this->request[$value]))
							{
								$this->arResult['FORM'][$value] = $this->request[$value];
							}
						}

						if(!empty($this->arResult['FORM']))
						{
							$this->saveConnectorSettings();
						}
						else
						{
							$this->error[] = Loc::getMessage('IMCONNECTOR_COMPONENT_IMESSAGE_NO_DATA_SAVE');
						}

						//Reset cache
						$this->cleanCache();

						if (isset($this->arResult['SAVE_STATUS']) && $this->arResult['SAVE_STATUS'] === true)
						{
							$this->registerConnector();

							//Reset cache
							$this->cleanCache();
						}
					}

					if($this->request[$this->connector. '_del'])
					{
						$rawDelete = $this->connectorOutput->deleteConnector();

						if($rawDelete->isSuccess())
						{
							Status::delete($this->connector, (int)$this->arParams['LINE']);
							$this->arResult['STATUS'] = false;
							$this->arResult['ACTIVE_STATUS'] = false;
							$this->arResult['CONNECTION_STATUS'] = false;
							$this->arResult['REGISTER_STATUS'] = false;
							$this->arResult['ERROR_STATUS'] = false;
							$this->arResult['PAGE'] = '';
						}
						else
						{
							$this->error[] = Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_NO_DISABLE');
						}

						//Reset cache
						$this->cleanCache();
					}
				}
			}
			else
			{
				$this->error[] = Loc::getMessage('IMCONNECTOR_COMPONENT_IMESSAGE_SESSION_HAS_EXPIRED');
			}
		}
	}

	public function constructionForm()
	{
		global $APPLICATION;

		$this->arResult['NAME'] = Connector::getNameConnectorReal($this->connector);
		$this->arResult['HELP_DESK_PARAMS'] = $this->helpDeskParams;

		$this->arResult['URL']['DELETE'] = $APPLICATION->GetCurPageParam('', [$this->pageId, 'open_block', 'business_id', 'business_name']);
		$this->arResult['URL']['SAVE_FORM'] = $APPLICATION->GetCurPageParam($this->pageId . '=save_form', [$this->pageId, 'open_block', 'business_id', 'business_name']);
		$this->arResult['URL']['CONNECTION_FORM'] = $APPLICATION->GetCurPageParam($this->pageId . '=connection', [$this->pageId, 'open_block']);
		$this->arResult['LANG_JS_SETTING'] = $this->getJsLangMessageSetting();

		if(
			!empty($this->arResult['PAGE']) &&
			$this->arResult['PAGE'] == 'connection'
		)
		{
			if(!empty($this->request['business_id']))
			{
				$this->arResult['FORM']['business_id'] = htmlspecialcharsbx($this->request['business_id']);
				if(isset($this->request['business_name']))
				{
					$this->arResult['FORM']['business_name'] = htmlspecialcharsbx($this->request['business_name']);
				}
				else
				{
					$this->arResult['FORM']['business_name'] = '';
				}
			}
			else
			{
				unset(
					$this->request['business_id'],
					$this->request['business_name'],
					$this->arResult['PAGE']
				);
			}
		}

		if($this->arResult['ACTIVE_STATUS'])
		{
			if(!empty($this->arResult['PAGE']))
			{
				$settings = $this->connectorOutput->readSettings();

				$result = $settings->getResult();

				foreach ($this->listOptions as $value)
				{
					if (empty($this->arResult['FORM'][$value]))
					{
						if (empty($result[$value]))
						{
							$this->arResult['FORM'][$value] = $result[$value] ?? '';
						}
						else
						{
							$this->arResult['SAVE_STATUS'] = true;
							$this->arResult['placeholder'][$value] = true;
						}
					}
				}
			}

			if($this->arResult['STATUS'])
			{
				if($this->arResult['PAGE'] == 'connection')
				{
					$this->error[] = Loc::getMessage('IMCONNECTOR_COMPONENT_IMESSAGE_CONFIRMATION_RECONNECTION');
				}

				$cache = Cache::createInstance();

				if ($cache->initCache(Library::CACHE_TIME_COMPONENT, $this->cacheId, Library::CACHE_DIR_COMPONENT))
				{
					$this->arResult['INFO_CONNECTION'] = $cache->getVars();
				}
				elseif ($cache->startDataCache())
				{
					$infoConnect = $this->connectorOutput->infoConnect();
					if($infoConnect->isSuccess())
					{
						$infoConnectData = $infoConnect->getData();
						$this->arResult['INFO_CONNECTION'] =  $infoConnectData;

						$cache->endDataCache($this->arResult['INFO_CONNECTION']);
					}
					else
					{
						$this->arResult['INFO_CONNECTION'] = [];
						$cache->abortDataCache();
					}
				}
			}
		}

		$this->arResult['CONNECTOR'] = $this->connector;
	}

	/**
	 * @return mixed|void
	 */
	public function executeComponent()
	{
		$this->includeComponentLang('class.php');

		if($this->checkModules())
		{
			if(Connector::isConnector($this->connector))
			{
				$this->arResult['PAGE'] = $this->request[$this->pageId];

				$this->initialization();

				$this->saveSettingsFromNetwork();
				$this->saveForm();

				$this->constructionForm();

				if(!empty($this->error))
				{
					$this->arResult['error'] = $this->error;
				}

				if(!empty($this->messages))
				{
					$this->arResult['messages'] = $this->messages;
				}

				$this->includeComponentTemplate();
			}
			else
			{
				ShowError(Loc::getMessage('IMCONNECTOR_COMPONENT_IMESSAGE_NO_ACTIVE_CONNECTOR'));
			}
		}
	}
}
