<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Recurring\Calculator;

Loc::loadMessages(__FILE__);

$data = $arParams['DATA'];
$messagePeriod = "";

switch ($data['PERIOD'])
{
	case 1:
		$type = ($data['DAILY_WORKDAY_ONLY'] == "Y") ? "CRM_RECURRING_HINT_ELEMENT_DAY_MASK_WORK" : "CRM_RECURRING_HINT_ELEMENT_DAY_MASK";
		$messagePeriod = Loc::getMessage(
			$type,
			array(
				"#DAY_NUMBER#" => (int)$data['DAILY_INTERVAL_DAY'] > 1 ? (int)$data['DAILY_INTERVAL_DAY']." " : "",
			)
		);
		break;
	case 2:
		$weekPeriod = (int)$data['WEEKLY_INTERVAL_WEEK'] > 1 ? (int)$data['WEEKLY_INTERVAL_WEEK']." " : "";
		if (sizeof($data['WEEKLY_WEEK_DAYS']) >= 7)
		{
			$weekDayList = Loc::getMessage("CRM_RECURRING_HINT_WEEKDAY_EVERY_DAY");
		}
		else
		{
			$weekDayList = "";
			if (is_array($data['WEEKLY_WEEK_DAYS']))
			{
				$end = end($data['WEEKLY_WEEK_DAYS']);
				foreach ($data['WEEKLY_WEEK_DAYS'] as $day)
				{
					$weekDayList .= Loc::getMessage("CRM_RECURRING_HINT_WEEKDAY_WD_".$day);
					if (isset($end) && $day !== $end)
					{
						$weekDayList .= ", ";
					}
				}
			}
			else
			{
				$weekDayList = Loc::getMessage("CRM_RECURRING_HINT_WEEKDAY_WD_1");
			}
		}

		$messagePeriod = Loc::getMessage(
			"CRM_RECURRING_HINT_ELEMENT_WEEKDAY_MASK",
			array(
				"#WEEK_NUMBER#" => $weekPeriod,
				"#LIST_WEEKDAY_NAMES#" => $weekDayList
			)
		);
		break;
	case 3:
		if ($data['MONTHLY_TYPE'] == 1)
		{
			$type = ($data['MONTHLY_WORKDAY_ONLY'] !== "Y") ? "CRM_RECURRING_HINT_ELEMENT_MONTH_MASK_1" : "CRM_RECURRING_HINT_ELEMENT_MONTH_MASK_1_WORK";
			$messagePeriod = Loc::getMessage(
				$type,
				array(
					"#DAY_NUMBER#" => (int)$data['MONTHLY_INTERVAL_DAY'] > 1 ? (int)$data['MONTHLY_INTERVAL_DAY'] : 1,
					"#MONTH_NUMBER#" => (int)$data['MONTHLY_MONTH_NUM_1'] > 1 ? (int)$data['MONTHLY_MONTH_NUM_1']." " : ''
				)
			);
		}
		else
		{
			$gender = "";
			if (LANGUAGE_ID == "ru" || LANGUAGE_ID == "ua")
			{
				if (in_array($data['MONTHLY_WEEK_DAY'], array(1,2,4)))
				{
					$gender = "_M";
				}
				elseif (in_array($data['MONTHLY_WEEK_DAY'], array(3,5,6)))
				{
					$gender = "_F";
				}
			}

			$weekDayNumber =  Loc::getMessage("CRM_RECURRING_HINT_WEEKDAY_NUMBER_".$data['MONTHLY_WEEKDAY_NUM'].$gender);
			$weekDayName =  Loc::getMessage("CRM_RECURRING_HINT_WEEKDAY_WD_".$data['MONTHLY_WEEK_DAY'].($gender == '_F' ? '_ALT' : ''));
			$each = Loc::getMessage('CRM_RECURRING_HINT_EACH'.$gender);
			$messagePeriod = Loc::getMessage($code);

			$messagePeriod = Loc::getMessage(
				"CRM_RECURRING_HINT_MONTHLY_EXT_TYPE_2",
				array(
					"#EACH#" => $each,
					"#WEEKDAY_NUMBER#" => $weekDayNumber,
					"#WEEKDAY_NAME#" => $weekDayName,
					"#MONTH_NUMBER#" => (int)$data['MONTHLY_MONTH_NUM_2'] > 1 ? (int)$data['MONTHLY_MONTH_NUM_2']." " : ''
				)
			);
		}
		break;
	case 4:
		if ($data['YEARLY_TYPE'] == 1)
		{
			$monthDayType = $data['YEARLY_WORKDAY_ONLY'] == "Y" ? "CRM_RECURRING_HINT_YEARLY_EXT_TYPE_1_WORKDAY" : "CRM_RECURRING_HINT_YEARLY_EXT_TYPE_1";
			$messagePeriod = Loc::getMessage(
				$monthDayType,
				array(
					"#DAY_NUMBER#" => $data['YEARLY_INTERVAL_DAY'],
					"#MONTH_NAME#" => Loc::getMessage("CRM_RECURRING_HINT_MONTH_".$data['YEARLY_MONTH_NUM_1'])
				)
			);
		}
		else
		{
			$gender = "";
			if (in_array($data['YEARLY_WEEK_DAY'], array(1,2,4)))
			{
				$gender = "_M";
			}
			elseif (in_array($data['YEARLY_WEEK_DAY'], array(3,5,6)))
			{
				$gender = "_F";
			}

			$weekDayName =  Loc::getMessage("CRM_RECURRING_HINT_MONTH_".$data['YEARLY_WEEKDAY_NUM'].$gender);
			$each = Loc::getMessage('CRM_RECURRING_HINT_EACH'.$gender);
			$messagePeriod = Loc::getMessage($code);
			$weekDayNumber =  Loc::getMessage("CRM_RECURRING_HINT_WEEKDAY_NUMBER_".$data['YEARLY_WEEK_DAY_NUM'].$gender);
			$weekDayName =  Loc::getMessage("CRM_RECURRING_HINT_WEEKDAY_WD_".$data['YEARLY_WEEK_DAY'].($gender == '_F' ? '_ALT' : ''));
			$messagePeriod = Loc::getMessage(
				"CRM_RECURRING_HINT_YEARLY_EXT_TYPE_2",
				array(
					"#EACH#" => $each,
					"#WEEKDAY_NUMBER#" => $weekDayNumber,
					"#WEEKDAY_NAME#" => $weekDayName,
					"#MONTH_NAME#" => Loc::getMessage('CRM_RECURRING_HINT_MONTH_'.$data['YEARLY_MONTH_NUM_2'])
				)
			);
		}
		break;
}

