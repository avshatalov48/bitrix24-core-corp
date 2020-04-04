<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2016 Bitrix
 */
namespace Bitrix\Crm\WebForm;

use Bitrix\Crm\WebForm\Internals\FieldDependenceTable;
use Bitrix\Crm\WebForm\Internals\FormStartEditTable;
use Bitrix\Crm\WebForm\Internals\FormTable;
use Bitrix\Crm\WebForm\Internals\FieldTable;
use Bitrix\Crm\WebForm\Internals\FormViewTable;
use Bitrix\Crm\WebForm\Internals\FormCounterTable;
use Bitrix\Crm\WebForm\Internals\PresetFieldTable;
use Bitrix\Crm\WebForm\ReCaptcha;
use Bitrix\Main\Application;
use Bitrix\Main\EventManager;
use Bitrix\Main\Event;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Entity\Result as EntityResult;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Mail\Event as MailEvent;
use Bitrix\Main\Context;
use Bitrix\Main\Config\Option;

Loc::loadMessages(__FILE__);

class Form
{
	const REDIRECT_DELAY = 5;

	protected $id = null;
	protected static $defaultParams = array(
		'ACTIVE' => 'N',
		'IS_SYSTEM' => 'N',
		'COPYRIGHT_REMOVED' => 'N',
		'USE_CAPTCHA' => 'N',
		'USE_LICENCE' => 'N',
		'LICENCE_BUTTON_IS_CHECKED' => 'Y',
		'FIELDS' => array(),
		'PRESET_FIELDS' => array(),
		'INVOICE_SETTINGS' => array(),
		'FORM_SETTINGS' => array(),
		'DEPENDENCIES' => array()
	);
	protected $params = array();
	protected $errors = array();

	public function __construct($id = null, array $params = null)
	{
		$this->params = self::$defaultParams;

		if($id)
		{
			$this->load($id);
		}

		if($params)
		{
			$this->set($params);
		}
	}

	public function isSystem()
	{
		return $this->params['IS_SYSTEM'] == 'Y';
	}

	public function setSystem()
	{
		$this->params['IS_SYSTEM'] = 'Y';
	}

	public function set(array $params)
	{
		$this->params = $params;
	}

	public function get()
	{
		return $this->params;
	}

	public function merge($params)
	{
		$this->set($params + $this->get());
	}

	public static function getIdByCode($formCode)
	{
		$idByCode = array();

		$formCodePieces = explode('_', $formCode);
		if(is_numeric($formCodePieces[0]))
		{
			return (int) $formCodePieces[0];
		}

		$cacheId = 'crm_webform_getIdByCode_' . serialize($formCode);
		$cache = \Bitrix\Main\Data\Cache::createInstance();
		if($cache->startDataCache(36000, $cacheId))
		{
			$formDb = FormTable::getList(array(
				'select' => array('ID', 'CODE'),
				'filter' => array('=CODE' => $formCode),
				'limit' => 1
			));
			while($form = $formDb->fetch())
			{
				$idByCode[$form['CODE']] = $form['ID'];
			}

			if(isset($idByCode[$formCode]))
			{
				if(defined("BX_COMP_MANAGED_CACHE"))
				{
					global $CACHE_MANAGER;
					$CACHE_MANAGER->startTagCache($cache->getPath($cacheId));
					$CACHE_MANAGER->RegisterTag(Form::getCacheTag($idByCode[$formCode]));
				}
			}

			$cache->endDataCache(array('CODE_BY_ID' => $idByCode));

			if(isset($idByCode[$formCode]))
			{
				if(defined("BX_COMP_MANAGED_CACHE"))
				{
					global $CACHE_MANAGER;
					$CACHE_MANAGER->endTagCache();
				}
			}
		}
		else
		{
			$cacheVars = $cache->getVars();
			if(isset($cacheVars['CODE_BY_ID']))
			{
				$idByCode = $cacheVars['CODE_BY_ID'];
			}
		}

		return isset($idByCode[$formCode]) ? $idByCode[$formCode] : null;
	}

	public static function updateBackgroundImage($formId, $fileId)
	{
		$updateResult = FormTable::update($formId, array('BACKGROUND_IMAGE' => $fileId));
		return $updateResult->isSuccess();
	}

	public static function delete($formId, $forceSystem = false)
	{
		$form = Internals\FormTable::getRowById($formId);
		if(!$form || (!$forceSystem && $form['IS_SYSTEM'] == 'Y'))
		{
			return false;
		}

		$deleteResult = Internals\FormTable::delete($formId);
		if($deleteResult->isSuccess())
		{
			static::cleanCacheByTag($formId);
			return true;
		}
		else
		{
			return false;
		}
	}

	public function isActive()
	{
		return $this->params['ACTIVE'] == 'Y';
	}

