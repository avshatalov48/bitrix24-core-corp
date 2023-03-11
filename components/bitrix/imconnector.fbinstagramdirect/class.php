<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)
{
	die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\Web\Uri;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\Localization\Loc;

use Bitrix\ImConnector\Output;
use Bitrix\ImConnector\Status;
use Bitrix\ImConnector\Library;
use Bitrix\ImConnector\Connector;

class ImConnectorFBInstagramDirect extends CBitrixComponent
{
	protected const HELP_DESK_ERROR_DISABLED_ACCESS_INSTAGRAM_DIRECT_MESSAGES = '13813998';
	protected const HELP_DESK_ERROR_IG_ACCOUNT_IS_NOT_ELIGIBLE_API = '13813984';

	protected $cacheId;

	protected $connector = 'fbinstagramdirect';
	protected $error = [];
	protected $messages = [];
	/**
	 * @see \Bitrix\ImConnectorServer\Connectors\FbInstagramDirect
	 * @var Output
	 */
	protected $connectorOutput;
	/**@var Status */
	protected $status;

	protected $pageId = 'page_fbinstdirect';

	protected $InvalidOauthAccessToken = false;

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

		ShowError(Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_MODULE_NOT_INSTALLED_MSGVER_1'));
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
		$this->arResult['DATA_STATUS'] = $this->status->getData();
		$region = Connector::getPortalRegion();
		$this->arResult['NEED_META_RESTRICTION_NOTE'] = Connector::needRestrictionNote($this->connector, $region, LANGUAGE_ID);

		$this->cacheId = Connector::getCacheIdConnector($this->arParams['LINE'], $this->connector);

		$this->arResult['PAGE'] = $this->request[$this->pageId];
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
			&& !empty($this->request[$this->connector . '_form'])
		)
		{
			//If the session actual
			if(check_bitrix_sessid())
			{
				//Activation
				if (
					$this->request[$this->connector . '_active']
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
					//If you remove the reference to the user
					if ($this->request[$this->connector. '_del_user'])
					{
						$delUser = $this->connectorOutput->delUserActive($this->request['user_id']);

						if ($delUser->isSuccess())
						{
							$this->setStatus(false);

							$this->messages[] = Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_OK_DEL_USER');
						}
						else
						{
							$this->error[] = Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_NO_DEL_USER');
						}

						//Reset cache
						$this->cleanCache();
					}

					//If you remove the reference to the group
					if ($this->request[$this->connector. '_del_page'])
					{
						$delPage = $this->connectorOutput->delPageActive($this->request['page_id']);

						if ($delPage->isSuccess())
						{
							$this->setStatus(false);

							$this->messages[] = Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_OK_DEL_PAGE');
						}
						else
						{
							$this->error[] = Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_NO_DEL_PAGE');
						}

						//Reset cache
						$this->cleanCache();
					}

					//If you bind to the group
					if ($this->request[$this->connector . '_authorization_page'])
					{
						$paramsAuthorizationPage = [];

						if ($this->request['comments'] === 'Y')
						{
							$paramsAuthorizationPage['comments'] = true;
						}

						$authorizationPage = $this->connectorOutput->authorizationPage($this->request['page_id'], $paramsAuthorizationPage);

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

							$this->messages[] =
								Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_OK_AUTHORIZATION_PAGE');
						}
						else
						{
							$this->setStatus(false);

							$errorsAuthorizationPage = $authorizationPage->getErrorMessages();

							if (!empty($errorsAuthorizationPage))
							{
								$errorsAuthorizationPage = array_filter($errorsAuthorizationPage);
							}

							if (
								$authorizationPage
									->getErrorCollection()
									->getErrorByCode('ERROR_INSTAGRAM_DISABLED_ACCESS_INSTAGRAM_DIRECT_MESSAGES') !== null
							)
							{
								$errorsAuthorizationPage[] =
									Loc::getMessage(
										'IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_ERROR_DISABLED_ACCESS_INSTAGRAM_DIRECT_MESSAGES',
									[
										'#A#' => '<a href="javascript:void(0)" onclick=\'top.BX.Helper.show("redirect=detail&code='
											. self::HELP_DESK_ERROR_DISABLED_ACCESS_INSTAGRAM_DIRECT_MESSAGES
											. '");event.preventDefault();\'>',
										'#A_END#' => '</a>'
									]);
							}

							if (
								$authorizationPage
									->getErrorCollection()
									->getErrorByCode('ERROR_INSTAGRAM_IG_ACCOUNT_IS_NOT_ELIGIBLE_API') !== null
							)
							{
								$errorsAuthorizationPage[] =
									Loc::getMessage(
										'IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_ERROR_IG_ACCOUNT_IS_NOT_ELIGIBLE_API_3',
										[
											'#A#' => '<a href="javascript:void(0)" onclick=\'top.BX.Helper.show("redirect=detail&code='
												. self::HELP_DESK_ERROR_IG_ACCOUNT_IS_NOT_ELIGIBLE_API
												. '");event.preventDefault();\'>',
											'#A_END#' => '</a>'
										]);
							}

							if (!empty($errorsAuthorizationPage))
							{
								$this->error = array_merge($this->error, $errorsAuthorizationPage);
							}
							else
							{
								$this->error[] =
									Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_NO_AUTHORIZATION_PAGE');
							}
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
							$this->arResult['DATA_STATUS'] = [];
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
				$this->error[] = Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_SESSION_HAS_EXPIRED');
			}
		}
	}

	public function constructionForm(): void
	{
		global $APPLICATION;

		$this->arResult['NAME'] = Connector::getNameConnectorReal($this->connector);

		$this->arResult['URL']['DELETE'] = $APPLICATION->GetCurPageParam(
			'',
			[$this->pageId, 'open_block', 'action']
		);
		$this->arResult['URL']['SIMPLE_FORM'] = $APPLICATION->GetCurPageParam(
			$this->pageId . '=simple_form',
			[$this->pageId, 'open_block', 'action']
		);
		$this->arResult['URL']['SIMPLE_FORM_EDIT'] = $APPLICATION->GetCurPageParam(
			$this->pageId . '=simple_form',
			[$this->pageId, 'open_block', 'action']
		);

		$this->arResult['FORM']['STEP'] = 1;

		if ($this->arResult['ACTIVE_STATUS'])
		{
			//Reset cache
			if (!empty($this->arResult['PAGE']))
			{
				$this->cleanCache();
			}

			$cache = Cache::createInstance();

			if ($cache->initCache(Library::CACHE_TIME_COMPONENT, $this->cacheId, Library::CACHE_DIR_COMPONENT))
			{
				$this->arResult['FORM'] = $cache->getVars();
			}
			elseif ($cache->startDataCache())
			{
				$uri = new Uri(Library::getCurrentUri());
				$uri->addParams([
					'reload' => 'Y',
					'ajaxid' => $this->arParams['AJAX_ID'],
					$this->pageId => 'simple_form'
				]);

				$infoOAuth = $this->connectorOutput->getAuthorizationInformation(urlencode($uri->getUri()));

				if($infoOAuth->isSuccess())
				{
					$this->arResult['FORM'] = $infoOAuth->getData();

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
						$this->error[] =
							Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_REMOVED_REFERENCE_TO_PAGE');
					}

					$cache->endDataCache($this->arResult['FORM']);
				}
				else
				{
					foreach ($infoOAuth->getErrorCollection() as $error)
					{
						if ($error->getCode() === Library::ERROR_CONNECTOR_MESSENGER_INVALID_OAUTH_ACCESS_TOKEN)
						{
							$InvalidOauthAccessToken = true;
						}
					}

					if(!empty($InvalidOauthAccessToken))
					{
						$this->arResult['FORM'] = $infoOAuth->getData();
						$this->arResult['FORM']['STEP'] = 1;
						$this->error[] =
							Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_INVALID_OAUTH_ACCESS_TOKEN');
					}
					else
					{
						$this->arResult['FORM'] = [];
						$this->error[] = Loc::getMessage(
								'IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_ERROR_REQUEST_INFORMATION_FROM_SERVER')
							. '<br>'
							. Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_REPEATING_ERROR'
							);
					}

					$cache->abortDataCache();
				}
			}

			//Analytic tags start
			if((int)$this->arResult['FORM']['STEP'] === 3)
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
			elseif((int)$this->arResult['FORM']['STEP'] === 2)
			{
				$uri = new Uri($this->arResult['URL']['SIMPLE_FORM']);
				$uri->addParams(['action' => 'connect']);
				$this->arResult['URL']['SIMPLE_FORM'] = $uri->getUri();
			}
			//Analytic tags end
		}

		$this->arResult['CONNECTOR'] = $this->connector;
	}

	/**
	 * @return bool
	 */
	public function executeComponent(): bool
	{
		$this->includeComponentLang('class.php');

		if($this->checkModules())
		{
			if(Connector::isConnector($this->connector))
			{
				$this->initialization();

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
				ShowError(Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_NO_ACTIVE_CONNECTOR'));

				return false;
			}
		}

		return true;
	}
}
