<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

if (!CModule::IncludeModule('mail'))
{
	ShowError(GetMessage('MAIL_MODULE_NOT_INSTALLED'));
	return;
}

global $APPLICATION, $USER;
$CrmPerms = new CCrmPerms($USER->GetID());
if (!$CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

CUtil::InitJSCore();

$arParams['PATH_TO_SS_CONFIG'] = CrmCheckPath('PATH_TO_SS_CONFIG', $arParams['PATH_TO_SS_CONFIG'], $APPLICATION->GetCurPage());
$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);
$arResult['LEAD_SOURCE_LIST'] = CCrmStatus::GetStatusList('SOURCE');
$arResult['BACK_URL'] = $arParams['PATH_TO_SS_CONFIG'];
$arResult['FORM_ID'] = 'CRM_SS_CONFIG';
$arResult['FIELDS'] = array();

// Current send&save settings
$arSettings = array(
	'NAME'=> '',
	'SERVER_TYPE' => 'pop3',
	'MAILBOXES' => array()
);

$arRegexpData = array();

// Processing 'SAVE' and 'APPLY' commands -->
if($_SERVER['REQUEST_METHOD'] === 'POST'
	&& (isset($_POST['save']) || isset($_POST['apply']) || isset($_POST['delete']))
	&& check_bitrix_sessid())
{
	$errorMsg = '';

	// '-1' - not selected
	// '0' - new

	$mailBoxID = isset($_POST['MAILBOX_ID']) ? intval($_POST['MAILBOX_ID']) : -1;
	$mailFilterID = intval(COption::GetOptionString('crm', 'mail_filter', 0));
	$email = mb_strtolower(isset($_POST['POP3_EMAIL'])? $_POST['POP3_EMAIL'] : COption::GetOptionString('crm', 'mail', ''));

	$emailLocalPart = isset($_POST['SMTP_EMAIL']) ? $_POST['SMTP_EMAIL'] : '';
	if($emailLocalPart === '')
	{
		$atpos = mb_strpos($email, '@');
		$emailLocalPart = $atpos > 0? mb_substr($email, 0, $atpos) : $email;
	}

	$arMailBox = array();

	if($mailBoxID < 0)
	{
		$errorMsg = GetMessage('CRM_ERROR_MAILBOX_NOT_SELECTED');
	}
	else
	{
		if(isset($_POST['delete']) && $mailBoxID > 0)
		{
			$rsMailBox = CMailBox::GetById($mailBoxID);
			$arMailBox = $rsMailBox->Fetch();
			if (!is_array($arMailBox))
			{
				$errorMsg = GetMessage(
					'CRM_ERROR_MAIL_BOX_NOT_FOUND',
					array('#ID#' => $mailBoxID)
				);
			}
			else
			{
				$mailBoxEntity = new CMailBox(false);
				if(!$mailBoxEntity->Delete($mailBoxID))
				{
					$ex =  $GLOBALS['APPLICATION']->GetException();
					$errorMsg = $ex->GetString();
					$GLOBALS['APPLICATION']->ResetException();
				}
			}
		}
		elseif(isset($_POST['save']) || isset($_POST['apply']))
		{
			$arMailBoxData = array(
				'LID' => SITE_ID
			);

			$arMailBoxData['ACTIVE'] = $arSettings['ACTIVE'] = $_POST['ACTIVE'] == 'Y' ? 'Y' : 'N';
			$arMailBoxData['SERVER'] = $arSettings['SERVER'] = isset($_POST['SERVER']) ? trim($_POST['SERVER']) : '';
			$arMailBoxData['USE_TLS'] = $arSettings['USE_TLS'] = $_POST['SSL'] == 'Y' ? ($_POST['SKIP_CERT'] == 'Y' ? 'S' : 'Y') : 'N';
			$arMailBoxData['PORT'] = $arSettings['PORT'] = isset($_POST['PORT']) ? trim($_POST['PORT']) : ($arMailBoxData['USE_TLS'] == 'Y' || $arMailBoxData['USE_TLS'] == 'S' ? 995 : 110);
			$arMailBoxData['LOGIN'] = $arSettings['LOGIN'] =  isset($_POST['LOGIN']) ? $_POST['LOGIN'] : '';
			$arMailBoxData['PASSWORD'] = $arSettings['PASSWORD'] = isset($_POST['PASSWORD']) ? $_POST['PASSWORD'] : '';
			$arMailBoxData['PERIOD_CHECK'] = $arSettings['PERIOD_CHECK'] = isset($_POST['PERIOD_CHECK']) ? intval($_POST['PERIOD_CHECK']) : 5;
			if($arMailBoxData['PERIOD_CHECK'] < 0)
			{
				$arMailBoxData['PERIOD_CHECK'] = 0;
			}

			$arMailBoxData['DELETE_MESSAGES'] = $arSettings['DELETE_MESSAGES'] = $_POST['DELETE'] == 'Y' ? 'Y' : 'N';

			if (IsModuleInstalled('bitrix24'))
				$arMailBoxData['MAX_MSG_COUNT'] = 500;

			$arRegexpData['LEAD'] = isset($_POST['REGEXP_LEAD']) ? $_POST['REGEXP_LEAD'] : '\\[LID#([0-9]+)\\]';
			$arRegexpData['CONTACT'] = isset($_POST['REGEXP_CONTACT']) ? $_POST['REGEXP_CONTACT'] : '\\[CID#([0-9]+)\\]';
			$arRegexpData['COMPANY'] = isset($_POST['REGEXP_COMPANY']) ? $_POST['REGEXP_COMPANY'] : '\\[COID#([0-9]+)\\]';
			$arRegexpData['DEAL'] = isset($_POST['REGEXP_DEAL']) ? $_POST['REGEXP_DEAL'] : '\\[DID#([0-9]+)\\]';

			if($mailBoxID > 0) // Update of existing POP3 mail box
			{
				$rsMailBox = CMailBox::GetById($mailBoxID);
				$arMailBox = $rsMailBox->Fetch();
				if (!is_array($arMailBox))
				{
					$errorMsg = GetMessage(
						'CRM_ERROR_MAIL_BOX_NOT_FOUND',
						array('#ID#' => $mailBoxID)
					);
				}
				else
				{
					$arMailBox = array_merge($arMailBox, $arMailBoxData);
					if(isset($arMailBox['ID']))
					{
						unset($arMailBox['ID']);
					}

					// Check email settings
					if($arMailBox['SERVER_TYPE'] === 'pop3')
					{
						if($email === '')
						{
							$errorMsg = GetMessage('CRM_ERROR_POP3_MAIL_NOT_DEFINED');
						}
						else
						{
							// Override mail box name
							$arMailBox['NAME'] = $email;
							$mailBoxEntity = new CMailBox(false);
							if(!$mailBoxEntity->Update($mailBoxID, $arMailBox))
							{
								$ex =  $GLOBALS['APPLICATION']->GetException();
								$errorMsg = $ex->GetString();
								$GLOBALS['APPLICATION']->ResetException();
							}
						}
					}
					else
					{
						if($emailLocalPart === '')
						{
							$errorMsg = GetMessage('CRM_ERROR_SMTP_MAIL_NOT_DEFINED');
						}

						$domain = isset($_POST['SMTP_DOMAIN']) ? $_POST['SMTP_DOMAIN'] : '';

						if($domain === '')
						{
							$errorMsg = GetMessage('CRM_ERROR_SMTP_DOMAIN_NOT_DEFINED');
						}

						$email = $emailLocalPart.'@'.$domain;

						// Don't change SMTP mail box
					}
				}
			}
			else // Create new POP3 mail box
			{
				// Check email settings (only POP3 mail boxes is allowed)
				if($email === '')
				{
					$errorMsg = GetMessage('CRM_ERROR_POP3_MAIL_NOT_DEFINED');
				}
				else
				{
					// Override mail box name and server type
					$arMailBoxData['NAME'] = $email;
					$arMailBoxData['SERVER_TYPE'] = 'pop3';
					//$arMailBoxData['PERIOD_CHECK'] = 5;

					$mailBoxEntity = new CMailBox(false);
					$mailBoxID = intval($mailBoxEntity->Add($arMailBoxData));
					if($mailBoxID <= 0)
					{
						$ex = $GLOBALS['APPLICATION']->GetException();
						$errorMsg = $ex->GetString();
						$GLOBALS['APPLICATION']->ResetException();
					}
					else
					{
						$rsMailBox = CMailBox::GetById($mailBoxID);
						$arMailBox = $rsMailBox->Fetch();
					}
				}
			}

			if($mailBoxID > 0 && $errorMsg === '')
			{
				$arMailFilterData = array(
					'MAILBOX_ID' => $mailBoxID,
					'NAME' => GetMessage('CRM_SS_RULE'),
					'ACTION_TYPE' => 'crm',
					'ACTION_VARS' => 'W_CRM_ENTITY_REGEXP_LEAD='.urlencode($arRegexpData['LEAD']).
						'&W_CRM_ENTITY_REGEXP_COMPANY='.urlencode($arRegexpData['COMPANY']).
						'&W_CRM_ENTITY_REGEXP_CONTACT='.urlencode($arRegexpData['CONTACT']).
						'&W_CRM_ENTITY_REGEXP_DEAL='.urlencode($arRegexpData['DEAL']),
					'WHEN_MAIL_RECEIVED' => 'Y',
					'WHEN_MANUALLY_RUN' => 'Y',
					'REGEXP_LEAD' => $arRegexpData['LEAD'],
					'REGEXP_COMPANY' => $arRegexpData['COMPANY'],
					'REGEXP_CONTACT' => $arRegexpData['CONTACT'],
					'REGEXP_DEAL' => $arRegexpData['DEAL']
				);
				if (IsModuleInstalled('bitrix24'))
				{
					$arMailFilterData['ACTION_DELETE_MESSAGE'] = 'Y';
					$arMailFilterData['ACTION_STOP_EXEC'] = 'Y';
				}

				$arMailFilter = array();
				if($mailFilterID <= 0)
				{
					$arMailFilter = $arMailFilterData;
				}
				else
				{
					$rsMailFilter = CMailFilter::GetById($mailFilterID);
					$arMailFilter = $rsMailFilter->Fetch();
					if (!is_array($arMailFilter))
					{
						$mailFilterID = 0;
						$arMailFilter = array();
					}
					else
					{
						$arMailFilter = array_merge($arMailFilter, $arMailFilterData);
					}
				}

				if(isset($arMailFilter['ID']))
				{
					unset($arMailFilter['ID']);
				}

				if($mailFilterID <= 0)
				{
					$mailFilterID = intval(CMailFilter::Add($arMailFilter));
					if ($mailFilterID <= 0)
					{
						$ex = $GLOBALS['APPLICATION']->GetException();
						$errorMsg = $ex->GetString();
						$GLOBALS['APPLICATION']->ResetException();
					}
				}
				else
				{
					if(!CMailFilter::Update($mailFilterID, $arMailFilter))
					{
						$ex = $GLOBALS['APPLICATION']->GetException();
						$errorMsg = $ex->GetString();
						$GLOBALS['APPLICATION']->ResetException();
					}
				}

				if($errorMsg === '' && $mailFilterID > 0)
				{
					// Deleting other crm related filters (only one active crm filter is allowed)
					$rsMailFilter = CMailFilter::GetList(array(), array('ACTION_TYPE'=>'crm'));
					while($arMailFilter = $rsMailFilter->Fetch())
					{
						$curMailFilterID = intval($arMailFilter['ID']);
						if($curMailFilterID !== $mailFilterID)
						{
							CMailFilter::Delete($curMailFilterID);
						}
					}
				}
			}
		}

	}

	$arSettings['EMAIL'] = $email;
	$arSettings['EMAIL_LOCAL_PART'] = $emailLocalPart;
	$arSettings['MAILBOX_ID'] = $mailBoxID;

	if ($errorMsg <> '')
	{
		ShowError($errorMsg);
	}
	else
	{
		COption::SetOptionString('crm', 'mail_box', $mailBoxID);
		COption::SetOptionString('crm', 'mail_filter', $mailFilterID);
		COption::SetOptionString('crm', 'mail', mb_strtolower($email));

		$contactResponsibleID = isset($_POST['CONTACT_RESPONSIBLE_ID']) ? intval($_POST['CONTACT_RESPONSIBLE_ID']) : 0;
		COption::SetOptionString('crm', 'email_contact_responsible_id', $contactResponsibleID);

		$createLead = isset($_POST['CREATE_LEAD_FOR_NEW_ADDRESSER'])? mb_strtoupper($_POST['CREATE_LEAD_FOR_NEW_ADDRESSER']) : 'N';
		if($createLead !== 'Y' && $createLead !== 'N')
		{
			$createLead = 'N';
		}
		COption::SetOptionString('crm', 'email_create_lead_for_new_addresser', $createLead);

		$leadResponsibleID = isset($_POST['LEAD_RESPONSIBLE_ID']) ? intval($_POST['LEAD_RESPONSIBLE_ID']) : 0;
		COption::SetOptionString('crm', 'email_lead_responsible_id', $leadResponsibleID);

		$leadSourceID = isset($_POST['LEAD_SOURCE_ID']) ? $_POST['LEAD_SOURCE_ID'] : '';
		COption::SetOptionString('crm', 'email_lead_source_id', $leadSourceID);

		CCrmEMailCodeAllocation::SetCurrent(
			isset($_POST['SERVICE_CODE_ALLOCATION'])
				? intval($_POST['SERVICE_CODE_ALLOCATION'])
				: CCrmEMailCodeAllocation::Body
		);

		LocalRedirect(
			CComponentEngine::MakePathFromTemplate(
				$arParams['PATH_TO_SS_CONFIG'],	array()
			)
		);
	}
}
// <-- Processing 'SAVE' and 'APPLY' commands

