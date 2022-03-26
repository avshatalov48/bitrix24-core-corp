<?

use Bitrix\Crm\Integration\UserConsent;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Crm;
use Bitrix\Crm\WebForm;
use Bitrix\Crm\WebForm\Manager;
use Bitrix\Crm\WebForm\Helper;
use Bitrix\Crm\WebForm\Form;
use Bitrix\Crm\WebForm\FieldSynchronizer;
use Bitrix\Crm\WebForm\Entity;
use Bitrix\Crm\WebForm\ResultEntity;
use Bitrix\Crm\WebForm\EntityFieldProvider;
use Bitrix\Crm\Category\DealCategory;
use Bitrix\Crm\WebForm\ReCaptcha;
use Bitrix\Crm\WebForm\ResponsibleQueue;
use Bitrix\Crm\Ads\AdsForm;
use Bitrix\Main\Type\Date;

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)
	die();

Loc::loadMessages(__FILE__);


class CCrmWebFormEditComponent extends \CBitrixComponent
{
	protected $errors = array();

	/** @var  Form */
	protected $crmWebForm;

	protected function processPostFields($fields)
	{
		$fieldList = array();
		foreach($fields as $fieldId => $field)
		{
			$field['CODE'] = $fieldId;
			$fields[$fieldId] = $field;
		}
		$fields = EntityFieldProvider::getFieldsDescription($fields);

		foreach($fields as $fieldId => $field)
		{
			$fieldTmp = array(
				'CODE' => $fieldId,
				'TYPE' => $field['TYPE'],
				'CAPTION' => $field['CAPTION'],
				'SORT' => (int) $field['SORT'],
				'ITEMS' => array(),
				'SETTINGS_DATA' => array(),
				'VALUE_TYPE' => '',
				'VALUE' => $field['VALUE']
			);

			$fieldTmp['REQUIRED'] = $field['REQUIRED'] == 'Y' ? 'Y' : 'N';
			$fieldTmp['MULTIPLE'] = $field['MULTIPLE'] == 'Y' ? 'Y' : 'N';

			if($field['TYPE'] == 'section' || $field['TYPE'] == 'page')
			{
				$fieldTmp['REQUIRED'] = 'N';
				$fieldTmp['MULTIPLE'] = 'N';
			}
			elseif($field['TYPE'] == 'product')
			{
				//$fieldTmp['REQUIRED'] = 'Y';
				//$fieldTmp['MULTIPLE'] = 'N';
			}
			else
			{
				$fieldTmp['PLACEHOLDER'] = $field['PLACEHOLDER'];
			}

			if(isset($field['VALUE_TYPE']) && isset($field['VALUE_TYPE_ORIGINAL']))
			{
				$isValueTypeExisted = false;
				foreach($field['VALUE_TYPE_ORIGINAL'] as $valueTypeItem)
				{
					if($valueTypeItem['ID'] == $field['VALUE_TYPE'])
					{
						$isValueTypeExisted = true;
						break;
					}
				}

				if($isValueTypeExisted)
				{
					$fieldTmp['VALUE_TYPE'] = $field['VALUE_TYPE'];
				}
			}

			if(is_array($field['ITEMS']))
			{
				foreach($field['ITEMS'] as $itemId => $item)
				{
					$unknownItemKeys = array_diff(
						array_keys($item),
						array('ID', 'VALUE', 'PRICE', 'CUSTOM_PRICE', 'DISCOUNT', 'NAME', 'SELECTED', 'DISABLED')
					);
					if(count($unknownItemKeys) == 0)
					{
						continue;
					}

					foreach($unknownItemKeys as $unknownItemKey)
					{
						unset($field['ITEMS'][$itemId][$unknownItemKey]);
					}
				}

				$fieldTmp['ITEMS'] = array_values($field['ITEMS']);
			}

			if($field['ID'] > 0)
			{
				$fieldTmp['ID'] = $field['ID'];
			}

			if($field['CAPTION'] == $field['ENTITY_FIELD_CAPTION'])
			{
				$fieldTmp['CAPTION'] = '';
			}

			if(is_array($field['SETTINGS_DATA']))
			{
				$fieldTmp['SETTINGS_DATA'] = $field['SETTINGS_DATA'];
			}

			$fieldList[] = $fieldTmp;
		}

		return $fieldList;
	}

	protected function processPostPresetFields($presetFields)
	{
		if(!is_array($presetFields) || count($presetFields) == 0)
		{
			return array();
		}

		$fields = array();
		$presetFieldCodeList = array_keys($presetFields);
		foreach($this->arResult['AVAILABLE_FIELDS'] as $field)
		{
			if(!in_array($field['name'], $presetFieldCodeList))
			{
				continue;
			}

			$fields[] = array(
				'ENTITY_NAME' => $field['entity_name'],
				'FIELD_NAME' => $field['entity_field_name'],
				'VALUE' => $presetFields[$field['name']]['VALUE'],
			);
		}

		return $fields;
	}

