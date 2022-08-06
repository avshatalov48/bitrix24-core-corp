<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Localization\LanguageTable;
use Bitrix\Currency\CurrencyClassifier;

Loc::loadMessages(__FILE__);

/**
 * Component for adding currency
 */
class CCurrencyClassifierComponent extends \CBitrixComponent
{
	/**
	 * Return array of two values: fields and currency id, prepared for adding or updating currency
	 * @param array $lastValues - Array of values for database
	 * @param string $formMode - Form mode
	 * @return array
	 */
	private function prepareDataForDatabase($lastValues, $formMode)
	{
		$fields = array();
		$currencyId = '';

		if ($formMode == 'ADD')
		{
			$values = $lastValues['ADD']['GENERAL'];
			$currencyData = $this->arResult['CLASSIFIER'][$values['SELECTED_ID']];

			$fields['NUMCODE'] = $currencyData['NUM_CODE'];
			$fields['AMOUNT_CNT'] = $values['NOMINAL'];
			$fields['AMOUNT'] = $values['EXCHANGE_RATE'];
			$fields['SORT'] = $values['SORT_INDEX'];

			$currencyId = $currencyData['SYM_CODE'];
			$baseCurrencyId = $this->arResult['BASE_CURRENCY_ID'];

			if ($currencyId == $baseCurrencyId)
			{
				$fields['AMOUNT_CNT'] = 1;
				$fields['AMOUNT'] = 1;
			}

			foreach ($this->arResult['LANGUAGE_IDS'] as $languageId)
			{
				$upperLanguageId = mb_strtoupper($languageId);
				$currencyLangProps = $currencyData[$upperLanguageId];

				$currencyLangProps['CURRENCY'] = $currencyId;
				$currencyLangProps['HIDE_ZERO'] = $lastValues['ADD'][$upperLanguageId]['HIDE_ZERO'];
				$currencyLangProps['FORMAT_STRING'] = strip_tags(str_replace('#VALUE#', '#', $currencyLangProps['FORMAT_STRING']));

				$fields['LANG'][$languageId] = $currencyLangProps;
			}
		}
		else
		{
			foreach ($lastValues['EDIT'] as $type => $values)
			{
				if ($type == 'GENERAL')
				{
					$fields['AMOUNT_CNT'] = $values['NOMINAL'];
					$fields['AMOUNT'] = $values['EXCHANGE_RATE'];
					$fields['SORT'] = $values['SORT_INDEX'];

					$currencyId = $values['SYM_CODE'];
					$baseCurrencyId = $this->arResult['BASE_CURRENCY_ID'];

					if ($currencyId == $baseCurrencyId)
					{
						$fields['AMOUNT_CNT'] = 1;
						$fields['AMOUNT'] = 1;
					}
				}
				else
				{
					if ($values['FULL_NAME'] == '')
					{
						$values['FULL_NAME'] = $currencyId;
					}

					$currencyLangProps = $values;
					$currencyLangProps['FULL_NAME'] = strip_tags($currencyLangProps['FULL_NAME']);
					$currencyLangProps['FORMAT_STRING'] = strip_tags($currencyLangProps['FORMAT_STRING']);
					$currencyLangProps['CURRENCY'] = $currencyId;

					$lowerType = mb_strtolower($type);
					$fields['LANG'][$lowerType] = $currencyLangProps;
				}
			}
		}

		return array('FIELDS' => $fields, 'CURRENCY_ID' => $currencyId);
	}