	public function isUsedCaptcha()
	{
		return $this->params['USE_CAPTCHA'] == 'Y';
	}

	public function isCallback()
	{
		return $this->params['IS_CALLBACK_FORM'] == 'Y';
	}

	public function checkSecurityCode($code)
	{
		return $this->params['SECURITY_CODE'] === $code;
	}

	public function loadOnlyForm($id)
	{
		$this->setId($id);
		$result = Internals\FormTable::getRowById($id);
		if(!$result)
		{
			return false;
		}

		$this->params = $result;
		return true;
	}

	public function load($id)
	{
		$this->setId($id);
		if(count($this->params) == count(self::$defaultParams) && !$this->loadOnlyForm($this->id))
		{
			return false;
		}

		$this->params['PRESET_FIELDS'] = array();
		$dbPresetField = PresetFieldTable::getList(array(
			'select' => array('ENTITY_NAME', 'FIELD_NAME', 'VALUE'),
			'filter' => array('=FORM_ID' => $id)
		));
		while($presetField = $dbPresetField->fetch())
		{
			$this->params['PRESET_FIELDS'][] = $presetField;
		}

		$fieldResult = FieldTable::getList(array(
			'filter' => array('=FORM_ID' => $id),
			'order' => array('SORT' => 'ASC', 'CAPTION')
		));
		$fieldResult->addFetchDataModifier(
			function ($data)
			{
				$data['ITEMS'] = is_array($data['ITEMS']) ? $data['ITEMS'] : [];
				return $data;
			}
		);
		$this->params['FIELDS'] = $fieldResult->fetchAll();


		$this->params['DEPENDENCIES'] = Internals\FieldDependenceTable::getList(array(
			'filter' => array('=FORM_ID' => $id)
		))->fetchAll();

		return true;
	}

