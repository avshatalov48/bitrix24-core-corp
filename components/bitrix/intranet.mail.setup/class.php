<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

require_once 'helper.php';

class CIntranetMailSetupComponent extends CBitrixComponent
{

	public function executeComponent()
	{
		global $USER, $APPLICATION;

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

		$page = isset($_REQUEST['page']) ? $_REQUEST['page'] : null;

		switch ($page)
		{
			case 'domain':
				return $this->executeDomainPage();
				break;
			case 'manage':
				return $this->executeManagePage();
				break;
			case 'success':
				return $this->executeSuccessPage();
				break;
			default:
				return $this->executeHomePage($page);
		}
	}

	private function redirect()
	{
		global $APPLICATION, $USER;

		$services = CIntranetMailSetupHelper::getMailServices();
		$mailbox  = CIntranetMailSetupHelper::getUserMailbox($USER->GetID());

		$serviceId = $mailbox['SERVICE_ID'];

		$error = false;
		switch ($services[$serviceId]['type'])
		{
			case 'imap':
				if (!empty($services[$serviceId]['link']))
					LocalRedirect($services[$serviceId]['link'], true);
				else if (!empty($mailbox['LINK']))
					LocalRedirect($mailbox['LINK'], true);
				else
					LocalRedirect($APPLICATION->GetCurPage().'?page=home');
				break;
			case 'controller':
				list($login, $domain) = explode('@', $mailbox['LOGIN'], 2);
				$crRedirect = CControllerClient::ExecuteEvent('OnMailControllerRedirect', array(
					'LOCALE' => LANGUAGE_ID,
					'DOMAIN' => $domain,
					'NAME'   => $login
				));
				if (isset($crRedirect['result']))
				{
					LocalRedirect($crRedirect['result'], true);
				}
				else
				{
					$error = empty($crRedirect['error'])
						? GetMessage('INTR_MAIL_CONTROLLER_INVALID')
						: CMail::getErrorMessage($crRedirect['error']);
				}
				break;
			case 'domain':
				list($login, $domain) = explode('@', $mailbox['LOGIN'], 2);
				$result = CMailDomain2::getRedirectUrl(
					LANGUAGE_ID,
					$services[$serviceId]['token'],
					$domain, $login,
					$errorUrl = '', $error
				);

				if (is_null($result))
					$error = CMail::getErrorMessage($error);
				else
					LocalRedirect($result, true);
				break;
			case 'crdomain':
				list($login, $domain) = explode('@', $mailbox['LOGIN'], 2);
				$crRedirect = CControllerClient::ExecuteEvent('OnMailControllerMemberRedirect', array(
					'LOCALE' => LANGUAGE_ID,
					'DOMAIN' => $domain,
					'NAME'   => $login
				));
				if (isset($crRedirect['result']))
				{
					LocalRedirect($crRedirect['result'], true);
				}
				else
				{
					$error = empty($crRedirect['error'])
						? GetMessage('INTR_MAIL_CONTROLLER_INVALID')
						: CMail::getErrorMessage($crRedirect['error']);
				}
				break;
			default:
				LocalRedirect($APPLICATION->GetCurPage().'?page=home');
		}

		if ($error !== false)
		{
			CUserCounter::Set($USER->GetID(), 'mail_unseen', -1, SITE_ID);

			CUserOptions::SetOption('global', 'last_mail_check_'.SITE_ID, time());
			CUserOptions::SetOption('global', 'last_mail_check_success_'.SITE_ID, false);

			LocalRedirect($APPLICATION->GetCurPage().'?page=home');
		}
	}

	// SUCCESS

	private function executeSuccessPage()
	{
		global $USER, $APPLICATION;

		$APPLICATION->SetTitle(GetMessage(IsModuleInstalled('bitrix24') ? 'INTR_MAIL_B24_PAGE_TITLE' : 'NTR_MAIL_PAGE_TITLE'));

		$this->arParams['SERVICES'] = CIntranetMailSetupHelper::getMailServices();
		$this->arParams['MAILBOX']  = CIntranetMailSetupHelper::getUserMailbox($USER->GetID());

		if (empty($this->arParams['MAILBOX']))
			LocalRedirect($APPLICATION->GetCurPage().'?page=home');

		$this->includeComponentTemplate('success');
	}

