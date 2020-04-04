<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

use \Bitrix\Main\Localization\Loc;
use \Bitrix\Rest\APAuth\PermissionTable;
use \Bitrix\Rest\APAuth\PasswordTable;
use \Bitrix\Main\Config\Option;

class CrmConfigExternalPluginsComponent extends \CBitrixComponent
{
	protected $id = '';
	protected $uid = 0;
	protected $allowedCMS = array('1cbitrix', 'wordpress', 'drupal7', 'magento2', 'joomla');

	/**
	 * Get current user id.
	 * return int
	 */
	protected function getUserId()
	{
		return \CCrmSecurityHelper::GetCurrentUserId();
	}

	/**
	 * Init class' vars.
	 * @return boolean
	 */
	protected function init()
	{
		Loc::loadMessages(__FILE__);
		if (!\Bitrix\Main\Loader::includeModule('crm'))
		{
			return false;
		}
		// check auth
		if (!($this->uid = $this->getUserId()) || !\CCrmPerms::IsAdmin($this->getUserId()))
		{
			$GLOBALS['APPLICATION']->AuthForm(Loc::getMessage('CRM_CONFIG_PLG_REST_NOT_AUTH'));
			return false;
		}
		// check modules
		if (!\Bitrix\Main\Loader::includeModule('rest'))
		{
			ShowError(Loc::getMessage('CRM_CONFIG_PLG_REST_NOT_INSTALLED'));
			return false;
		}
		// check vars
		if (isset($this->arParams['CMS_ID']) &&
			in_array($this->arParams['CMS_ID'], $this->allowedCMS)
		)
		{
			$this->id = $this->arParams['CMS_ID'];
		}
		//set domain zone
		if (LANGUAGE_ID == 'ru')
		{
			$this->arResult['B24_LANG'] = 'ru';
		}
		elseif (LANGUAGE_ID == 'de')
		{
			$this->arResult['B24_LANG'] = 'de';
		}
		else
		{
			$this->arResult['B24_LANG'] = 'com';
		}

		$this->arResult['ERROR'] = '';
		$this->arParams['IS_AJAX'] = isset($this->arParams['IS_AJAX']) && $this->arParams['IS_AJAX'] == 'Y';

		return true;
	}

	/**
	 * Get private url.
	 * @return string
	 */
	protected function getUrl()
	{
		if ($this->id != '')
		{
			if ($plugins = unserialize(Option::get('crm', 'config_external_plugins')))
			{
				if (is_array($plugins) && isset($plugins[$this->id]))
				{
					if ($password = PasswordTable::getById($plugins[$this->id])->fetch())
					{
						if ($password['USER_ID'] != $this->getUserId())
						{
							return '********************';
						}
						else
						{
							return \CRestUtil::getWebhookEndpoint($password['PASSWORD'], $password['USER_ID']);
						}
					}
				}
			}
		}

		return '';
	}

	/**
	 * Activate / deactivate current plugin.
	 * @param int $status 1 or 0.
	 * @return void
	 */
	protected function activate($status)
	{
		if (!$this->id)
		{
			return;
		}

		$plugins = unserialize(Option::get('crm', 'config_external_plugins'));
		if (!is_array($plugins))
		{
			$plugins = array();
		}
		// enable plugin
		if ($status && !isset($plugins[$this->id]))
		{
			$result = PasswordTable::add(
				array(
					'USER_ID' => $this->uid,
					'PASSWORD' => PasswordTable::generatePassword(),
					'DATE_CREATE' => new \Bitrix\Main\Type\DateTime(),
					'TITLE' => $this->id,
			   )
			);
			if ($result->getId())
			{
				PermissionTable::add(array(
					'PASSWORD_ID' => $result->getId(),
					'PERM' => 'crm',
				));

				PermissionTable::add(array(
					'PASSWORD_ID' => $result->getId(),
					'PERM' => 'user',
				));
				$plugins[$this->id] = $result->getId();
			}
		}
		// disable plugin
		if (!$status && isset($plugins[$this->id]))
		{
			PasswordTable::delete($plugins[$this->id]);
			unset($plugins[$this->id]);
		}
		// set option
		Option::set('crm', 'config_external_plugins', serialize($plugins));
	}

	/**
	 * Make some actions.
	 * @param Bitrix\Main\HttpRequest $request
	 * @return void
	 */
	protected function makeAction(Bitrix\Main\HttpRequest $request)
	{
		$redirect = false;

		if ($this->id)
		{
			if (($request->get('enable') == 1 || $request->get('enable') == 0) && check_bitrix_sessid())
			{
				$redirect = true;
				$this->activate($request->get('enable'));
			}
		}

		if ($redirect && !$this->arParams['IS_AJAX'])
		{
			$uri = new \Bitrix\Main\Web\Uri($request->getRequestUri());
			\LocalRedirect($uri->deleteParams(array('enable', 'sessid'))->getUri());
		}
	}

	/**
	 * Base executable method.
	 * @return void
	 */
	public function executeComponent()
	{
		if (!$this->init())
		{
			return;
		}

		$context = \Bitrix\Main\Application::getInstance()->getContext();
		$request = $context->getRequest();

		$this->makeAction($request);

		$this->arResult['REQUEST'] = $request;
		$this->arResult['CONNECTOR_URL'] = $this->getUrl();

		if ($this->arParams['IS_AJAX'])
		{
			return $this->arResult;
		}
		else
		{
			$GLOBALS['APPLICATION']->setTitle(Loc::getMessage('CRM_CONFIG_PLG_TITLE'));
			$this->IncludeComponentTemplate($this->id);
		}
	}
}