	protected function processPostDependencies($dependencies)
	{
		$dependencyList = array();
		foreach($dependencies as $dependencyId => $dependency)
		{
			$dependencyList[] = array(
				'IF_FIELD_CODE' => $dependency['IF_FIELD_CODE'],
				'IF_ACTION' => \Bitrix\Crm\WebForm\Internals\FieldDependenceTable::ACTION_ENUM_CHANGE,
				'IF_VALUE' => $dependency['IF_VALUE'],
				'DO_FIELD_CODE' => $dependency['DO_FIELD_CODE'],
				'DO_ACTION' => $dependency['DO_ACTION'],
			);
		}

		return $dependencyList;
	}

	public function processPostScripts($scripts)
	{
		return $scripts;
	}

	public function processPostBackgroundImage($fieldName)
	{
		$needDeleteImage = $this->request->get($fieldName . '_del') == 'Y';
		$needUpdateImage = true;

		$fileList = $this->request->getFile($fieldName);
		if(is_array($fileList['tmp_name']))
		{
			$fileListTmp = array();
			foreach($fileList as $fileKey => $fileValue)
			{
				foreach($fileValue as $valueIndex => $value)
				{
					$fileListTmp[$valueIndex][$fileKey] = $value;
				}
			}

			$fileList = $fileListTmp;
		}
		else
		{
			$fileList = array($fileList);
		}

		foreach($fileList as $file)
		{
			$fileId = CFile::SaveFile($file, 'crm/webform');
			if($fileId)
			{
				$isSuccessUpdate = Form::updateBackgroundImage($this->crmWebForm->getId(), $fileId);
				if($isSuccessUpdate)
				{
					$needDeleteImage = true;
					$needUpdateImage = false;
				}
			}
			break;
		}

		if($needDeleteImage)
		{
			$form = $this->crmWebForm->get();
			if($form[$fieldName])
			{

				CFile::Delete($form[$fieldName]);
			}

			if($needUpdateImage)
			{
				Form::updateBackgroundImage($this->crmWebForm->getId(), null);
			}
		}

		return $fileList;
	}

	public function processPostRemoveCopyRight($copyright)
	{
		return ($copyright == 'Y' && Form::canRemoveCopyright()) ? 'Y' : 'N';
	}

	public function processPostInvoiceSettings($requestInvoiceSettings)
	{
		$defaultPayer = ResultEntity::INVOICE_PAYER_CONTACT;
		$payerTypeList = array_keys($this->arResult['INVOICE_PAYER_TYPES']);
		$payerType = $requestInvoiceSettings['PAYER'];
		if(!$payerType || !in_array($payerType, $payerTypeList))
		{
			$payerType = $defaultPayer;
		}

		return array(
			'PAYER' => $payerType
		);
	}

	protected function processPostFormSettings($requestFormSettings)
	{
		if(!$requestFormSettings['DEAL_CATEGORY'] || !DealCategory::exists($requestFormSettings['DEAL_CATEGORY']))
		{
			$requestFormSettings['DEAL_CATEGORY'] = null;
		}

		$views = [];
		foreach ($requestFormSettings['VIEWS'] as $mode => $settings)
		{
			if (!in_array($mode, ['auto', 'click', 'inline']))
			{
				continue;
			}

			$settings = is_array($settings) ? $settings : [];
			$views[$mode] = array_filter(
				$settings,
				function ($value, $key)
				{
					if (!in_array($key, ['type', 'vertical', 'position', 'delay', 'title']))
					{
						return false;
					}
					if (empty($value) || !is_string($value))
					{
						return false;
					}
					if (empty($key) || !is_string($key) || is_numeric($key))
					{
						return false;
					}

					return true;
				},
				ARRAY_FILTER_USE_BOTH
			);
		}
		$requestFormSettings['VIEWS'] = $views;

		return $requestFormSettings;
	}

