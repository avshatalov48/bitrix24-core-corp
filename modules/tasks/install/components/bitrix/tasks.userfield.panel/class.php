<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Localization\LanguageTable;
use Bitrix\Tasks\Util\Restriction\Bitrix24Restriction\Limit\TaskLimit;
use Bitrix\Tasks\Util\Result;
use Bitrix\Tasks\Util\Type\DateTime;
use Bitrix\Tasks\Util\User;
use Bitrix\Tasks\Util\UserField;
use Bitrix\Tasks\Util\UserField\Restriction;

Loc::loadMessages(__FILE__);

CBitrixComponent::includeComponentClass("bitrix:tasks.base");

class TasksUserFieldPanelComponent extends TasksBaseComponent
{
	protected $state = null;
	protected $ctrl = null;
	protected $stateCtrl = null;

	protected function checkParameters()
	{
		static::tryParseEnumerationParameter($this->arParams['ENTITY_CODE'], array('TASK', 'TASK_TEMPLATE'), false);
		if(!$this->arParams['ENTITY_CODE'])
		{
			$this->errors->add('INVALID_PARAMETER.ENTITY_CODE', 'Unknown entity code');
		}
		static::tryParseArrayParameter($this->arParams['EXCLUDE']);
		static::tryParseArrayParameter($this->arParams['RELATED_ENTITIES']);

		return $this->errors->checkNoFatals();
	}

	protected function getData()
	{
		$fields = array();
		$types = array();
		$state = array();

		$this->ctrl = static::getControllerByEntity($this->arParams['ENTITY_CODE']);
		$entityCode = $this->ctrl::getEntityCode();

		if ($this->ctrl && Restriction::canUse($entityCode))
		{
			$fields = $this->ctrl::getScheme();
			$types = $this->ctrl::getTypes();

			foreach ($fields as $ufCode => $ufDesc)
			{
				$fields[$ufCode]['VALUE'] = $this->getUfValue($ufCode, $ufDesc);
				$fields[$ufCode]['LABEL'] = $this->getUfLabel($ufDesc);
			}

			$this->stateCtrl = static::getStateController($this->ctrl);
			if($this->stateCtrl)
			{
				$state = $this->stateCtrl->get();
				foreach($state as $ufCode => $ufState)
				{
					if(in_array($ufCode, $this->arParams['EXCLUDE']))
					{
						unset($state[$ufCode]);
					}
				}
			}
		}

		$this->arResult['DATA']['FIELDS'] = $fields;
		$this->arResult['AUX_DATA']['FIELD_TYPE'] = $types;
		$this->arResult['DATA']['STATE'] = $state;

		$this->arResult['COMPONENT_DATA']['RESTRICTION'] = array(
			'USE' => Restriction::canUse($entityCode, $this->userId),
			'MANAGE' => Restriction::canManage($entityCode, $this->userId),
			'CREATE_MANDATORY' => Restriction::canCreateMandatory($entityCode, $this->userId),
			'TASK_LIMIT_EXCEEDED' => TaskLimit::isLimitExceeded(TaskLimit::isLimitExist() ? 0 : 100),
		);
	}

	/**
	 * Returns site languages
	 *
	 * @return array
	 */
	protected static function getLanguages()
	{
		$languages = array();

		try
		{
			$languageList = LanguageTable::getList(array('order' => 'SORT'));
			while ($language = $languageList->fetch())
			{
				$languages[] = $language['LID'];
			}
		}
		catch(Exception $ex)
		{
			return $languages;
		}

		return $languages;
	}

	/**
	 * @param $entityCode
	 * @return null|UserField
	 */
	protected static function getControllerByEntity($entityCode)
	{
		$className = UserField::getControllerClassByEntityCode($entityCode);
		if($className)
		{
			return new $className();
		}

		return null;
	}

