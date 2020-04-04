<?php

use Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

require_once $_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/intranet.mail.setup/helper.php';

Loc::loadMessages(__FILE__);

class CIntranetMailConfigManageComponent extends CBitrixComponent
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

		return $this->executeManagePage();
	}

	private function executeManagePage()
	{
		global $USER, $APPLICATION;

		$APPLICATION->setTitle(Loc::getMessage('INTR_MAIL_MANAGE_PAGE_TITLE'));

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

		$res = \Bitrix\Mail\MailboxTable::getList(array(
			'select' => array('SERVICE_ID', 'LOGIN'),
			'filter' => array(
				'=LID'         => SITE_ID,
				'=ACTIVE'      => 'Y',
				'>USER_ID'     => 0,
				'>SERVICE_ID'  => 0,
				'@SERVER_TYPE' => array('controller', 'domain', 'crdomain'),
			)
		));

		$serviceMailboxes = array();
		while ($mailbox = $res->fetch())
		{
			$serviceId = $mailbox['SERVICE_ID'];
			if (empty($serviceMailboxes[$serviceId]))
				$serviceMailboxes[$serviceId] = array();
			$serviceMailboxes[$serviceId][] = strtolower($mailbox['LOGIN']);
		}

		$this->arParams['SERVICES'] = array();
		$services = CIntranetMailSetupHelper::getMailServices();
		foreach ($services as $service)
		{
			switch ($service['type'])
			{
				case 'controller':
					if (\CIntranetMailConfigComponent::isFeatureAvailable('b24_service') < 1)
						continue 2;
				case 'domain':
				case 'crdomain':
					if (\CIntranetMailConfigComponent::isFeatureAvailable('domain_service') < 1)
						continue 2;
			}

			$service['server'] = strtolower($service['server']);

			if (empty($serviceMailboxes[$service['id']]))
				$serviceMailboxes[$service['id']] = array();

			if ($service['type'] == 'controller')
			{
				$crDomains = CControllerClient::ExecuteEvent('OnMailControllerGetDomains', array());
				if (!empty($crDomains['result']) && is_array($crDomains['result']))
				{
					$service['domains'] = array_map('strtolower', $crDomains['result']);
					$service['users']   = array();
					foreach ($service['domains'] as $domain)
						$service['users'][$domain] = array();

					$crUsers = CControllerClient::ExecuteEvent('OnMailControllerGetUsers', array());
					if (!empty($crUsers['result']) && is_array($crUsers['result']))
					{
						foreach ($crUsers['result'] as $email)
						{
							$email = strtolower($email);
							if (!in_array($email, $serviceMailboxes[$service['id']]))
							{
								list($login, $domain) = explode('@', $email, 2);
								if (isset($service['users'][$domain]))
									$service['users'][$domain][] = $login;
							}
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

					$serviceUsers = array();
					if ($service['type'] == 'domain')
					{
						$users = CMailDomain2::getDomainUsers($service['token'], $service['server'], $error);
						if (!empty($users) && is_array($users))
							$serviceUsers = $users;
					}
					else if ($service['type'] == 'crdomain')
					{
						$crUsers = CControllerClient::ExecuteEvent('OnMailControllerGetMemberUsers', array(
							'DOMAIN' => $service['server']
						));
						if (!empty($crUsers['result']) && is_array($crUsers['result']))
							$serviceUsers = $crUsers['result'];
					}

					foreach ($serviceUsers as $login)
					{
						$login = strtolower($login);
						if (!in_array(sprintf('%s@%s', $login, $service['server']), $serviceMailboxes[$service['id']]))
							$service['users'][$service['server']][] = $login;
					}

					$this->arParams['SERVICES'][] = $service;
				}
			}
		}

		$this->arResult['MODE'] = !empty($_REQUEST['mode']) && $_REQUEST['mode'] == 'mailbox' ? 'mailbox' : 'user';

		if ('user' == $this->arResult['MODE'])
		{
			$this->arResult['GRID_ID']   = 'mail_manage_user_grid';
			$this->arResult['FILTER_ID'] = 'mail_manage_user_grid_filter';

			$this->arResult['FILTER'] = array(
				array(
					'id'      => 'HAS_EMAIL',
					'name'    => Loc::getMessage('INTR_MAIL_FILTER_HAS_EMAIL'),
					'type'    => 'checkbox',
					'default' => true,
				),
			);
		}
		else
		{
			$this->arResult['GRID_ID']   = 'mail_manage_mailbox_grid';
			$this->arResult['FILTER_ID'] = 'mail_manage_mailbox_grid_filter';

			$this->arResult['FILTER'] = array(
				array(
					'id'      => 'HAS_USER',
					'name'    => Loc::getMessage('INTR_MAIL_FILTER_HAS_USER'),
					'type'    => 'checkbox',
					'default' => true,
				),
			);

			if (!empty($this->arParams['SERVICES']))
			{
				$domains = array();
				foreach ($this->arParams['SERVICES'] as $service)
					$domains = array_merge($domains, $service['domains']);

				$this->arResult['FILTER'][] = array(
					'id'      => 'DOMAIN_NAME',
					'name'    => Loc::getMessage('INTR_MAIL_FILTER_DOMAIN'),
					'type'    => 'list',
					'items'   => array_combine($domains, $domains),
					'default' => true,
				);
			}
		}

		$filterOption = new \Bitrix\Main\UI\Filter\Options($this->arResult['FILTER_ID']);
		$filterData   = $filterOption->getFilter($this->arResult['FILTER']);

		$gridOptions = new \Bitrix\Main\Grid\Options($this->arResult['GRID_ID']);

		$navData = $gridOptions->getNavParams(array('nPageSize' => 50));
		$nav = new \Bitrix\Main\UI\PageNavigation('nav-mail-manage');
		$nav->setPageSize($navData['nPageSize'])->initFromUri();

		if ('user' == $this->arResult['MODE'])
		{
			$runtime = array(
				new \Bitrix\Main\Entity\ReferenceField(
					'MAILBOX', 'Bitrix\Mail\Mailbox',
					array(
						'=this.ID'    => 'ref.USER_ID',
						'=ref.LID'    => new \Bitrix\Main\DB\SqlExpression('?s', SITE_ID),
						'=ref.ACTIVE' => new \Bitrix\Main\DB\SqlExpression('?s', 'Y'),
					)
				),
			);

			$filter = array(
				'=ACTIVE'        => 'Y',
				'!UF_DEPARTMENT' => false,
			);
			if (!empty($filterData['FILTER_APPLIED']))
			{
				if (isset($filterData['FIND']) && trim($filterData['FIND']))
				{
					$runtime[] = new \Bitrix\Main\Entity\ExpressionField('LOWER_NAME', 'LOWER(%s)', array('NAME'));
					$runtime[] = new \Bitrix\Main\Entity\ExpressionField('LOWER_LAST_NAME', 'LOWER(%s)', array('LAST_NAME'));
					$runtime[] = new \Bitrix\Main\Entity\ExpressionField('LOWER_LOGIN', 'LOWER(%s)', array('LOGIN'));

					$sqlHelper = \Bitrix\Main\Application::getConnection()->getSqlHelper();

					$words = preg_split('/\s+/', strtolower(trim($filterData['FIND'])), ($wordsLimit = 10)+1);
					$words = array_unique(array_slice($words, 0, $wordsLimit));
					foreach ($words as $word)
					{
						$pattern = sprintf('%s%%', $sqlHelper->forSql($word));

						$filter[] = array(
							'LOGIC' => 'OR',
							'=%LOWER_NAME'      => $pattern,
							'=%LOWER_LAST_NAME' => $pattern,
							'=%LOWER_LOGIN'     => $pattern,
						);
					}
				}

				if (isset($filterData['HAS_EMAIL']))
				{
					if ($filterData['HAS_EMAIL'] == 'Y')
						$filter['>MAILBOX.ID'] = 0;
					elseif ($filterData['HAS_EMAIL'] == 'N')
						$filter['==MAILBOX_ID'] = null;
				}	
			}

			$sortData = $gridOptions->getSorting(array(
				'sort' => array('NAME' => 'ASC'),
				'vars' => array('by' => 'by', 'order' => 'order'),
			));
			list($sortBy, $sortTo) = array_map('strtoupper', each($sortData['sort']));

			if (!in_array($sortBy, array('NAME')))
				$sortBy = 'NAME';
			if (!in_array($sortTo, array('ASC', 'DESC')))
				$sortTo = 'ASC';

			$order = array($sortBy => $sortTo);
			if ($sortBy == 'NAME')
			{
				$order = array(
					'LAST_NAME' => $sortTo,
					'NAME'      => $sortTo,
					'ID'        => $sortTo,
				);
			}

			$dbUsers = \Bitrix\Main\UserTable::getList(array(
				'runtime'     => $runtime,
				'select'      => array(
					'ID', 'LOGIN', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'PERSONAL_PHOTO', 'WORK_POSITION',
					new \Bitrix\Main\Entity\ExpressionField('MAILBOX_ID', 'MAX(%s)', array('MAILBOX.ID')),
				),
				'filter'      => $filter,
				'group'       => array('ID'),
				'order'       => $order,
				'offset'      => $nav->getOffset(),
				'limit'       => $nav->getLimit(),
				'count_total' => true,
			));

			$nav->setRecordCount($dbUsers->getCount());

			$arRows = array();
			$users = array();
			$mailboxes = array();

			$mailboxIds = array();
			while ($user = $dbUsers->fetch())
			{
				$arRows[$user['ID']] = array($user['ID'], $user['MAILBOX_ID']);

				$users[$user['ID']] = $user;
				if ($user['MAILBOX_ID'] > 0)
					$mailboxIds[] = $user['MAILBOX_ID'];
			}

			if (!empty($mailboxIds))
			{
				$res = \Bitrix\Mail\MailboxTable::getList(array(
					'select' => array('ID', 'NAME', 'LOGIN', 'SERVER_TYPE', 'SERVER', 'PORT', 'OPTIONS'),
					'filter' => array('@ID' => $mailboxIds)
				));

				while ($mailbox = $res->fetch())
				{
					$mailbox['EMAIL_NAME'] = $mailbox['SERVER_TYPE'] == 'imap' && strpos($mailbox['NAME'], '@') > 0
						? $mailbox['NAME'] : $mailbox['LOGIN'];

					$mailboxes[$mailbox['ID']] = $mailbox;
				}
			}
		}
		else
		{
			if (!empty($filterData['FILTER_APPLIED']))
			{
				if (isset($filterData['FIND']) && trim($filterData['FIND']))
					$searchString = strtolower(trim($filterData['FIND']));

				if (!empty($filterData['HAS_USER']))
				{
					if ($filterData['HAS_USER'] == 'Y')
						$skipHasNoUser = true;
					if ($filterData['HAS_USER'] != 'Y')
						$skipHasUser = true;
				}

				if (!empty($filterData['DOMAIN_NAME']))
					$domainName = strtolower($filterData['DOMAIN_NAME']);
			}

			$arRows = array();
			$users = array();
			$mailboxes = array();

			if (empty($skipHasUser))
			{
				$res = \Bitrix\Mail\MailboxTable::getList(array(
					'select' => array('ID', 'SERVICE_ID', 'NAME', 'LOGIN', 'SERVER_TYPE', 'SERVER', 'PORT', 'OPTIONS', 'USER_ID'),
					'filter' => array(
						'=LID'         => SITE_ID,
						'=ACTIVE'      => 'Y',
						'>USER_ID'     => 0,
						'>SERVICE_ID'  => 0,
						'@SERVER_TYPE' => array('imap', 'controller', 'domain', 'crdomain'),
					)
				));

				$userIds = array();
				while ($mailbox = $res->fetch())
				{
					$mailbox['EMAIL_NAME'] = $mailbox['SERVER_TYPE'] == 'imap' && strpos($mailbox['NAME'], '@') > 0
						? $mailbox['NAME'] : $mailbox['LOGIN'];

					if (!empty($domainName) && !preg_match(sprintf('/@%s$/i', preg_quote($domainName, '/')), $mailbox['EMAIL_NAME']))
						continue;

					if (!empty($searchString) && strpos($mailbox['EMAIL_NAME'], $searchString) === false)
						continue;

					$arRows[$mailbox['ID']] = array($mailbox['USER_ID'], $mailbox['ID']);

					$mailboxes[$mailbox['ID']] = $mailbox;
					$userIds[] = $mailbox['USER_ID'];
				}

				if (!empty($userIds))
				{
					$res = \Bitrix\Main\UserTable::getList(array(
						'select' => array('ID', 'LOGIN', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'PERSONAL_PHOTO', 'WORK_POSITION'),
						'filter' => array('@ID' => $userIds),
					));

					while ($user = $res->fetch())
						$users[$user['ID']] = $user;
				}
			}

			if (empty($skipHasNoUser) && !empty($this->arParams['SERVICES']))
			{
				foreach ($this->arParams['SERVICES'] as $service)
				{
					foreach ($service['users'] as $domain => $logins)
					{
						if (!empty($domainName))
						{
							if ($domain != $domainName)
								continue;
						}

						foreach ($logins as $login)
						{
							$mailboxName = sprintf('%s@%s', $login, $domain);
							$mailboxId   = sprintf('%u:%s', $service['id'], $mailboxName);

							if (!empty($searchString) && strpos($mailboxName, $searchString) === false)
								continue;

							$arRows[$mailboxId] = array(0, $mailboxId);

							$mailboxes[$mailboxId] = array(
								'EMAIL_NAME'  => $mailboxName,
								'SERVER_TYPE' => $service['type'],
							);
						}
					}
				}
			}

			$nav->setRecordCount(count($arRows));

			$sortData = $gridOptions->getSorting(array(
				'sort' => array('EMAIL' => 'ASC'),
				'vars' => array('by' => 'by', 'order' => 'order'),
			));
			list($sortBy, $sortTo) = array_map('strtoupper', each($sortData['sort']));

			if (!in_array($sortBy, array('EMAIL')))
				$sortBy = 'EMAIL';
			if (!in_array($sortTo, array('ASC', 'DESC')))
				$sortTo = 'ASC';

			$order = array($sortBy => $sortTo);
			if ($sortBy == 'EMAIL')
			{
				$orderSign = $sortTo == 'ASC' ? 1 : -1;
				uasort(
					$arRows,
					function ($a, $b) use ($mailboxes, $orderSign)
					{
						list($loginA, $domainA) = explode('@', $mailboxes[$a[1]]['EMAIL_NAME']);
						list($loginB, $domainB) = explode('@', $mailboxes[$b[1]]['EMAIL_NAME']);

						if ($domainA != $domainB)
							return $orderSign * ($domainA > $domainB ? 1 : -1);
						elseif ($loginA != $loginB)
							return $orderSign * ($loginA > $loginB ? 1 : -1);
						else
							return 0;
					}
				);
			}

			$arRows = array_slice($arRows, $nav->getOffset(), $nav->getLimit(), true);
		}

		$detailUrlTemplate = \COption::getOptionString('intranet', 'search_user_url', '/user/#ID#/');
		foreach ($arRows as $id => $row)
		{
			$arCols = array(
				'NAME'  => '<span href="#" style="font-weight: normal; color: #d0d0d0; ">'.Loc::getMessage('INTR_MAIL_FILTER_NO_USER').'</span>',
				'EMAIL' => '<span href="#" style="font-weight: normal; color: #d0d0d0; ">'.Loc::getMessage('INTR_MAIL_FILTER_NO_EMAIL').'</span>',
				'CRM'   => '<span href="#" style="font-weight: normal; color: #d0d0d0; ">CRM &#10007;</span>',
			);

			$actions = array();

			if (!empty($users[$row[0]]))
			{
				$user = $users[$row[0]];

				$user['DETAIL_URL'] = str_replace(array('#ID#', '#USER_ID#'), array($user['ID'], $user['ID']), $detailUrlTemplate);

				$user['PHOTO_THUMB'] = '<span class="manage-user-image"></span>';
				if (intval($user['PERSONAL_PHOTO']) > 0)
				{
					$imageFile = CFile::getFileArray($user['PERSONAL_PHOTO']);
					if ($imageFile !== false)
					{
						$arFileTmp = CFile::resizeImageGet(
							$imageFile, array('width' => 38, 'height' => 38),
							BX_RESIZE_IMAGE_EXACT, false
						);
						$user['PHOTO_THUMB'] = sprintf(
							'<span class="manage-user-image" style="background: url(\'%s\'); "></span>',
							$arFileTmp['src']
						);					
					}
				}

				$arCols['NAME'] = sprintf(
					'%s<span class="manage-user-details"><a href="%s"><b>%s</b></a><div>%s</div></span>',
					$user['PHOTO_THUMB'], $user['DETAIL_URL'],
					\CUser::FormatName(\CSite::getNameFormat(), $user, true, true),
					htmlspecialcharsbx($user['WORK_POSITION'])
				);
			}

			if (!empty($mailboxes[$row[1]]))
			{
				$mailbox = $mailboxes[$row[1]];

				$arCols['EMAIL'] = $mailbox['EMAIL_NAME'];

				if ($mailbox['SERVER_TYPE'] == 'imap' && !strpos($arCols['EMAIL'], '@'))
					$arCols['EMAIL'] .= '<br><span style="font-weight: normal; ">imap://'.$mailbox['SERVER'].':'.$mailbox['PORT'].'</span>';

				if ($row[0] > 0)
				{
					$arCols['CRM'] = !empty($mailbox['OPTIONS']['flags']) && in_array('crm_connect', (array) $mailbox['OPTIONS']['flags'])
						? '<span href="#" style="font-weight: normal; color: green; ">CRM &#10004;</span>'
						: '<span href="#" style="font-weight: normal; color: red; ">CRM &#10008;</span>';
				}
			}

			$actions = array();

			if (!empty($mailboxes[$row[1]]))
			{
				if (in_array($mailbox['SERVER_TYPE'], array('controller', 'domain', 'crdomain')))
				{
					$actions[] = array(
						'text'    => Loc::getMessage('INTR_MAIL_MANAGE_PASSWORD'),
						'onclick' => 'mb.changePassword(\''.$id.'\', \''.$row[1].'\');'
					);
				}

				if (!empty($users[$row[0]]))
				{
					//$actions[] = array(
					//	'text'    => Loc::getMessage('INTR_MAIL_MANAGE_CHANGE2'),
					//	'onclick' => 'void(0);'
					//);
					$actions[] = array(
						'text'    => Loc::getMessage('INTR_MAIL_MANAGE_DETACH'),
						'onclick' => 'mb.release(\''.$id.'\', '.$row[1].');'
					);
				}
			}

			if ('user' == $this->arResult['MODE'])
			{
				if (empty($mailboxes[$row[1]]) && !empty($this->arParams['SERVICES']))
				{
					$actions[] = array(
						'text'    => Loc::getMessage('INTR_MAIL_MANAGE_CONNECT'),
						'onclick' => 'mb.create(\''.$id.'\', '.$row[0].');'
					);
				}
			}
			else
			{
				if (empty($users[$row[0]]))
				{
					$actions[] = array(
						'text'    => Loc::getMessage('INTR_MAIL_MANAGE_CONNECT2'),
						'onclick' => 'mb.create(\''.$id.'\', -1);'
					);

					if (in_array($mailbox['SERVER_TYPE'], array('controller', 'domain', 'crdomain')))
					{
						$actions[] = array(
							'text'    => Loc::getMessage('INTR_MAIL_MANAGE_DELETE'),
							'onclick' => 'mb.remove(\''.$id.'\');'
						);
					}
				}
			}

			$arCols['NAME']  = '<span id="user_'.htmlspecialcharsbx($id).'">'.$arCols['NAME'].'</span>';
			$arCols['EMAIL'] = '<span id="email_'.htmlspecialcharsbx($id).'" style="font-weight: bold; white-space: nowrap; ">'.$arCols['EMAIL'].'</span>';
			$arCols['CRM']   = '<span id="crm_'.htmlspecialcharsbx($id).'" style="white-space: nowrap; ">'.$arCols['CRM'].'</span>';

			$arRows[$id] = array(
				'id'      => $id,
				'data'    => array(
					'NAME'  => $row[0],
					'EMAIL' => $row[1],
				),
				'columns' => $arCols,
				'actions' => $actions,
			);
		}

		$this->arResult['ROWS'] = $arRows;

		$this->arResult['NAV_OBJECT'] = $nav;

		if (\CModule::includeModule('socialnetwork'))
			$this->arParams['COMPANY_STRUCTURE'] = \CSocNetLogDestination::getStucture();

		$this->arParams['CURRENT_USER'] = \Bitrix\Main\UserTable::getList(array(
			'filter' => array('=ID' => $USER->getId()),
		))->fetch();

		$this->includeComponentTemplate();
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

}