	public function processPost()
	{
		global $USER;
		$request = \Bitrix\Main\Context::getCurrent()->getRequest();

		$fields = is_array($request->get('FIELD')) ? $request->get('FIELD') : array();
		$presetFields = is_array($request->get('FIELD_PRESET')) ? $request->get('FIELD_PRESET') : array();
		$dependencies = is_array($request->get('DEPENDENCIES')) ? $request->get('DEPENDENCIES') : array();
		$scripts = is_array($request->get('SCRIPTS')) ? $request->get('SCRIPTS') : array();
		$languageId = isset($this->arResult['LANGUAGES'][$request->get('LANGUAGE_ID')]) ? $request->get('LANGUAGE_ID') : $this->crmWebForm->getLanguageId();

		$entityScheme = (string) $request->get('ENTITY_SCHEME');

		$fieldSynchronizer = new FieldSynchronizer;
		$fieldSynchronizer->replacePostFields($entityScheme, $fields, $dependencies);

		$useCss = $request->get('USE_CSS_TEXT') == 'Y';

		$assignedById = explode(',', $request->get('ASSIGNED_BY_ID'));
		$assignedByIdTmp = array();
		foreach($assignedById as $assignedByIdItem)
		{
			$assignedByIdItem = (int) $assignedByIdItem;
			if ($assignedByIdItem > 0)
			{
				$assignedByIdTmp[] = $assignedByIdItem;
			}
		}
		$assignedById = $assignedByIdTmp;
		if(count($assignedById) == 0)
		{
			$assignedById = array($USER->GetID());
		}


		$canUseCallBack = Loader::includeModule('voximplant');

		$params = array(
			//'ACTIVE' => $request->get('ACTIVE') == 'Y' ? 'Y' : 'N',
			'ACTIVE_CHANGE_BY' => $USER->GetID(),
			'LANGUAGE_ID' => $languageId,
			'NAME' => $request->get('NAME'),
			'CAPTION' => $request->get('CAPTION'),
			'DESCRIPTION' => $request->get('DESCRIPTION'),
			'BUTTON_CAPTION' => $request->get('BUTTON_CAPTION'),
			'ENTITY_SCHEME' => $entityScheme,
			'FIELDS' => $this->processPostFields($fields),
			'SCRIPTS' => $this->processPostScripts($scripts),
			'DEPENDENCIES' => $this->processPostDependencies($dependencies),
			'PRESET_FIELDS' => $this->processPostPresetFields($presetFields),
			'TEMPLATE_ID' => $request->get('TEMPLATE_ID'),
			'IS_PAY' => $request->get('IS_PAY') ? 'Y' : 'N',
			'COPYRIGHT_REMOVED' => $this->processPostRemoveCopyRight($request->get('COPYRIGHT_REMOVED')),
			'USE_CAPTCHA' => $this->request->get('USE_CAPTCHA') == 'Y' ? 'Y' : 'N',
			'CAPTCHA_KEY' => $this->request->get('CAPTCHA_KEY'),
			'CAPTCHA_SECRET' => $this->request->get('CAPTCHA_SECRET'),
			'DUPLICATE_MODE' => $request->get('DUPLICATE_MODE'),
			'SCRIPT_INCLUDE_SETTINGS' => array(),
			'INVOICE_SETTINGS' => $this->processPostInvoiceSettings($request->get('INVOICE_SETTINGS')),
			'FORM_SETTINGS' => $this->processPostFormSettings([
				'DEAL_CATEGORY' => $request->get('DEAL_CATEGORY'),
				'DEAL_DC_ENABLED' => $request->get('DEAL_DC_ENABLED') == 'Y' ? 'Y' : 'N',
				'DYNAMIC_CATEGORY' => $request->get('DYNAMIC_CATEGORY'),
				'NO_BORDERS' => $request->get('NO_BORDERS') == 'Y' ? 'Y' : 'N',
				'REDIRECT_DELAY' => (int) $request->get('RESULT_REDIRECT_DELAY'),
				'VIEWS' => is_array($request->get('VIEWS')) ? $request->get('VIEWS') : [],
			]),
			'ASSIGNED_BY_ID' => $assignedById,
			'ASSIGNED_WORK_TIME' => $request->get('ASSIGNED_WORK_TIME') == 'Y' ? 'Y' : 'N',

			'YANDEX_METRIC_ID' => $request->get('YANDEX_METRIC_ID'),
			'GOOGLE_ANALYTICS_ID' => $request->get('GOOGLE_ANALYTICS_ID'),
			'GOOGLE_ANALYTICS_PAGE_VIEW' => $request->get('GOOGLE_ANALYTICS_PAGE_VIEW') == 'Y' ? 'Y' : 'N',

			'CSS_PATH' => $useCss ? $request->get('CSS_PATH') : '',
			'CSS_TEXT' => $useCss ? $request->get('CSS_TEXT') : '',
			'BUTTON_COLOR_FONT' => $request->get('BUTTON_COLOR_FONT'),
			'BUTTON_COLOR_BG' => $request->get('BUTTON_COLOR_BG'),

			'USE_LICENCE' => $request->get('USE_LICENCE') == 'Y' ? 'Y' : 'N',
			'AGREEMENT_ID' => (integer) $request->get('AGREEMENT_ID'),
			'LICENCE_BUTTON_IS_CHECKED' => $request->get('LICENCE_BUTTON_IS_CHECKED') == 'Y' ? 'Y' : 'N',

			'RESULT_SUCCESS_TEXT' => $request->get('USE_RESULT_SUCCESS_TEXT') == 'Y' ? $request->get('RESULT_SUCCESS_TEXT') : '',
			'RESULT_SUCCESS_URL' => $request->get('USE_RESULT_SUCCESS_URL') == 'Y' ? $request->get('RESULT_SUCCESS_URL') : '',
			'RESULT_FAILURE_TEXT' => $request->get('USE_RESULT_FAILURE_TEXT') == 'Y' ? $request->get('RESULT_FAILURE_TEXT') : '',
			'RESULT_FAILURE_URL' => $request->get('USE_RESULT_FAILURE_URL') == 'Y' ? $request->get('RESULT_FAILURE_URL') : '',

			'IS_CALLBACK_FORM' => ($canUseCallBack && $request->get('IS_CALLBACK_FORM') == 'Y') ? 'Y' : 'N',
			'CALL_TEXT' => ($canUseCallBack) ? $request->get('CALL_TEXT') : '',
			'CALL_FROM' => ($canUseCallBack) ? $request->get('CALL_FROM') : '',
		);

		$agreements = $this->crmWebForm->get()['AGREEMENTS'] ?? [];
		if ($agreements)
		{
			if (!$request->get('AGREEMENT_ID') || $request->get('AGREEMENT_ID') !=  $this->crmWebForm->get()['AGREEMENT_ID'])
			{
				array_shift($agreements);
				$params['AGREEMENTS'] = $agreements;
			}
		}

		if ($request->get('ACTIVE') == 'Y')
		{
			$params['ACTIVE'] = 'Y';
		}

		$this->crmWebForm->merge($params);
		$this->crmWebForm->save();

		if(!$this->crmWebForm->hasErrors())
		{
			$this->processPostBackgroundImage('BACKGROUND_IMAGE');
			if($this->request->get('SCRIPTS_SEND_TO_EMAIL') == 'Y' && check_email($this->request->get('SCRIPTS_EMAIL_TO')))
			{
				$this->crmWebForm->sendScriptsEmail($this->request->get('SCRIPTS_EMAIL_TO'));
			}

			$this->redirectTo();
		}
		else
		{
			$this->errors = $this->crmWebForm->getErrors();
			$this->arResult['FORM'] = $this->crmWebForm->get();
		}
	}