	/**
	 * Check fields errors
	 * @param array $fields - Array of values in fields
	 * @param string $formMode - Form mode
	 * @return bool
	 */
	private function checkFieldsErrors($fields, $formMode)
	{
		foreach ($fields[$formMode] as $type => $values)
		{
			if ($type == 'GENERAL')
			{
				$maxIntValue = 2147483647;

				if ($formMode == 'EDIT')
					if (!preg_match('/^[A-Za-z]{3}$/', $values['SYM_CODE']))
						$this->arResult['ERRORS']['GENERAL']['SYM_CODE'] = Loc::getMessage('CRM_CURRENCY_CLASSIFIER_FIELD_SYM_CODE_ERROR');

				if (!preg_match('/^[1-9][0-9]{0,10}$/', $values['NOMINAL']) || ($values['NOMINAL'] > $maxIntValue))
					$this->arResult['ERRORS']['GENERAL']['NOMINAL'] = Loc::getMessage('CRM_CURRENCY_CLASSIFIER_FIELD_INCORRECT_VALUE_ERROR');

				if (!preg_match('/^[0-9]{0,14}[\.]{0,1}[0-9]{0,4}$/', $values['EXCHANGE_RATE']) ||
					($values['EXCHANGE_RATE'] <= 0) ||
					($values['EXCHANGE_RATE'] > 99999999999999))
				{
					$this->arResult['ERRORS']['GENERAL']['EXCHANGE_RATE'] = Loc::getMessage('CRM_CURRENCY_CLASSIFIER_FIELD_INCORRECT_VALUE_ERROR');
				}

				if (!preg_match('/^[1-9][0-9]{0,10}$/', $values['SORT_INDEX']) || ($values['SORT_INDEX'] > $maxIntValue))
					$this->arResult['ERRORS']['GENERAL']['SORT_INDEX'] = Loc::getMessage('CRM_CURRENCY_CLASSIFIER_FIELD_INCORRECT_VALUE_ERROR');
			}
			elseif ($formMode == 'EDIT')
			{
				$fullNameLength = mb_strlen($values['FULL_NAME']);
				$maxFullNameLength = 50;
				$maxSepAndDecPointLength = 5;
				$maxSignLength = 48;
				$maxDecimalsValue = 127;

				if ($fullNameLength > $maxFullNameLength)
					$this->arResult['ERRORS'][$type]['FULL_NAME'] = Loc::getMessage('CRM_CURRENCY_CLASSIFIER_FIELD_FULL_NAME_ERROR');

				if ($values['THOUSANDS_VARIANT'] == 'OWN')
					if (mb_strlen($values['THOUSANDS_SEP']) > $maxSepAndDecPointLength)
						$this->arResult['ERRORS'][$type]['THOUSANDS_SEP'] = Loc::getMessage('CRM_CURRENCY_CLASSIFIER_FIELD_MAX_LENGTH_ERROR', array('#max_length#' => $maxSepAndDecPointLength));

				if (mb_strlen($values['SIGN']) > $maxSignLength)
					$this->arResult['ERRORS'][$type]['SIGN'] = Loc::getMessage('CRM_CURRENCY_CLASSIFIER_FIELD_MAX_LENGTH_ERROR', array('#max_length#' => $maxSignLength));

				if (mb_strlen($values['DEC_POINT']) > $maxSepAndDecPointLength)
					$this->arResult['ERRORS'][$type]['DEC_POINT'] = Loc::getMessage('CRM_CURRENCY_CLASSIFIER_FIELD_MAX_LENGTH_ERROR', array('#max_length#' => $maxSepAndDecPointLength));

				if (!preg_match('/^[0-9]{0,3}$/', $values['DECIMALS']) || ($values['DECIMALS'] > $maxDecimalsValue))
					$this->arResult['ERRORS'][$type]['DECIMALS'] = Loc::getMessage('CRM_CURRENCY_CLASSIFIER_FIELD_INCORRECT_VALUE_ERROR');
			}
		}

		return (count($this->arResult['ERRORS']) == 0);
	}