if (strlen($data['PERIOD']))
{
	$arResult['NEXT_EXECUTION_HINT'] =  Loc::getMessage('NEXT_EXECUTION_HINT', array('#DATE_EXECUTION#' => Calculator::getNextDate($data)));
}


if (strlen($data['START_DATE']))
{
	$start = Loc::getMessage('CRM_RECURRING_HINT_START_DATE', array('#DATETIME#' => htmlspecialcharsbx($data['START_DATE'])));
}
else
{
	$start = Loc::getMessage('CRM_RECURRING_HINT_START_EMPTY');
}

$endless = true;
if ((int)$data['LIMIT_REPEAT'] > 0 && $data['REPEAT_TILL'] === 'times')
{
	$times = abs((int)$data['LIMIT_REPEAT']);
	if (LANGUAGE_ID == "en" || LANGUAGE_ID == "de")
	{
		$form = (($times !== 1) ? Loc::getMessage('CRM_RECURRING_HINT_END_CONSTRAINT_TIMES_PLURAL_1') : Loc::getMessage('CRM_RECURRING_HINT_END_CONSTRAINT_TIMES_PLURAL_0'));
	}
	elseif (LANGUAGE_ID == "ru" || LANGUAGE_ID == "ua")
	{
		$form = \Bitrix\Crm\MessageHelper::getNumberDeclension(
			$times,
			Loc::getMessage('CRM_RECURRING_HINT_END_CONSTRAINT_TIMES_PLURAL_0'),
			Loc::getMessage('CRM_RECURRING_HINT_END_CONSTRAINT_TIMES_PLURAL_1'),
			Loc::getMessage('CRM_RECURRING_HINT_END_CONSTRAINT_TIMES_PLURAL_2')
		);
	}
	else
	{
		$form = Loc::getMessage('CRM_RECURRING_HINT_END_TIMES_PLURAL');
	}
	$constraint = Loc::getMessage(
		'CRM_RECURRING_HINT_END_TIMES',
		array(
			"#TIMES#" => $times,
			"#TIMES_PLURAL#" => $form
		)
	);
}
elseif (strlen($data['END_DATE']) > 0 && $data['REPEAT_TILL'] === 'date')
{
	$constraint = Loc::getMessage('CRM_RECURRING_HINT_END', array('#DATETIME#' => $data['END_DATE']));
}
else
{
	$constraint = Loc::getMessage('CRM_RECURRING_HINT_END_NONE');
}

$arResult['HINT'] = Loc::getMessage(
	"CRM_RECURRING_HINT_BASE",
	array(
		'#ELEMENT#' => $messagePeriod,
		'#START#' => $start,
		'#END#' =>$constraint
	)
);	