	protected function redirectTo()
	{
		$isSaved = $this->request->get('save') === 'Y';
		$isReloadList = $this->arParams['RELOAD_LIST'];
		$url = ($isSaved && !$this->arParams['IFRAME']) ? $this->arParams['PATH_TO_WEB_FORM_LIST'] : $this->arParams['PATH_TO_WEB_FORM_EDIT'];

		$replaceList = array('id' => $this->crmWebForm->getId(), 'form_id' => $this->crmWebForm->getId());
		$url = CComponentEngine::makePathFromTemplate($url, $replaceList);
		if ($this->arParams['IFRAME'])
		{
			$uri = new \Bitrix\Main\Web\Uri($url);
			$uri->addParams(['IFRAME' => 'Y']);
			if ($isSaved)
			{
				$uri->addParams(['IS_SAVED' => 'Y']);
			}
			if (!$isReloadList)
			{
				$uri->addParams(['RELOAD_LIST' => 'N']);
			}
			$url = $uri->getLocator();
		}
		LocalRedirect($url);
	}

	protected function setResultCurrency()
	{
		/* Currency */
		$currencyFormatParams = \CCrmCurrency::GetCurrencyFormatParams($this->crmWebForm->getCurrencyId());
		if(!is_array($currencyFormatParams))
		{
			$this->arResult['CURRENCY'] = array(
				'SHORT_NAME' => $this->crmWebForm->getCurrencyId(),
				'FORMAT_STRING' => '# ' . $this->crmWebForm->getCurrencyId(),
				'DEC_POINT' => '.',
				'DECIMALS' => 2,
				'THOUSANDS_SEP' => ' ',
			);
		}
		else
		{
			$this->arResult['CURRENCY'] = array(
				'SHORT_NAME' => str_replace(array('# ', '#'), array('', ''), $currencyFormatParams['FORMAT_STRING']),
				'FORMAT_STRING' => $currencyFormatParams['FORMAT_STRING'],
				'DEC_POINT' => $currencyFormatParams['DEC_POINT'],
				'DECIMALS' => $currencyFormatParams['DECIMALS'],
				'THOUSANDS_SEP' => $currencyFormatParams['THOUSANDS_SEP'],
			);
		}
	}

	protected function setResultFields()
	{
		$this->arResult['FORM']['FIELDS'] = array();
		$fields = EntityFieldProvider::getFieldsDescription($this->crmWebForm->getFields());
		foreach($fields as $field)
		{
			if(!$field['CAPTION'])
			{
				$field['CAPTION'] = $field['ENTITY_FIELD_CAPTION'];
			}

			$this->arResult['FORM']['FIELDS'][] = $field;
		}
	}

	protected function setResultDependencyFields()
	{
		foreach($this->arResult['FORM']['DEPENDENCIES'] as $key => $dep)
		{
			if(!$dep['ID'])
			{
				$dep['ID'] = 'n' . mt_rand(11111, 99999);
			}

			$this->arResult['FORM']['DEPENDENCIES'][$key] = $dep;
		}
	}