	/**
	 * On submit form action
	 */
	private function processFormActions()
	{
		$application = Application::getInstance();
		$context = $application->getContext();
		$request = $context->getRequest();

		if ($request->isPost() && ($request->get('save') || $request->get('apply')) && check_bitrix_sessid())
		{
			$action = $request->get('save') ? 'SAVE' : 'APPLY';
			$formMode = $request->get('current_form_mode');
			$afterAdding = $request->get('after_adding');
			$this->arResult['TARGET_FORM_MODE'] = $formMode;
			$lowerCaseFormMode = mb_strtolower($formMode);

			$lastValues = array(
				'ADD' => array(
					'GENERAL' => array(
						'NEEDLE' => $request->get('add_classifier_currency_needle'),
						'SELECTED_ID' => $request->get('add_sym_code'),
						'NOMINAL' => $request->get('add_nominal'),
						'EXCHANGE_RATE' => str_replace(',', '.', $request->get('add_exchange_rate')),
						'SORT_INDEX' => $request->get('add_sort_index'),
						'BASE_FOR_REPORTS' => $request->get('add_base_for_reports'),
						'BASE_FOR_COUNT' => $request->get('add_base_for_count')
					)
				),
				'EDIT' => array(
					'GENERAL' => array(
						'SYM_CODE' => $request->get('edit_sym_code'),
						'NOMINAL' => $request->get('edit_nominal'),
						'EXCHANGE_RATE' => str_replace(',', '.', $request->get('edit_exchange_rate')),
						'SORT_INDEX' => $request->get('edit_sort_index'),
						'BASE_FOR_REPORTS' => $request->get('edit_base_for_reports'),
						'BASE_FOR_COUNT' => $request->get('edit_base_for_count')
					)
				)
			);
			$lastValues['ADD'] = array_merge($lastValues['ADD'], $this->getPostCurrencyLangSettings('ADD', $request));
			$lastValues['EDIT'] = array_merge($lastValues['EDIT'], $this->getPostCurrencyLangSettings('EDIT', $request));

			if (!$this->checkFieldsErrors($lastValues, $formMode))
			{
				$this->writeValuesInResult($lastValues, $afterAdding);
				return;
			}

			$dataForDatabase = $this->prepareDataForDatabase($lastValues, $formMode);
			$currencyId = $dataForDatabase['CURRENCY_ID'];
			$fields = $dataForDatabase['FIELDS'];

			$presentFields = $this->arResult['EXISTING_CURRENCIES'][$currencyId];

			if (is_array($presentFields))
			{
				$result = CCrmCurrency::Update($currencyId, $fields);
				if (!$result)
				{
					$err = CCrmCurrency::GetLastError();
					$this->arResult['ERRORS']['UPDATE'] = ($err !== '') ? $err : Loc::getMessage('CRM_CURRENCY_CLASSIFIER_UPDATE_UNKNOWN_ERROR');
				}
			}
			else
			{
				$fields['CURRENCY'] = $currencyId;
				$currencyId = CCrmCurrency::Add($fields);
				$result = is_string($currencyId) && ($currencyId !== '');
				if (!$result)
				{
					$err = CCrmCurrency::GetLastError();
					$this->arResult['ERRORS']['ADD'] = ($err !== '') ? $err : Loc::getMessage('CRM_CURRENCY_CLASSIFIER_ADD_UNKNOWN_ERROR');
				}
			}

			if (!$result)
			{
				$this->writeValuesInResult($lastValues, $afterAdding);
				return;
			}

			if ($currencyId == CCrmCurrency::GetAccountCurrencyID())
			{
				if (!$request->get($lowerCaseFormMode.'_base_for_reports'))
					$lastValues[$formMode]['GENERAL']['BASE_FOR_REPORTS'] = 'Y';
			}
			else
			{
				if ($request->get($lowerCaseFormMode.'_base_for_reports'))
					CCrmCurrency::SetAccountCurrencyID($currencyId);
			}

			if ($currencyId == CCrmCurrency::getInvoiceDefault())
			{
				if (!$request->get($lowerCaseFormMode.'_base_for_count'))
					$lastValues[$formMode]['GENERAL']['BASE_FOR_COUNT'] = 'Y';
			}
			else
			{
				if ($request->get($lowerCaseFormMode . '_base_for_count'))
					CCrmCurrency::setInvoiceDefault($currencyId);
			}

			if ($action == 'SAVE')
			{
				if ($this->arParams['IFRAME'])
					$this->arResult['CLOSE_SLIDER'] = true;
				else
					LocalRedirect(CComponentEngine::makePathFromTemplate($this->arParams['PATH_TO_CURRENCY_LIST']));
			}
			else
			{
				if ($this->arResult['PRIMARY_FORM_MODE'] == 'ADD')
					$this->prepareData(true, $currencyId);
				else
					$this->writeValuesInResult($lastValues, $afterAdding);
			}
		}
	}

