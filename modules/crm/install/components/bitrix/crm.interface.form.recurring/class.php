<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc,
	Bitrix\Crm\Recurring\Calculator,
	Bitrix\Crm\Category\DealCategory,
	Bitrix\Crm\Recurring\Manager,
	Bitrix\Crm\Recurring\Entity\Invoice,
	Bitrix\Crm\Recurring\Entity\Base;

Loc::loadMessages(__FILE__);

class CrmInterfaceFormRecurring extends CBitrixComponent
{
	private $data = array();
	private $type = array();

	public function onPrepareComponentParams($params)
	{
		$this->data = $params['DATA'];

		if ($params['ENTITY_TYPE'] !== Manager::DEAL)
		{
			$params['ENTITY_TYPE'] = Manager::INVOICE;
		}

		$this->type = $params['ENTITY_TYPE'];
		$params['ENTITY_TYPE'] = strtoupper($params['ENTITY_TYPE']);

		return $params;
	}

	public function executeComponent()
	{
		$this->fillResultEmailInfo();

		if ($this->getTemplateName() == 'edit')
		{
			$weekMap = array(0, 1, 2, 3, 4, 5, 6);

			$this->arResult['TEMPLATE_DATA']['WEEKDAY_MAP'] = $weekMap;
		}

		$this->arResult['HINT'] = $this->getHint();
		if (strlen($this->data['PERIOD']))
		{
			$nextDate = Invoice::getNextDate($this->data);
			$this->arResult['NEXT_EXECUTION_HINT'] = Loc::getMessage('NEXT_EXECUTION_' . $this->arParams['ENTITY_TYPE'] . '_HINT', array('#DATE_EXECUTION#' => $nextDate));
		}
		else
		{
			$this->arResult['NEXT_EXECUTION_HINT'] = Loc::getMessage('NEXT_' . $this->arParams['ENTITY_TYPE'] . '_EMPTY');
		}

		$this->arResult['AJAX_URL'] = '/bitrix/components/bitrix/crm.interface.form.recurring/ajax.php?siteID=' . SITE_ID . '&' . bitrix_sessid_get();

		if ($this->type === Manager::DEAL)
		{
			$this->arResult["DEAL_CATEGORISES"] = static::getDealCategorises();
			$this->arResult["ENTITY_TYPE_ID"] = \CCrmOwnerType::Deal;
		}
		else
		{
			$this->arResult["ENTITY_TYPE_ID"] = \CCrmOwnerType::Invoice;
		}

		if (CModule::IncludeModule('bitrix24'))
		{
			$this->arResult['RESTRICTED_LICENCE'] = !Manager::isAllowedExpose($this->type) ? "Y" : "N";
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
			$this->arResult["TRIAL_TEXT"]['TITLE'] = Loc::getMessage("CRM_RECURRING_{$this->arParams['ENTITY_TYPE']}_B24_BLOCK_TITLE");
			$this->arResult["TRIAL_TEXT"]['TEXT'] = Loc::getMessage("CRM_RECURRING_{$this->arParams['ENTITY_TYPE']}_B24_BLOCK_TEXT", array("#LINK#" => $link));
		}

		$this->includeComponentTemplate();
	}

	/**
	 *    Collect info for email block.
	 */
	protected function fillResultEmailInfo()
	{
		if ($this->type == Manager::INVOICE)
		{
			if ((int)$this->data['UF_MYCOMPANY_ID'])
			{
				$myCompanyData = CCrmFieldMulti::GetList(
					array('ID' => 'asc'),
					array(
						'ENTITY_ID' => 'COMPANY',
						'ELEMENT_ID' => (int)$this->data['UF_MYCOMPANY_ID'],
						'TYPE_ID' => 'EMAIL'
					)
				);
				$myCompanyMail = $myCompanyData->Fetch();
				$this->arResult['ALLOW_SEND_BILL'] = strlen($myCompanyMail['VALUE']) > 0 ? "Y" : 'N';
			}

			$this->arResult['EMAIL_LIST'] = $this->loadPayerMailList();
			$this->arResult['EMAIL_TEMPLATES'] = $this->loadMailTemplateList();
			$this->arResult['EMAIL_TEMPLATE_LAST'] = \CCrmMailTemplate::GetLastUsedTemplateID(\CCrmOwnerType::Invoice);
			$this->arResult['PATH_TO_EMAIL_TEMPLATE_ADD'] = rtrim(SITE_DIR, '/') . '/crm/configs/mailtemplate/add/';
		}
	}