	/**
	 * Returns user field value
	 *
	 * @param $ufCode
	 * @param $ufDesc
	 * @return DateTime|string
	 */
	protected function getUfValue($ufCode, $ufDesc)
	{
		$ufValue = '';

		if (!empty($this->arParams['DATA']) && isset($this->arParams['DATA'][$ufCode]))
		{
			$ufValue = $this->arParams['DATA'][$ufCode];
		}
		elseif (isset($ufDesc['SETTINGS']['DEFAULT_VALUE']))
		{
			if ($ufDesc['USER_TYPE']['USER_TYPE_ID'] == 'datetime')
			{
				if ($ufDesc['SETTINGS']['DEFAULT_VALUE']['TYPE'] == 'NOW')
				{
					$ufValue = DateTime::createFromTimestamp(User::getTime());
				}
				else
				{
					$ufValue = $ufDesc['SETTINGS']['DEFAULT_VALUE']['VALUE'];
				}
			}
			else
			{
				$ufValue = $ufDesc['SETTINGS']['DEFAULT_VALUE'];
			}
		}

		return $ufValue;
	}

	/**
	 * Returns user field label
	 *
	 * @param $ufDesc
	 * @return mixed
	 */
	protected function getUfLabel($ufDesc)
	{
		$ufLabel = ((string)$ufDesc['EDIT_FORM_LABEL'] != ''? $ufDesc['EDIT_FORM_LABEL'] : $ufDesc['FIELD_NAME_ORIG']);

		if ($ufLabel === null || $ufLabel === "")
		{
			$userField = \CUserTypeEntity::GetByID($ufDesc['ID']);
			$userFieldLabels = $userField['EDIT_FORM_LABEL'];

			if (isset($userFieldLabels) && !empty($userFieldLabels))
			{
				foreach (static::getLanguages() as $languageId)
				{
					if (isset($userFieldLabels[$languageId]))
					{
						$ufLabel = $userFieldLabels[$languageId];
						break;
					}
				}

				if ($ufLabel === null || $ufLabel === "")
				{
					reset($userFieldLabels);
					$ufLabel = current($userFieldLabels);
				}
			}
		}

		return $ufLabel;
	}

	protected static function getItemControllerByEntity($entityCode, $entityId = 0)
	{
		// todo: hardcoded by now, later auto-search may be implemented

		if($entityCode == 'TASK')
		{
			return new \Bitrix\Tasks\Item\Task($entityId);
		}
		elseif($entityCode == 'TASK_TEMPLATE')
		{
			return new \Bitrix\Tasks\Item\Task\Template($entityId);
		}
		else
		{
			return null;
		}
	}

	protected static function getStateController($ufController)
	{
		return new TasksUserFieldPanelComponentState($ufController);
	}

	public static function getAllowedMethods()
	{
		return array(
			'saveField',
			'deleteField',
			'setState',
			'getFieldHTML',
		);
	}

