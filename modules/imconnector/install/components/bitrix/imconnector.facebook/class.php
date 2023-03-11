<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
	die();

use Bitrix\Main\Loader;
use Bitrix\Main\Web\Uri;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectNotFoundException;

use Bitrix\ImConnector\Output;
use Bitrix\ImConnector\Status;
use Bitrix\ImConnector\Library;
use Bitrix\ImConnector\Connector;

use Bitrix\Catalog\v2\IoC\ServiceContainer;
use Bitrix\Catalog\v2\Integration\Seo\Facebook\FacebookFacade;


class ImConnectorFacebook extends CBitrixComponent
{
	protected $cacheId;

	protected $connector = 'facebook';
	protected $error = [];
	protected $messages = [];
	/** @var Output */
	protected $connectorOutput;
	/**@var Status */
	protected $status;

	protected $pageId = 'page_fb';

	protected $InvalidOauthAccessToken = false;

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
			ShowError(Loc::getMessage('IMCONNECTOR_COMPONENT_FACEBOOK_MODULE_NOT_INSTALLED_MSGVER_1'));

			return false;
		}
	}

	protected function initialization()
	{
		$this->connectorOutput = new Output($this->connector, (int)$this->arParams['LINE']);

		$this->status = Status::getInstance($this->connector, (int)$this->arParams['LINE']);

		$this->arResult['STATUS'] = $this->status->isStatus();
		$this->arResult['ACTIVE_STATUS'] = $this->status->getActive();
		$this->arResult['CONNECTION_STATUS'] = $this->status->getConnection();
		$this->arResult['REGISTER_STATUS'] = $this->status->getRegister();
		$this->arResult['ERROR_STATUS'] = $this->status->getError();
		$this->arResult['DATA_STATUS'] = $this->status->getData();
		$region = Connector::getPortalRegion();
		$this->arResult['NEED_META_RESTRICTION_NOTE'] = Connector::needRestrictionNote($this->connector, $region, LANGUAGE_ID);

		$this->cacheId = Connector::getCacheIdConnector($this->arParams['LINE'], $this->connector);

		$this->arResult['PAGE'] = $this->request[$this->pageId];
		$this->arResult["WRONG_USER"] = $this->request['wrong_user'];
		$this->arResult['IFRAME'] = $this->request->get('IFRAME') === 'Y';
		$this->arResult['MENU_TAB'] = (htmlspecialcharsbx($this->request->get('MENU_TAB')) ?: 'connector');
		$this->arResult['CONFIG_MENU'] = $this->getPagesMenu();
		$this->arResult['CATALOG_AUTH'] = $this->getCatalogAuth();
	}

	/**
	 * @param $status
	 * @param bool $resetError
	 * @param array|null $data
	 */
	protected function setStatus($status, bool $resetError = true, ?array $data = []): void
	{
		$this->arResult['STATUS'] = $status;

		$this->status->setConnection((bool)$status);
		$this->arResult['CONNECTION_STATUS'] = $status;
		$this->status->setRegister((bool)$status);
		$this->arResult['REGISTER_STATUS'] = $status;

		if ($data !== null)
		{
			$this->status->setData($data);
			$this->arResult['DATA_STATUS'] = $data;
		}

		if ($resetError)
		{
			$this->status->setError(false);
			$this->arResult['ERROR_STATUS'] = false;
		}
	}

	private function saveCatalogPermissionData(array $formData): void
	{
		if (!isset($formData['PERMISSIONS']['CATALOG']))
		{
			$formData['PERMISSIONS']['CATALOG'] = 'N';
		}

		$currentStatusData = $this->status->getData();
		if (
			!is_array($currentStatusData)
			|| !isset($currentStatusData['CATALOG'])
			|| ($currentStatusData['CATALOG'] !== $formData['PERMISSIONS']['CATALOG']))
		{
			$dataToSave = [
				'CATALOG' => $formData['PERMISSIONS']['CATALOG'],
			];

			if (is_array($currentStatusData))
			{
				$dataToSave = array_merge($currentStatusData, $dataToSave);
			}
			$this->status->setData($dataToSave);
			$this->arResult['DATA_STATUS'] = $this->status->getData();
		}
	}

	/**
	 * Reset cache
	 */
	protected function cleanCache()
	{
		Connector::cleanCacheConnector($this->arParams['LINE'], $this->cacheId);
	}

	public function saveForm()
	{
		//If been sent the current form
		if ($this->request->isPost() && !empty($this->request[$this->connector . '_form']))
		{
			//If the session actual
			if (check_bitrix_sessid())
			{
				//Activation
				if ($this->request[$this->connector . '_active'] && empty($this->arResult['ACTIVE_STATUS']))
				{
					$this->status->setActive(true);
					$this->arResult['ACTIVE_STATUS'] = true;

					//Reset cache
					$this->cleanCache();
				}

				if (!empty($this->arResult['ACTIVE_STATUS']))
				{
					//If you remove the reference to the user
					if ($this->request[$this->connector . '_del_user'])
					{
						$delUser = $this->connectorOutput->delUserActive($this->request['user_id']);

						if ($delUser->isSuccess())
						{
							$this->setStatus(false);

							$this->messages[] = Loc::getMessage('IMCONNECTOR_COMPONENT_FACEBOOK_OK_DEL_USER');
						}
						else
						{
							$this->error[] = Loc::getMessage('IMCONNECTOR_COMPONENT_FACEBOOK_NO_DEL_USER');
						}

						//Reset cache
						$this->cleanCache();
					}

					//If you remove the reference to the group
					if ($this->request[$this->connector . '_del_page'])
					{
						$delPage = $this->connectorOutput->delPageActive($this->request['page_id']);

						if ($delPage->isSuccess())
						{
							$this->setStatus(false);

							$this->messages[] = Loc::getMessage('IMCONNECTOR_COMPONENT_FACEBOOK_OK_DEL_PAGE');
						}
						else
						{
							$this->error[] = Loc::getMessage('IMCONNECTOR_COMPONENT_FACEBOOK_NO_DEL_PAGE');
						}

						//Reset cache
						$this->cleanCache();
					}

					//If you bind to the group
					if ($this->request[$this->connector . '_authorization_page'])
					{
						$authorizationPage = $this->connectorOutput->authorizationPage($this->request['page_id']);

						if ($authorizationPage->isSuccess())
						{
							if ($this->request['human_agent'] === 'Y')
							{
								$data = ['HUMAN_AGENT' => true];
							}
							else
							{
								$data = [];
							}

							$this->setStatus(true, true, $data);

							$this->messages[] = Loc::getMessage('IMCONNECTOR_COMPONENT_FACEBOOK_OK_AUTHORIZATION_PAGE');
						}
						else
						{
							$this->setStatus(false);

							$this->error[] = Loc::getMessage('IMCONNECTOR_COMPONENT_FACEBOOK_NO_AUTHORIZATION_PAGE');
						}

						//Reset cache
						$this->cleanCache();
					}

					if ($this->request[$this->connector . '_del'])
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
							$this->arResult['DATA_STATUS'] = [];
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
				$this->error[] = Loc::getMessage('IMCONNECTOR_COMPONENT_FACEBOOK_SESSION_HAS_EXPIRED');
			}
		}
	}

	public function constructionForm()
	{
		global $APPLICATION;

		$this->arResult['NAME'] = Connector::getNameConnectorReal($this->connector);

		$this->arResult['URL']['DELETE'] = $APPLICATION->GetCurPageParam('', [$this->pageId, 'open_block', 'action']);
		$this->arResult['URL']['SIMPLE_FORM'] = $APPLICATION->GetCurPageParam($this->pageId . '=simple_form', [$this->pageId, 'open_block', 'action']);
		$this->arResult['URL']['SIMPLE_FORM_EDIT'] = $APPLICATION->GetCurPageParam($this->pageId . '=simple_form', [$this->pageId, 'open_block', 'action']);

		$this->arResult['FORM']['STEP'] = 1;

		if ($this->arResult['ACTIVE_STATUS'])
		{
			//Reset cache
			if (!empty($this->arResult['PAGE']))
				$this->cleanCache();

			$cache = Cache::createInstance();

			if ($cache->initCache(Library::CACHE_TIME_COMPONENT, $this->cacheId, Library::CACHE_DIR_COMPONENT))
			{
				$this->arResult['FORM'] = $cache->getVars();
			}
			elseif ($cache->startDataCache())
			{
				$uri = new Uri(Library::getCurrentUri());
				$uri->addParams(['reload' => 'Y', 'ajaxid' => $this->arParams['AJAX_ID'], $this->pageId => 'simple_form']);
				$uri->deleteParams(['wrong_user']);

				$infoOAuth = $this->connectorOutput->getAuthorizationInformation(urlencode($uri->getUri()));

				if ($infoOAuth->isSuccess())
				{
					$this->arResult['FORM'] = $infoOAuth->getData();
					$this->saveCatalogPermissionData($this->arResult["FORM"]);

					if (!empty($this->arResult['FORM']['PAGE']))
					{
						$this->arResult['FORM']['STEP'] = 3;

						$this->setStatus(true, true, null);
					}
					elseif (!empty($this->arResult['FORM']['PAGES']))
					{
						$this->arResult['FORM']['STEP'] = 2;

						$this->setStatus(false, false);
					}
					elseif (!empty($this->arResult['FORM']['USER']))
					{
						$this->arResult['FORM']['STEP'] = 1;

						$this->setStatus(false, false);
					}

					if (!empty($this->arResult['FORM']['GROUP_DEL']))
					{
						$this->error[] = Loc::getMessage('IMCONNECTOR_COMPONENT_FACEBOOK_REMOVED_REFERENCE_TO_PAGE');
					}

					$cache->endDataCache($this->arResult['FORM']);
				}
				else
				{
					foreach ($infoOAuth->getErrorCollection() as $error)
					{
						if ($error->getCode() == Library::ERROR_CONNECTOR_MESSENGER_INVALID_OAUTH_ACCESS_TOKEN)
						{
							$InvalidOauthAccessToken = true;
						}
					}

					if (!empty($InvalidOauthAccessToken))
					{
						$this->arResult['FORM'] = $infoOAuth->getData();
						$this->arResult['FORM']['STEP'] = 1;
						$this->error[] = Loc::getMessage('IMCONNECTOR_COMPONENT_FACEBOOK_INVALID_OAUTH_ACCESS_TOKEN');
					}
					else
					{
						$this->arResult['FORM'] = [];
						$this->error[] = Loc::getMessage('IMCONNECTOR_COMPONENT_FACEBOOK_ERROR_REQUEST_INFORMATION_FROM_SERVER') . '<br>' . Loc::getMessage('IMCONNECTOR_COMPONENT_FACEBOOK_REPEATING_ERROR');
					}

					$cache->abortDataCache();
				}
			}

			//Analytic tags start
			if ($this->arResult['FORM']['STEP'] == 3)
			{
				$uri = new Uri($this->arResult['URL']['DELETE']);
				$uri->addParams(['action' => 'disconnect']);
				$this->arResult['URL']['DELETE'] = $uri->getUri();

				$uri = new Uri($this->arResult['URL']['SIMPLE_FORM']);
				$uri->addParams(['action' => 'disconnect']);
				$this->arResult['URL']['SIMPLE_FORM'] = $uri->getUri();

				if ($this->request->get($this->pageId))
				{
					$uri = new Uri($this->arResult['URL']['SIMPLE_FORM_EDIT']);
					$uri->addParams(['action' => 'edit']);
					$this->arResult['URL']['SIMPLE_FORM_EDIT'] = $uri->getUri();
				}
			}
			elseif ($this->arResult['FORM']['STEP'] == 2)
			{
				$uri = new Uri($this->arResult['URL']['SIMPLE_FORM']);
				$uri->addParams(['action' => 'connect']);
				$this->arResult['URL']['SIMPLE_FORM'] = $uri->getUri();
			}
			//Analytic tags end
		}

		$this->arResult['CONNECTOR'] = $this->connector;
	}

	protected function getPagesMenu(): array
	{
		$menuList = [
			'connector' => [
				'PAGE' => 'connector.php',
				'NAME' => Loc::getMessage("IMCONNECTOR_COMPONENT_FACEBOOK_MENU_TAB_OPEN_LINES"),
				'ACTIVE' => $this->arResult['MENU_TAB'] === 'connector',
			],
		];

		if ($this->isCatalogExportAvailable())
		{
			$menuList['catalog'] = [
				'PAGE' => 'catalog.php',
				'NAME' => Loc::getMessage("IMCONNECTOR_COMPONENT_FACEBOOK_MENU_TAB_CATALOG"),
				'ACTIVE' => $this->arResult['MENU_TAB'] === 'catalog',
			];
		}

		$menuItemBase = Library::getCurrentUri();
		$uri = new Uri($menuItemBase);
		if ($this->request['IFRAME'] === 'Y')
		{
			$uri->addParams(['IFRAME' => 'Y']);
		}

		foreach ($menuList as $code => &$menuItem)
		{
			$uri->addParams(['MENU_TAB' => $code]);
			$menuItem['ATTRIBUTES']['HREF'] = $uri->getUri();
		}

		return $menuList;
	}

	public function executeComponent()
	{
		Loc::loadMessages(__FILE__);

		if ($this->checkModules())
		{
			if (Connector::isConnector($this->connector))
			{
				$this->initialization();

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
				ShowError(Loc::getMessage('IMCONNECTOR_COMPONENT_FACEBOOK_NO_ACTIVE_CONNECTOR'));

				return false;
			}
		}
	}

	private function getCatalogAuth(): array
	{
		$result = [];

		if ($this->isCatalogExportAvailable())
		{
			$result['HAS_AUTH'] = $this->hasCatalogExportAuth();
			//$result['PAGE_ID'] = $this->getPageId();
		}

		return $result;
	}

	private function getFacebookFacade(): ?FacebookFacade
	{
		static $facade = null;

		if ($facade === null)
		{
			if (Loader::includeModule('catalog') && Loader::includeModule('crm'))
			{
				try
				{
					$iblockId = CCrmCatalog::EnsureDefaultExists();
					$facade = ServiceContainer::get('integration.seo.facebook.facade', compact('iblockId'));
				}
				catch (ObjectNotFoundException $exception)
				{
				}
			}
		}

		return $facade;
	}

	private function isCatalogExportAvailable(): bool
	{
		if ($facebookFacade = $this->getFacebookFacade())
		{
			return $facebookFacade->isExportAvailable();
		}

		return false;
	}

	private function hasCatalogExportAuth(): bool
	{
		if ($facebookFacade = $this->getFacebookFacade())
		{
			return $facebookFacade->hasAuth();
		}

		return false;
	}

	private function getPageId(): ?string
	{
		if ($facebookFacade = $this->getFacebookFacade())
		{
			return $facebookFacade->getPageId();
		}

		return null;
	}
}