// Settings initialization -->
if(!isset($arSettings['EMAIL']))
{
	$arSettings['EMAIL'] = mb_strtolower(COption::GetOptionString('crm', 'mail', ''));
	if($arSettings['EMAIL'] !== '')
	{
		$atpos = mb_strpos($arSettings['EMAIL'], '@');
		if ($atpos > 0)
		{
			$arSettings['EMAIL_LOCAL_PART'] = mb_substr($arSettings['EMAIL'], 0, $atpos);
		}
	}
}

if(!isset($arSettings['MAILBOX_ID']))
{
	$arSettings['MAILBOX_ID'] = intval(COption::GetOptionString('crm', 'mail_box', 0));
}

if ($arSettings['MAILBOX_ID'] <= 0)
{
	COption::SetOptionString('crm', 'mail_filter', 0);
	COption::SetOptionString('crm', 'mail', '');

	if (isModuleInstalled('bitrix24'))
	{
		localRedirect('/crm/configs/');
	}
}
else
{
	$rsMailbox = CMailBox::GetById($arSettings['MAILBOX_ID']);
	$arCurrentMailBox = $rsMailbox->Fetch();
	if (!is_array($arCurrentMailBox))
	{
		// Reset settings and reload page
		COption::SetOptionString('crm', 'mail_box', 0);
		COption::SetOptionString('crm', 'mail_filter', 0);
		COption::SetOptionString('crm', 'mail', '');

		LocalRedirect(
			CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_SS_CONFIG'],	array())
		);
	}

	if(!isset($arSettings['ACTIVE']))
	{
		$arSettings['ACTIVE'] = isset($arCurrentMailBox['ACTIVE']) ? $arCurrentMailBox['ACTIVE'] : 'Y';
	}
	if(isset($arCurrentMailBox['NAME']))
	{
		$arSettings['NAME'] = $arCurrentMailBox['NAME'];
	}
	if(!isset($arSettings['SERVER']))
	{
		$arSettings['SERVER'] = isset($arCurrentMailBox['SERVER']) ? $arCurrentMailBox['SERVER'] : '';
	}
	if(!isset($arSettings['USE_TLS']))
	{
		$arSettings['USE_TLS'] = isset($arCurrentMailBox['USE_TLS']) ? $arCurrentMailBox['USE_TLS'] : 'Y';
	}
	if(!isset($arSettings['PORT']))
	{
		$arSettings['PORT'] = isset($arCurrentMailBox['PORT']) ? $arCurrentMailBox['PORT'] : ($arSettings['USE_TLS'] == 'Y' || $arSettings['USE_TLS'] == 'S' ? 995 : 110);
	}
	if(isset($arCurrentMailBox['SERVER_TYPE']))
	{
		$arSettings['SERVER_TYPE'] = $arCurrentMailBox['SERVER_TYPE'];
	}
	if(!isset($arSettings['LOGIN']))
	{
		$arSettings['LOGIN'] = isset($arCurrentMailBox['LOGIN']) ? $arCurrentMailBox['LOGIN'] : '';
	}
	if(!isset($arSettings['PASSWORD']))
	{
		$arSettings['PASSWORD'] = isset($arCurrentMailBox['PASSWORD']) ? $arCurrentMailBox['PASSWORD'] : '';
	}
	if(!isset($arSettings['PERIOD_CHECK']))
	{
		$arSettings['PERIOD_CHECK'] = isset($arCurrentMailBox['PERIOD_CHECK']) ? $arCurrentMailBox['PERIOD_CHECK'] : '5';
	}
	if(!isset($arSettings['DELETE_MESSAGES']))
	{
		$arSettings['DELETE_MESSAGES'] = isset($arCurrentMailBox['DELETE_MESSAGES']) ? $arCurrentMailBox['DELETE_MESSAGES'] : 'N';
	}
	if($arSettings['EMAIL'] === '' && $arSettings['SERVER_TYPE'] === 'smtp')
	{
		$arSettings['EMAIL'] = 'crm@'.$_SERVER['HTTP_HOST'];
	}
}

