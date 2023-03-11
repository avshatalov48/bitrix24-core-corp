<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Main\Loader;
use Bitrix\Main\Web\Uri;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\Localization\Loc;

use Bitrix\ImConnector\Output;
use Bitrix\ImConnector\Status;
use Bitrix\ImConnector\Library;
use Bitrix\ImConnector\Connector;

/**
 * Class ImConnectorTelegrambot
 */
class ImConnectorTelegrambot extends \CBitrixComponent
{
	protected $cacheId;

	protected $connector = 'telegrambot';
	protected $error = [];
	protected $messages = [];
	/** @var Output */
	protected $connectorOutput;
	/** @var Status */
	protected $status;

	protected $pageId = 'page_tg';

	protected $listOptions = ['api_token', 'welcome_message', 'eshop_enabled', 'eshop_custom_url', 'eshop_name', 'eshop_id'];

	protected function getEshopList(): array
	{
		$result = [];

		if (!\Bitrix\Main\Loader::includeModule('landing'))
		{
			return $result;
		}

		if (!\Bitrix\Main\Loader::includeModule('salescenter'))
		{
			return $result;
		}

		$dbRes = \Bitrix\Landing\Site::getList([
			'filter' => [
				'!=ID' => \Bitrix\SalesCenter\Integration\LandingManager::getInstance()->getConnectedSiteId(),
				'CHECK_PERMISSIONS' => 'N',
				'=DELETED' => ['N'],
				'=TYPE' => 'STORE',
			]
		]);

		while ($data = $dbRes->fetch())
		{
			$data['PUBLIC_URL'] = \Bitrix\Landing\Site::getPublicUrl($data['ID']);

			$result[] = $data;
		}

		return $result;
	}

	function installTelegramTradingPlatform(): void
	{
		if (Loader::includeModule('crm'))
		{
			$platformCode = \Bitrix\Crm\Order\TradingPlatform\Telegram\Telegram::TRADING_PLATFORM_CODE;
			$platform = \Bitrix\Crm\Order\TradingPlatform\Telegram\Telegram::getInstanceByCode($platformCode);
			if (!$platform->isInstalled())
			{
				$platform->install();
			}
		}
	}

	/**
	 * Check the connection of the necessary modules.
	 * @return bool
	 */
	protected function checkModules(): bool
	{
		if (Loader::includeModule('imconnector'))
		{
			return true;
		}

		ShowError(Loc::getMessage('IMCONNECTOR_COMPONENT_TELEGRAMBOT_MODULE_NOT_INSTALLED_MSGVER_1'));
		return false;
	}

	protected function initialization(): void
	{
		$this->connectorOutput = new Output($this->connector, (int)$this->arParams['LINE']);

		$this->status = Status::getInstance($this->connector, (int)$this->arParams['LINE']);

		$this->arResult['STATUS'] = $this->status->isStatus();
		$this->arResult['ACTIVE_STATUS'] = $this->status->getActive();
		$this->arResult['CONNECTION_STATUS'] = $this->status->getConnection();
		$this->arResult['REGISTER_STATUS'] = $this->status->getRegister();
		$this->arResult['ERROR_STATUS'] = $this->status->getError();
		$this->arResult['IS_SUCCESS_SAVE'] = false;

		$this->cacheId = Connector::getCacheIdConnector($this->arParams['LINE'], $this->connector);
	}

	/**
	 * Reset cache
	 */
	protected function cleanCache(): void
	{
		Connector::cleanCacheConnector($this->arParams['LINE'], $this->cacheId);
	}