	/**
	 * Write last entered values in arResult array
	 * @param array $lastValues - Array of last values entered in form
	 * @param bool $afterAdding
	 */
	private function writeValuesInResult($lastValues, $afterAdding)
	{
		foreach ($lastValues as $formMode => $types)
		{
			foreach ($types as $type => $values)
			{
				foreach ($values as $key => $value)
				{
					$this->arResult['LAST_VALUES'][$formMode][$type][$key] = $value;
				}
			}
		}

		if ($afterAdding)
		{
			$this->arResult['PRIMARY_FORM_MODE'] = $this->arResult['CURRENT_FORM_MODE'] = $this->arResult['TARGET_FORM_MODE'] = 'EDIT';
			$this->arResult['AFTER_ADDING'] = true;
		}
	}

	/**
	 * Return array of language properties by form mode from request
	 * @param string $formMode - Form Mode
	 * @param \Bitrix\Main\HttpRequest $request - Request
	 * @return array
	 */
	private function getPostCurrencyLangSettings($formMode, \Bitrix\Main\HttpRequest $request)
	{
		$currencyLangSettings = array();

		if ($formMode == 'ADD')
		{
			foreach ($this->arResult['LANGUAGE_IDS'] as $languageId)
			{
				$langSettings = array();
				$langSettings['HIDE_ZERO'] = $request->get('add_hide_zero_' . $languageId) ? 'Y' : 'N';

				$currencyLangSettings[mb_strtoupper($languageId)] = $langSettings;
			}
		}
		else
		{
			foreach ($this->arResult['LANGUAGE_IDS'] as $languageId)
			{
				$formatTemplate = $request->get('edit_format_template_' . $languageId);
				$sign = strip_tags($request->get('edit_sign_' . $languageId));
				$signPosition = $request->get('edit_sign_position_' . $languageId);
				$decPoint = $request->get('edit_dec_point_' . $languageId);

				$langSettings = array();
				$langSettings['FULL_NAME'] = $request->get('edit_full_name_' . $languageId);
				$langSettings['FORMAT_TEMPLATE'] = $formatTemplate;
				$langSettings['FORMAT_STRING'] = ($signPosition == 'B') ? $sign . '#' : '# ' . $sign;
				$langSettings['THOUSANDS_VARIANT'] = $request->get('edit_thousands_variant_' . $languageId);
				$langSettings['THOUSANDS_SEP'] = $request->get('edit_thousands_sep_' . $languageId);
				$langSettings['DEC_POINT'] = $decPoint ? $decPoint : '.';
				$langSettings['DECIMALS'] = $request->get('edit_decimals_' . $languageId);
				$langSettings['HIDE_ZERO'] = $request->get('edit_hide_zero_' . $languageId) ? 'Y' : 'N';
				$langSettings['SIGN'] = $sign;
				$langSettings['SIGN_POSITION'] = $request->get('edit_sign_position_' . $languageId);
				$langSettings['CONTENT_EXPANDED'] = ($this->arResult['PRIMARY_FORM_MODE'] == 'EDIT' && $formatTemplate == '-') ? 'Y' : $request->get('expandable_content_hidden_input_' . $languageId);

				$currencyLangSettings[mb_strtoupper($languageId)] = $langSettings;
			}
		}

		return $currencyLangSettings;
	}