	/**
	 * @return string
	 */
	protected function getHint()
	{
		$data = $this->data;

		$messagePeriod = $this->getPeriodHint($data);

		if (strlen($data['START_DATE']))
		{
			$start = Loc::getMessage('CRM_RECURRING_HINT_START_DATE', array('#DATETIME#' => htmlspecialcharsbx($data['START_DATE'])));
		}
		else
		{
			$start = Loc::getMessage('CRM_RECURRING_HINT_START_EMPTY');
		}

		$constraint = $this->getConstraintHint($data);

		return Loc::getMessage(
			"CRM_RECURRING_" . strtoupper($this->type) . "_HINT_BASE",
			array(
				'#ELEMENT#' => $messagePeriod,
				'#START#' => $start,
				'#END#' => $constraint
			)
		);
	}

	/**
	 * Get payer's email list.
	 *
	 * @return array
	 */
	protected function loadPayerMailList()
	{
		$data = $this->data;
		$mailList = array();

		if (is_array($data['CLIENT_SECONDARY_ENTITY_IDS']) && !empty($data['CLIENT_SECONDARY_ENTITY_IDS']))
		{
			$clientData = CCrmFieldMulti::GetList(
				array('ID' => 'asc'),
				array(
					'ENTITY_ID' => 'CONTACT',
					'ELEMENT_ID' => $data['CLIENT_SECONDARY_ENTITY_IDS'],
					'TYPE_ID' => 'EMAIL'
				)
			);
			while ($client = $clientData->Fetch())
			{
				$clientMail = array(
					'value' => $client['ID'],
					'text' => $client['VALUE']
				);
				if ($data['RECURRING_EMAIL_ID'] == $client['ID'])
				{
					array_unshift($mailList, $clientMail);
				}
				else
				{
					$mailList[] = $clientMail;
				}
			}
		}

		if ((int)$data['CLIENT_PRIMARY_ENTITY_ID'] > 0)
		{
			$companyData = CCrmFieldMulti::GetList(
				array('ID' => 'asc'),
				array(
					'ENTITY_ID' => $data['CLIENT_PRIMARY_ENTITY_TYPE_NAME'],
					'ELEMENT_ID' => (int)$data['CLIENT_PRIMARY_ENTITY_ID'],
					'TYPE_ID' => 'EMAIL'
				)
			);
			while ($company = $companyData->Fetch())
			{
				$companyMail = array(
					'value' => $company['ID'],
					'text' => $company['VALUE']
				);

				if ($data['RECURRING_EMAIL_ID'] == $company['ID'])
				{
					array_unshift($mailList, $companyMail);
				}
				else
				{
					$mailList[] = $companyMail;
				}
			}
		}

		$mailFilter = array();
		$result = array();
		foreach ($mailList as $key => $mail)
		{
			if (!in_array($mail['text'], $mailFilter))
			{
				$mailFilter[] = $mail['text'];
				$result[] = $mail;
			}
		}

		return $result;
	}

	/**
	 * Get entity email templates list.
	 *
	 * @return array
	 */
	protected function loadMailTemplateList()
	{
		global $USER;
		$mailList = array();
		$mailTemplateData = \CCrmMailTemplate::GetList(
			array(),
			array(
				'IS_ACTIVE' => 'Y',
				'__INNER_FILTER_TYPE' => array(
					'LOGIC' => 'OR',
					'__INNER_FILTER_TYPE_1' => array('ENTITY_TYPE_ID' => \CCrmOwnerType::Invoice),
					'__INNER_FILTER_TYPE_2' => array('ENTITY_TYPE_ID' => 0),
					),
				'__INNER_FILTER_SCOPE' => array(
					'LOGIC' => 'OR',
					'__INNER_FILTER_PERSONAL' => array(
						'OWNER_ID' => $USER->getId(),
						'SCOPE'    => \CCrmMailTemplateScope::Personal,
					),
					'__INNER_FILTER_COMMON' => array(
						'SCOPE' => \CCrmMailTemplateScope::Common,
					),
				),
			)
		);

		while ($template = $mailTemplateData->Fetch())
		{
			$mailList[$template['ID']] = $template['TITLE'];
		}
		return $mailList;
	}

	/**
	 * @param $data
	 *
	 * @return string
	 */
	protected function getPeriodHint($data)
	{
		if ($this->type == Manager::INVOICE)
		{
			$messagePeriod = $this->getInvoicePeriodHint($data);
		}
		else
		{
			$messagePeriod = $this->getDealPeriodHint($data);
		}

		return $messagePeriod;
	}