$arSettings['CONTACT_RESPONSIBLE_ID'] = intval(COption::GetOptionString('crm', 'email_contact_responsible_id', 0));
$arSettings['CONTACT_RESPONSIBLE_NAME'] = $arSettings['CONTACT_RESPONSIBLE_ID'] > 0
	? CCrmViewHelper::GetFormattedUserName($arSettings['CONTACT_RESPONSIBLE_ID']) : '';

$arSettings['CREATE_LEAD_FOR_NEW_ADDRESSER'] = mb_strtoupper(COption::GetOptionString('crm', 'email_create_lead_for_new_addresser', 'Y'));
$arSettings['LEAD_RESPONSIBLE_ID'] = intval(COption::GetOptionString('crm', 'email_lead_responsible_id', 0));
$arSettings['LEAD_RESPONSIBLE_NAME'] = $arSettings['LEAD_RESPONSIBLE_ID'] > 0
	? CCrmViewHelper::GetFormattedUserName($arSettings['LEAD_RESPONSIBLE_ID']) : '';
$arSettings['LEAD_SOURCE_ID'] = COption::GetOptionString('crm', 'email_lead_source_id', '');
if($arSettings['LEAD_SOURCE_ID'] === '' || !isset($arResult['LEAD_SOURCE_LIST'][$arSettings['LEAD_SOURCE_ID']]))
{
	if(isset($arResult['LEAD_SOURCE_LIST']['EMAIL']))
	{
		$arSettings['LEAD_SOURCE_ID'] = 'EMAIL';
	}
	elseif(isset($arResult['LEAD_SOURCE_LIST']['OTHER']))
	{
		$arSettings['LEAD_SOURCE_ID'] = 'OTHER';
	}
}