	public function save($onlyCheck = false)
	{
		$this->errors = array();
		$result = $this->params;

		$scripts = $result['SCRIPTS'];
		unset($result['SCRIPTS']);

		$fields = $result['FIELDS'];
		unset($result['FIELDS']);

		$dependencies = $result['DEPENDENCIES'];
		unset($result['DEPENDENCIES']);

		$presetFields = $result['PRESET_FIELDS'];
		unset($result['PRESET_FIELDS']);

		$assignedById = $result['ASSIGNED_BY_ID'];
		$assignedWorkTime = $result['ASSIGNED_WORK_TIME'];
		unset($result['ASSIGNED_BY_ID']);
		unset($result['ASSIGNED_WORK_TIME']);

		// captcha
		$captchaKey = isset($result['CAPTCHA_KEY']) ? $result['CAPTCHA_KEY'] : null;
		$captchaSecret = isset($result['CAPTCHA_SECRET']) ? $result['CAPTCHA_SECRET'] : null;
		if ($captchaKey !== null && $captchaSecret !== null)
		{
			ReCaptcha::setKey($captchaKey, $captchaSecret);
		}
		unset($result['CAPTCHA_KEY']);
		unset($result['CAPTCHA_SECRET']);

		if($onlyCheck)
		{
			if(!in_array($result['ENTITY_SCHEME'], $this->getAllowedEntitySchemes()))
			{
				$this->errors[] = Loc::getMessage('CRM_WEBFORM_FORM_ERROR_SCHEME');
			}

			// captcha
			if($result['USE_CAPTCHA'] == 'Y')
			{
				$hasCaptchaKey = ((strlen(ReCaptcha::getKey()) > 0) ? 1 : 0) + ((strlen(ReCaptcha::getSecret()) > 0) ? 1 : 0);
				$hasCaptchaDefaultKey = strlen(ReCaptcha::getDefaultKey()) > 0 && strlen(ReCaptcha::getDefaultSecret()) > 0;
				if ($hasCaptchaKey == 1 || ($hasCaptchaKey == 0 && !$hasCaptchaDefaultKey))
				{
					$this->errors[] = Loc::getMessage('CRM_WEBFORM_FORM_ERROR_CAPTCHA_KEY');
				}
			}

			$formResult = new EntityResult;
			$result['DATE_CREATE'] = new DateTime();
			Internals\FormTable::checkFields($formResult, $this->id, $result);
			$this->prepareResult('FIELDS', $formResult);

			foreach($presetFields as $presetField)
			{
				$presetField['FORM_ID'] = (int) $this->id;
				$presetFieldResult = new EntityResult;
				Internals\PresetFieldTable::checkFields($presetFieldResult, null, $presetField);
				$replaceList = null;
				if(!$presetFieldResult->isSuccess())
				{
					$field = EntityFieldProvider::getField($presetField['ENTITY_NAME'] . '_' . $presetField['FIELD_NAME']);
					if($field)
					{
						$replaceList = array('VALUE' => $field['caption']);
					}
				}
				$this->prepareResult('PRESET_FIELDS', $presetFieldResult, $replaceList);
			}

			$fieldCodeList = array();
			foreach($fields as $field)
			{
				$field['FORM_ID'] = (int) $this->id;

				$fieldResult = new EntityResult;
				Internals\FieldTable::checkFields($fieldResult, null, $field);
				$this->prepareResult('FIELDS', $fieldResult);

				$fieldCodeList[] = $field['CODE'];
			}

			foreach($dependencies as $dependency)
			{
				$dependency['FORM_ID'] = (int) $this->id;

				if(!in_array($dependency['IF_FIELD_CODE'], $fieldCodeList))
				{
					continue;
				}
				if(!in_array($dependency['DO_FIELD_CODE'], $fieldCodeList))
				{
					continue;
				}

				$dependencyResult = new EntityResult;
				Internals\FieldDependenceTable::checkFields($dependencyResult, null, $dependency);
				$this->prepareResult('DEPENDENCIES', $dependencyResult);
			}

			return;
		}

		if(!$this->check())
		{
			return;
		}

		if($this->id)
		{
			unset($result['ID']);
			$formResult = Internals\FormTable::update($this->id, $result);
		}
		else
		{
			$result['DATE_CREATE'] = new DateTime();
			$formResult = Internals\FormTable::add($result);
			$this->id = $formResult->getId();
		}

		if(!$formResult->isSuccess())
		{
			return;
		}

		/* RESPONSIBLE QUEUE */
		$assignedById = is_array($assignedById) ? $assignedById : array($assignedById);
		$responsibleQueue = new ResponsibleQueue($this->id);
		$responsibleQueue->setList($assignedById, $assignedWorkTime == 'Y');

		/* PRESET FIELDS */
		Internals\PresetFieldTable::delete(array('FORM_ID' => $this->id));
		foreach($presetFields as $presetField)
		{
			$presetFieldResult = Internals\PresetFieldTable::add(array(
				'ENTITY_NAME' => $presetField['ENTITY_NAME'],
				'FIELD_NAME' => $presetField['FIELD_NAME'],
				'VALUE' => $presetField['VALUE'],
				'FORM_ID' => $this->id
			));
			$this->prepareResult('PRESET_FIELDS', $presetFieldResult);
		}


		/* FIELDS */
		$existedFieldList = array();
		$existedFieldDb = Internals\FieldTable::getList(array(
			'select' => array('ID'),
			'filter' => array('=FORM_ID' => $this->id)
		));
		while($existedField = $existedFieldDb->fetch())
		{
			$existedFieldList[] = $existedField['ID'];
		}

		$newFieldList = array();
		foreach($fields as $field)
		{
			$field['FORM_ID'] = $this->id;

			if($field['ID'] > 0)
			{
				$fieldId = $field['ID'];
				unset($field['ID']);
				$fieldResult = Internals\FieldTable::update($fieldId, $field);
				$newFieldList[] = $fieldId;
			}
			else
			{
				$fieldResult = Internals\FieldTable::add($field);
			}

			$this->prepareResult('FIELDS', $fieldResult);
		}

		$deleteFieldList = array_diff($existedFieldList, $newFieldList);
		foreach($deleteFieldList as $deleteFieldId)
		{
			Internals\FieldTable::delete($deleteFieldId);
		}

		/* DEPENDENCIES */
		$fieldCodeList = array();
		$fieldCodeDb = Internals\FieldTable::getList(array(
			'select' => array('CODE'),
			'filter' => array('=FORM_ID' => $this->id)
		));
		while($fieldCode = $fieldCodeDb->fetch())
		{
			$fieldCodeList[] = $fieldCode['CODE'];
		}

		$fieldDepDb = Internals\FieldDependenceTable::getList(array('filter' => array('=FORM_ID' => $this->id)));
		while($fieldDep = $fieldDepDb->fetch())
		{
			Internals\FieldDependenceTable::delete($fieldDep['ID']);
		}
		foreach($dependencies as $dependency)
		{
			if(!in_array($dependency['IF_FIELD_CODE'], $fieldCodeList))
			{
				continue;
			}
			if(!in_array($dependency['DO_FIELD_CODE'], $fieldCodeList))
			{
				continue;
			}

			$dependency['FORM_ID'] = $this->id;

			$dependencyResult = Internals\FieldDependenceTable::add($dependency);
			$this->prepareResult('DEPENDENCIES', $dependencyResult);
		}

		$this->cleanCacheByTag($this->id);
	}

	public function getErrors()
	{
		return $this->errors;
	}

	public function hasErrors()
	{
		return count($this->errors) > 0;
	}