	protected function setResultPresetFields()
	{
		$this->arResult['FORM']['PRESET_FIELDS'] = array();

		$presetFields = $this->crmWebForm->getPresetFields();
		foreach($this->arResult['AVAILABLE_FIELDS'] as $field)
		{
			foreach($presetFields as $presetFieldKey => $presetField)
			{
				if($presetField['ENTITY_NAME'] != $field['entity_name'])
				{
					continue;
				}

				if($presetField['FIELD_NAME'] != $field['entity_field_name'])
				{
					continue;
				}

				$presetField['CODE'] = $presetField['ENTITY_NAME'] . "_" . $presetField['FIELD_NAME'];
				$presetField['ENTITY_CAPTION'] = $field['entity_caption'];
				$presetField['ENTITY_FIELD_CAPTION'] = $field['caption'];
				$this->arResult['FORM']['PRESET_FIELDS'][] = $presetField;
				break;
			}
		}

		$this->arResult['FORM']['HAS_PRESET_FIELDS'] = count($this->arResult['FORM']['PRESET_FIELDS']) > 0;

		$this->arResult['PRESET_MACROS_LIST'] = array(
			array(
				'CODE' => '%from_domain%',
				'NAME' => Loc::getMessage('CRM_WEBFORM_EDIT_PRESET_MACROS_DOMAIN'),
				'DESC' => Loc::getMessage('CRM_WEBFORM_EDIT_PRESET_MACROS_DOMAIN_DESC'),
			),
			array(
				'CODE' => '%from_url%',
				'NAME' => Loc::getMessage('CRM_WEBFORM_EDIT_PRESET_MACROS_URL'),
				'DESC' => Loc::getMessage('CRM_WEBFORM_EDIT_PRESET_MACROS_URL_DESC'),
			),
			array(
				'CODE' => '%my_param1%',
				'NAME' => Loc::getMessage('CRM_WEBFORM_EDIT_PRESET_MACROS_PARAM'),
				'DESC' => Loc::getMessage('CRM_WEBFORM_EDIT_PRESET_MACROS_PARAM_DESC'),
			),
			array(
				'CODE' => '%crm_result_id%',
				'NAME' => Loc::getMessage('CRM_WEBFORM_EDIT_PRESET_MACROS_RESULT_ID'),
				'DESC' => Loc::getMessage('CRM_WEBFORM_EDIT_PRESET_MACROS_RESULT_ID_DESC'),
			),
			array(
				'CODE' => '%crm_form_id%',
				'NAME' => Loc::getMessage('CRM_WEBFORM_EDIT_PRESET_MACROS_FORM_ID'),
				'DESC' => Loc::getMessage('CRM_WEBFORM_EDIT_PRESET_MACROS_FORM_ID_DESC'),
			),
			array(
				'CODE' => '%crm_form_name%',
				'NAME' => Loc::getMessage('CRM_WEBFORM_EDIT_PRESET_MACROS_FORM_NAME'),
				'DESC' => Loc::getMessage('CRM_WEBFORM_EDIT_PRESET_MACROS_FORM_NAME_DESC'),
			),
		);
	}

	protected function setResultTemplates()
	{
		$templates = Helper::getTemplateList();
		foreach($templates as $templateId => $templateName)
		{
			if(!$this->arResult['FORM']['TEMPLATE_ID'])
			{
				$this->arResult['FORM']['TEMPLATE_ID'] = $templateId;
			}

			$this->arResult['TEMPLATES'][] = array(
				'ID' => $templateId,
				'CAPTION' => $templateName,
				'SELECTED' => $this->arResult['FORM']['TEMPLATE_ID'] == $templateId,
			);
		}
	}

	protected function setResultDuplicateModes()
	{
		$duplicateModes = ResultEntity::getDuplicateModeList();
		foreach($duplicateModes as $duplicateModeId => $duplicateModeCaption)
		{
			if(!isset($this->arResult['FORM']['DUPLICATE_MODE']))
			{
				$this->arResult['FORM']['DUPLICATE_MODE'] = $duplicateModeId;
			}

			$this->arResult['DUPLICATE_MODES'][] = array(
				'ID' => $duplicateModeId,
				'CAPTION' => $duplicateModeCaption,
				'SELECTED' => $this->arResult['FORM']['DUPLICATE_MODE'] == $duplicateModeId,
			);
		}
	}

	protected function setResultDealCategory()
	{
		if(!is_array($this->arResult['FORM']['FORM_SETTINGS']))
		{
			$this->arResult['FORM']['FORM_SETTINGS'] = array();
		}

		$categoryId = null;
		if($this->arResult['FORM']['FORM_SETTINGS']['DEAL_CATEGORY'])
		{
			$categoryId = $this->arResult['FORM']['FORM_SETTINGS']['DEAL_CATEGORY'];
		}

		$this->arResult['DEAL_CATEGORY_LIST'] = array();
		$categoryList = DealCategory::getAll(true);
		foreach($categoryList as $category)
		{
			$this->arResult['DEAL_CATEGORY_LIST'][] = array(
				'ID' => $category['ID'],
				'NAME' => $category['NAME'],
				'SELECTED' => $categoryId == $category['ID'],
			);
		}
	}

	protected function setResultLicence()
	{
		$this->arResult['LICENCE'] = array(
			'HAS_STANDARD' => false,
			'DESCRIPTION' => '',
			'FIELDS_DESCRIPTION' => '',
			'TYPES' => array(),
			'STANDARD_FIELDS' => array(),
		);
	}

	protected function setResultCallBackForm()
	{
		$this->arResult['CALL_BACK_FORM'] = array(
			'CAN_USE' => false,
			'CALL_FROM' => array()
		);
		if(!Loader::includeModule('voximplant'))
		{
			return;
		}

		$this->arResult['CALL_BACK_FORM']['CAN_USE'] = true;
		$callbackNumbers = \Bitrix\Crm\WebForm\Callback::getPhoneNumbers();
		foreach($callbackNumbers as $number)
		{
			$selected = $number['CODE'] == $this->arResult['FORM']['CALL_FROM'];
			$this->arResult['CALL_BACK_FORM']['CALL_FROM'][] = array(
				'ID' => $number['CODE'],
				'VALUE' => $number['NAME'],
				'SELECTED' => $selected,
			);
		}
	}

	protected function setResultEntitySchemes()
	{
		$allowedEntitySchemes = $this->crmWebForm->getAllowedEntitySchemes();
		$this->arResult['ENTITY_SCHEMES'] = Entity::getSchemesByInvoice(
			$this->arResult['FORM']['ENTITY_SCHEME']
			/*,$allowedEntitySchemes*/
		);
	}