if ((int)$data['UF_MYCOMPANY_ID'])
{
	$myCompanyData = CCrmFieldMulti::GetList(
		array('ID' => 'asc'),
		array(
			'ENTITY_ID' => 'COMPANY',
			'ELEMENT_ID' => (int)$data['UF_MYCOMPANY_ID'],
			'TYPE_ID' => 'EMAIL'
		)
	);
	$myCompanyMail = $myCompanyData->Fetch();
	$arResult['ALLOW_SEND_BILL'] = strlen($myCompanyMail['VALUE']) > 0 ? "Y" : 'N';
}

if ($this->getTemplateName() == 'edit')
{
	$weekMap = array(0,1,2,3,4,5,6);
	global $USER;

	$arResult['TEMPLATE_DATA']['WEEKDAY_MAP'] = $weekMap;

	$mailList = array();
	if (is_array($arParams['DATA']['CLIENT_SECONDARY_ENTITY_IDS']) && !empty($arParams['DATA']['CLIENT_SECONDARY_ENTITY_IDS']))
	{
		$clientData = CCrmFieldMulti::GetList(
			array('ID' => 'asc'),
			array(
				'ENTITY_ID' => 'CONTACT',
				'ELEMENT_ID' => $arParams['DATA']['CLIENT_SECONDARY_ENTITY_IDS'],
				'TYPE_ID' => 'EMAIL'
			)
		);
		while ($client = $clientData->Fetch())
		{
			$clientMail = array(
				'value' => $client['ID'],
				'text' => $client['VALUE']
			);
			if ($arParams['DATA']['RECURRING_EMAIL_ID'] == $client['ID'])
			{
				array_unshift($mailList, $clientMail);
			}
			else
			{
				$mailList[] = $clientMail;
			}
		}
	}

	if ((int)$arParams['DATA']['CLIENT_PRIMARY_ENTITY_ID'] > 0)
	{
		$companyData = CCrmFieldMulti::GetList(
			array('ID' => 'asc'),
			array(
				'ENTITY_ID' => $arParams['DATA']['CLIENT_PRIMARY_ENTITY_TYPE_NAME'],
				'ELEMENT_ID' => (int)$arParams['DATA']['CLIENT_PRIMARY_ENTITY_ID'],
				'TYPE_ID' => 'EMAIL'
			)
		);
		while ($company = $companyData->Fetch())
		{
			$companyMail = array(
				'value' => $company['ID'],
				'text' => $company['VALUE']
			);

			if ($arParams['DATA']['RECURRING_EMAIL_ID'] == $company['ID'])
			{
				array_unshift($mailList, $companyMail);
			}
			else
			{
				$mailList[] = $companyMail;
			}
		}
	}

	$arResult['EMAIL_LIST'] = $mailList;

	$mailTemplateData = \CCrmMailTemplate::GetList(
		array(),
		array(
			"ENTITY_TYPE_ID" => \CCrmOwnerType::Invoice,
			array(
				"LOGIC" => "OR",
				array(
					'=OWNER_ID' => $USER->GetID(),
					'=SCOPE' => CCrmMailTemplateScope::Personal,
					'IS_ACTIVE' => 'Y'
				),
				array(
					'=SCOPE' => CCrmMailTemplateScope::Common,
					'IS_ACTIVE' => 'Y'
				),
			)
		)
	);

	$arResult['EMAIL_TEMPLATES'] = array();
	$arResult['EMAIL_TEMPLATE_LAST'] = \CCrmMailTemplate::GetLastUsedTemplateID(\CCrmOwnerType::Invoice);
	while ($template = $mailTemplateData->Fetch())
	{
		$arResult['EMAIL_TEMPLATES'][$template['ID']] = $template['TITLE'];
	}

	$arResult['PATH_TO_EMAIL_TEMPLATE_ADD'] = rtrim(SITE_DIR, '/').'/crm/configs/mailtemplate/add/';
}

/* @ToDo*/
if (CModule::IncludeModule('bitrix24'))
{
	$arResult['RESTRICTED_LICENCE'] = in_array(\CBitrix24::getLicenseType(), array('project', 'tf')) ? "Y" : "N";
	switch (LANGUAGE_ID)
	{
		case "ru":
		case "kz":
		case "by":
			$link = 'https://www.bitrix24.ru/pro/crm.php ';
			break;
		case "de":
			$link = 'https://www.bitrix24.de/pro/crm.php';
			break;
		case "ua":
			$link = 'https://www.bitrix24.ua/pro/crm.php';
			break;
		default:
			$link = 'https://www.bitrix24.com/pro/crm.php';
	}
	$arResult["TRIAL_TEXT"]['TITLE'] = Loc::getMessage('CRM_RECURRING_B24_BLOCK_TITLE');
	$arResult["TRIAL_TEXT"]['TEXT'] = Loc::getMessage('CRM_RECURRING_B24_BLOCK_TEXT', array("#LINK#" => $link));
}	
	
$this->IncludeComponentTemplate();