// <-- Settings initialization

// Test POP3 connection
if($arSettings['SERVER_TYPE'] === 'pop3' && isset($arSettings['SERVER']) && $arSettings['SERVER'] !== '' && isset($arSettings['LOGIN']) && $arSettings['LOGIN'] !== '')
{
	$mailBoxEntity = new CMailBox(false);
	$arCheck = $mailBoxEntity->Check(
		$arSettings['SERVER'],
		$arSettings['PORT'],
		$arSettings['USE_TLS'],
		$arSettings['LOGIN'],
		$arSettings['PASSWORD']
	);

	if(!$arCheck[0])
	{
		ShowError(
			GetMessage(
				'CRM_ERROR_CHECK_SERVER_CONNECTION',
				array(
					'#SERVER#' => $arSettings['SERVER'],
					'#ERROR#' => $arCheck[1]
				)
			)
		);
	}
}

// Preparing mail box list
$arResult['MAILBOXES_LIST'] =
	array(
		'-1' => GetMessage('CRM_EMAIL_MAILBOX_SEL')
	);

// a. Selection of smtp servers
$rsMailFilter = CMailFilter::GetList(array(), array('SERVER_TYPE' => 'smtp', 'EMPTY' => 'Y' ));
while($arMailF = $rsMailFilter->Fetch())
{
	$arResult['MAILBOXES_LIST'][$arMailF['MAILBOX_ID']] = $arMailF['MAILBOX_NAME'];
	if($arMailF['MAILBOX_TYPE'] == 'smtp' && count($arMailF['DOMAINS']) > 0)
	{
		$mailBoxID = $arMailF['MAILBOX_ID'];
		$rsMailbox = CMailBox::GetById($mailBoxID);
		$arMailbox = $rsMailbox->Fetch();
		if(!is_array($arMailbox))
		{
			continue;
		}

		$arSettings['MAILBOXES'][$mailBoxID] = array(
			'ID' => $mailBoxID,
			'SERVER_TYPE' => 'smtp',
			'SMTP_DOMAIN' => preg_split("/[\r\n]+/", $arMailF['DOMAINS'], -1, PREG_SPLIT_NO_EMPTY),
			'SMTP_EMAIL' => $mailBoxID === $arSettings['MAILBOX_ID'] ? $arSettings['EMAIL_LOCAL_PART'] : ''
		);
	}
}