	protected function setResultDynamicEntities()
	{
		$this->arResult['DYNAMIC_ENTITIES'] = Crm\WebForm\Options\Dictionary::instance()->getDocument()['dynamic'];
	}

	protected function prepareResultCaptcha()
	{
		$this->arResult['CAPTCHA'] = array(
			'KEY' => ReCaptcha::getKey(),
			'SECRET' => ReCaptcha::getSecret(),
			'HAS_OWN_KEY' => ReCaptcha::getKey() && ReCaptcha::getSecret(),
			'HAS_DEFAULT_KEY' => ReCaptcha::getDefaultKey() && ReCaptcha::getDefaultSecret()
		);
	}

	public function initInvoicePayerTypes()
	{
		$this->arResult['INVOICE_PAYER_TYPES'] = array(
			ResultEntity::INVOICE_PAYER_CONTACT => array(
				'ID' => ResultEntity::INVOICE_PAYER_CONTACT,
				'SELECTED' => true,
			),
			ResultEntity::INVOICE_PAYER_COMPANY => array(
				'ID' => ResultEntity::INVOICE_PAYER_COMPANY,
				'SELECTED' => false,
			),
		);

		if(!$this->arResult['FORM']['INVOICE_SETTINGS'])
		{
			return;
		}

		if(!$this->arResult['FORM']['INVOICE_SETTINGS']['PAYER'])
		{
			return;
		}

		if(!in_array($this->arResult['FORM']['INVOICE_SETTINGS']['PAYER'], array_keys($this->arResult['INVOICE_PAYER_TYPES'])))
		{
			return;
		}

		foreach($this->arResult['INVOICE_PAYER_TYPES'] as $payerTypeId => $payerType)
		{
			$isSelected = $payerTypeId == $this->arResult['FORM']['INVOICE_SETTINGS']['PAYER'];
			$this->arResult['INVOICE_PAYER_TYPES'][$payerTypeId]['SELECTED'] = $isSelected;
		}
	}