	protected function prepareResult($sect, EntityResult $entityResult, $replaceList = null)
	{
		if($entityResult->isSuccess())
		{
			return;
		}

		$errors = $entityResult->getErrors();
		foreach($errors as $error)
		{
			$errorMessage = $error->getMessage();
			if($replaceList)
			{
				$errorMessage = str_replace(array_keys($replaceList), array_values($replaceList), $errorMessage);
			}

			switch ($sect)
			{
				case 'PRESET_FIELDS':
					$errorMessage = Loc::getMessage('CRM_WEBFORM_FORM_PRESET_FIELDS') . ": " . $errorMessage;
					break;
			}

			$this->errors[] = $errorMessage;
		}
	}

	public function check()
	{
		$this->save(true);

		return count($this->errors) === 0;
	}

	public function setId($id)
	{
		$this->id = $id;
	}

	public function getId()
	{
		return $this->id;
	}

	public function getButtonCaption()
	{
		return $this->params['BUTTON_CAPTION'] ? $this->params['BUTTON_CAPTION'] : Loc::getMessage('CRM_WEBFORM_FORM_BUTTON_CAPTION_DEFAULT');
	}

	public function getAgreementUrl()
	{
		return Script::getAgreementUrl($this->get());
	}

	public function getSuccessPageUrl()
	{
		return $this->params['RESULT_SUCCESS_URL'] ?: Script::getSuccessPageUrl($this->get());
	}

	public function getFields()
	{
		return $this->params['FIELDS'];
	}

	public function getAllowedEntitySchemes()
	{
		//TODO: fields checker
		return array_keys(Entity::getSchemes());
		//return array(Helper::ENUM_ENTITY_SCHEME_CONTACT);
	}

	public function getPresetFields()
	{
		return $this->params['PRESET_FIELDS'];
	}

	public function getDependencies()
	{
		$dependencyList = array();
		foreach($this->params['DEPENDENCIES'] as $dependency)
		{
			$dependencyList[$dependency['DO_FIELD_CODE']][] = array(
				'if' => array(
					'fieldname' => $dependency['IF_FIELD_CODE'],
					'action' => $dependency['IF_ACTION'],
					'value' => $dependency['IF_VALUE'],
				),
				'do' => array(
					'action' => $dependency['DO_ACTION'],
					'value' => $dependency['DO_VALUE'],
				),
			);

			// add mirror dependency
			if($dependency['IF_ACTION'] != 'change')
			{
				continue;
			}

			if(!in_array($dependency['DO_ACTION'], array('show', 'hide')))
			{
				continue;
			}

			$mirror = $dependency['DO_ACTION'] == 'hide' ? 'show' : 'hide';
			$dependencyList[$dependency['DO_FIELD_CODE']][] = array(
				'if' => array(
					'fieldname' => $dependency['IF_FIELD_CODE'],
					'action' => $dependency['IF_ACTION'],
					'value' => $dependency['IF_VALUE'],
					'operation' => '!='
				),
				'do' => array(
					'action' => $mirror,
				),
			);
		}

		return $dependencyList;
	}

	public function getFieldsDescription()
	{
		return EntityFieldProvider::getFieldsDescription($this->getFields());
	}

	public function getFieldsMap()
	{
		$fields = $this->getFieldsDescription();
		$dependencyList = $this->getDependencies();
		$currencyId = $this->getCurrencyId();

		$fieldList = array();
		foreach($fields as $field)
		{

			if($field['TYPE'] == 'section')
			{
				$preparedField = array(
					'type' => $field['TYPE'],
					'name' => $field['CODE'],
					'caption' => $field['CAPTION'],
				);
			}
			else
			{
				$preparedField = array(
					'type' => $field['TYPE'],
					'type_original' => $field['TYPE_ORIGINAL'],
					'name' => $field['CODE'], // 'uf_field_' . $field['ID'],
					'entity_name' => $field['ENTITY_NAME'],
					'entity_field_name' => $field['ENTITY_FIELD_NAME'],
					'caption' => $field['CAPTION'] ? $field['CAPTION'] : $field['ENTITY_FIELD_CAPTION'],
					'required' => $field['REQUIRED'] == 'Y' ? true : false,
					'multiple' => $field['MULTIPLE'] == 'Y' ? true : false,
					'multiple_original' => $field['MULTIPLE_ORIGINAL'],
					'hidden' => false,
					'placeholder' => $field['PLACEHOLDER'],
					'value' => $field['VALUE'],
					'value_type' => $field['VALUE_TYPE'],
					'settings_data' => $field['SETTINGS_DATA']
				);

				if(isset($field['ITEMS']))
				{
					$preparedField['items'] = array();
					foreach($field['ITEMS'] as $item)
					{
						$price = isset($item['PRICE']) ? $item['PRICE'] : null;
						if ($price !== null && !is_numeric($price))
						{
							$price = 0;
						}
						$preparedItem = array(
							'title' => $item['VALUE'],
							'value' => $item['ID'],
						);
						if ($price !== null)
						{
							$preparedItem['price'] = $price;
							$preparedItem['price_formatted'] = \CCrmCurrency::MoneyToString($price, $currencyId);
						}
						$preparedField['items'][] = $preparedItem;
					}
				}
			}

			if(isset($dependencyList[$field['CODE']]))
			{
				$preparedField['dependences'] = $dependencyList[$field['CODE']];
				$preparedField['hidden'] = true;
			}

			$fieldList[] = $preparedField;
		}

		return $fieldList;
	}

