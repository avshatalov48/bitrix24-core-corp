<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

require_once $_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/intranet.mail.setup/helper.php';

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

class CIntranetMailConfigDomainComponent extends CBitrixComponent
{

	public function executeComponent()
	{
		global $USER, $APPLICATION;

		\CBitrixComponent::includeComponentClass('bitrix:intranet.mail.config');

		if (!CModule::IncludeModule('mail'))
		{
			ShowError(GetMessage('MAIL_MODULE_NOT_INSTALLED'));
			return;
		}

		if (!is_object($USER) || !$USER->IsAuthorized())
		{
			$APPLICATION->AuthForm('');
			return;
		}

		if (!CIntranetUtils::IsExternalMailAvailable())
		{
			ShowError(GetMessage('INTR_MAIL_UNAVAILABLE'));
			return;
		}

		if (\CIntranetMailConfigComponent::isFeatureAvailable('domain_service') < 1)
			localRedirect($this->arParams['PATH_TO_MAIL_CONFIG']);

		return $this->executeDomainPage();
	}

	private static function checkDomainStatus($settings, &$error)
	{
		$error = null;

		if ($settings['type'] == 'domain')
		{
			$result = CMailDomain2::getDomainStatus($settings['token'], $settings['domain'], $error);

			if (is_null($result))
				$error = CMail::getErrorMessage($error);
			else if (!isset($result['stage']) || !in_array($result['stage'], array('owner-check', 'mx-check', 'added')))
				$error = CMail::getErrorMessage(CMail::ERR_API_DENIED);
		}
		else if ($settings['type'] == 'crdomain')
		{
			$result = null;

			$crResponse = CControllerClient::ExecuteEvent('OnMailControllerCheckMemberDomain', array(
				'DOMAIN' => $settings['domain']
			));
			if (!isset($crResponse['result']))
			{
				$error = empty($crResponse['error'])
					? GetMessage('INTR_MAIL_CONTROLLER_INVALID')
					: CMail::getErrorMessage($crResponse['error']);
			}
			else
			{
				$result = $crResponse['result'];

				if (!is_array($result))
					$error = GetMessage('INTR_MAIL_CONTROLLER_INVALID');
				else if (!isset($result['stage']) || !in_array($result['stage'], array('owner-check', 'mx-check', 'added')))
					$error = GetMessage('INTR_MAIL_CONTROLLER_INVALID');
				else if ($result['stage'] == 'owner-check' && (!isset($result['secrets']['name']) || !isset($result['secrets']['content'])))
					$error = GetMessage('INTR_MAIL_CONTROLLER_INVALID');
			}
		}

		return $result;
	}