	/**
	 * @param $data
	 *
	 * @return string
	 */
	protected function getInvoicePeriodHint($data)
	{
		$messagePeriod = '';
		switch ($data['PERIOD'])
		{
			case Calculator::SALE_TYPE_DAY_OFFSET:
				$type = ($data['DAILY_WORKDAY_ONLY'] == "Y") ? "CRM_RECURRING_HINT_ELEMENT_DAY_MASK_WORK" : "CRM_RECURRING_HINT_ELEMENT_DAY_MASK";
				$messagePeriod = Loc::getMessage(
					$type,
					array(
						"#DAY_NUMBER#" => (int)$data['DAILY_INTERVAL_DAY'] > 1 ? (int)$data['DAILY_INTERVAL_DAY'] . " " : "",
					)
				);
				break;
			case Calculator::SALE_TYPE_WEEK_OFFSET:
				$weekPeriod = (int)$data['WEEKLY_INTERVAL_WEEK'] > 1 ? (int)$data['WEEKLY_INTERVAL_WEEK'] . " " : "";
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
							$weekDayList .= Loc::getMessage("CRM_RECURRING_HINT_WEEKDAY_WD_" . $day);
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
			case  Calculator::SALE_TYPE_MONTH_OFFSET:
				if ($data['MONTHLY_TYPE'] == 1)
				{
					$type = ($data['MONTHLY_WORKDAY_ONLY'] == "Y") ? "CRM_RECURRING_HINT_ELEMENT_MONTH_MASK_1_WORK" : "CRM_RECURRING_HINT_ELEMENT_MONTH_MASK_1";
					$messagePeriod = Loc::getMessage(
						$type,
						array(
							"#DAY_NUMBER#" => (int)$data['MONTHLY_INTERVAL_DAY'] > 1 ? (int)$data['MONTHLY_INTERVAL_DAY'] : 1,
							"#MONTH_NUMBER#" => (int)$data['MONTHLY_MONTH_NUM_1'] > 1 ? (int)$data['MONTHLY_MONTH_NUM_1'] . " " : ''
						)
					);
				}
				else
				{
					$gender = "";
					if (LANGUAGE_ID == "ru" || LANGUAGE_ID == "ua")
					{
						if (in_array($data['MONTHLY_WEEK_DAY'], array(1, 2, 4)))
						{
							$gender = "_M";
						}
						elseif (in_array($data['MONTHLY_WEEK_DAY'], array(3, 5, 6)))
						{
							$gender = "_F";
						}
					}

					$weekDayNumber = Loc::getMessage("CRM_RECURRING_HINT_WEEKDAY_NUMBER_" . $data['MONTHLY_WEEKDAY_NUM'] . $gender);
					$weekDayName = Loc::getMessage("CRM_RECURRING_HINT_WEEKDAY_WD_" . $data['MONTHLY_WEEK_DAY'] . ($gender == '_F' ? '_ALT' : ''));
					$each = Loc::getMessage('CRM_RECURRING_HINT_EACH' . $gender);

					$messagePeriod = Loc::getMessage(
						"CRM_RECURRING_HINT_MONTHLY_EXT_TYPE_2",
						array(
							"#EACH#" => $each,
							"#WEEKDAY_NUMBER#" => $weekDayNumber,
							"#WEEKDAY_NAME#" => $weekDayName,
							"#MONTH_NUMBER#" => (int)$data['MONTHLY_MONTH_NUM_2'] > 1 ? (int)$data['MONTHLY_MONTH_NUM_2'] . " " : ''
						)
					);
				}
				break;
			case Calculator::SALE_TYPE_YEAR_OFFSET:
				if ($data['YEARLY_TYPE'] == 1)
				{
					$monthDayType = $data['YEARLY_WORKDAY_ONLY'] == "Y" ? "CRM_RECURRING_HINT_YEARLY_EXT_TYPE_1_WORKDAY" : "CRM_RECURRING_HINT_YEARLY_EXT_TYPE_1";
					$messagePeriod = Loc::getMessage(
						$monthDayType,
						array(
							"#DAY_NUMBER#" => $data['YEARLY_INTERVAL_DAY'],
							"#MONTH_NAME#" => Loc::getMessage("CRM_RECURRING_HINT_MONTH_" . $data['YEARLY_MONTH_NUM_1'])
						)
					);
				}
				else
				{
					$gender = "";
					if (in_array($data['YEARLY_WEEK_DAY'], array(1, 2, 4)))
					{
						$gender = "_M";
					}
					elseif (in_array($data['YEARLY_WEEK_DAY'], array(3, 5, 6)))
					{
						$gender = "_F";
					}

					$each = Loc::getMessage('CRM_RECURRING_HINT_EACH' . $gender);
					$weekDayNumber = Loc::getMessage("CRM_RECURRING_HINT_WEEKDAY_NUMBER_" . $data['YEARLY_WEEK_DAY_NUM'] . $gender);
					$weekDayName = Loc::getMessage("CRM_RECURRING_HINT_WEEKDAY_WD_" . $data['YEARLY_WEEK_DAY'] . ($gender == '_F' ? '_ALT' : ''));
					$messagePeriod = Loc::getMessage(
						"CRM_RECURRING_HINT_YEARLY_EXT_TYPE_2",
						array(
							"#EACH#" => $each,
							"#WEEKDAY_NUMBER#" => $weekDayNumber,
							"#WEEKDAY_NAME#" => $weekDayName,
							"#MONTH_NAME#" => Loc::getMessage('CRM_RECURRING_HINT_MONTH_' . $data['YEARLY_MONTH_NUM_2'])
						)
					);
				}
				break;
		}

		return $messagePeriod;
	}