	public function getExternalAnalyticsData()
	{
		$data = Helper::getExternalAnalyticsData($this->params['CAPTION']);
		$steps = array();

		$steps[] = array(
			'NAME' => $data['view']['name'],
			'CODE' => $data['view']['code']
		);
		$steps[] = array(
			'NAME' => $data['start']['name'],
			'CODE' => $data['start']['code']
		);
		foreach($this->getFieldsMap() as $field)
		{
			if(Internals\FieldTable::isUiFieldType($field['type']))
			{
				continue;
			}

			$steps[] = array(
				'NAME' => str_replace('%name%', $field['caption'], $data['field']['name']),
				'CODE' => str_replace('%code%', $field['name'], $data['field']['code']),
			);
		}
		$steps[] = array(
			'NAME' => $data['end']['name'],
			'CODE' => $data['end']['code']
		);

		foreach($steps as $stepIndex => $step)
		{
			$step['NAME'] = str_replace('%name%', $step['NAME'], $data['template']['name']);
			$step['EVENT'] = str_replace(array('%code%', '%form_id%'), array($step['CODE'], (int) $this->getId()), $data['eventTemplate']['code']);
			$step['CODE'] = str_replace('%code%', $step['CODE'], $data['template']['code']);
			$steps[$stepIndex] = $step;
		}

		return array(
			'CATEGORY' => $data['category'],
			'STEPS' => $steps
		);
	}

	public function getCurrencyId()
	{
		return $this->params['CURRENCY_ID'] ? $this->params['CURRENCY_ID'] : \CCrmCurrency::GetBaseCurrencyID();
	}

	public static function copy($formId, $userId = null)
	{
		// copy form
		$form = FormTable::getRowById($formId);
		if(!$form)
		{
			return null;
		}

		unset($form['ID'], $form['DATE_CREATE'], $form['ACTIVE_CHANGE_DATE'], $form['SECURITY_CODE'], $form['XML_ID']);
		$form['NAME'] = Loc::getMessage('CRM_WEBFORM_FORM_COPY_NAME_PREFIX') . ' ' . $form['NAME'];
		$form['ACTIVE'] = 'N';
		$form['IS_SYSTEM'] = 'N';
		$form['ACTIVE_CHANGE_BY'] = $userId;
		$form['DATE_CREATE'] = new DateTime();
		$resultFormAdd = FormTable::add($form);
		if(!$resultFormAdd->isSuccess())
		{
			return null;
		}
		$newFormId = $resultFormAdd->getId();

		// copy fields
		$fieldDb = FieldTable::getList(array(
			'filter' => array('=FORM_ID' => $formId)
		));
		while($field = $fieldDb->fetch())
		{
			unset($field['ID']);
			$field['FORM_ID'] = $newFormId;
			FieldTable::add($field);
		}

		// copy field dependencies
		$fieldDepDb = FieldDependenceTable::getList(array(
			'filter' => array('=FORM_ID' => $formId)
		));
		while($fieldDep = $fieldDepDb->fetch())
		{
			unset($fieldDep['ID']);
			$fieldDep['FORM_ID'] = $newFormId;
			FieldDependenceTable::add($fieldDep);
		}

		// copy preset fields
		$presetFieldDb = PresetFieldTable::getList(array(
			'filter' => array('=FORM_ID' => $formId)
		));
		while($presetField = $presetFieldDb->fetch())
		{
			$presetField['FORM_ID'] = $newFormId;
			PresetFieldTable::add($presetField);
		}


		return $newFormId;
	}

	public static function activate($formId, $isActivate = true, $changeUserBy = null)
	{
		$updateFields = array('ACTIVE' => $isActivate ? 'Y' : 'N');
		if($changeUserBy)
		{
			$updateFields['ACTIVE_CHANGE_BY'] = $changeUserBy;
		}
		$updateResult = FormTable::update($formId, $updateFields);
		if($updateResult->isSuccess())
		{
			static::cleanCacheByTag($formId);
			return true;
		}
		else
		{
			return false;
		}
	}