	// LANDING

	private function executeHomePage($page)
	{
		global $USER, $APPLICATION;

		$APPLICATION->SetTitle(GetMessage(IsModuleInstalled('bitrix24') ? 'INTR_MAIL_B24_PAGE_TITLE' : 'NTR_MAIL_PAGE_TITLE'));

		$this->arParams['SERVICES'] = CIntranetMailSetupHelper::getMailServices();
		$this->arParams['MAILBOX']  = CIntranetMailSetupHelper::getUserMailbox($USER->GetID());

		$this->arParams['CRM_AVAILABLE'] = false;
		if (\CModule::includeModule('crm') && \CCrmPerms::isAccessEnabled())
		{
			$this->arParams['CRM_AVAILABLE'] = $USER->isAdmin() || $USER->canDoOperation('bitrix24_config')
				|| COption::getOptionString('intranet', 'allow_external_mail_crm', 'Y', SITE_ID) == 'Y';
		}

		if (\CModule::includeModule('crm'))
		{
			$this->arParams['LEAD_SOURCE_LIST'] = \CCrmStatus::getStatusList('SOURCE');
			reset($this->arParams['LEAD_SOURCE_LIST']);
			$this->arParams['DEFAULT_LEAD_SOURCE'] = key($this->arParams['LEAD_SOURCE_LIST']);
			if (is_set($this->arParams['LEAD_SOURCE_LIST'], 'EMAIL'))
				$this->arParams['DEFAULT_LEAD_SOURCE'] = 'EMAIL';
			elseif (is_set($this->arParams['LEAD_SOURCE_LIST'], 'OTHER'))
				$this->arParams['DEFAULT_LEAD_SOURCE'] = 'OTHER';
		}

		if (!empty($this->arParams['MAILBOX']))
		{
			if (empty($page))
				return $this->redirect();

			$mbData = $this->arParams['MAILBOX'];

			$this->arParams['MAILBOX_LEAD_SOURCE'] = $this->arParams['DEFAULT_LEAD_SOURCE'];

			$this->arParams['BLACKLIST'] = array();
			$res = \Bitrix\Mail\BlacklistTable::getList(array(
				'select' => array('ITEM_TYPE', 'ITEM_VALUE'), // ITEM_TYPE for fetch_data_modification
				'filter' => array('MAILBOX_ID' => $mbData['ID']),
			));
			while ($item = $res->fetch())
				$this->arParams['BLACKLIST'][] = $item['ITEM_VALUE'];

			if (!empty($mbData['OPTIONS']['flags']) && is_array($mbData['OPTIONS']['flags']))
			{
				if ($this->arParams['CRM_AVAILABLE'] && in_array('crm_preconnect', $mbData['OPTIONS']['flags']))
				{
					if (in_array($mbData['SERVER_TYPE'], array('imap', 'controller', 'crdomain', 'domain')))
					{
						$this->arParams['CRM_PRECONNECT'] = true;

						$imapData = $mbData;
						if (in_array($mbData['SERVER_TYPE'], array('controller', 'crdomain')))
						{
							// @TODO: request controller
							$result = CMailDomain2::getImapData();

							$imapData['SERVER']  = $result['server'];
							$imapData['PORT']    = $result['port'];
							$imapData['USE_TLS'] = $result['secure'];
						}
						elseif ($mbData['SERVER_TYPE'] == 'domain')
						{
							$result = CMailDomain2::getImapData();

							$imapData['SERVER']  = $result['server'];
							$imapData['PORT']    = $result['port'];
							$imapData['USE_TLS'] = $result['secure'];
						}

						$imapDirs = \Bitrix\Mail\Helper::listImapDirs($imapData, $error, $errors);
						if ($imapDirs !== false)
						{
							$mbData['OPTIONS']['imap'] = array(
								'income'  => array(),
								'outcome' => array(),
							);

							foreach ($imapDirs as $i => $item)
							{
								if (!empty($item['disabled']))
									continue;

								if ($item['income'])
									$mbData['OPTIONS']['imap']['income'][] = $item['path'];
								elseif ($item['outcome'])
									$mbData['OPTIONS']['imap']['outcome'][] = $item['path'];
							}

							$imapOptions = $mbData['OPTIONS']['imap'];
							if (empty($imapOptions['income']) || empty($imapOptions['outcome']))
							{
								$this->arParams['IMAP_DIRS'] = $imapDirs;
							}
							else
							{
								$mbData['OPTIONS']['flags'] = array_diff($mbData['OPTIONS']['flags'], array('crm_preconnect'));
								$mbData['OPTIONS']['flags'][] = 'crm_connect';

								$result = CMailbox::update($mbData['ID'], array('OPTIONS' => $mbData['OPTIONS']));

								if ($result > 0)
								{
									$this->arParams['MAILBOX']['OPTIONS'] = $mbData['OPTIONS'];
									unset($this->arParams['CRM_PRECONNECT']);

									$filterFields = array(
										'MAILBOX_ID'         => $mbData['ID'],
										'NAME'               => sprintf('CRM IMAP %u', $USER->getId()),
										'ACTION_TYPE'        => 'crm_imap',
										'WHEN_MAIL_RECEIVED' => 'Y',
										'WHEN_MANUALLY_RUN'  => 'Y',
									);

									\CMailFilter::add($filterFields);
								}
							}
						}
						else
						{
							$this->arParams['IMAP_ERROR'] = $error;
							if ($errors instanceof \Bitrix\Main\ErrorCollection)
							{
								$messages = array();
								$details  = array();

								foreach ($errors as $item)
									${$item->getCode() < 0 ? 'details' : 'messages'}[] = $item;

								if (count($messages) == 1 && reset($messages)->getCode() == \Bitrix\Mail\Imap::ERR_AUTH)
								{
									$messages = array(
										new \Bitrix\Main\Error(getMessage('INTR_MAIL_IMAP_AUTH_ERR_EXT'), \Bitrix\Mail\Imap::ERR_AUTH)
									);
								}

								$this->arParams['IMAP_ERROR'] = join(': ', array_map(function($error)
								{
									return $error->getMessage();
								}, $messages));
								$this->arParams['IMAP_ERROR_EXT'] = join(': ', array_map(function($error)
								{
									return $error->getMessage();
								}, $details));
							}
						}
					}
				}

				if (!empty($mbData['OPTIONS']['crm_lead_source']) && array_key_exists($mbData['OPTIONS']['crm_lead_source'], $this->arParams['LEAD_SOURCE_LIST']))
					$this->arParams['MAILBOX_LEAD_SOURCE'] = $mbData['OPTIONS']['crm_lead_source'];
			}
		}

		foreach ($this->arParams['SERVICES'] as $service)
		{
			if ($service['type'] == 'controller')
			{
				$crDomains = CControllerClient::ExecuteEvent('OnMailControllerGetDomains', array());
				if (!empty($crDomains['result']) && is_array($crDomains['result']))
					$this->arParams['CR_DOMAINS'] = $crDomains['result'];
			}
			else if (in_array($service['type'], array('domain', 'crdomain')))
			{
				$this->arParams['DOMAIN_STATUS'] = self::checkDomainStatus(array(
					'type'   => $service['type'],
					'domain' => $service['server'],
					'token'  => $service['token']
				), $error);
			}
		}

		if ($USER->isAdmin() || $USER->canDoOperation('bitrix24_config'))
		{
			$crDomains = CControllerClient::ExecuteEvent('OnMailControllerGetMemberDomains', array('REGISTERED' => true));
			$this->arParams['REG_DOMAIN'] = empty($crDomains['result']) ? false : reset($crDomains['result']);
		}

		$this->includeComponentTemplate('home');
	}