	/**
	 * Return array of language properties for currency or base language properties when no currency id passed
	 * @param string $currencyId - currency id
	 * @return array
	 */
	private function getStartCurrencyLangSettings($currencyId = '')
	{
		$currencyLangSettings = array();

		if ($currencyId !== '')
		{
			$currencyLangList = CCurrencyLang::GetList('', '', $currencyId);
			while ($currencyLang = $currencyLangList->Fetch())
			{
				$langSettings = $currencyLang;
				$formatString = $currencyLang['FORMAT_STRING'];
				$thousandsVariant = ($currencyLang['THOUSANDS_VARIANT'] == null) ? 'OWN' : $currencyLang['THOUSANDS_VARIANT'];
				$formatTemplate = '-';

				foreach ($this->arResult['THOUSANDS_SEP'] as $key => $sep)
				{
					if ($currencyLang['DEC_POINT'] == $sep)
					{
						$formatTemplate = $thousandsVariant.$key;
						if (!array_key_exists($formatTemplate, $this->arResult['FORMAT_TEMPLATES']))
							$formatTemplate = '-';
						break;
					}
				}

				$langSettings['THOUSANDS_VARIANT'] = $thousandsVariant;
				$langSettings['FORMAT_STRING'] = strip_tags($formatString);
				$langSettings['SIGN'] = trim(str_replace('&#', '[*]', $formatString));
				$langSettings['SIGN'] = trim(str_replace('#', '', $langSettings['SIGN']));
				$langSettings['SIGN'] = trim(str_replace('[*]', '&#', $langSettings['SIGN']));
				$langSettings['SIGN_POSITION'] = (mb_strpos($formatString, '#') == 0) ? 'A' : 'B';
				$langSettings['FORMAT_TEMPLATE'] = $formatTemplate;
				$langSettings['CONTENT_EXPANDED'] = ($formatTemplate == '-') ? 'Y' : 'N';

				$currencyLangSettings[mb_strtoupper($currencyLang['LID'])] = $langSettings;
			}
		}
		else
		{
			foreach ($this->arResult['LANGUAGE_IDS'] as $languageId)
			{
				$langSettings = array();

				$langSettings['DEC_POINT'] = '.';
				$langSettings['DECIMALS'] = 2;
				$langSettings['HIDE_ZERO'] = 'Y';
				$langSettings['CONTENT_EXPANDED'] = 'N';

				$currencyLangSettings[mb_strtoupper($languageId)] = $langSettings;
			}
		}

		return $currencyLangSettings;
	}