	/*
	 * Return true if has result.
	 *
	 * @param string $originId Origin ID.
	 * @return bool
	 * */
	public function hasResult($originId)
	{
		$webFormResult = Internals\ResultTable::getList(array(
			'select' => array('ID'),
			'filter' => array(
				'=FORM_ID' => $this->getId(),
				'=ORIGIN_ID' => $originId,
			),
			'limit' => 1
		));

		return $webFormResult->getSelectedRowsCount() > 0;
	}

	/*
	 * Add result.
	 *
	 * @param array $resultFields Result fields.
	 * @param array $resultParameters Result parameters.
	 * @return Result
	 * */
	public function addResult($resultFields, $resultParameters = array())
	{
		$this->errors = array();

		// get section fields
		$sectionFields = array();
		$currentSection = '';
		$fieldsMap = $this->getFieldsMap();
		foreach($fieldsMap as $field)
		{
			if($field['type'] == 'section')
			{
				$currentSection = $field['name'];
				continue;
			}

			if(!$currentSection)
			{
				continue;
			}

			$sectionFields[$currentSection][] = $field['name'];
		}

		// format fields by name
		$fields = array();
		foreach($resultFields as $fieldKey => $field)
		{
			$fields[$field['name']] = $field;
		}

		$hiddenFieldNames = array();
		// set hidden flag
		foreach($resultFields as $fieldKey => $field)
		{
			if(!isset($field['dependences']))
			{
				$field['hidden'] = false;
				$data['FIELDS'][$fieldKey] = $field;
				continue;
			}

			$isHidden = false;
			foreach($field['dependences'] as $dep)
			{
				if($dep['if']['action'] != 'change')
				{
					continue;
				}

				if(!isset($fields[$dep['if']['fieldname']]))
				{
					continue;
				}

				$valuesA = $fields[$dep['if']['fieldname']]['values'];
				$valueB = $dep['if']['value'];
				$isSuccess = false;
				switch($dep['if']['operation'])
				{
					case '!=':
						$isSuccess = !in_array($valueB, $valuesA);
						break;

					default:
						$isSuccess = in_array($valueB, $valuesA);
						break;
				}

				if(!$isSuccess)
				{
					continue;
				}

				$isHidden = $dep['do']['action'] == 'hide';
			}

			if($field['type'] == 'section')
			{
				foreach($sectionFields[$field['name']] as $sectionFieldName)
				{
					$hiddenFieldNames[$sectionFieldName][] = $isHidden;
				}
			}
			else
			{
				$field['hidden'] = $isHidden;
				$resultFields[$fieldKey] = $field;
			}
		}

		$fieldEmailValue = null;
		$fieldPhoneValue = null;
		$fieldPhoneEntityTypeName = null;
		foreach($resultFields as $fieldKey => $field)
		{
			if(($field['entity_field_name'] == 'EMAIL' || $field['type'] == 'email') && $field['values'][0])
			{
				$fieldEmailValue = $field['values'][0];
			}
			if(($field['entity_field_name'] == 'PHONE' || $field['type'] == 'phone') && $field['values'][0])
			{
				$fieldPhoneEntityTypeName = $field['entity_name'];
				$fieldPhoneValue = $field['values'][0];
			}

			if(!isset($hiddenFieldNames[$field['name']]))
			{
				continue;
			}

			$field['hidden'] = end($hiddenFieldNames[$field['name']]);
			$resultFields[$fieldKey] = $field;
		}

		$resultProducts = array();
		$activityFields = array();
		foreach($resultFields as $fieldKey => $field)
		{
			if($field['hidden'])
			{
				continue;
			}

			if(!isset($field['values'][0]) || !$field['values'][0])
			{
				continue;
			}

			$activityFieldValues = array();
			if(is_array($field['items']) && count($field['items']) > 0)
			{
				foreach($field['items'] as $item)
				{
					if(!in_array($item['value'], $field['values']))
					{
						continue;
					}

					$activityFieldValues[] = $item;
				}
			}
			else
			{
				$activityFieldValues = $field['values'];
			}
			$activityFields[] = array(
				'type' => $field['type'],
				'code' => $field['name'],
				'required' => $field['required'],
				'caption' => $field['caption'],
				'value' => $activityFieldValues,
			);


			if($field['type'] != 'product')
			{
				continue;
			}

			foreach($field['items'] as $item)
			{
				if(!in_array($item['value'], $field['values']))
				{
					continue;
				}

				$productId = is_numeric($item['value']) ? $item['value'] : 0;
				$product = [
					'ID' => $productId,
					'NAME' => $item['title'],
					'PRICE' => $item['price'],
				];
				if ($productId && ($productData = \CCrmProduct::GetByID($productId)))
				{
					$product['VAT_INCLUDED'] = $productData['VAT_INCLUDED'];
					if ($productData['VAT_ID'])
					{
						$vatData = \CCrmVat::GetByID($productData['VAT_ID']);
						if ($vatData && $vatData['RATE'])
						{
							$product['VAT_RATE'] = floatval($vatData['RATE']) / 100;
						}
					}
				}
				$resultProducts[] = $product;
			}
		}

		// set responsible
		$responsibleQueue = new ResponsibleQueue($this->id);
		$responsibleId = $responsibleQueue->getNextId();
		$this->params['ASSIGNED_BY_ID'] = $responsibleId ? $responsibleId : 1;

		// add Result
		$data = array(
			'FORM' => $this->params,
			'DUPLICATE_MODE' => $this->params['DUPLICATE_MODE'],
			'PRESET_FIELDS' => $this->params['PRESET_FIELDS'],
			'COMMON_FIELDS' => isset($resultParameters['COMMON_FIELDS']) ? $resultParameters['COMMON_FIELDS'] : array(),
			'COMMON_DATA' => $resultParameters['COMMON_DATA'],
			'PLACEHOLDERS' => isset($resultParameters['PLACEHOLDERS']) ? $resultParameters['PLACEHOLDERS'] : array(),
			'ORIGIN_ID' => isset($resultParameters['ORIGIN_ID']) ? $resultParameters['ORIGIN_ID'] : null,
			'ENTITY_SCHEME' => $this->params['ENTITY_SCHEME'],
			'INVOICE_SETTINGS' => $this->params['INVOICE_SETTINGS'],
			'FORM_ID' => $this->id,
			'FIELDS' => $resultFields,
			'ASSIGNED_BY_ID' => $this->params['ASSIGNED_BY_ID'],
			'PRODUCTS' => $resultProducts,
			'CURRENCY_ID' => $this->getCurrencyId(),
			'ACTIVITY_FIELDS' => $activityFields,
			'IS_CALLBACK' => $this->isCallback(),
			'CALLBACK_PHONE' => $fieldPhoneValue,
		);
		$result = new Result(null, $data);
		$result->save();
		if($result->hasErrors())
		{
			$this->errors = $result->getErrors();
		}
		else
		{
			if($fieldEmailValue)
			{
				self::sendEventFormSent(array(
						'RESULT_SUCCESS_TEXT' => $this->params['RESULT_SUCCESS_TEXT'],
						'RESULT_SUCCESS_URL' => $this->params['RESULT_SUCCESS_URL'],
						'RESULT_FAILURE_TEXT' => $this->params['RESULT_FAILURE_TEXT'],
						'RESULT_FAILURE_URL' => $this->params['RESULT_FAILURE_URL'],
						'FORM_CAPTION' => $this->params['CAPTION'],
						'EMAIL_TO' => $fieldEmailValue,
					)
				);
			}

			$stopCallBack = false;
			if (isset($resultParameters['STOP_CALLBACK']) && $resultParameters['STOP_CALLBACK'])
			{
				$stopCallBack = true;
			}

			if($fieldPhoneValue && $this->isCallback())
			{
				if(Callback::hasPhoneNumbers())
				{
					Callback::sendCallEvent(array(
						'CRM_ENTITY_TYPE' => $fieldPhoneEntityTypeName,
						'CRM_ENTITY_ID' => $result->getResultEntity()->getEntityIdByTypeName($fieldPhoneEntityTypeName),
						'CALL_FROM' => $this->params['CALL_FROM'],
						'CALL_TO' => $fieldPhoneValue,
						'TEXT' => $this->params['CALL_TEXT'],
						'STOP_CALLBACK' => $stopCallBack,
						'CRM_ENTITY_LIST' => $result->getResultEntity()->getResultEntities()
					));
				}
			}
		}

		return $result;
	}