	public function saveForm(): void
	{
		//If been sent the current form
		if (
			$this->request->isPost()
			&& !empty($this->request[$this->connector. '_form'])
		)
		{
			//If the session actual
			if (check_bitrix_sessid())
			{
				//Activation bot
				if (
					$this->request[$this->connector. '_active']
					&& empty($this->arResult['ACTIVE_STATUS'])
				)
				{
					$this->status->setActive(true);
					$this->arResult['ACTIVE_STATUS'] = true;

					//Reset cache
					$this->cleanCache();
				}

				if (!empty($this->arResult['ACTIVE_STATUS']))
				{
					//If saving
					if ($this->request[$this->connector. '_save'])
					{
						foreach($this->listOptions as $value)
						{
							if (!empty($this->request[$value]))
							{
								$this->arResult['FORM'][$value] = $this->request[$value];
							}
						}

						if (!empty($this->arResult['FORM']))
						{
							if (!empty($this->arResult['REGISTER_STATUS']))
							{
								$this->connectorOutput->unregister();
							}

							$dataToSave = [
								'eshop_enabled' => $this->arResult['FORM']['eshop_enabled'] ?? 'N',
								'welcome_message' => $this->arResult['FORM']['welcome_message'] ? : ''
							];

							if (isset($this->arResult['FORM']['eshop_enabled']) && $this->arResult['FORM']['eshop_enabled'] === 'Y')
							{
								if ((int)$this->request['eshop_id'] > 0)
								{
									$this->installTelegramTradingPlatform();
									foreach ($this->getEshopList() as $eshop)
									{
										if ((int)$eshop['ID'] === (int)$this->request['eshop_id'])
										{
											$dataToSave['eshop_url'] = $eshop['PUBLIC_URL'];
											$dataToSave['eshop_id'] = $eshop['ID'];

											break;
										}
									}
								}
								else if ((int)$this->request['eshop_id'] === 0)
								{
									$dataToSave['eshop_url'] = $this->arResult['FORM']['eshop_custom_url'];
									$dataToSave['eshop_id'] = '0';
								}
								$dataToSave['eshop_name'] = Loc::getMessage('IMCONNECTOR_COMPONENT_TELEGRAMBOT_ESHOP_DEFAULT_NAME');

								$this->arResult['FORM']['eshop_name'] = $dataToSave['eshop_name'];
							}
							$this->status->setData($dataToSave);

							$dataToSaveOnServer = [
								'eshop_name' => $dataToSave['eshop_name'] ?? '',
								'eshop_url' => $dataToSave['eshop_url'] ?? '',
								'api_token' => $this->arResult['FORM']['api_token'] ?? '',
							];

							if (is_null($dataToSaveOnServer['api_token']))
							{
								unset($dataToSaveOnServer['api_token']);
							}
							$saved = $this->connectorOutput->saveSettings($dataToSaveOnServer);

							if ($saved->isSuccess())
							{
								$this->messages[] = Loc::getMessage('IMCONNECTOR_COMPONENT_TELEGRAMBOT_OK_SAVE');
								$this->arResult['SAVE_STATUS'] = true;

								$this->status->setError(false);
								$this->arResult['ERROR_STATUS'] = false;
							}
							else
							{
								$this->error[] = Loc::getMessage('IMCONNECTOR_COMPONENT_TELEGRAMBOT_NO_SAVE');
								$this->arResult['SAVE_STATUS'] = false;

								$this->status->setConnection(false);
								$this->arResult['CONNECTION_STATUS'] = false;
								$this->status->setRegister(false);
								$this->arResult['REGISTER_STATUS'] = false;

								$this->arResult['STATUS'] = false;
							}
						}
						else
						{
							$this->error[] = Loc::getMessage('IMCONNECTOR_COMPONENT_TELEGRAMBOT_NO_DATA_SAVE');
						}

						//Reset cache
						$this->cleanCache();
					}

					//If the test connection or save
					if (
						(
							$this->request[$this->connector. '_save']
							&& $this->arResult['SAVE_STATUS']
						)
						|| $this->request[$this->connector. '_tested']
					)
					{
						$testConnect = $this->connectorOutput->testConnect();

						if ($testConnect->isSuccess())
						{
							$this->messages[] = Loc::getMessage('IMCONNECTOR_COMPONENT_TELEGRAMBOT_OK_CONNECT');

							$this->status->setConnection(true);
							$this->arResult['CONNECTION_STATUS'] = true;

							$this->status->setError(false);
							$this->arResult['ERROR_STATUS'] = false;
						}
						else
						{
							$this->error[] = Loc::getMessage('IMCONNECTOR_COMPONENT_TELEGRAMBOT_NO_CONNECT');

							$testConnect->getResult();

							$this->status->setConnection(false);
							$this->arResult['CONNECTION_STATUS'] = false;

							$this->status->setRegister(false);
							$this->arResult['REGISTER_STATUS'] = false;

							$this->arResult['STATUS'] = false;
						}

						//Reset cache
						$this->cleanCache();
					}

					//If the check or test connection or save
					if (
						(
							$this->request[$this->connector. '_register']
							|| (
								$this->request[$this->connector. '_save']
								&& $this->arResult['SAVE_STATUS']
							)
							|| $this->request[$this->connector. '_tested']
						)
						&& $this->arResult['CONNECTION_STATUS']
					)
					{
						$register = $this->connectorOutput->register();

						if ($register->isSuccess())
						{
							Cache::createInstance()->cleanDir(Library::CACHE_DIR_COMPONENT);

							$this->messages[] = Loc::getMessage('IMCONNECTOR_COMPONENT_TELEGRAMBOT_OK_REGISTER');

							$this->status->setRegister(true);
							$this->arResult['REGISTER_STATUS'] = true;
							$this->arResult['STATUS'] = true;
							$this->arResult['IS_SUCCESS_SAVE'] = true;

							$this->status->setError(false);
							$this->arResult['ERROR_STATUS'] = false;
						}
						else
						{
							$this->error[] = Loc::getMessage('IMCONNECTOR_COMPONENT_TELEGRAMBOT_NO_REGISTER');

							$this->status->setRegister(false);
							$this->arResult['REGISTER_STATUS'] = false;

							$this->arResult['STATUS'] = false;
						}

						//Reset cache
						$this->cleanCache();
					}

					if ($this->request[$this->connector. '_del'])
					{
						$rawDelete = $this->connectorOutput->deleteConnector();

						if ($rawDelete->isSuccess())
						{
							Status::delete($this->connector, (int)$this->arParams['LINE']);
							$this->arResult['STATUS'] = false;
							$this->arResult['ACTIVE_STATUS'] = false;
							$this->arResult['CONNECTION_STATUS'] = false;
							$this->arResult['REGISTER_STATUS'] = false;
							$this->arResult['ERROR_STATUS'] = false;
							$this->arResult['PAGE'] = '';

							//$this->messages[] = Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_OK_DISABLE');
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
				$this->error[] = Loc::getMessage('IMCONNECTOR_COMPONENT_TELEGRAMBOT_SESSION_HAS_EXPIRED');
			}
		}
	}

	public function constructionForm(): void
	{
		global $APPLICATION;

		$this->arResult['NAME'] = Connector::getNameConnectorReal($this->connector);

		$this->arResult['URL']['INDEX'] = $APPLICATION->GetCurPageParam($this->pageId . '=index', [$this->pageId, 'open_block', 'action']);
		$this->arResult['URL']['MASTER'] = $APPLICATION->GetCurPageParam($this->pageId . '=master', [$this->pageId, 'open_block', 'action']);
		$this->arResult['URL']['MASTER_NEW'] = $APPLICATION->GetCurPageParam($this->pageId . '=master_new', [$this->pageId, 'open_block', 'action']);
		$this->arResult['URL']['DELETE'] = $APPLICATION->GetCurPageParam('', [$this->pageId, 'open_block', 'action']);
		$this->arResult['URL']['SIMPLE_FORM'] = $APPLICATION->GetCurPageParam($this->pageId . '=simple_form', [$this->pageId, 'open_block', 'action']);
		$this->arResult['URL']['SIMPLE_FORM_EDIT'] = $APPLICATION->GetCurPageParam($this->pageId . '=simple_form', [$this->pageId, 'open_block', 'action']);

		if ($this->arResult['ACTIVE_STATUS'])
		{
			if (!empty($this->arResult['PAGE']))
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

				$statusData = $this->status->getData();
				if (!empty($statusData['welcome_message']))
				{
					$this->arResult['FORM']['welcome_message'] = $statusData['welcome_message'];
				}
				elseif (!isset($statusData['welcome_message']))
				{
					$companyName = \Bitrix\Main\Config\Option::get("main", "site_name", "");
					$this->arResult['FORM']['welcome_message'] = Loc::getMessage('IMCONNECTOR_COMPONENT_TELEGRAMBOT_WELCOME_MESSAGE_DEFAULT', ['#COMPANY_TITLE#' => $companyName]);
				}
				else
				{
					$this->arResult['FORM']['welcome_message'] = '';
				}
				if (!empty($statusData['eshop_url']))
				{
					$this->arResult['FORM']['eshop_enabled'] = 'Y';
					$this->arResult['FORM']['eshop_url'] = $statusData['eshop_url'];
					if ((int)$statusData['eshop_id'] === 0)
					{
						$this->arResult['FORM']['eshop_custom_url'] = $statusData['eshop_url'];
					}
					$this->arResult['FORM']['eshop_id'] = $statusData['eshop_id'];
				}
				if (!$this->arResult['FORM']['eshop_enabled'])
				{
					unset($this->arResult['FORM']['eshop_enabled']);
				}
			}

			if ($this->arResult['STATUS'])
			{
				$cache = Cache::createInstance();

				if ($cache->initCache(Library::CACHE_TIME_COMPONENT, $this->cacheId, Library::CACHE_DIR_COMPONENT))
				{
					$this->arResult['INFO_CONNECTION'] = $cache->getVars();
				}
				elseif ($cache->startDataCache())
				{
					$infoConnect = $this->connectorOutput->infoConnect();
					if ($infoConnect->isSuccess())
					{
						$infoConnectData = $infoConnect->getData();

						$this->arResult['INFO_CONNECTION'] = [
							'ID' => $infoConnectData['id'],
							'URL' => $infoConnectData['url'],
							'NAME' => $infoConnectData['name'],
						];
						if ($infoConnectData['eshop_url'])
						{
							$this->arResult['INFO_CONNECTION']['ESHOP_URL'] = $infoConnectData['eshop_url'];
						}

						$cache->endDataCache($this->arResult['INFO_CONNECTION']);
					}
					else
					{
						$this->arResult['INFO_CONNECTION'] = [];
						$cache->abortDataCache();
					}
				}

				$uri = new Uri($this->arResult['URL']['DELETE']);
				$uri->addParams(['action' => 'disconnect']);
				$this->arResult['URL']['DELETE'] = $uri->getUri();

				$uri = new Uri($this->arResult['URL']['SIMPLE_FORM_EDIT']);
				$uri->addParams(['action' => 'edit']);
				$this->arResult['URL']['SIMPLE_FORM_EDIT'] = $uri->getUri();
			}
			else
			{
				$uri = new Uri($this->arResult['URL']['SIMPLE_FORM_EDIT']);
				$uri->addParams(['action' => 'connect']);
				$this->arResult['URL']['SIMPLE_FORM_EDIT'] = $uri->getUri();
			}
		}

		$this->arResult['CONNECTOR'] = $this->connector;

		$this->arResult['ESHOP_LIST'] = $this->getEshopList();
	}

	public function executeComponent()
	{
		$this->includeComponentLang('class.php');

		if ($this->checkModules())
		{
			if (Connector::isConnector($this->connector))
			{
				$this->initialization();

				$this->arResult['PAGE'] = $this->request[$this->pageId];
				$this->saveForm();

				$this->constructionForm();

				if (!empty($this->error))
				{
					$this->arResult['error'] = $this->error;
				}

				if (!empty($this->messages))
				{
					$this->arResult['messages'] = $this->messages;
				}

				$this->includeComponentTemplate();
			}
			else
			{
				ShowError(Loc::getMessage('IMCONNECTOR_COMPONENT_TELEGRAMBOT_NO_ACTIVE_CONNECTOR'));
			}
		}
	}
};