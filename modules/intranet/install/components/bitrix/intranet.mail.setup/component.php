<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

global $USER, $APPLICATION;

$APPLICATION->SetTitle(GetMessage('NTR_MAIL_PAGE_TITLE'));

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

$arParams['OPTIONS'] = array(
	'link',
	'server',
	'port',
	'encryption',
	'login',
	'password'
);

$result = Bitrix\Mail\MailServicesTable::getList(array(
	'filter' => array('ACTIVE' => 'Y', '=SITE_ID' => SITE_ID),
	'order'  => array('SORT' => 'ASC', 'NAME' => 'ASC')
));

$arParams['SERVICES'] = array();
while (($service = $result->fetch()) !== false)
{
	$arParams['SERVICES'][$service['ID']] = array(
		'name'       => $service['NAME'],
		'link'       => $service['LINK'],
		'icon'       => Bitrix\Mail\MailServicesTable::getIconSrc($service['NAME'], $service['ICON']),
		'server'     => $service['SERVER'],
		'port'       => $service['PORT'],
		'encryption' => $service['ENCRYPTION']
	);
}

if (empty($arParams['SERVICES']))
{
	ShowError(GetMessage('MAIL_SERVICES_NOT_FOUND'));
	return;
}

function CheckOption($option, $value, &$error)
{
	if (!in_array($option, array('encryption')) && empty($value))
	{
		$error = GetMessage('INTR_MAIL_INP_'.strtoupper($option).'_EMPTY');
	}

	if (empty($error))
	{
		switch ($option)
		{
			case 'server':
				$regExp = '`(?:(?:http|https|ssl|tls|imap)://)?((?:[a-z0-9](?:-*[a-z0-9])*\.?)+)`';
				if (preg_match($regExp, strtolower($value), $matches) && strlen($matches[1]) > 0)
				{
					$value = $matches[1];
				}
				else
				{
					$error = GetMessage('INTR_MAIL_INP_SERVER_BAD');
				}
				break;
			case 'port':
				if (preg_match('/^[0-9]+$/', $value))
				{
					$value = intval($value);
					if ($value < 1 || $value > 65535)
						$error = GetMessage('INTR_MAIL_INP_PORT_BAD');
				}
				else
				{
					$error = GetMessage('INTR_MAIL_INP_PORT_BAD');
				}
				break;
			case 'encryption':
				if ($value != 'Y')
					$value = 'N';
				break;
			case 'link':
				$regExp = '`((?:http|https)://)?((?:[a-z0-9](?:-*[a-z0-9])*\.?)+)(:[0-9]+)?(/.*)?`';
				if (preg_match($regExp, strtolower($value), $matches) && strlen($matches[2]) > 0)
				{
					$value = $matches[0];
					if (strlen($matches[1]) == 0)
						$value = 'http://' . $value;
				}
				else
				{
					$error = GetMessage('INTR_MAIL_INP_LINK_BAD');
				}
				break;
			case 'login':
				break;
			case 'password':
				break;
		}
	}

	return $value;
}

$arResult['STEP'] = isset($_REQUEST['STEP']) ? $_REQUEST['STEP'] : 'choose';
if (!in_array($arResult['STEP'], array('choose', 'check', 'setup', 'confirm', 'remove')))
	$arResult['STEP'] = 'choose';

$acc = CMailbox::GetList(
	array(
		'TIMESTAMP_X' => 'DESC'
	),
	array(
		'LID'         => SITE_ID,
		'ACTIVE'      => 'Y',
		'SERVER_TYPE' => 'imap',
		'USER_ID'     => $USER->GetID()
	)
)->Fetch();

if (empty($acc))
{
	if ($arResult['STEP'] == 'confirm' && isset($_REQUEST['ACT']) && $_REQUEST['ACT'] == 'remove');
	else if ($arResult['STEP'] == 'setup' && !empty($_REQUEST['SERVICE']) && array_key_exists($_REQUEST['SERVICE'], $arParams['SERVICES']))
	{
		$arResult['SERVICE'] = $_REQUEST['SERVICE'];
	}
	else if (in_array($arResult['STEP'], array('choose', 'setup')) && count($arParams['SERVICES']) == 1)
	{
		$arResult['STEP']    = 'setup';
		$arResult['SERVICE'] = key($arParams['SERVICES']);
	}
	else
	{
		$arResult['STEP'] = 'choose';
	}
}
else
{
	$arResult['ID']       = $acc['ID'];
	$arResult['SERVICE']  = $acc['SERVICE_ID'];
	$arResult['SETTINGS'] = array(
		'name'       => $acc['NAME'],
		'server'     => $acc['SERVER'],
		'port'       => $acc['PORT'],
		'link'       => $acc['LINK'],
		'login'      => $acc['LOGIN'],
		'password'   => $acc['PASSWORD'],
		'encryption' => $acc['USE_TLS'] == 'Y' ? 'Y' : 'N',
	);
}