	protected function sendEventFormSent($fields)
	{
		MailEvent::send(array(
				'EVENT_NAME' => 'CRM_WEB_FORM_FILLED',
				'C_FIELDS' => $fields,
				'LID' => Context::getCurrent()->getSite()
		));

		MailEvent::send(array(
				'EVENT_NAME' => 'CRM_WEB_FORM_FILLED_' . $this->getId(),
				'C_FIELDS' => $fields,
				'LID' => Context::getCurrent()->getSite()
		));
	}

	public function getScripts($publicFormPath)
	{
		$script = new Script(
			Context::getCurrent()->getServer()->getHttpHost(),
			Context::getCurrent()->getRequest()->isHttps()
		);

		$scriptParams = array(
			'id' => $this->params['ID'],
			'lang' => Context::getCurrent()->getLanguage(),
			'sec' => $this->params['SECURITY_CODE']
		);

		return array(
			'INLINE' => $script->getInline($scriptParams),
			'BUTTON' => $script->getButton($scriptParams + array('button_caption' => Loc::getMessage('CRM_WEBFORM_FORM_SCRIPT_BUTTON_TEXT'))),
			'LINK' => $script->getLink($scriptParams + array('button_caption' => Loc::getMessage('CRM_WEBFORM_FORM_SCRIPT_BUTTON_TEXT'))),
			'DELAY' => $script->getDelay($scriptParams + array('delay' => 5))
		);
	}