	// MANAGE

	private function executeManagePage()
	{
		global $USER, $APPLICATION;

		$APPLICATION->setTitle(GetMessage('INTR_MAIL_MANAGE_PAGE_TITLE'));

		CJSCore::Init(array('admin_interface'));

		if (!$USER->isAdmin() && !$USER->canDoOperation('bitrix24_config'))
		{
			$APPLICATION->AuthForm(GetMessage('ACCESS_DENIED'));
			return;
		}

		$this->arParams['BLACKLIST'] = array();
		$res = \Bitrix\Mail\BlacklistTable::getList(array(
			'select' => array('ITEM_TYPE', 'ITEM_VALUE'), // ITEM_TYPE for fetch_data_modification
			'filter' => array('MAILBOX_ID' => 0, 'SITE_ID' => SITE_ID),
		));
		while ($item = $res->fetch())
			$this->arParams['BLACKLIST'][] = $item['ITEM_VALUE'];

		$this->arParams['ALLOW_CRM'] = COption::getOptionString('intranet', 'allow_external_mail_crm', 'Y', SITE_ID) == 'Y';

		$this->arParams['SERVICES'] = array();
		$services = CIntranetMailSetupHelper::getMailServices();
		foreach ($services as $service)
		{
			if ($service['type'] == 'controller')
			{
				$crDomains = CControllerClient::ExecuteEvent('OnMailControllerGetDomains', array());
				if (!empty($crDomains['result']) && is_array($crDomains['result']))
				{
					$service['domains'] = $crDomains['result'];
					$service['users']   = array();
					foreach ($service['domains'] as $domain)
						$service['users'][$domain] = array();

					$crUsers = CControllerClient::ExecuteEvent('OnMailControllerGetUsers', array());
					if (!empty($crUsers['result']) && is_array($crUsers['result']))
					{
						foreach ($crUsers['result'] as $email)
						{
							list($login, $domain) = explode('@', $email, 2);

							if (isset($service['users'][$domain]))
								$service['users'][$domain][] = $login;
						}

						$dbMailboxes = CMailbox::getList(
							array(
								'TIMESTAMP_X' => 'ASC'
							),
							array(
								'ACTIVE'      => 'Y',
								'!USER_ID'    => 0,
								'SERVICE_ID'  => $service['id']
							)
						);
						while ($mailbox = $dbMailboxes->fetch())
						{
							list($login, $domain) = explode('@', $mailbox['LOGIN'], 2);
							if (!empty($service['users'][$domain]) && ($key = array_search($login, $service['users'][$domain])) !== false)
								array_splice($service['users'][$domain], $key, 1);
						}
					}

					$this->arParams['SERVICES'][] = $service;
				}
			}

			if (in_array($service['type'], array('domain', 'crdomain')))
			{
				$result = self::checkDomainStatus(array(
					'type'   => $service['type'],
					'domain' => $service['server'],
					'token'  => $service['token'],
				), $error);

				if (!empty($result['stage']) && $result['stage'] == 'added')
				{
					$service['domains'] = array($service['server']);
					$service['users']   = array($service['server'] => array());

					if ($service['type'] == 'domain')
					{
						$users = CMailDomain2::getDomainUsers($service['token'], $service['server'], $error);
						if (!empty($users) && is_array($users))
							$service['users'][$service['server']] = $users;
					}
					else if ($service['type'] == 'crdomain')
					{
						$crUsers = CControllerClient::ExecuteEvent('OnMailControllerGetMemberUsers', array(
							'DOMAIN' => $service['server']
						));
						if (!empty($crUsers['result']) && is_array($crUsers['result']))
							$service['users'][$service['server']] = $crUsers['result'];
					}

					$dbMailboxes = CMailbox::getList(
						array(
							'TIMESTAMP_X' => 'ASC'
						),
						array(
							'ACTIVE'      => 'Y',
							'!USER_ID'    => 0,
							'SERVICE_ID'  => $service['id']
						)
					);
					while ($mailbox = $dbMailboxes->fetch())
					{
						list($login, $domain) = explode('@', $mailbox['LOGIN'], 2);
						if (!empty($service['users'][$domain]) && ($key = array_search($login, $service['users'][$domain])) !== false)
							array_splice($service['users'][$domain], $key, 1);
					}

					$this->arParams['SERVICES'][] = $service;
				}
			}
		}

		$this->arResult['GRID_ID'] = 'manage_domain_grid';

		$gridOptions = new CGridOptions($this->arResult['GRID_ID']);

		$arSort = $gridOptions->getSorting(array('sort' => array('ID' => 'ASC'), 'vars' => array('by' => 'by', 'order' => 'order')));
		$arNav  = $gridOptions->getNavParams(array('nPageSize' => 50));

		$sortBy = key($arSort['sort']);
		$sortOrder = current($arSort['sort']);

		$arFilter = array('ACTIVE' => 'Y', '!UF_DEPARTMENT' => false);
		if (isset($_REQUEST['act']) && $_REQUEST['act'] == 'search' && !empty($_REQUEST['FILTER']))
		{
			$this->arResult['FILTER'] = $_REQUEST['FILTER'];

			$userIds = array();
			$dbMailboxes = CMailbox::getList(
				array(
					'TIMESTAMP_X' => 'ASC'
				),
				array(
					'LID'      => SITE_ID,
					'ACTIVE'   => 'Y',
					'!USER_ID' => 0,
					'LOGIN'    => $_REQUEST['FILTER']
				)
			);
			while ($mailbox = $dbMailboxes->fetch())
			{
				if (in_array($mailbox['SERVER_TYPE'], array('imap', 'controller', 'domain')))
					$userIds[] = $mailbox['USER_ID'];
			}

			$arFilter['ID'] = empty($userIds) ? 0 : join('|', $userIds);
		}

		$dbUsers = CUser::GetList(
			$sortBy, $sortOrder, $arFilter,
			array('FIELDS' => array('ID', 'LOGIN', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'PERSONAL_PHOTO', 'WORK_POSITION'))
		);

		$dbUsers->navStart($arNav['nPageSize']);

		$arRows = array();
		while ($user = $dbUsers->fetch())
		{

			$user['DETAIL_URL'] = COption::getOptionString('intranet', 'search_user_url', '/user/#ID#/');
			$user['DETAIL_URL'] = str_replace(array('#ID#', '#USER_ID#'), array($user['ID'], $user['ID']), $user['DETAIL_URL']);

			$user['PHOTO_THUMB'] = '<img src="/bitrix/components/bitrix/main.user.link/templates/.default/images/nopic_30x30.gif" border="0" alt="" width="32" height="32">';
			if (intval($user['PERSONAL_PHOTO']) > 0)
			{
				$imageFile = CFile::getFileArray($user['PERSONAL_PHOTO']);
				if ($imageFile !== false)
				{
					$arFileTmp = CFile::resizeImageGet(
						$imageFile, array('width' => 42, 'height' => 42),
						BX_RESIZE_IMAGE_EXACT, false
					);
					$user['PHOTO_THUMB'] = CFile::showImage($arFileTmp['src'], 32, 32);
				}
			}

			$anchor_id = RandString(8);
			$arCols = array(
				'NAME' => '<table id="user_'.$user['ID'].'" style="border-collapse: collapse; border: none; ">
					<tr>
						<td style="border: none !important; padding: 0px !important; ">
							<div style="width: 32px; height: 32px; margin:2px; padding: 2px; box-shadow:0 0 2px 1px rgba(0, 0, 0, 0.1);">
								<a href="'.$user['DETAIL_URL'].'">'.$user['PHOTO_THUMB'].'</a>
							</div>
						</td>
						<td style="border: none !important; padding: 0px 0px 0px 7px !important; vertical-align: top; ">
							<a href="'.$user['DETAIL_URL'].'"><b>'.CUser::formatName(CSite::getNameFormat(), $user, true, true).'</b></a><br>
							'.htmlspecialcharsbx($user['WORK_POSITION']).'
						</td>
					</tr>
				</table>',
				'EMAIL'  => '',
				'ADD'    => '',
				'DELETE' => ''

			);

			if ($mailbox = CIntranetMailSetupHelper::getUserMailbox($user['ID']))
			{
				switch ($mailbox['SERVER_TYPE'])
				{
					case 'imap':
						$arCols['EMAIL'] = $mailbox['LOGIN'];
						if (mb_strpos($mailbox['LOGIN'], '@') === false)
							$arCols['EMAIL'] .= '<br><span style="font-weight: normal; ">imap://'.$mailbox['SERVER'].':'.$mailbox['PORT'].'</span>';
						$arCols['ADD'] = '<a href="#" onclick="mb.create('.$user['ID'].'); return false; ">'.GetMessage('INTR_MAIL_MANAGE_CHANGE').'</a>';
						break;
					case 'controller':
					case 'domain':
					case 'crdomain':
						$arCols['EMAIL']  = $mailbox['LOGIN'];
						$arCols['ADD'] = '<a href="#" onclick="mb.create('.$user['ID'].'); return false; ">'.GetMessage('INTR_MAIL_MANAGE_CHANGE').'</a><br><a href="#" onclick="mb.changePassword('.$user['ID'].'); return false; ">'.GetMessage('INTR_MAIL_MANAGE_PASSWORD').'</a>';
						$arCols['DELETE'] = '<a href="#" onclick="mb.remove('.$user['ID'].'); return false; ">'.GetMessage('INTR_MAIL_MANAGE_DELETE').'</a>';
						break;
				}
			}
			else
			{
				$arCols['ADD'] = '<a href="#" onclick="mb.create('.$user['ID'].'); return false; ">'.GetMessage('INTR_MAIL_MANAGE_CREATE').'</a>';
			}

			if (empty($this->arParams['SERVICES']))
			{
				$arCols['ADD']    = '';
				$arCols['DELETE'] = '';
			}

			$arCols['EMAIL']  = '<span id="email_'.$user['ID'].'" style="font-weight: bold; white-space: nowrap; ">'.$arCols['EMAIL'].'</span>';
			$arCols['ADD']    = '<span id="create_'.$user['ID'].'" style="white-space: nowrap; ">'.$arCols['ADD'].'</span>';
			$arCols['DELETE'] = '<span id="delete_'.$user['ID'].'" style="white-space: nowrap; ">'.$arCols['DELETE'].'</span>';

			$arRows[$user['ID']] = array('data' => $user, 'columns' => $arCols);
		}

		$this->arResult['ROWS'] = $arRows;
		$this->arResult['ROWS_COUNT'] = $dbUsers->selectedRowsCount();

		$this->arResult['NAV_OBJECT'] = $dbUsers;
		$this->arResult['NAV_OBJECT']->bShowAll = false;

		$this->includeComponentTemplate('manage');
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

		$services = CIntranetMailSetupHelper::getMailServices();
		if ($domainService = CIntranetMailSetupHelper::getDomainService())
		{
			$serviceId = $domainService['id'];
			$settings  = array(
				'type'   => $domainService['type'],
				'domain' => $domainService['server'],
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
						$settings['domain'] = $_REQUEST['domain'];
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
									'SORT'    => $serviceId ? $services[$serviceId]['sort']+1 : 100,
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
									LocalRedirect($APPLICATION->GetCurPage().'?page=manage');
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
					LocalRedirect($APPLICATION->GetCurPage().'?page=home');
				else
					LocalRedirect($APPLICATION->GetCurPage().'?page=domain');
			}
		}

		$this->arParams['SERVICES'] = $services;
		$this->arResult['SERVICE']  = $serviceId;

		$this->arResult['SETTINGS'] = $settings;
		$this->arResult['ERRORS']   = $errors;
		$this->arResult['STATUS']   = $status;

		$this->includeComponentTemplate('domain');
	}

}