switch ($arResult['STEP'])
{
	case 'choose':
		if (!empty($arResult['ID']))
		{
			if (!empty($arParams['SERVICES'][$arResult['SERVICE']]['link']))
				LocalRedirect($arParams['SERVICES'][$arResult['SERVICE']]['link'], true);
			else if (!empty($arResult['SETTINGS']['link']))
				LocalRedirect($arResult['SETTINGS']['link'], true);
			else
				LocalRedirect($APPLICATION->GetCurPage().'?STEP=setup');
		}
		break;
	case 'check':
		$APPLICATION->RestartBuffer();

		$error = false;
		$unseen = CMailUtil::CheckImapMailbox(
			$acc['SERVER'], $acc['PORT'], $acc['USE_TLS'],
			$acc['LOGIN'], $acc['PASSWORD'],
			$error, 30
		);

		CUserCounter::Set($USER->GetID(), 'mail_unseen', $unseen, SITE_ID);

		CUserOptions::SetOption('global', 'last_mail_check_'.SITE_ID, time());
		CUserOptions::SetOption('global', 'last_mail_check_success_'.SITE_ID, $unseen >= 0);

		$response = array(
			'result' => $error === false ? 'ok' : 'error',
			'unseen' => $unseen,
			'error'  => CharsetConverter::ConvertCharset($error, SITE_CHARSET, 'UTF-8')
		);

		header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
		echo json_encode($response);
		die;

		break;
	case 'setup':
		if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST['SERVICE']))
		{
			$arResult['ERRORS'] = array();

			$arResult['SETTINGS'] = $arParams['SERVICES'][$arResult['SERVICE']];
			foreach ($arParams['OPTIONS'] as $option)
			{
				if (empty($arResult['SETTINGS'][$option]))
				{
					unset($error);

					$arResult['SETTINGS'][$option] = isset($_REQUEST[$option]) ? $_REQUEST[$option] : null;
					$arResult['SETTINGS'][$option] = CheckOption($option, $arResult['SETTINGS'][$option], $error);

					if (!empty($error))
						$arResult['ERRORS'][] = $error;
				}
			}

			if (!check_bitrix_sessid())
				$arResult['ERRORS'][] = GetMessage('INTR_MAIL_CSRF');

			if (empty($arResult['ERRORS']))
			{
				$error = false;
				$unseen = CMailUtil::CheckImapMailbox(
					$arResult['SETTINGS']['server'], $arResult['SETTINGS']['port'], $arResult['SETTINGS']['encryption'],
					$arResult['SETTINGS']['login'], $arResult['SETTINGS']['password'],
					$error, 30
				);

				if ($error !== false)
					$arResult['ERRORS'][] = $error;
			}

			if (empty($arResult['ERRORS']))
			{
				$arFields = array(
					//ID int(18) not null auto_increment,
					//TIMESTAMP_X timestamp,
					'LID' => SITE_ID,
					'ACTIVE' => 'Y',
					'SERVICE_ID' => $arResult['SERVICE'],
					'NAME' => $arResult['SETTINGS']['name'],
					'SERVER' => $arResult['SETTINGS']['server'],
					'PORT' => $arResult['SETTINGS']['port'],
					'LINK' => $arResult['SETTINGS']['link'],
					'LOGIN' => $arResult['SETTINGS']['login'],
					//CHARSET varchar(255),
					'PASSWORD' => $arResult['SETTINGS']['password'],
					//DESCRIPTION text,
					//USE_MD5 char(1) not null default 'N',
					//DELETE_MESSAGES char(1) not null default 'N',
					//PERIOD_CHECK int(15),
					//MAX_MSG_COUNT int(11) default '0',
					//MAX_MSG_SIZE int(11) default '0',
					//MAX_KEEP_DAYS int(11) default '0',
					'USE_TLS' => $arResult['SETTINGS']['encryption'] == 'Y' ? 'Y' : 'N',
					'SERVER_TYPE' => 'imap',
					//DOMAINS varchar(255) null,
					//RELAY char(1) NOT NULL DEFAULT 'Y',
					//AUTH_RELAY char(1) NOT NULL DEFAULT 'Y',
					'USER_ID' => $USER->GetID()
				);

				if (!empty($arResult['ID']))
				{
					$res = CMailbox::Update($arResult['ID'], $arFields);
				}
				else
				{
					$arResult['ID'] = CMailbox::Add($arFields);
					$res = $arResult['ID'] > 0;
				}

				if (!$res)
				{
					$arResult['ERRORS'][] = GetMessage('INTR_MAIL_SAVE_ERROR');
				}
				else
				{
					CUserCounter::Set($USER->GetID(), 'mail_unseen', $unseen, SITE_ID);

					CUserOptions::SetOption('global', 'last_mail_check_'.SITE_ID, time());
					CUserOptions::SetOption('global', 'last_mail_check_success_'.SITE_ID, $unseen >= 0);

					LocalRedirect($APPLICATION->GetCurPage().'?STEP=confirm&ACT=setup');
				}
			}
		}
		break;
	case 'remove':
		if (check_bitrix_sessid())
		{
			CMailbox::Delete($arResult['ID']);

			CUserCounter::Clear($USER->GetID(), 'mail_unseen', SITE_ID);

			CUserOptions::DeleteOption('global', 'last_mail_check_'.SITE_ID);
			CUserOptions::DeleteOption('global', 'last_mail_check_success_'.SITE_ID);

			LocalRedirect($APPLICATION->GetCurPage().'?STEP=confirm&ACT=remove');
		}
		else
		{
			LocalRedirect($APPLICATION->GetCurPage().'?STEP=setup');
		}
		break;
	case 'confirm':
		$arResult['ACT'] = isset($_REQUEST['ACT']) ? $_REQUEST['ACT'] : '';
		break;
}

$this->IncludeComponentTemplate();