	public function sendScriptsEmail($email)
	{

	}

	public static function getCacheTag($formId)
	{
		return 'BX_CRM_WEBFORM_ID_' . $formId;
	}

	public static function cleanCacheByTag($formId)
	{
		if(defined("BX_COMP_MANAGED_CACHE"))
		{
			$taggedCache = Application::getInstance()->getTaggedCache();
			$taggedCache->clearByTag(static::getCacheTag($formId));
		}
	}

	public static function getCounters($formId, $formEntityScheme = null)
	{
		$result = array(
			'ENTITY' => array(),
			'COMMON' => array()
		);
		$entityList = Entity::getList();
		if($formEntityScheme)
		{
			$entitySchemes = Entity::getSchemes();
			if($entitySchemes[$formEntityScheme])
			{
				foreach($entityList as $entityName => $entityCaption)
				{
					if(!in_array($entityName, $entitySchemes[$formEntityScheme]['ENTITIES']))
					{
						unset($entityList[$entityName]);
					}
				}
			}
		}

		$entityFieldMap = FormCounterTable::getEntityFieldsMap();
		$counters = FormCounterTable::getByFormId($formId);
		foreach($counters as $counter => $value)
		{
			if(isset($entityFieldMap[$counter]))
			{
				$entityName = $entityFieldMap[$counter];
				if(!isset($entityList[$entityName]))
				{
					continue;
				}

				$entityCaption = $entityList[$entityName];
				$result['ENTITY'][] = array(
					'ENTITY_NAME' => $entityName,
					'ENTITY_CAPTION' => $entityCaption,
					'VALUE' => $value,
				);
			}
			else
			{
				$result['COMMON'][$counter] = $value;
			}
		}

		return $result;
	}

	public static function incCounterView($formId)
	{
		FormViewTable::add(array('FORM_ID' => $formId));
		return FormCounterTable::incCounters($formId, array('VIEWS'));
	}

	public static function incCounterStartFill($formId)
	{
		FormStartEditTable::add(array('FORM_ID' => $formId));
		return FormCounterTable::incCounters($formId, array('START_FILL'));
	}

	public static function incCounterEndFill($formId)
	{
		return FormCounterTable::incCounters($formId, array('END_FILL'));
	}

	public static function resetCounters($formId)
	{
		$newCounterId = FormCounterTable::addByFormId($formId);
		// TODO: merge all counters
		return $newCounterId;
	}

	public static function canRemoveCopyright()
	{
		if(!Loader::includeModule('bitrix24'))
		{
			return true;
		}

		return \CBitrix24::IsLicensePaid();
	}

	protected static function getMaxActivatedFormLimit()
	{
		return intval(Option::get('crm', '~crm_webform_max_activated', 99999));
	}

	public static function canActivateForm()
	{
		if(!Loader::includeModule("bitrix24"))
		{
			return true;
		}

		$maxActivated = self::getMaxActivatedFormLimit();
		return $maxActivated > FormTable::getCount(array('=ACTIVE' => 'Y', '=IS_SYSTEM' => 'N'));
	}

	public static function actualizeFormsActiveState($maxActivated = null)
	{
		if(!$maxActivated)
		{
			$maxActivated = self::getMaxActivatedFormLimit();
		}

		$formDb = FormTable::getList(array(
			'select' => array('ID'),
			'filter' => array('=ACTIVE' => 'Y', '=IS_SYSTEM' => 'N'),
			'order' => array('ID' => 'ASC')
		));
		while($form = $formDb->fetch())
		{
			if($maxActivated > 0)
			{
				--$maxActivated;
				continue;
			}

			static::activate($form['ID'], false);
		}

		if(!self::canRemoveCopyright())
		{
			$connection = Application::getConnection();
			$connection->query("UPDATE b_crm_webform SET COPYRIGHT_REMOVED='N'");
		}
	}

	public static function onAfterSetOptionCrmWebFormMaxActivated(\Bitrix\Main\Event $event)
	{
		self::actualizeFormsActiveState();
	}

	public static function onBitrix24LicenseChange(\Bitrix\Main\Event $event)
	{
		preg_match("/(project|tf|team)$/is", $event->getParameter(0), $matches);
		$licenseType = strtolower($matches[0]);
		if ($licenseType)
		{
			$maxActivated = null;
			switch($licenseType)
			{
				case 'project':
					$maxActivated = 1;
					break;
				case 'tf':
					$maxActivated = 2;
					break;
				case 'team':
					$maxActivated = 4;
					break;
				case 'demo':
					$maxActivated = 9999;
					break;
			}

			self::actualizeFormsActiveState($maxActivated);
		}
	}
}