// b. Selection of pop3 servers
$pop3MailBoxExist = false;
//$rsMailFilter = CMailFilter::GetList(array(), array('SERVER_TYPE' => 'pop3'));
//while($arMailF = $rsMailFilter->Fetch())
$rsMailBoxes = CMailBox::GetList(array(), array('SERVER_TYPE' => 'pop3'));
while($arMailBox = $rsMailBoxes->Fetch())
{
	//$mailBoxID = intval($arMailF['MAILBOX_ID']);
	//$rsMailbox = CMailBox::GetById($mailBoxID);
	//$arMailbox = $rsMailbox->Fetch();
	$mailBoxID = intval($arMailBox['ID']);
	if(!is_array($arMailBox))
	{
		continue;
	}

	$arMailBoxSettings = array(
		'ID' => $mailBoxID,
		'SERVER_TYPE' => 'pop3',
		'ACTIVE' => $arMailBox['ACTIVE'],
		'SERVER' => $arMailBox['SERVER'],
		'PORT' => $arMailBox['PORT'],
		'LOGIN' => $arMailBox['LOGIN'],
		'PASSWORD' => $arMailBox['PASSWORD'],
		'SSL' => $arMailBox['USE_TLS'] == 'Y' || $arMailBox['USE_TLS'] == 'S' ? 'Y' : 'N',
		'SKIP_CERT' => $arMailBox['USE_TLS'] == 'S' ? 'Y' : 'N',
		'DELETE' => $arMailBox['DELETE_MESSAGES'] === 'Y',
		'PERIOD_CHECK' => $arMailBox['PERIOD_CHECK'],
		'POP3_EMAIL' => ''
	);

	if(isset($arSettings['MAILBOX_ID']) &&  $arSettings['MAILBOX_ID'] === $mailBoxID)
	{
		if(isset($arSettings['ACTIVE']))
		{
			$arMailBoxSettings['ACTIVE'] = $arSettings['ACTIVE'];
		}
		if(isset($arSettings['SERVER']))
		{
			$arMailBoxSettings['SERVER'] = $arSettings['SERVER'];
		}
		if(isset($arSettings['PORT']))
		{
			$arMailBoxSettings['PORT'] = $arSettings['PORT'];
		}
		if(isset($arSettings['LOGIN']))
		{
			$arMailBoxSettings['LOGIN'] = $arSettings['LOGIN'];
		}
		if(isset($arSettings['PASSWORD']))
		{
			$arMailBoxSettings['PASSWORD'] = $arSettings['PASSWORD'];
		}
		if(isset($arSettings['USE_TLS']))
		{
			$arMailBoxSettings['SSL'] = $arSettings['USE_TLS'] == 'Y' || $arSettings['USE_TLS'] == 'S' ? 'Y' : 'N';
			$arMailBoxSettings['SKIP_CERT'] = $arSettings['USE_TLS'] == 'S' ? 'Y' : 'N';
		}
		if(isset($arSettings['PERIOD_CHECK']))
		{
			$arMailBoxSettings['PERIOD_CHECK'] = $arSettings['PERIOD_CHECK'];
		}
		if(isset($arSettings['DELETE_MESSAGES']))
		{
			$arMailBoxSettings['DELETE'] = $arSettings['DELETE_MESSAGES'];
		}
		if(isset($arSettings['EMAIL']))
		{
			$arMailBoxSettings['POP3_EMAIL'] = $arSettings['EMAIL'];
		}
	}

	$arSettings['MAILBOXES'][$mailBoxID] = &$arMailBoxSettings;
	unset($arMailBoxSettings);

	//$arResult['MAILBOXES_LIST'][$mailBoxID] = $arMailF['MAILBOX_NAME'];
	$arResult['MAILBOXES_LIST'][$mailBoxID] = $arMailBox['NAME'];

	if(!$pop3MailBoxExist)
	{
		$pop3MailBoxExist = true;
	}
}