	private function executeDomainPage()
	{
		global $USER, $APPLICATION;

		$APPLICATION->setTitle(GetMessage('INTR_MAIL_DOMAIN_PAGE_TITLE'));

		if (!$USER->isAdmin() && !$USER->canDoOperation('bitrix24_config'))
		{
			$APPLICATION->AuthForm(GetMessage('ACCESS_DENIED'));
			return;
		}

		$errors = array();
		$status = false;

		$serviceId = null;
		$settings  = array();

		if ($domainService = CIntranetMailSetupHelper::getDomainService())
		{
			$serviceId = $domainService['id'];
			$settings  = array(
				'type'   => $domainService['type'],
				'domain' => strtolower($domainService['server']),
				'flags'  => $domainService['flags'],
				'token'  => $domainService['token'],
				'public' => $domainService['encryption'] == 'N' ? 'Y' : 'N'
			);
		}

		if ($serviceId)
		{
			$status = self::checkDomainStatus($settings, $error); // не нужно при посте
			if ($error)
				$errors[] = $error;
		}

		if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST['act']))
		{
			$errors = array();

			if (!check_bitrix_sessid())
				$errors[] = GetMessage('INTR_MAIL_CSRF');

			if (!empty($errors));
			else if ($_POST['act'] == 'save')
			{
				if (empty($_REQUEST['type']) || !in_array($_REQUEST['type'], array('delegate', 'connect')))
					$errors[] = GetMessage('INTR_MAIL_FORM_ERROR');

				if (empty($errors))
				{
					if (!$serviceId && empty($_REQUEST['domain']))
						$errors[] = GetMessage('INTR_MAIL_INP_DOMAIN_EMPTY');

					if ($_REQUEST['type'] == 'connect' && empty($_REQUEST['token']))
						$errors[] = GetMessage('INTR_MAIL_INP_TOKEN_EMPTY');

					if (!$serviceId)
					{
						$settings['type']   = $_REQUEST['type'] == 'connect' ? 'domain' : 'crdomain';
						$settings['domain'] = strtolower($_REQUEST['domain']);
					}
					if ($settings['type'] == 'domain')
						$settings['token'] = $_REQUEST['token'];
					$settings['public'] = isset($_REQUEST['public']) && $_REQUEST['public'] == 'Y' ? 'Y' : 'N';

					if (empty($errors))
					{
						$status = self::checkDomainStatus($settings, $error);
						if ($error)
							$errors[] = $error;

						if (empty($errors))
						{
							if ($serviceId)
							{
								$result = \Bitrix\Mail\MailServicesTable::update($serviceId, array(
									'TOKEN'      => $settings['token'],
									'ENCRYPTION' => $settings['public'] == 'Y' ? 'N' : 'Y'
								));
							}
							else
							{
								$result = \Bitrix\Mail\MailServicesTable::add(array(
									'SITE_ID' => SITE_ID,
									'ACTIVE'  => 'Y',
									'SERVICE_TYPE' => $settings['type'],
									'NAME'    => $settings['domain'],
									'SERVER'  => $settings['domain'],
									'ENCRYPTION' => $settings['public'] == 'Y' ? 'N' : 'Y',
									'SORT'    => 100,
									'TOKEN'   => $settings['token']
								));

								if ($result->isSuccess())
								{
									$serviceId = $result->getId();

									if ($settings['type'] == 'domain')
									{
										if ($status['stage'] == 'added')
										{
											CAgent::addAgent('CIntranetUtils::notifyMailDomain("nomailbox", '.$serviceId.', '.$USER->getId().');', 'intranet', 'N', 3600*24*7);
										}
										else
										{
											CAgent::addAgent('CIntranetUtils::checkMailDomain('.$serviceId.', '.$USER->getId().');', 'intranet', 'N', 600);
											CAgent::addAgent('CIntranetUtils::notifyMailDomain("nocomplete", '.$serviceId.', '.$USER->getId().');', 'intranet', 'N', 3600*24*3);
										}

										CMailDomain2::setDomainLogo(
											$settings['token'], $settings['domain'],
											$_SERVER['DOCUMENT_ROOT'] . $this->getPath() . '/images/' . GetMessage('INTR_MAIL_DOMAIN_ICON'),
											$replace = false,
											$error
										);

										CMailDomain2::selLocale($settings['token'], $settings['domain'], LANGUAGE_ID, $error);
									}
								}
							}

							if ($result->isSuccess())
							{
								if ($status['stage'] == 'added')
									localRedirect($this->arParams['PATH_TO_MAIL_CFG_MANAGE']);
							}
							else
							{
								$errors[] = GetMessage('INTR_MAIL_SAVE_ERROR');
							}
						}
					}
				}
			}
			else if ($serviceId && $_POST['act'] == 'remove')
			{
				$result = \Bitrix\Mail\MailServicesTable::delete($serviceId);

				if ($result->isSuccess())
					localRedirect($this->arParams['PATH_TO_MAIL_CONFIG']);
				else
					localRedirect($this->arParams['PATH_TO_MAIL_CFG_DOMAIN']);
			}
		}

		$this->arResult['SERVICE']  = $serviceId;

		$this->arResult['SETTINGS'] = $settings;
		$this->arResult['ERRORS']   = $errors;
		$this->arResult['STATUS']   = $status;

		$this->includeComponentTemplate();
	}

}