	protected function setResultActions()
	{
		if (isset($this->arResult['FORM']['FORM_SETTINGS']['REDIRECT_DELAY']))
		{
			$delay = (int) $this->arResult['FORM']['FORM_SETTINGS']['REDIRECT_DELAY'];
		}
		else
		{
			$delay = Form::REDIRECT_DELAY;
		}

		$list = array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10);
		$this->arResult['RESULT_REDIRECT_DELAY_LIST'] = array();
		foreach ($list as $item)
		{
			$this->arResult['RESULT_REDIRECT_DELAY_LIST'][] = array(
				'VALUE' => $item,
				'NAME' => $item . ' ' . Loc::getMessage('CRM_WEBFORM_EDIT_SECOND_SHORT'),
				'SELECTED' => $item == $delay
			);
		}
	}

	protected function setResultAdsForm()
	{
		$this->arResult['FORM']['HAS_ADS_FORM_LINKS'] = AdsForm::hasFormLinks($this->arResult['FORM']['ID']) ? 'Y' : 'N';

		$this->arResult['ADS_FORM'] = array();
		if (AdsForm::canUse() && $this->crmWebForm->getId())
		{
			$adsTypes = AdsForm::getServiceTypes();
			foreach ($adsTypes as $adsType)
			{
				$this->arResult['ADS_FORM'][] = array(
					'TYPE' => $adsType,
					'NAME' => AdsForm::getServiceTypeName($adsType),
					'HAS_LINKS' => AdsForm::hasFormLinks($this->crmWebForm->getId(), $adsType),
					'PATH_TO_ADS' => CComponentEngine::makePathFromTemplate(
						$this->arParams['PATH_TO_WEB_FORM_ADS'],
						['id' => $this->crmWebForm->getId(), 'ads_type' => $adsType]
					),
				);
			}
		}
	}

	public function setResultAssignedBy()
	{
		$this->arResult['ASSIGNED_BY'] = array(
			'LIST' => array(),
			'WORK_TIME' => false,
			'IS_SUPPORTED_WORK_TIME' => false
		);

		$responsibleQueue = new ResponsibleQueue($this->crmWebForm->getId());
		$list = $responsibleQueue->getList();
		$this->arResult['ASSIGNED_BY']['IS_SUPPORTED_WORK_TIME'] = $responsibleQueue->isSupportedWorkTime();
		$this->arResult['ASSIGNED_BY']['WORK_TIME'] = $responsibleQueue->isWorkTimeCheckEnabled();

		foreach ($list as $item)
		{
			$userFields = \Bitrix\Main\UserTable::getRowById($item);
			if (!$userFields)
			{
				continue;
			}

			$this->arResult['ASSIGNED_BY']['LIST'][] = array(
				'ID' => $item,
				'NAME' => CUser::FormatName(
					$this->arParams['NAME_TEMPLATE'],
					array(
						'LOGIN' => $userFields['LOGIN'],
						'NAME' => $userFields['NAME'],
						'LAST_NAME' => $userFields['LAST_NAME'],
						'SECOND_NAME' => $userFields['SECOND_NAME']
					),
					true, false
				)
			);
		}

		if (count($this->arResult['ASSIGNED_BY']['LIST']) == 0)
		{
			/*@global $USER \CUser*/
			global $USER;
			$userId = $USER->GetID();
			$userFields = \Bitrix\Main\UserTable::getRowById($userId);

			$this->arResult['ASSIGNED_BY']['LIST'][] = array(
				'ID' => $userId,
				'NAME' => CUser::FormatName(
					$this->arParams['NAME_TEMPLATE'],
					array(
						'LOGIN' => $userFields['LOGIN'],
						'NAME' => $userFields['NAME'],
						'LAST_NAME' => $userFields['LAST_NAME'],
						'SECOND_NAME' => $userFields['SECOND_NAME']
					),
					true, false
				)
			);
		}

		$this->arResult['CONFIG_ASSIGNED_BY'] = array(
			'valueInputName' => 'ASSIGNED_BY_ID',
			'selected'       => array(),
			'multiple' => true,
			'required' => true,
		);
		foreach ($this->arResult['ASSIGNED_BY']['LIST'] as $assignedBy)
		{
			$this->arResult['CONFIG_ASSIGNED_BY']['selected'][] = array(
				'id'         => 'U' . (int) $assignedBy['ID'],
				'entityId'   => (int) $assignedBy['ID'],
				'entityType' => 'users',
				'name'       => htmlspecialcharsBx($assignedBy['NAME']),
				'avatar' => '',
				'desc' => '&nbsp;'
			);
		}
	}

	public function prepareResult()
	{
		$this->arResult['FORM'] = array();

		/**@var $USER \CUser*/
		global $USER;
		$CrmPerms = new CCrmPerms($USER->GetID());

		if($CrmPerms->HavePerm('WEBFORM', BX_CRM_PERM_NONE))
		{
			$this->errors[] = Loc::getMessage('CRM_PERMISSION_DENIED');
			$this->showErrors();
			return false;
		}

		$id = $this->arParams['ELEMENT_ID'];
		$this->arResult['PERM_CAN_EDIT'] = !$CrmPerms->HavePerm('WEBFORM', BX_CRM_PERM_NONE, 'WRITE');
		if (!$this->arResult['PERM_CAN_EDIT'])
		{
			$this->errors[] = Loc::getMessage('CRM_PERMISSION_DENIED');
			$this->showErrors();
			return false;
		}

		$this->arResult['AVAILABLE_FIELDS'] = EntityFieldProvider::getFields();
		$this->arResult['AVAILABLE_FIELDS_TREE'] = EntityFieldProvider::getFieldsTree();
		$this->arResult['PRESET_AVAILABLE_FIELDS_TREE'] = EntityFieldProvider::getPresetFieldsTree();
		$this->arResult['AVAILABLE_ENTITIES'] = Entity::getList();

		$this->crmWebForm = new Form($id);

		/* Set form data */
		$this->arResult['LANGUAGES'] = \Bitrix\Crm\SiteButton\Manager::getLanguages();
		foreach($this->arResult['LANGUAGES'] as $languageId => $language)
		{
			$language['SELECTED'] = $this->crmWebForm->getLanguageId() === $languageId;
			$this->arResult['LANGUAGES'][$languageId] = $language;

		}
		$this->arResult['IS_AVAILABLE_EMBEDDING_PORTAL'] = Manager::isEmbeddingAvailable();
		$this->arResult['IS_AVAILABLE_EMBEDDING'] = $this->crmWebForm->isEmbeddingAvailable();
		$this->arResult['FORM'] = $this->crmWebForm->get();
		$this->arResult['FORM']['BACKGROUND_IMAGE_PATH'] = \CFile::GetPath($this->arResult['FORM']['BACKGROUND_IMAGE']);
		$this->arResult['FORM']['IS_READONLY'] = (
			!$this->arResult['PERM_CAN_EDIT']
			||
			$this->arResult['FORM']['HAS_ADS_FORM_LINKS'] == 'Y'
		) ? 'Y' : 'N';

		/* Case for making new callback form from contact-center*/
		if (intval($id) === 0)
		{
			$canUseCallBack = Loader::includeModule('voximplant');
			$this->arResult['FORM']['IS_CALLBACK_FORM'] = $canUseCallBack && ($this->request->get('IS_CALLBACK_FORM') === 'Y') ? 'Y' : false;

			$formData = [
				'XML_ID' => '',
				'ACTIVE' => 'Y',
				'CAPTION' => '',
				'DESCRIPTION' => '',
				'IS_SYSTEM' => 'N',
				'ACTIVE_CHANGE_BY' => is_object($GLOBALS['USER']) ? $GLOBALS['USER']->getId() : null,
				'IS_CALLBACK_FORM' => $this->arResult['FORM']['IS_CALLBACK_FORM'] ?: 'N',
			];

			$agreementId = UserConsent::getDefaultAgreementId();
			$formData['USE_LICENCE'] = $agreementId ? 'Y': 'N';
			if ($agreementId)
			{
				$formData['AGREEMENTS'] = [[
					'ID' => $agreementId,
					'CHECKED' => 'Y',
					'REQUIRED' => 'Y',
				]];
			}

			if(!$formData['BUTTON_CAPTION'])
			{
				$formData['BUTTON_CAPTION'] = $this->crmWebForm->getButtonCaption();
			}

			$formData = $formData + ($this->arResult['FORM']['IS_CALLBACK_FORM']
				? WebForm\Preset::getCallback('', '')
				: WebForm\Preset::getById('crm_preset_cd')
			);
			$formData['TEMPLATE_ID'] = !$this->arResult['FORM']['IS_CALLBACK_FORM']
				? 'contacts'
				: 'callback';

			// add date to form name
			$culture = \Bitrix\Main\Application::getInstance()->getContext()->getCulture();
			$formData['NAME'] = Loc::getMessage(
				'CRM_WEBFORM_SCENARIO_NAME_TEMPLATE',
				[
					'#NAME#' => $formData['NAME'],
					'#DATE#' => FormatDate($culture->getDayMonthFormat(), new Date()),
				]
			);

			$this->crmWebForm->merge($formData);
			$this->crmWebForm->save();
			if ($this->crmWebForm->hasErrors())
			{
				$this->errors = $this->crmWebForm->getErrors();
				$this->showErrors();
				return true;
			}
		}

		$this->arResult['FORM_ACTION'] = $GLOBALS['APPLICATION']->GetCurPageParam();

		//////////////////////////////////////
		//// REDIRECTED (compatible mode)
		//////////////////////////////////////
		$landingUrl = WebForm\Internals\LandingTable::getLandingEditUrl($this->crmWebForm->getId());
		if ($landingUrl)
		{
			if (intval($id) === 0)
			{
				$landingUrlParams = ['formCreated' => 'y'];

				if ($preset = $this->request->get('PRESET'))
				{
					if (preg_match('#^[A-Za-z0-9-_]+$#D', $preset))
					{
						$landingUrlParams['preset'] = $preset;
					}
				}

				$landingUrl = (new \Bitrix\Main\Web\Uri($landingUrl))->addParams($landingUrlParams)->getLocator();
			}

			if ($this->request->get('IFRAME') === 'Y')
			{
				echo "<script>top.location.href='" . \CUtil::JSEscape($landingUrl) . "';</script>";
				return false;
			}
			else
			{
				LocalRedirect($landingUrl);
			}
			return true;
		}

		$this->errors[] = Loc::getMessage('CRM_MODULE_ERROR_NOT_FOUNT');
		$this->showErrors();
		return false;
	}

	public function checkParams()
	{
		$this->arParams['NAME_TEMPLATE'] = empty($this->arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $this->arParams["NAME_TEMPLATE"]);
		$this->arParams['IFRAME'] = isset($this->arParams['IFRAME']) ? (bool) $this->arParams['IFRAME'] : $this->request->get('IFRAME') === 'Y';
		$this->arParams['IS_SAVED'] = $this->request->get('IS_SAVED') === 'Y';
		$this->arParams['RELOAD_LIST'] = isset($this->arParams['RELOAD_LIST']) ? (bool) $this->arParams['RELOAD_LIST'] : $this->request->get('RELOAD_LIST') !== 'N';

		return true;
	}

	public function executeComponent()
	{
		global $APPLICATION;
		$APPLICATION->SetTitle(Loc::getMessage('CRM_WEBFORM_EDIT_TITLE'));

		if (!$this->checkModules())
		{
			$this->showErrors();
			return;
		}

		if (!$this->checkParams())
		{
			$this->showErrors();
			return;
		}

		if (intval($this->arParams['ELEMENT_ID']) <= 0)
		{
			$APPLICATION->SetTitle(Loc::getMessage('CRM_WEBFORM_EDIT_TITLE_ADD'));
		}
		else
		{
			$APPLICATION->SetTitle(Loc::getMessage('CRM_WEBFORM_EDIT_TITLE'));
		}

		if (!$this->prepareResult())
		{
			return;
		}

		if ($this->arResult['FORM']['ID'] && $this->arResult['FORM']['IS_READONLY'] == 'Y')
		{
			$APPLICATION->SetTitle(Loc::getMessage('CRM_WEBFORM_EDIT_TITLE_VIEW'));
		}

		$this->includeComponentTemplate();
	}

	protected function checkModules()
	{
		if(!Loader::includeModule('crm'))
		{
			$this->errors[] = Loc::getMessage('CRM_MODULE_NOT_INSTALLED');
			return false;
		}

		if(!CAllCrmInvoice::installExternalEntities())
		{
			return false;
		}

		if(!CCrmQuote::LocalComponentCausedUpdater())
		{
			return false;
		}

		if(!Loader::includeModule('currency'))
		{
			$this->errors[] = Loc::getMessage('CRM_MODULE_NOT_INSTALLED_CURRENCY');
			return false;
		}

		if(!Loader::includeModule('catalog'))
		{
			$this->errors[] = Loc::getMessage('CRM_MODULE_NOT_INSTALLED_CATALOG');
			return false;
		}

		if(!Loader::includeModule('sale'))
		{
			$this->errors[] = Loc::getMessage('CRM_MODULE_NOT_INSTALLED_CATALOG');
			return false;
		}

		if (!Crm\Integration\Landing\FormLanding::getInstance()->canUse())
		{
			$this->errors[] = Loc::getMessage('CRM_MODULE_NOT_INSTALLED_LANDING');
			return false;
		}

		return true;
	}

	protected function hasErrors()
	{
		return (count($this->errors) > 0);
	}

	protected function showErrors()
	{
		if(count($this->errors) <= 0)
		{
			return;
		}

		$this->arResult['ERRORS'] = $this->errors;
		$this->includeComponentTemplate('unavailable');
	}
}