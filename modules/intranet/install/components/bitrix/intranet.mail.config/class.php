<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

require_once $_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/intranet.mail.setup/helper.php';

class CIntranetMailConfigComponent extends CBitrixComponent
{

	public function executeComponent()
	{
		global $APPLICATION;

		$defaultUrlTemplates = array(
			'home'   => '',
			'domain' => 'domain/',
			'manage' => 'manage/',
		);

		$componentPage = '';

		if ($this->arParams['SEF_MODE'] == 'Y')
		{
			$urlTemplates  = \CComponentEngine::makeComponentUrlTemplates($defaultUrlTemplates, $this->arParams['SEF_URL_TEMPLATES']);
			$componentPage = \CComponentEngine::parseComponentPath($this->arParams['SEF_FOLDER'], $urlTemplates, $dummy);

			foreach ($urlTemplates as $page => $path)
			{
				$key = 'PATH_TO_MAIL_CFG_'.mb_strtoupper($page);
				$this->arResult[$key] = $this->arParams[$key] ?: $this->arParams['SEF_FOLDER'].$path;
			}

			$this->arResult['PATH_TO_MAIL_CONFIG']  = $this->arParams['PATH_TO_MAIL_CONFIG'] ?: $this->arParams['SEF_FOLDER'].'?config';
			$this->arResult['PATH_TO_MAIL_SUCCESS'] = $this->arParams['PATH_TO_MAIL_SUCCESS'] ?: $this->arParams['SEF_FOLDER'].'?success';
		}
		else
		{
			if (!empty($_REQUEST['page']))
				$componentPage = $_REQUEST['page'];

			foreach ($defaultUrlTemplates as $page => $path)
			{
				$this->arResult['PATH_TO_MAIL_CFG_'.mb_strtoupper($page)] = sprintf(
					'%s?page=%s',
					$APPLICATION->getCurPage(),
					mb_strtolower($page)
				);
			}

			$this->arResult['PATH_TO_MAIL_CONFIG']  = sprintf('%s?page=home&config', $APPLICATION->getCurPage());
			$this->arResult['PATH_TO_MAIL_SUCCESS'] = sprintf('%s?page=home&success', $APPLICATION->getCurPage());
		}

		if ($_REQUEST['IFRAME'] === 'Y')
		{
			foreach ($this->arResult as $code => &$field)
			{
				if (mb_strpos($code, 'PATH_TO_MAIL_') === 0)
				{
					$uri = new \Bitrix\Main\Web\Uri($field);
					$uri->addParams(array('IFRAME' => 'Y'));
					$field = $uri->getUri();
				}
			}
		}

		if (empty($componentPage) || !array_key_exists($componentPage, $defaultUrlTemplates))
			$componentPage = 'home';

		$this->includeComponentTemplate($componentPage);
	}

	public static function isFeatureAvailable($id)
	{
		static $features = array();

		if (!array_key_exists($id, $features))
		{
			$features[$id] = -1;

			switch ($id)
			{
				case 'b24_service':
				{
					if (\CModule::includeModule('bitrix24'))
					{
						$licensePrefix = \CBitrix24::getLicensePrefix();

						if ($licensePrefix == 'ua')
						{
							$features[$id] = -1;
							break;
						}

						foreach (\CIntranetMailSetupHelper::getMailServices() as $settings)
						{
							if ($settings['type'] == 'controller' && $settings['name'] == 'bitrix24')
							{
								if ($domains = \CIntranetMailConfigComponent::getControllerDomains())
								{
									$features[$id] = in_array('bitrix24.com', $domains) ? 0 : 1;
								}
							}
						}
					}
				} break;
				case 'domain_service':
				{
					foreach (\CIntranetMailSetupHelper::getMailServices() as $settings)
					{
						if ($settings['type'] == 'domain' || $settings['type'] == 'crdomain')
							$features[$id] = 1;
					}

					if (\CModule::includeModule('bitrix24'))
					{
						$licensePrefix = \CBitrix24::getLicensePrefix();

						if ($licensePrefix == 'ua')
						{
							$features[$id] = -1;
							break;
						}

						if (in_array($licensePrefix, array('ru', 'ua', 'by', 'kz')))
						{
							$features[$id] = 1;
						}
						else
						{
							$licenseType = \CBitrix24::getLicenseType();

							$features[$id] = in_array($licenseType, array('project', 'demo'))
								? max($features[$id], 0) : 1;
						}
					}
					else
					{
						if (in_array(LANGUAGE_ID, array('ru', 'ua', 'by', 'kz')))
							$features[$id] = 1;
					}
				} break;
			}
		}

		return $features[$id];
	}

	public static function getControllerDomains()
	{
		static $domains = null;

		if (is_null($domains))
		{
			$response = \CControllerClient::executeEvent('OnMailControllerGetDomains', array());
			$domains = !empty($response['result']) && is_array($response['result'])
				? array_map('mb_strtolower', $response['result'])
				: array();
		}

		return $domains;
	}

}
