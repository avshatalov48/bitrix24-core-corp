<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Catalog\Access\AccessController;
use Bitrix\Catalog\Access\ActionDictionary;
use Bitrix\Crm\Order\Import\Instagram;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Uri;
use Bitrix\Main\Application;

class CrmOrderConnectorInstagramEdit extends CBitrixComponent
	implements \Bitrix\Main\Engine\Contract\Controllerable, \Bitrix\Main\Errorable
{
	protected $pageId = 'page_fbinst_store';

	/** @var [] */
	protected $status;

	protected $messages = [];
	protected $errorCollection;

	public function __construct($component = null)
	{
		parent::__construct($component);
		$this->errorCollection = new ErrorCollection();
	}

	public function getErrors()
	{
		return $this->errorCollection->toArray();
	}

	public function getErrorByCode($code)
	{
		return $this->errorCollection->getErrorByCode($code);
	}

	public function configureActions()
	{
		return [];
	}

	protected function listKeysSignedParameters()
	{
		return [];
	}

	/**
	 * Check the connection of the necessary modules.
	 * @return bool
	 * @throws LoaderException
	 */
	protected function checkModules()
	{
		$state = true;

		if (!Loader::includeModule('crm'))
		{
			$this->errorCollection[] = new Error(Loc::getMessage('CRM_OIIE_MODULE_NOT_INSTALLED_CRM'));
			$state = false;
		}

		if (!Loader::includeModule('catalog'))
		{
			$this->errorCollection[] = new Error(Loc::getMessage('CRM_OIIE_MODULE_NOT_INSTALLED_CATALOG'));
			$state = false;
		}

		return $state;
	}

	/**
	 * Check access to execute instagram import
	 *
	 * @return bool
	 */
	private function checkAccess(): bool
	{
		return AccessController::getCurrent()->check(
			ActionDictionary::ACTION_CATALOG_IMPORT_EXECUTION
		);
	}

	protected function checkSessionNotifications()
	{
		if (!empty($_SESSION['IMPORT_INSTAGRAM_NOTIFICATION']) && is_array($_SESSION['IMPORT_INSTAGRAM_NOTIFICATION']))
		{
			$this->arResult['NOTIFICATIONS'] = $_SESSION['IMPORT_INSTAGRAM_NOTIFICATION'];
		}
	}

	public static function markSessionNotificationsRead()
	{
		unset($_SESSION['IMPORT_INSTAGRAM_NOTIFICATION']);
	}

	protected function addSessionNotification($message)
	{
		if (!is_array($_SESSION['IMPORT_INSTAGRAM_NOTIFICATION']))
		{
			$_SESSION['IMPORT_INSTAGRAM_NOTIFICATION'] = [];
		}

		$_SESSION['IMPORT_INSTAGRAM_NOTIFICATION'][] = $message;
	}

	protected function initialization()
	{
		$this->status = Instagram::getStatus();

		$this->arResult['STATUS'] = $this->status['STATUS'];
		$this->arResult['ACTIVE_STATUS'] = $this->status['ACTIVE'];
		$this->arResult['CONNECTION_STATUS'] = $this->status['CONNECTION'];
		$this->arResult['REGISTER_STATUS'] = $this->status['REGISTER'];

		$this->arResult['PAGE'] = $this->request->get($this->pageId);
	}

	protected function setStatus($status)
	{
		$status = (bool)$status;

		$this->arResult['STATUS'] = $status;

		$this->status['CONNECTION'] = $status;
		$this->arResult['CONNECTION_STATUS'] = $status;

		$this->status['REGISTER'] = $status;
		$this->arResult['REGISTER_STATUS'] = $status;

		Instagram::setStatus($this->status);
	}

	protected function setDeleteStatus()
	{
		$this->status['STATUS'] = false;
		$this->arResult['STATUS'] = false;

		$this->status['ACTIVE'] = Instagram::isAvailable();
		$this->arResult['ACTIVE_STATUS'] = Instagram::isAvailable();

		$this->status['CONNECTION'] = false;
		$this->arResult['CONNECTION_STATUS'] = false;

		$this->status['REGISTER'] = false;
		$this->arResult['REGISTER_STATUS'] = false;

		Instagram::setStatus($this->status);
	}

	public function saveForm()
	{
		$connector = Instagram::getConnectorName();

		if ($this->request->isPost() && !empty($this->request[$connector.'_form']))
		{
			if (check_bitrix_sessid())
			{
				if (!empty($this->arResult['ACTIVE_STATUS']))
				{
					// If you remove the reference to the user
					if ($this->request[$connector.'_del_user'])
					{
						$deleteResult = Instagram::deleteActiveUser($this->request->get('user_id'));

						if ($deleteResult->isSuccess())
						{
							$this->setStatus(false);
							$this->messages[] = Loc::getMessage('CRM_OIIE_FACEBOOK_OK_DEL_USER');
							$this->arResult['SHOW_ACTUAL_PAGE'] = true;
						}
						else
						{
							$this->errorCollection[] = new Error(Loc::getMessage('CRM_OIIE_FACEBOOK_NO_DEL_USER'));
						}
					}

					// If you remove the reference to the group
					if ($this->request[$connector.'_del_page'])
					{
						$deleteResult = Instagram::deleteActivePage($this->request->get('page_id'));

						if ($deleteResult->isSuccess())
						{
							$this->setStatus(false);
							$this->messages[] = Loc::getMessage('CRM_OIIE_FACEBOOK_OK_DEL_PAGE');
							$this->arResult['SHOW_ACTUAL_PAGE'] = true;
						}
						else
						{
							$this->errorCollection[] = new Error(Loc::getMessage('CRM_OIIE_FACEBOOK_NO_DEL_PAGE'));
						}
					}

					// If you bind to the group
					if ($this->request[$connector.'_authorization_page'])
					{
						$authPageResult = Instagram::bindAuthorizationPage($this->request->get('page_id'));

						if ($authPageResult->isSuccess())
						{
							$this->setStatus(true);
							Option::set('crm', Instagram::LAST_VIEWED_TIMESTAMP_OPTION, 0);

							if ($this->arParams['REDIRECT_TO_IMPORT'] === 'Y')
							{
								$this->arResult['SHOW_ACTUAL_PAGE'] = true;
								$this->addSessionNotification(Loc::getMessage('CRM_OIIE_IMPORT_CONNECTED'));
							}
							else
							{
								$this->messages[] = Loc::getMessage('CRM_OIIE_FACEBOOK_OK_AUTHORIZATION_PAGE');
								$this->addSessionNotification(Loc::getMessage('CRM_OIIE_GO_TO_IMPORT', [
									'#LINK#' => $this->arParams['PATH_TO_CONNECTOR_INSTAGRAM_VIEW_FULL'],
								]));
							}
						}
						else
						{
							$this->setStatus(false);
							$this->errorCollection[] = new Error(Loc::getMessage('CRM_OIIE_FACEBOOK_NO_AUTHORIZATION_PAGE'));
						}

						// Reset cache
						Instagram::cleanCache();
					}

					if ($this->request[$connector.'_del'])
					{
						$deleteResult = Instagram::deleteConnector();

						if ($deleteResult->isSuccess())
						{
							$this->setDeleteStatus();
							$this->arResult['PAGE'] = '';
							$this->arResult['SHOW_ACTUAL_PAGE'] = true;

							$this->addSessionNotification(Loc::getMessage('CRM_OIIE_IMPORT_DISCONNECTED'));
						}
						else
						{
							$this->errorCollection[] = new Error(Loc::getMessage('CRM_OIIE_SETTINGS_NO_DISABLE'));
						}
					}
				}

				if (!$this->arResult['STATUS'])
				{
					\Bitrix\Crm\Order\Import\Instagram::clearNewMediaOption();
				}
			}
			else
			{
				$this->errorCollection[] = new Error(Loc::getMessage('CRM_OIIE_FACEBOOK_SESSION_HAS_EXPIRED'));
			}
		}
	}

	protected function getRedirectUri()
	{
		$uri = new Uri(Instagram::getCurrentUri().$this->arParams['PATH_TO_CONNECTOR_INSTAGRAM_EDIT_FULL']);
		$uri->addParams([
			'reload' => 'Y',
			'ajaxid' => $this->arParams['AJAX_ID'],
			$this->pageId => 'simple_form',
		]);

		return urlencode($uri->getUri());
	}

	public function obtainForm()
	{
		$uri = new Uri($this->arParams['PATH_TO_CONNECTOR_INSTAGRAM_EDIT_FULL']);
		$uri->deleteParams([$this->pageId, 'open_block']);
		$this->arResult['URL']['DELETE'] = $uri->getUri();

		$uri = new Uri($this->arParams['PATH_TO_CONNECTOR_INSTAGRAM_EDIT_FULL']);
		$uri->deleteParams([$this->pageId, 'open_block']);
		$uri->addParams([$this->pageId => 'open_block']);
		$this->arResult['URL']['SIMPLE_FORM'] = $uri->getUri();

		$this->arResult['FORM']['STEP'] = 1;

		if ($this->arResult['ACTIVE_STATUS'])
		{
			// Reset cache
			if (!empty($this->arResult['PAGE']))
			{
				Instagram::cleanAuthCache();
			}

			$this->arResult['FORM'] = Instagram::getConnection();
			$this->arResult['FORM']['USER']['URI'] .= $this->getRedirectUri();

			if (empty($this->arResult['FORM']['ERRORS']))
			{
				if (!empty($this->arResult['FORM']['PAGE']))
				{
					$this->arResult['FORM']['STEP'] = 3;

					$this->setStatus(true);
				}
				elseif (!empty($this->arResult['FORM']['PAGES']))
				{
					$this->arResult['FORM']['STEP'] = 2;

					$this->setStatus(false);
				}
				elseif (!empty($this->arResult['FORM']['USER']))
				{
					$this->arResult['FORM']['STEP'] = 1;

					$this->setStatus(false);
				}

				if (!empty($this->arResult['FORM']['GROUP_DEL']))
				{
					$this->errorCollection[] = new Error(
						Loc::getMessage('CRM_OIIE_FACEBOOK_REMOVED_REFERENCE_TO_PAGE')
					);
				}
			}
			else
			{
				/** @var Error $error */
				foreach ($this->arResult['FORM']['ERRORS'] as $error)
				{
					if ($error->getCode() === Instagram::ERROR_CONNECTOR_MESSENGER_INVALID_OAUTH_ACCESS_TOKEN)
					{
						$this->arResult['FORM']['STEP'] = 1;
					}

					$this->errorCollection[] = $error;
				}
			}
		}

		$this->arResult['CONNECTOR'] = Instagram::getConnectorName();
	}

	protected function isImportAvailable()
	{
		return Instagram::isAvailable();
	}

	protected function showErrors()
	{
		if (!$this->errorCollection->isEmpty())
		{
			if (count($this->errorCollection) > 1)
			{
				ShowError(implode('<br>', $this->errorCollection->toArray()));
			}
			else
			{
				$errors = $this->errorCollection->toArray();

				$this->arResult['ERROR_TITLE'] = (string)reset($errors);
				$this->includeComponentTemplate('error');
			}
		}
	}

	public function executeComponent()
	{
		global $APPLICATION;

		$this->arResult['NEED_RESTRICTION_NOTE'] = $this->needAsteriskForCompanyName();

		$APPLICATION->SetTitle($this->getLocalizationMessage('CRM_OIIE_TITLE'));

		Loc::loadMessages(__FILE__);

		if ($this->checkModules())
		{
			if (!$this->checkAccess())
			{
				$this->errorCollection[] = new Error(Loc::getMessage('CRM_OIIE_ERROR_ACCESS_DENIED'));
			}
			elseif ($this->isImportAvailable())
			{
				if ($this->request->get('reload') === 'y' || $this->request->get('reload') === 'Y')
				{
					$this->arResult['RELOAD'] = $this->request->get('ajaxid');

					$addParams = ['bxajaxid' => $this->arResult['RELOAD']];

					if ($this->request->get('IFRAME_TYPE') === 'SIDE_SLIDER')
					{
						$addParams['IFRAME'] = 'Y';
					}

					$uri = new Uri(\Bitrix\Main\Context::getCurrent()->getServer()->getRequestUri());
					$uri->deleteParams(['reload', 'ajaxid']);
					$uri->addParams($addParams);
					$this->arResult['URL_RELOAD'] = $uri->getUri();
				}

				if (!empty($this->arResult['URL_RELOAD']))
				{
					$this->includeComponentTemplate('reload');
				}
				else
				{
					$this->initialization();
					$this->saveForm();
					$this->obtainForm();

					if (!$this->errorCollection->isEmpty())
					{
						$this->arResult['error'] = $this->errorCollection->toArray();
					}

					if (!empty($this->messages))
					{
						$this->arResult['messages'] = $this->messages;
					}

					$this->checkSessionNotifications();

					$this->includeComponentTemplate();
				}

				$this->errorCollection->clear();
			}
			else
			{
				$this->errorCollection[] = new Error(Loc::getMessage('CRM_OIIE_FACEBOOK_NO_IMPORT_AVAILABLE'));
			}
		}

		$this->showErrors();
	}

	private function needAsteriskForCompanyName(): bool
	{
		static $result = null;
		if ($result)
		{
			return $result;
		}

		if (LANGUAGE_ID !== 'ru')
		{
			$result = false;
			return $result;
		}

		$region = Application::getInstance()->getLicense()->getRegion();
		$result = ($region === null || $region === 'ru');

		return $result;
	}

	public function getLocalizationMessage(string $code, array $replace = null): ?string
	{
		$result = '';
		$asteriskPostfix = '_WITH_ASTERISK';

		if ($this->needAsteriskForCompanyName())
		{
			$result = Loc::getMessage($code . $asteriskPostfix, $replace);
		}

		return $result ?: Loc::getMessage($code, $replace);
	}
}