// Putting user filter settings in associated mail box
if(isset($arSettings['MAILBOXES'][$arSettings['MAILBOX_ID']]))
{
	$arCurrentMailBox = &$arSettings['MAILBOXES'][$arSettings['MAILBOX_ID']];

	if(isset($arRegexpData['LEAD']))
	{
		$arCurrentMailBox['REGEXP_LEAD'] = $arSettings['REGEXP_LEAD'] = $arRegexpData['LEAD'];
	}

	if(isset($arRegexpData['COMPANY']))
	{
		$arCurrentMailBox['REGEXP_COMPANY'] = $arSettings['REGEXP_COMPANY'] = $arRegexpData['COMPANY'];
	}

	if(isset($arRegexpData['CONTACT']))
	{
		$arCurrentMailBox['REGEXP_CONTACT'] = $arSettings['REGEXP_CONTACT'] = $arRegexpData['CONTACT'];
	}

	if(isset($arRegexpData['DEAL']))
	{
		$arCurrentMailBox['REGEXP_DEAL'] = $arSettings['REGEXP_DEAL'] = $arRegexpData['DEAL'];
	}
}

// Allow creation of new pop3 mail box only if it is not found
if(!$pop3MailBoxExist)
{
	if(count($arResult['MAILBOXES_LIST']) == 1)
	{
		// Remove 'Select mail box' item if there are no mail boxes
		unset($arResult['MAILBOXES_LIST']['-1']);
	}

	$arResult['MAILBOXES_LIST']['0'] = GetMessage('CRM_EMAIL_MAILBOX_NEW');
}

// Mail filter ID
$mailFilterID = intval(COption::GetOptionString('crm', 'mail_filter', 0));
if ($mailFilterID > 0)
{
	$rsMailFilter = CMailFilter::GetById($mailFilterID);
	$arMailFilter = $rsMailFilter->Fetch();
	if (is_array($arMailFilter))
	{
		$mailBoxID = $arMailFilter['MAILBOX_ID'];

		// Putting saved filter settings in associated mail box
		if(isset($arSettings['MAILBOXES'][$mailBoxID]))
		{
			$mailBox = &$arSettings['MAILBOXES'][$mailBoxID];
			$arActions = explode('&', $arMailFilter['ACTION_VARS']);
			for($i = count($arActions) - 1; $i >= 0; $i--)
			{
				$arExp = explode('=', $arActions[$i]);
				if(!isset($arExp[1]))
				{
					continue;
				}

				$expName = '';

				if($i == 0)
				{
					$expName = 'REGEXP_LEAD';
				}
				elseif($i == 1)
				{
					$expName = 'REGEXP_COMPANY';
				}
				elseif($i == 2)
				{
					$expName = 'REGEXP_CONTACT';
				}
				elseif($i == 3)
				{
					$expName = 'REGEXP_DEAL';
				}

				if($expName != '' && !isset($mailBox[$expName]))
				{
					$mailBox[$expName] = urldecode($arExp[1]);
				}
			}
		}

	}
}

$arResult['SETTINGS'] = $arSettings;

// Construction of form fields -->
$arResult['FIELDS']['tab_config'][] = array(
	'id' => 'section_mail_info',
	'name' => GetMessage('CRM_SECTION_MAIL_INFO'),
	'type' => 'section'
);

// MAILBOX_ID -->
if(!IsModuleInstalled('bitrix24'))
{
	$arResult['FIELDS']['tab_config'][] = array(
		'id' => 'MAILBOX',
		'name' => GetMessage('CRM_FIELD_MAILBOX'),
		'items' => $arResult['MAILBOXES_LIST'],
		'type' => 'list',
		'value' => $arSettings['MAILBOX_ID'],
		'required' => true
	);
}
// <-- MAILBOX_ID

// NAME -->
//$arResult['FIELDS']['tab_config'][] = array(
//	'id' => 'NAME',
//	'name' => GetMessage('CRM_FIELD_MAILBOX_NAME'),
//	'params' => array('size' => 50),
//	'type' => 'text',
//	'value' => isset($arMailboxFields['NAME']) ? ($bVarsFromForm ? htmlspecialcharsbx($arMailboxFields['NAME']) : '') : '',
//	'required' => true
//);
// <-- NAME

// POP3_EMAIL -->
$arResult['FIELDS']['tab_config'][] = array(
	'id' => 'POP3_EMAIL',
	'name' => GetMessage('CRM_FIELD_POP3_EMAL'),
	'params' => array('size' => 50),
	'type' => 'text',
	'value' => $arSettings['EMAIL'],
	'required' => true
);
// <-- POP3_EMAIL

// SMTP_EMAIL -->
$arResult['FIELDS']['tab_config'][] = array(
	'id' => 'SMTP_EMAIL',
	'name' => GetMessage('CRM_FIELD_SMTP_EMAL'),
	'params' => array('size' => 50),
	'type' => 'custom',
	'value' => '<input name="SMTP_EMAIL" value="'.htmlspecialcharsbx($arSettings['EMAIL_LOCAL_PART']).'"/>@<select name="SMTP_DOMAIN"></select>',
	'required' => true
);
// <-- SMTP_EMAIL

// ACTIVE -->
$arResult['FIELDS']['tab_config'][] = array(
	'id' => 'ACTIVE',
	'name' => GetMessage('CRM_FIELD_MAILBOX_ACTIVE'),
	'type' => 'checkbox',
	'params' => array(),
	'value' => $arSettings['ACTIVE']
);
// <-- ACTIVE

// SERVER -->
$arResult['FIELDS']['tab_config'][] = array(
	'id' => 'SERVER',
	'name' => GetMessage('CRM_FIELD_POP3_SERVER'),
	'params' => array('size' => 50),
	'type' => 'text',
	'value' => $arSettings['SERVER'],
	'required' => true
);
// <-- SERVER