	public static function saveField($id = 0, array $data, array $parameters = array())
	{
		$result = new Result();

		$ufController = static::getControllerByEntity($data['ENTITY_CODE']);
		if (!$ufController)
		{
			$result->addError('ILLEGAL_ARGUMENT.ENTITY_CODE', Loc::getMessage('TASKS_TUFE_UF_UNKNOWN_ENTITY_CODE'));
		}

		$type = $data['USER_TYPE_ID'];
		$label = trim((string)$data['LABEL']);

		if (!is_array($parameters['RELATED_ENTITIES']))
		{
			$parameters['RELATED_ENTITIES'] = array();
		}
		else
		{
			$parameters['RELATED_ENTITIES'] = array_diff($parameters['RELATED_ENTITIES'], array($data['ENTITY_CODE']));
		}

		if (!\Bitrix\Tasks\Util\User::isSuper())
		{
			$result->addError('ACTION_NOT_ALLOWED', Loc::getMessage('TASKS_TUFE_UF_ADMIN_RESTRICTED'));
		}
		if ($label == '')
		{
			$result->addError('ILLEGAL_ARGUMENT.LABEL', Loc::getMessage('TASKS_TUFE_EMPTY_LABEL'));
		}
		if (
			!Restriction::canManage($ufController->getEntityCode())
			&& TaskLimit::isLimitExceeded(TaskLimit::isLimitExist() ? 0 : 100)
		)
		{
			$result->addError('ACTION_RESTRICTED', Loc::getMessage('TASKS_TUFE_UF_MANAGING_RESTRICTED'));
		}
		if ($data['MANDATORY'] == 'Y' && !Restriction::canCreateMandatory($ufController->getEntityCode()))
		{
			$result->addError('ACTION_RESTRICTED', Loc::getMessage('TASKS_TUFE_UF_USAGE_RESTRICTED_MANDATORY'));
		}

		if ($id) // update existing field
		{
			if ($result->isSuccess())
			{
				$userField = \CUserTypeEntity::GetByID($id);
				$userFieldLabels = $userField['EDIT_FORM_LABEL'];

				$editFormLabel = array(
					LANGUAGE_ID => $label
				);

				foreach (static::getLanguages() as $languageId)
				{
					if (!array_key_exists($languageId, $userFieldLabels))
					{
						$editFormLabel[$languageId] = $label;
					}
				}

				$field = array(
					'MANDATORY' => ($data['MANDATORY']? 'Y' : 'N'),
					'EDIT_FORM_LABEL' => $editFormLabel,
				);

				$updateResult = $ufController->updateField($id, $field);
				if (!$updateResult->getErrors()->isEmpty())
				{
					$result->adoptErrors($updateResult);
				}
				else
				{
					$fData = $ufController->getField($id);
					if ($fData !== null)
					{
						static::updateRelatedFields($fData['FIELD_NAME'], $parameters['RELATED_ENTITIES'], $field, $result);
					}
				}
			}
		}
		else // create a new one
		{
			if (!in_array($type, array('string', 'double', 'boolean', 'datetime')))
			{
				$result->addError('ILLEGAL_ARGUMENT.USER_TYPE_ID', Loc::getMessage('TASKS_TUFE_UF_UNKNOWN_TYPE'));
			}

			$freeName = static::getFreeFieldName($data['ENTITY_CODE'], $parameters['RELATED_ENTITIES']);
			if (!$freeName)
			{
				$result->addError('INTERNAL_ERROR', Loc::getMessage('TASKS_TUFE_UF_NAME_GENERATION_FAILED'));
			}

			if ($result->isSuccess())
			{
				$editFormLabel = array(
					LANGUAGE_ID => $label
				);

				foreach (static::getLanguages() as $languageId)
				{
					$editFormLabel[$languageId] = $label;
				}

				$field = array(
					'FIELD_NAME' => $freeName,
					'USER_TYPE_ID' => $type,
					'XML_ID' => '',
					'MULTIPLE' => ($data['MULTIPLE']? 'Y' : 'N'),
					'MANDATORY' => ($data['MANDATORY']? 'Y' : 'N'),
					'SHOW_FILTER' => 'Y',
					'SHOW_IN_LIST' => 'Y',
					'EDIT_IN_LIST' => 'Y',
					'IS_SEARCHABLE' => 'Y',
					'EDIT_FORM_LABEL' => $editFormLabel,
				);

				$addResult = $ufController->addField($field);
				if (!$addResult->getErrors()->isEmpty())
				{
					$result->adoptErrors($addResult);
				}

				if ($result->isSuccess())
				{
					$data['ID'] = $id = $addResult->getData();

					// also get html
					$fieldData = array();
					$scheme = $ufController->getScheme();
					foreach ($scheme as $code => $fData)
					{
						if ($fData['ID'] == $id)
						{
							$fieldData = $fData;
						}
					}

					$inputPrefix = trim((string)$parameters['INPUT_PREFIX']);
					if ($inputPrefix)
					{
						$fieldData['FIELD_NAME'] = $inputPrefix.'['.$fieldData['FIELD_NAME'].']';
					}

					$data['FIELD_HTML'] = static::getFieldUIHtml($fieldData);

					static::addRelatedFields($parameters['RELATED_ENTITIES'], $field, $result);
				}
			}
		}

		$result->setData($data);

		return $result;
	}