	/**
	 * Prepare data to show
	 * @param bool $afterAdding - True if currency was just added
	 * @param string $currencyId - Currency id
	 */
	private function prepareData($afterAdding = false, $currencyId = '')
	{
		if ($afterAdding)
		{
			$formMode = 'EDIT';
			$this->arResult['CURRENCY_ID'] = $currencyId;
			$this->arResult['AFTER_ADDING'] = true;
		}
		else
		{
			$formMode = $this->arParams['FORM_MODE'];
			$this->arResult['CURRENCY_ID'] = $this->arParams['CURRENCY_ID'];
			$this->arResult['PRIMARY_FORM_MODE'] = $this->arResult['CURRENT_FORM_MODE'] = $this->arResult['TARGET_FORM_MODE'] = $formMode;
		}

		$this->arResult['IFRAME'] = $this->arParams['IFRAME'];
		$this->arResult['PATH_TO_CURRENCY_LIST'] = $this->arParams['PATH_TO_CURRENCY_LIST'];
		$this->arResult['EXISTING_CURRENCIES'] = CCrmCurrency::GetAll();
		$this->arResult['BASE_CURRENCY_ID'] = CCrmCurrency::GetBaseCurrencyID();
		$this->arResult['THOUSANDS_SEP'] = CCurrencyLang::GetSeparators();
		$this->arResult['THOUSANDS_VARIANTS'] = CCurrencyLang::GetSeparatorTypes(true);
		$this->arResult['THOUSANDS_VARIANTS']['OWN'] = Loc::getMessage('CRM_CURRENCY_CLASSIFIER_FIELD_THOUSANDS_VARIANT_OWN');
		$this->arResult['CLOSE_SLIDER'] = false;

		$languages = array();
		$languageIdList = array();

		$languageList = LanguageTable::getList([
			'select' => ['LANGUAGE_ID', 'SORT', 'NAME'],
			'order' => ['SORT' => 'ASC']
		]);
		while ($language = $languageList->fetch())
		{
			$title = trim(Loc::getMessage('BX_CRM_COMPONENT_CCC_LANGUAGE_TITLE_'.mb_strtoupper($language['LANGUAGE_ID'])));
			if ($title === '')
				$title = $language['NAME'];
			$languageId = $language['LANGUAGE_ID'];
			$languageIdList[] = $languageId;
			$languages[$languageId] = $title;
		}

		reset($languages);
		if (isset($languages[LANGUAGE_ID]) && (current($languages) !== $languages[LANGUAGE_ID]))
		{
			$value = $languages[LANGUAGE_ID];
			unset($languages[LANGUAGE_ID]);
			$languages = array(LANGUAGE_ID => $value) + $languages;
		}

		$this->arResult['LANGUAGES'] = $languages;
		$this->arResult['LANGUAGE_IDS'] = $languageIdList;
		$this->arResult['CLASSIFIER'] = CurrencyClassifier::get($languageIdList, LANGUAGE_ID);
		$this->arResult['FORMAT_TEMPLATES'] = array(
			'-' => '-',
			'ND' => '12345.67',
			'NC' => '12345,67',
			'CD' => '12,345.67',
			'DC' => '12.345,67',
			'BD' => '12 345.67',
			'BC' => '12 345,67'
		);
		$this->arResult['SIGN_POSITIONS'] = array(
			'B' => Loc::getMessage('CRM_CURRENCY_CLASSIFIER_FIELD_SIGN_POSITION_BEFORE'),
			'A' => Loc::getMessage('CRM_CURRENCY_CLASSIFIER_FIELD_SIGN_POSITION_AFTER')
		);

		$currentElement = current($this->arResult['CLASSIFIER']);

		if ($formMode == "EDIT")
		{
			$currencyId = $this->arResult['CURRENCY_ID'];

			if (!($currencyData = CCrmCurrency::GetByID($currencyId)))
			{
				$this->arResult['ERRORS']['NOT_FOUND'] = Loc::getMessage('CRM_CURRENCY_CLASSIFIER_CURRENCY_NOT_FOUND', array('#currency#' => $currencyId));
				return;
			}

			$currencyId = $currencyData['CURRENCY'];
			$this->arResult['CURRENCY_ID'] = $currencyId;

			$currencyData['BASE_FOR_REPORTS'] = $currencyId === CCrmCurrency::GetAccountCurrencyID();
			$currencyData['BASE_FOR_COUNT'] = $currencyId === CCrmCurrency::getInvoiceDefault();

			$editLastValues = array(
				'EDIT' => array(
					'GENERAL' => array(
						'SYM_CODE' => $currencyId,
						'NOMINAL' => $currencyData['AMOUNT_CNT'],
						'EXCHANGE_RATE' => $currencyData['AMOUNT'],
						'SORT_INDEX' => $currencyData['SORT'],
						'BASE_FOR_REPORTS' => $currencyData['BASE_FOR_REPORTS'],
						'BASE_FOR_COUNT' => $currencyData['BASE_FOR_COUNT']
					)
				)
			);
		}
		else
		{
			$currencyId = '';

			$editLastValues = array(
				'EDIT' => array(
					'GENERAL' => array(
						'NOMINAL' => 1,
						'SORT_INDEX' => 100,
					)
				)
			);
		}

		$lastValues = array(
			'ADD' => array(
				'GENERAL' => array(
					'NEEDLE' => '',
					'SELECTED_ID' => $currentElement['SYM_CODE'],
					'NOMINAL' => 1,
					'SORT_INDEX' => 100
				)
			)
		);

		$lastValues['ADD'] = array_merge($lastValues['ADD'], $this->getStartCurrencyLangSettings());
		$editLastValues['EDIT'] = array_merge($editLastValues['EDIT'], $this->getStartCurrencyLangSettings($currencyId));
		$lastValues = array_merge($lastValues, $editLastValues);
		$this->writeValuesInResult($lastValues, $afterAdding);
	}

	/**
	 * Check if CRM and Currency modules are included
	 * @return bool
	 */
	private function checkModulesIncluding()
	{
		$this->arResult['ERRORS'] = array();

		if (!CModule::IncludeModule('crm'))
		{
			$this->arResult['ERRORS']['CRM'] = Loc::getMessage('CRM_CURRENCY_CLASSIFIER_MODULE_NOT_INSTALLED_CRM');
		}
		if (!CModule::IncludeModule('currency'))
		{
			$this->arResult['ERRORS']['CURRENCY'] = Loc::getMessage('CRM_CURRENCY_CLASSIFIER_MODULE_NOT_INSTALLED_CURRENCY');
		}

		return (count($this->arResult['ERRORS']) == 0);
	}

	/**
	 * Execute component
	 */
	public function executeComponent()
	{
		global $USER;

		$CrmPerms = new CCrmPerms($USER->GetID());
		if (!$CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
		{
			ShowError(Loc::getMessage('CRM_CURRENCY_CLASSIFIER_PERMISSION_DENIED'));
			return;
		}
		if ($this->checkModulesIncluding())
		{
			$this->prepareData();
			$this->processFormActions();
		}
		$this->includeComponentTemplate();
	}
}