// PORT -->
$arResult['FIELDS']['tab_config'][] = array(
	'id' => 'PORT',
	'name' => GetMessage('CRM_FIELD_MAILBOX_PORT'),
	'params' => array('size' => 5),
	'type' => 'text',
	'value' => $arSettings['PORT'],
	'required' => true
);
// <-- PORT

// USE SSL -->
$arResult['FIELDS']['tab_config'][] = array(
	'id' => 'SSL',
	'name' => GetMessage('CRM_FIELD_USE_SSL2'),
	'type' => 'checkbox',
	'params' => array(),
	'value' => $arSettings['USE_TLS'] == 'Y' || $arSettings['USE_TLS'] == 'S' ? 'Y' : 'N'
);
// <-- USE SSL

$arResult['FIELDS']['tab_config'][] = array(
	'id' => 'SKIP_CERT',
	'name' => GetMessage('CRM_FIELD_SKIP_CERT'),
	'type' => 'checkbox',
	'params' => array(),
	'value' => $arSettings['USE_TLS'] == 'S' ? 'Y' : 'N'
);

// LOGIN -->
$arResult['FIELDS']['tab_config'][] = array(
	'id' => 'LOGIN',
	'name' => GetMessage('CRM_FIELD_MAILBOX_LOGIN'),
	'params' => array('size' => 50),
	'type' => 'text',
	'value' => $arSettings['LOGIN'],
	'required' => true
);
// <-- LOGIN

// PASSWORD -->
$arResult['FIELDS']['tab_config'][] = array(
	'id' => 'PASSWORD',
	'name' => GetMessage('CRM_FIELD_MAILBOX_PASSWORD'),
	'params' => array('size' => 50),
	'type' => 'custom',
	'value' => '<input autocomplete="off" type="password" name="PASSWORD" value="'.htmlspecialcharsbx($arSettings['PASSWORD']).'"/>',
	'required' => true
);
// <-- PASSWORD

// PERIOD CHECK -->
$arResult['FIELDS']['tab_config'][] = array(
	'id' => 'PERIOD_CHECK',
	'name' => GetMessage('CRM_FIELD_MAILBOX_PERIOD_CHECK'),
	'params' => array('size' => 5),
	'type' => 'text',
	'value' => $arSettings['PERIOD_CHECK']
);
// <-- PERIOD CHECK

// DELETE_MESSAGES -->
$arResult['FIELDS']['tab_config'][] = array(
	'id' => 'DELETE',
	'name' => GetMessage('CRM_FIELD_MAILBOX_DELETE'),
	'type' => 'checkbox',
	'params' => array(
		'onmouseover'=>'BX.hint(this, \''.CUtil::JSEscape(GetMessage('CRM_FIELD_MAILBOX_DELETE_HINT')).'\');',
		'onchange' => 'if(!this.checked) return; if(!confirm(\''.CUtil::JSEscape(GetMessage('CRM_FIELD_MAILBOX_DELETE_CONFIRM')).'\')) this.checked = false;'
	),
	'value' => $arSettings['DELETE_MESSAGES']
);
// <-- DELETE_MESSAGES

$arResult['FIELDS']['tab_config'][] = array(
	'id' => 'section_mail_processing', //section_incoming_mail_processing
	'name' => GetMessage('CRM_SECTION_MAIL_PROCESSING'),
	'type' => 'section'
);

// CONTACT_RESPONSIBLE_ID -->
ob_start();
CCrmViewHelper::RenderUserCustomSearch(
	array(
		'ID' => 'CONTACT_RESPONSIBLE',
		'SEARCH_INPUT_ID' => 'CONTACT_RESPONSIBLE_SEARCH',
		'SEARCH_INPUT_HINT' => GetMessage('CRM_FIELD_CONTACT_RESPONSIBLE_HINT'),
		'DATA_INPUT_ID' => 'CONTACT_RESPONSIBLE_ID',
		'COMPONENT_NAME' => 'CONTACT_RESPONSIBLE',
		'NAME_FORMAT' => $arParams['NAME_TEMPLATE'],
		'USER' => array(
			'ID' => $arSettings['CONTACT_RESPONSIBLE_ID'],
			'NAME' => $arSettings['CONTACT_RESPONSIBLE_NAME']
		)
	)
);
$userSelectorHtml = ob_get_contents();
ob_end_clean();

$arResult['FIELDS']['tab_config'][] = array(
	'id' => 'CONTACT_RESPONSIBLE_ID',
	'name' => GetMessage('CRM_FIELD_CONTACT_RESPONSIBLE'),
	'type' => 'custom',
	'value' => $userSelectorHtml
);
// <-- CONTACT_RESPONSIBLE_ID

// CREATE_LEAD_FOR_NEW_ADDRESSER -->
$arResult['FIELDS']['tab_config'][] = array(
	'id' => 'CREATE_LEAD_FOR_NEW_ADDRESSER',
	'name' => GetMessage('CRM_FIELD_CREATE_LEAD_FOR_NEW_ADDRESSER'),
	'type' => 'checkbox',
	'params' => array('onmouseover'=>'BX.hint(this, \''.CUtil::JSEscape(GetMessage('CRM_FIELD_CREATE_LEAD_FOR_NEW_ADDRESSER_HINT')).'\');'),
	'value' => $arSettings['CREATE_LEAD_FOR_NEW_ADDRESSER']
);
// <-- CREATE_LEAD_FOR_NEW_ADDRESSER