	public static function deleteField($id, array $parameters = array())
	{
		$result = new Result();

		if(!intval($id))
		{
			$result->addError('ILLEGAL_ARGUMENT.ID', Loc::getMessage('TASKS_TUFE_UF_UNKNOWN_ID'));
		}
		else
		{
			if(!is_array($parameters['RELATED_ENTITIES']))
			{
				$parameters['RELATED_ENTITIES'] = array();
			}

			if(!\Bitrix\Tasks\Util\User::isSuper())
			{
				$result->addError('ACTION_NOT_ALLOWED', Loc::getMessage('TASKS_TUFE_UF_ADMIN_RESTRICTED'));
			}

			$data = \CUserTypeEntity::GetByID($id);
			if(!$data)
			{
				$result->addError('FIELD_NOT_FOUND', Loc::getMessage('TASKS_TUFE_UF_NOT_FOUND'));
			}
			else
			{
				$ufController = static::getControllerByEntity($data['ENTITY_ID']);
				if(!$ufController)
				{
					$result->addError('ILLEGAL_ARGUMENT.ENTITY_CODE', Loc::getMessage('TASKS_TUFE_UF_UNKNOWN_ENTITY_CODE'));
				}

				if($result->isSuccess())
				{
					$deleteResult = $ufController->deleteField($id);
					if(!$deleteResult->getErrors()->isEmpty())
					{
						$result->adoptErrors($deleteResult);
					}
					else
					{
						Restriction::canUse($ufController->getEntityCode(), 0, true);
					}
					// todo: not sure about delete
//					else
//					{
//						$fData = $ufController->getField($id);
//						if($fData !== null)
//						{
//							static::deleteRelatedFields($fData['FIELD_NAME'], $parameters['RELATED_ENTITIES'], $field, $result);
//						}
//					}
				}
			}
		}

		return $result;
	}

	public static function getFieldHTML($id, $entityCode, $entityId = 0, array $parameters = array())
	{
		$result = new Result();
		$html = '';

		$item = static::getItemControllerByEntity($entityCode, $entityId);
		$ufController = static::getControllerByEntity($entityCode);

		if(!$item || !$ufController)
		{
			$result->addError('ILLEGAL_ARGUMENT.ENTITY_CODE', Loc::getMessage('TASKS_TUFE_UF_UNKNOWN_ENTITY_CODE'));
		}
		else
		{
			if(!Restriction::canUse($ufController->getEntityCode()))
			{
				$result->addError('ACTION_RESTRICTED', Loc::getMessage('TASKS_TUFE_UF_USAGE_RESTRICTED'));
			}
		}

		if($result->isSuccess())
		{
			$scheme = $ufController->getScheme();
			$code = '';
			foreach($scheme as $ufCode => $ufField)
			{
				if($ufField['ID'] == $id)
				{
					$code = $ufCode;
					break;
				}
			}

			if($code == '')
			{
				$result->addError('ILLEGAL_ARGUMENT.ID', Loc::getMessage('TASKS_TUFE_UF_UNKNOWN_ID'));
			}
			else
			{
				// todo: $item->setUserFieldController($ufController) should be used here, when implemented
				// todo: $item[$code] leads to *, UF_* in select query. Optimize it by calling getData() in un-greedy mode (when implemented)
				$value = $item[$code]; // we need to use item, because we have to check rights

				$fieldData = $scheme[$code];
				$fieldData['VALUE'] = $value;
				$inputPrefix = trim((string) $parameters['INPUT_PREFIX']);
				if($inputPrefix)
				{
					$fieldData['FIELD_NAME'] = $inputPrefix.'['.$fieldData['FIELD_NAME'].']';
				}

				$html = static::getFieldUIHtml($fieldData, !$entityId);
			}
		}

		$result->setData($html);
		return $result;
	}

	public static function setState(array $state, $entityCode, $dropAll = false)
	{
		$result = new Result();

		$ufController = static::getControllerByEntity($entityCode);
		if(!$ufController)
		{
			$result->addError('ILLEGAL_ARGUMENT.ENTITY_CODE', Loc::getMessage('TASKS_TUFE_UF_UNKNOWN_ENTITY_CODE'));
		}
		else
		{
			if(!Restriction::canUse($ufController->getEntityCode(), 0, true))
			{
				$result->addError('ACTION_RESTRICTED', Loc::getMessage('TASKS_TUFE_UF_USAGE_RESTRICTED'));
			}
			else
			{
				if($dropAll && !\Bitrix\Tasks\Util\User::isSuper())
				{
					$result->addError('ACTION_NOT_ALLOWED', Loc::getMessage('TASKS_TUFE_UF_ADMIN_RESTRICTED'));
				}
				else
				{
					$ctrl = static::getStateController($ufController);
					if($dropAll)
					{
						$ctrl->removeForAllUsers();
					}
					$ctrl->set($state);
				}
			}
		}

		return $result;
	}