	/**
	 * @param $data
	 *
	 * @return string
	 */
	protected function getDealPeriodHint($data)
	{
		if ((int)$data['EXECUTION_TYPE'] === Manager::MULTIPLY_EXECUTION)
		{
			$messagePeriod = Loc::getMessage('CRM_RECURRING_HINT_EVERY_DEAL_'.(int)$data['PERIOD_DEAL']);
		}
		else
		{
			$typeElement = \Bitrix\Crm\MessageHelper::getNumberDeclension(
				(int)$data['DEAL_COUNT_BEFORE'],
				Loc::getMessage("CRM_RECURRING_HINT_".(int)$data['DEAL_TYPE_BEFORE']."_PLURAL_0"),
				Loc::getMessage("CRM_RECURRING_HINT_".(int)$data['DEAL_TYPE_BEFORE']."_PLURAL_1"),
				Loc::getMessage("CRM_RECURRING_HINT_".(int)$data['DEAL_TYPE_BEFORE']."_PLURAL_2")
			);

			if (strlen($data['DEAL_DATEPICKER_BEFORE']) > 0)
			{
				$dateMessage = Loc::getMessage('CRM_RECURRING_HINT_BEFORE_DATE', array('#DATE#' => htmlspecialcharsbx($data['DEAL_DATEPICKER_BEFORE'])));
			}
			else
			{
				$dateMessage = "";
			}

			$messagePeriod = Loc::getMessage(
				'CRM_RECURRING_HINT_A_FEW_DAYS_BEFORE_DATE',
					array(
						'#COUNT_ELEMENT#' => (int)$data['DEAL_COUNT_BEFORE'],
						'#TYPE_ELEMENT#' => $typeElement,
						'#BEFORE_DATE#' => $dateMessage
					)
			);
		}

		return $messagePeriod;
	}

	/**
	 * @param $data
	 *
	 * @return string
	 */
	protected function getConstraintHint($data)
	{
		if ((int)$data['LIMIT_REPEAT'] > 0 && ($data['REPEAT_TILL'] === 'times' || $data['REPEAT_TILL'] === Base::LIMITED_BY_TIMES))
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
			return $constraint;
		}
		elseif (strlen($data['END_DATE']) > 0 && ($data['REPEAT_TILL'] === 'date' || $data['REPEAT_TILL'] === Base::LIMITED_BY_DATE))
		{
			$constraint = Loc::getMessage('CRM_RECURRING_HINT_END', array('#DATETIME#' => $data['END_DATE']));
			return $constraint;
		}
		else
		{
			$constraint = Loc::getMessage('CRM_RECURRING_HINT_END_NONE');
			return $constraint;
		}
	}

	/**
	 * Collect deal categorises list for current user.
	 * @return array
	 */
	protected function getDealCategorises()
	{
		$categorises = array();
		$userPermissions = CCrmPerms::GetCurrentUserPermissions();
		$map = array_fill_keys(\CCrmDeal::GetPermittedToReadCategoryIDs($userPermissions), true);
		foreach(DealCategory::getAll(true) as $item)
		{
			if (isset($map[$item['ID']]))
			{
				$categorises[$item['ID']] = isset($item['NAME']) ? $item['NAME'] : "[{$item['ID']}]";
			}
		}

		return $categorises;
	}
}