// LEAD_RESPONSIBLE_ID -->
ob_start();
CCrmViewHelper::RenderUserCustomSearch(
	array(
		'ID' => 'LEAD_RESPONSIBLE',
		'SEARCH_INPUT_ID' => 'LEAD_RESPONSIBLE_SEARCH',
		'SEARCH_INPUT_HINT' => GetMessage('CRM_FIELD_LEAD_RESPONSIBLE_HINT'),
		'DATA_INPUT_ID' => 'LEAD_RESPONSIBLE_ID',
		'COMPONENT_NAME' => 'LEAD_RESPONSIBLE',
		'NAME_FORMAT' => $arParams['NAME_TEMPLATE'],
		'USER' => array(
			'ID' => $arSettings['LEAD_RESPONSIBLE_ID'],
			'NAME' => $arSettings['LEAD_RESPONSIBLE_NAME']
		)
	)
);
$userSelectorHtml = ob_get_contents();
ob_end_clean();

$arResult['FIELDS']['tab_config'][] = array(
	'id' => 'LEAD_RESPONSIBLE_ID',
	'name' => GetMessage('CRM_FIELD_LEAD_RESPONSIBLE'),
	'type' => 'custom',
	'value' => $userSelectorHtml
);
// <-- LEAD_RESPONSIBLE_ID

// LEAD_SOURCE_ID -->

$arResult['FIELDS']['tab_config'][] = array(
	'id' => 'LEAD_SOURCE_ID',
	'name' => GetMessage('CRM_FIELD_LEAD_SOURCE_ID'),
	'type' => 'list',
	'items' => $arResult['LEAD_SOURCE_LIST'],
	'value' => $arSettings['LEAD_SOURCE_ID']
);

//<-- LEAD_SOURCE_ID

$arResult['FIELDS']['tab_config'][] = array(
	'id' => 'section_outgoing_mail_processing',
	'name' => GetMessage('CRM_SECTION_OUTGOING_MAIL_PROCESSING'),
	'type' => 'section'
);

// SERVICE_CODE_ALLOCATION -->
$arResult['FIELDS']['tab_config'][] = array(
	'id' => 'SERVICE_CODE_ALLOCATION',
	'name' => GetMessage('CRM_FIELD_SERVICE_CODE_ALLOCATION'),
	'items' => CCrmEMailCodeAllocation::GetAllDescriptions(),
	'type' => 'list',
	'value' => CCrmEMailCodeAllocation::GetCurrent(),
	'required' => false
);
// <-- SERVICE_CODE_ALLOCATION

$arResult['FIELDS']['tab_config'][] = array(
	'id' => 'section_mail_config',
	'name' => GetMessage('CRM_SECTION_MAIL_RULES'),
	'type' => 'section'
);
//$arResult['FIELDS']['tab_config'][] = array(
//	'id' => 'section_mail_config2',
//	'name' => GetMessage('CRM_SECTION_MAIL_CONFIG2'),
//	'type' => 'section'
//);


// LEAD REGEX-->
$arResult['FIELDS']['tab_config'][] = array(
	'id' => 'REGEXP_LEAD',
	'name' => GetMessage('CRM_FIELD_REGEXP_LEAD'),
	'params' => array('size' => 50),
	'type' => 'text',
	'value' => isset($arSettings['REGEXP_LEAD']) ? $arSettings['REGEXP_LEAD'] : ''

);
// <-- LEAD REGEX

// CONTACT REGEX -->
$arResult['FIELDS']['tab_config'][] = array(
	'id' => 'REGEXP_CONTACT',
	'name' => GetMessage('CRM_FIELD_REGEXP_CONTACT'),
	'params' => array('size' => 50),
	'type' => 'text',
	'value' => isset($arSettings['REGEXP_CONTACT']) ? $arSettings['REGEXP_CONTACT'] : ''
);
// <-- CONTACT REGEX

// COMPANY REGEX -->
$arResult['FIELDS']['tab_config'][] = array(
	'id' => 'REGEXP_COMPANY',
	'name' => GetMessage('CRM_FIELD_REGEXP_COMPANY'),
	'params' => array('size' => 50),
	'type' => 'text',
	'value' => isset($arSettings['REGEXP_COMPANY']) ? $arSettings['REGEXP_COMPANY'] : ''
);
// <-- COMPANY REGEX

// DEAL REGEX -->
$arResult['FIELDS']['tab_config'][] = array(
	'id' => 'REGEXP_DEAL',
	'name' => GetMessage('CRM_FIELD_REGEXP_DEAL'),
	'params' => array('size' => 50),
	'type' => 'text',
	'value' => isset($arSettings['REGEXP_DEAL']) ? $arSettings['REGEXP_DEAL'] : ''
);
// <-- DEAL REGEX
// <-- Construction of form fields
$arResult['ENABLE_CONTROL_PANEL'] = isset($arParams['ENABLE_CONTROL_PANEL']) ? $arParams['ENABLE_CONTROL_PANEL'] : true;
$this->IncludeComponentTemplate();

$APPLICATION->AddChainItem(GetMessage('CRM_SS_LIST'), $arParams['PATH_TO_SS_CONFIG']);

?>