	private static function getFreeFieldName($mainEntity, $relatedEntities = array())
	{
		$name = '';
		for($i = 0; $i < 10 && $name == ''; $i++)
		{
			$nameCandidate = static::getControllerByEntity($mainEntity)->getFreeFieldName();
			$badCandidate = false;
			if(is_array($relatedEntities) && !empty($relatedEntities))
			{
				foreach($relatedEntities as $relatedEntityCode)
				{
					$ctrl = static::getControllerByEntity($relatedEntityCode);
					if($ctrl !== null && $ctrl->isFieldExist($nameCandidate))
					{
						$badCandidate = true;
						break;
					}
				}
			}

			if(!$badCandidate)
			{
				$name = $nameCandidate;
				break;
			}
		}

		return $name;
	}

	/**
	 * @param $relatedEntities
	 * @param $field
	 * @param Result $result
	 */
	private static function addRelatedFields($relatedEntities, $field, $result)
	{
		foreach($relatedEntities as $relatedEntity)
		{
			$controller = static::getControllerByEntity($relatedEntity);

			if($controller)
			{
				$saveResult = $controller->addField($field);

				if(!$saveResult->getErrors()->isEmpty())
				{
					$result->addWarning('INTERNAL_ERROR', Loc::getMessage('TASKS_TUFE_UF_RELATED_FIELDS_CREATING_ERROR'));
				}
			}
		}
	}

	/**
	 * @param $name
	 * @param $relatedEntities
	 * @param $field
	 * @param Result $result
	 */
	private static function updateRelatedFields($name, $relatedEntities, $field, $result)
	{
		foreach($relatedEntities as $relatedEntity)
		{
			$controller = static::getControllerByEntity($relatedEntity);

			if($controller)
			{
				$fData = $controller->getField($name);
				if($fData)
				{
					$saveResult = $controller->updateField($fData['ID'], $field);

					if(!$saveResult->getErrors()->isEmpty())
					{
						$result->addWarning('INTERNAL_ERROR', Loc::getMessage('TASKS_TUFE_UF_RELATED_FIELDS_UPDATING_ERROR'));
					}
				}
			}
		}
	}

	private static function getFieldUIHtml($fieldData, $preferDefault = false)
	{
		if(empty($fieldData))
		{
			return '';
		}

		ob_start();
		\Bitrix\Tasks\Util\UserField\UI::showEdit($fieldData, array(
			'PREFER_DEFAULT' => $preferDefault,
			'RANDOM' => md5(rand(100,999).rand(100,999)), // enable generating random ids while getting with ajax
		));
		return ob_get_clean();
	}
}

if(CModule::IncludeModule('tasks'))
{
	final class TasksUserFieldPanelComponentState extends Bitrix\Tasks\Util\Type\ArrayOption
	{
		protected $controller = null;

		public function __construct($controller)
		{
			$this->controller = $controller;
			parent::__construct();

			// set option name according to the entity code
			$code = $controller->getEntityCode();
			if($code == 'TASKS_TASK')
			{
				$optionName = static::getFilterOptionName(); // for backward compatibility
			}
			else
			{
				$optionName = 'tc_'.ToLower($code).'_ufp_st';
			}

			$this->setOptionName($optionName);
		}

		protected static function getFilterOptionName()
		{
			return 'tasks_component_ufp_state';
		}

		public function check($value, $initial = false)
		{
			$value = parent::check($value);

			// resort by S, for sure
			uasort($value, function($a, $b){

				$sA = intval($a['S']);
				$sB = intval($b['S']);

				return $sA > $sB ? 1 : ($sA == $sB ? 0 : -1);
			});

			// now reset sort indexes, to avoid holes
			$i = 0;
			foreach($value as $k => $v)
			{
				$value[$k]['S'] = ++$i;
			}

			return $value;
		}

		protected function getRules()
		{
			$ufs = $this->controller->getScheme();

			$rules = array();
			foreach($ufs as $k => $v)
			{
				if(UserField\UI::isSuitable($v))
				{
					$rules[$v['ID']] = array('VALUE' => array(
						'D' => array('VALUE' => 'boolean', 'DEFAULT' => true),
						'S' => array('VALUE' => 'integer', 'DEFAULT' => 0)
					), 'DEFAULT' => array());
				}
			}

			return $rules;
		}

		protected function fetchOptionValue()
		{
			$name = $this->getOptionName();

			$value = User::getOption($name);
			if(empty($value))
			{
				$value = User::getOption($name, User::getAdminId());
			}

			return $value;
		}
	}
}