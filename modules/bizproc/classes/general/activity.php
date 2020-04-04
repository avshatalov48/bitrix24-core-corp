<?php

use Bitrix\Bizproc;
use Bitrix\Main;

abstract class CBPActivity
{
	public $parent = null;

	public $executionStatus = CBPActivityExecutionStatus::Initialized;
	public $executionResult = CBPActivityExecutionResult::None;

	private $arStatusChangeHandlers = array();

	const StatusChangedEvent = 0;
	const ExecutingEvent = 1;
	const CancelingEvent = 2;
	const ClosedEvent = 3;
	const FaultingEvent = 4;

	const ValuePattern = '#^\s*\{=\s*(?<object>[a-z0-9_]+)\s*\:\s*(?<field>[a-z0-9_\.]+)(\s*>\s*(?<mod1>[a-z0-9_\:]+)(\s*,\s*(?<mod2>[a-z0-9_]+))?)?\s*\}\s*$#i';
	const ValueInlinePattern = '#\{=\s*(?<object>[a-z0-9_]+)\s*\:\s*(?<field>[a-z0-9_\.]+)(\s*>\s*(?<mod1>[a-z0-9_\:]+)(\s*,\s*(?<mod2>[a-z0-9_]+))?)?\s*\}#i';
	/** Internal pattern used in calc.php */
	const ValueInternalPattern = '\{=\s*([a-z0-9_]+)\s*\:\s*([a-z0-9_\.]+)(\s*>\s*([a-z0-9_\:]+)(\s*,\s*([a-z0-9_]+))?)?\s*\}';

	const CalcPattern = '#^\s*(=\s*(.*)|\{\{=\s*(.*)\s*\}\})\s*$#is';
	const CalcInlinePattern = '#\{\{=\s*(.*?)\s*\}\}([^\}]|$)#is';

	protected $arProperties = array();
	protected $arPropertiesTypes = array();

	protected $name = "";
	/** @var CBPWorkflow $workflow */
	public $workflow = null;

	public $arEventsMap = array();

	/************************  PROPERTIES  ************************************************/

	public function GetDocumentId()
	{
		$rootActivity = $this->GetRootActivity();
		return $rootActivity->GetDocumentId();
	}

	public function SetDocumentId($documentId)
	{
		$rootActivity = $this->GetRootActivity();
		$rootActivity->SetDocumentId($documentId);
	}

	public function GetDocumentType()
	{
		$rootActivity = $this->GetRootActivity();
		if (!is_array($rootActivity->documentType) || count($rootActivity->documentType) <= 0)
		{
			/** @var CBPDocumentService $documentService */
			$documentService = $this->workflow->GetService("DocumentService");
			$rootActivity->documentType = $documentService->GetDocumentType($rootActivity->documentId);
		}
		return $rootActivity->documentType;
	}

	public function SetDocumentType($documentType)
	{
		$rootActivity = $this->GetRootActivity();
		$rootActivity->documentType = $documentType;
	}

	public function getDocumentEventType()
	{
		$rootActivity = $this->GetRootActivity();
		return (int)$rootActivity->getRawProperty(CBPDocument::PARAM_DOCUMENT_EVENT_TYPE);
	}

	public function GetWorkflowStatus()
	{
		$rootActivity = $this->GetRootActivity();
		return $rootActivity->GetWorkflowStatus();
	}

	public function SetWorkflowStatus($status)
	{
		$rootActivity = $this->GetRootActivity();
		$rootActivity->SetWorkflowStatus($status);
	}

	public function SetFieldTypes($arFieldTypes = array())
	{
		if (count($arFieldTypes) > 0)
		{
			$rootActivity = $this->GetRootActivity();
			foreach ($arFieldTypes as $key => $value)
				$rootActivity->arFieldTypes[$key] = $value;
		}
	}

	public function GetWorkflowTemplateId()
	{
		$rootActivity = $this->GetRootActivity();
		//prevent recursion by checking setter
		if (method_exists($rootActivity, 'SetWorkflowTemplateId'))
		{
			return $rootActivity->GetWorkflowTemplateId();
		}

		return 0;
	}

	public function getTemplateUserId()
	{
		$userId = 0;
		$rootActivity = $this->GetRootActivity();
		//prevent recursion by checking setter
		if (method_exists($rootActivity, 'setTemplateUserId'))
		{
			$userId = $rootActivity->getTemplateUserId();
		}

		if (!$userId && $tplId = $this->GetWorkflowTemplateId())
		{
			$userId = CBPWorkflowTemplateLoader::getTemplateUserId($tplId);
		}

		return $userId;
	}

	/**********************************************************/
	protected function ClearProperties()
	{
		$rootActivity = $this->GetRootActivity();
		$documentId = $rootActivity->GetDocumentId();
		$documentType = $this->GetDocumentType();
		/** @var CBPDocumentService $documentService */
		$documentService = $this->workflow->GetService("DocumentService");

		if (is_array($rootActivity->arPropertiesTypes) && count($rootActivity->arPropertiesTypes) > 0
			&& is_array($rootActivity->arFieldTypes) && count($rootActivity->arFieldTypes) > 0)
		{
			foreach ($rootActivity->arPropertiesTypes as $key => $value)
			{
				if ($rootActivity->arFieldTypes[$value["Type"]]["BaseType"] == "file")
				{
					foreach ((array) $rootActivity->arProperties[$key] as $v)
					{
						if (intval($v) > 0)
						{
							$iterator = \CFile::getByID($v);
							if ($file = $iterator->fetch())
							{
								if ($file['MODULE_ID'] === 'bizproc')
									CFile::Delete($v);
							}
						}
					}
				}

				$fieldType = \Bitrix\Bizproc\FieldType::normalizeProperty($value);
				if ($fieldTypeObject = $documentService->getFieldTypeObject($documentType, $fieldType))
				{
					$fieldTypeObject->setDocumentId($documentId)
									->clearValue($rootActivity->arProperties[$key]);
				}
			}
		}
	}

	public function GetPropertyBaseType($propertyName)
	{
		$rootActivity = $this->GetRootActivity();
		return $rootActivity->arFieldTypes[$rootActivity->arPropertiesTypes[$propertyName]["Type"]]["BaseType"];
	}

	public function getTemplatePropertyType($propertyName)
	{
		$rootActivity = $this->GetRootActivity();
		return $rootActivity->arPropertiesTypes[$propertyName];
	}

	public function SetProperties($arProperties = array())
	{
		if (count($arProperties) > 0)
		{
			foreach ($arProperties as $key => $value)
				$this->arProperties[$key] = $value;
		}
	}

	public function SetPropertiesTypes($arPropertiesTypes = array())
	{
		if (count($arPropertiesTypes) > 0)
		{
			foreach ($arPropertiesTypes as $key => $value)
				$this->arPropertiesTypes[$key] = $value;
		}
	}

	/**********************************************************/
	protected function ClearVariables()
	{
		$rootActivity = $this->GetRootActivity();
		$documentId = $rootActivity->GetDocumentId();
		$documentType = $this->GetDocumentType();
		/** @var CBPDocumentService $documentService */
		$documentService = $this->workflow->GetService("DocumentService");

		if (is_array($rootActivity->arVariablesTypes) && count($rootActivity->arVariablesTypes) > 0
			&& is_array($rootActivity->arFieldTypes) && count($rootActivity->arFieldTypes) > 0)
		{
			foreach ($rootActivity->arVariablesTypes as $key => $value)
			{
				if ($rootActivity->arFieldTypes[$value["Type"]]["BaseType"] == "file")
				{
					foreach ((array) $rootActivity->arVariables[$key] as $v)
					{
						if (intval($v) > 0)
						{
							$iterator = \CFile::getByID($v);
							if ($file = $iterator->fetch())
							{
								if ($file['MODULE_ID'] === 'bizproc')
									CFile::Delete($v);
							}
						}
					}
				}

				$fieldType = \Bitrix\Bizproc\FieldType::normalizeProperty($value);
				if ($fieldTypeObject = $documentService->getFieldTypeObject($documentType, $fieldType))
				{
					$fieldTypeObject->setDocumentId($documentId)
						->clearValue($rootActivity->arVariables[$key]);
				}
			}
		}
	}

	public function GetVariableBaseType($variableName)
	{
		$rootActivity = $this->GetRootActivity();
		return $rootActivity->arFieldTypes[$rootActivity->arVariablesTypes[$variableName]["Type"]]["BaseType"];
	}

	public function SetVariables($arVariables = array())
	{
		if (count($arVariables) > 0)
		{
			$rootActivity = $this->GetRootActivity();
			foreach ($arVariables as $key => $value)
				$rootActivity->arVariables[$key] = $value;
		}
	}

	public function SetVariablesTypes($arVariablesTypes = array())
	{
		if (count($arVariablesTypes) > 0)
		{
			$rootActivity = $this->GetRootActivity();
			foreach ($arVariablesTypes as $key => $value)
				$rootActivity->arVariablesTypes[$key] = $value;
		}
	}

	public function SetVariable($name, $value)
	{
		$rootActivity = $this->GetRootActivity();
		$rootActivity->arVariables[$name] = $value;
	}

	public function GetVariable($name)
	{
		$rootActivity = $this->GetRootActivity();

		if (array_key_exists($name, $rootActivity->arVariables))
			return $rootActivity->arVariables[$name];

		return null;
	}

	public function getVariableType($name)
	{
		$rootActivity = $this->GetRootActivity();
		return isset($rootActivity->arVariablesTypes[$name]) ? $rootActivity->arVariablesTypes[$name] : null;
	}

	private function GetConstantTypes()
	{
		$rootActivity = $this->GetRootActivity();
		if (method_exists($rootActivity, 'GetWorkflowTemplateId'))
		{
			$templateId = $rootActivity->GetWorkflowTemplateId();
			if ($templateId > 0)
			{
				return CBPWorkflowTemplateLoader::getTemplateConstants($templateId);
			}
		}
		return null;
	}

	public function GetConstant($name)
	{
		$constants = $this->GetConstantTypes();
		if (isset($constants[$name]['Default']))
			return $constants[$name]['Default'];
		return null;
	}

	public function GetConstantType($name)
	{
		$constants = $this->GetConstantTypes();
		if (isset($constants[$name]))
			return $constants[$name];
		return array('Type' => null, 'Multiple' => false, 'Required' => false, 'Options' => null);
	}

	public function IsVariableExists($name)
	{
		$rootActivity = $this->GetRootActivity();
		return array_key_exists($name, $rootActivity->arVariables);
	}

	/************************************************/
	public function GetName()
	{
		return $this->name;
	}

	public function GetRootActivity()
	{
		$p = $this;
		while ($p->parent != null)
			$p = $p->parent;
		return $p;
	}

	public function SetWorkflow(CBPWorkflow $workflow)
	{
		$this->workflow = $workflow;
	}

	public function GetWorkflowInstanceId()
	{
		return $this->workflow->GetInstanceId();
	}

	public function SetStatusTitle($title = '')
	{
		$rootActivity = $this->GetRootActivity();
		$stateService = $this->workflow->GetService("StateService");
		if ($rootActivity instanceof CBPStateMachineWorkflowActivity)
		{
			$arState = $stateService->GetWorkflowState($this->GetWorkflowInstanceId());

			$arActivities = $rootActivity->CollectNestedActivities();
			/** @var CBPActivity $activity */
			foreach ($arActivities as $activity)
				if ($activity->GetName() == $arState["STATE_NAME"])
					break;

			$stateService->SetStateTitle(
				$this->GetWorkflowInstanceId(),
				$activity->Title.($title != '' ? ": ".$title : '')
			);
		}
		else
		{
			if ($title != '')
			{
				$stateService->SetStateTitle(
					$this->GetWorkflowInstanceId(),
					$title
				);
			}
		}
	}

	public function AddStatusTitle($title = '')
	{
		if ($title == '')
			return;

		$stateService = $this->workflow->GetService("StateService");

		$mainTitle = $stateService->GetStateTitle($this->GetWorkflowInstanceId());
		$mainTitle .= ((strpos($mainTitle, ": ") !== false) ? ", " : ": ").$title;

		$stateService->SetStateTitle($this->GetWorkflowInstanceId(), $mainTitle);
	}

	public function DeleteStatusTitle($title = '')
	{
		if ($title == '')
			return;

		$stateService = $this->workflow->GetService("StateService");
		$mainTitle = $stateService->GetStateTitle($this->GetWorkflowInstanceId());

		$ar1 = explode(":", $mainTitle);
		if (count($ar1) <= 1)
			return;

		$newTitle = "";

		$ar2 = explode(",", $ar1[1]);
		foreach ($ar2 as $a)
		{
			$a = trim($a);
			if ($a != $title)
			{
				if (strlen($newTitle) > 0)
					$newTitle .= ", ";
				$newTitle .= $a;
			}
		}

		$result = $ar1[0].(strlen($newTitle) > 0 ? ": " : "").$newTitle;

		$stateService->SetStateTitle($this->GetWorkflowInstanceId(), $result);
	}

	private function GetPropertyValueRecursive($val, $convertToType = null)
	{
		// array(2, 5, array("SequentialWorkflowActivity1", "DocumentApprovers"))
		// array("Document", "IBLOCK_ID")
		// array("Workflow", "id")
		// "Hello, {=SequentialWorkflowActivity1:DocumentApprovers}, {=Document:IBLOCK_ID}!"

		$parsed = $this->parseValueExpression($val);
		if ($parsed)
		{
			$result = null;
			if ($convertToType)
				$parsed['modifiers'][] = $convertToType;
			$this->GetRealParameterValue($parsed['object'], $parsed['field'], $result, $parsed['modifiers']);
			return array(1, $result);
		}
		elseif (is_array($val))
		{
			$b = true;
			$r = array();

			$keys = array_keys($val);

			$i = 0;
			foreach ($keys as $key)
			{
				if ($key."!" != $i."!")
				{
					$b = false;
					break;
				}
				$i++;
			}

			foreach ($keys as $key)
			{
				list($t, $a) = $this->GetPropertyValueRecursive($val[$key], $convertToType);
				if ($b)
				{
					if ($t == 1 && is_array($a))
						$r = array_merge($r, $a);
					else
						$r[] = $a;
				}
				else
				{
					$r[$key] = $a;
				}
			}

			if (count($r) == 2)
			{
				$keys = array_keys($r);
				if ($keys[0] == 0 && $keys[1] == 1 && is_string($r[0]) && is_string($r[1]))
				{
					$result = null;
					$modifiers = $convertToType ? array($convertToType) : array();
					if ($this->GetRealParameterValue($r[0], $r[1], $result, $modifiers))
						return array(1, $result);
				}
			}
			return array(2, $r);
		}
		else
		{
			if (is_string($val))
			{
				$typeClass = null;
				$fieldTypeObject = null;
				if ($convertToType)
				{
					/** @var CBPDocumentService $documentService */
					$documentService = $this->workflow->GetService("DocumentService");
					$documentType = $this->GetDocumentType();

					$typesMap = $documentService->getTypesMap($documentType);
					$convertToType = strtolower($convertToType);
					if (isset($typesMap[$convertToType]))
					{
						$typeClass = $typesMap[$convertToType];
						$fieldTypeObject = $documentService->getFieldTypeObject(
							$documentType,
							array('Type' => \Bitrix\Bizproc\FieldType::STRING)
						);
					}
				}

				$calc = new CBPCalc($this);
				if (preg_match(self::CalcPattern, $val))
				{
					$r = $calc->Calculate($val);
					if ($r !== null)
					{
						if ($typeClass && $fieldTypeObject)
						{
							if (is_array($r))
								$fieldTypeObject->setMultiple(true);
							$r = $fieldTypeObject->convertValue($r, $typeClass);
						}
						return array(is_array($r)? 1 : 2, $r);
					}
				}

				//parse inline calculator
				$val = preg_replace_callback(
					static::CalcInlinePattern,
					function($matches) use ($calc)
					{
						$r = $calc->Calculate($matches[1]);
						if (is_array($r))
							$r = implode(', ', CBPHelper::MakeArrayFlat($r));
						return $r !== null? $r.$matches[2] : $matches[0];
					},
					$val
				);

				//parse properties
				$val = preg_replace_callback(
					static::ValueInlinePattern,
					array($this, "ParseStringParameter"),
					$val
				);

				//converting...
				if ($typeClass && $fieldTypeObject)
				{
					$val = $fieldTypeObject->convertValue($val, $typeClass);
				}
			}

			return array(2, $val);
		}
	}

	private function GetRealParameterValue($objectName, $fieldName, &$result, array $modifiers = null)
	{
		$return = true;
		$property = null;
		/** @var CBPDocumentService $documentService */
		$documentService = $this->workflow->GetService("DocumentService");

		if ($objectName == "Document")
		{
			$rootActivity = $this->GetRootActivity();
			$documentId = $rootActivity->GetDocumentId();

			$documentType = $this->GetDocumentType();
			$document = $documentService->GetDocument($documentId, $documentType);
			$documentFields = $documentService->GetDocumentFields($documentType);
			//check aliases
			$documentFieldsAliasesMap = CBPDocument::getDocumentFieldsAliasesMap($documentFields);
			if (!isset($document[$fieldName]) && strtoupper(substr($fieldName, -strlen('_PRINTABLE'))) == '_PRINTABLE')
			{
				$fieldName = substr($fieldName, 0, -strlen('_PRINTABLE'));
				if (!in_array('printable', $modifiers))
					$modifiers[] = 'printable';
			}
			if (!isset($document[$fieldName]) && isset($documentFieldsAliasesMap[$fieldName]))
			{
				$fieldName = $documentFieldsAliasesMap[$fieldName];
			}

			$result = '';

			if (isset($document[$fieldName]))
			{
				$result = $document[$fieldName];
				if (is_array($result) && strtoupper(substr($fieldName, -strlen('_PRINTABLE'))) == '_PRINTABLE')
					$result = implode(", ", CBPHelper::MakeArrayFlat($result));

				$property = isset($documentFields[$fieldName]) ? $documentFields[$fieldName] : null;
			}
		}
		elseif (in_array($objectName, ['Template', 'Variable', 'Constant']))
		{
			$rootActivity = $this->GetRootActivity();

			if (substr($fieldName, -strlen("_printable")) == "_printable")
			{
				$fieldName = substr($fieldName, 0, strlen($fieldName) - strlen("_printable"));
				$modifiers = array('printable');
			}

			switch ($objectName)
			{
				case 'Variable':
					$result = $rootActivity->GetVariable($fieldName);
					$property = $rootActivity->getVariableType($fieldName);
					break;
				case 'Constant':
					$result = $rootActivity->GetConstant($fieldName);
					$property = $rootActivity->GetConstantType($fieldName);
					break;
				default:
					$result = $rootActivity->__get($fieldName);
					$property = $rootActivity->getTemplatePropertyType($fieldName);
			}
		}
		elseif ($objectName === 'GlobalConst')
		{
			$result = Bizproc\Workflow\Type\GlobalConst::getValue($fieldName);
			$property = Bizproc\Workflow\Type\GlobalConst::getById($fieldName);
		}
		elseif ($objectName == "Workflow")
		{
			$result = $this->GetWorkflowInstanceId();
			$property = array('Type' => 'string');
		}
		elseif ($objectName == "User")
		{
			$result = 0;
			if (isset($GLOBALS["USER"]) && is_object($GLOBALS["USER"]) && $GLOBALS["USER"]->isAuthorized())
				$result = "user_".$GLOBALS["USER"]->GetID();
			$property = array('Type' => 'user');
		}
		elseif ($objectName == "System")
		{
			global $DB;

			$result = null;
			$property = array('Type' => 'datetime');
			$systemField = strtolower($fieldName);
			if ($systemField === 'now')
			{
				$result = new Bizproc\BaseType\Value\DateTime();
				//$result = date($DB->DateFormatToPHP(CSite::GetDateFormat("FULL")));
			}
			elseif ($systemField === 'nowlocal')
			{
				$result = new Bizproc\BaseType\Value\DateTime(time(), CTimeZone::GetOffset());
				//$result = time();
				//if (CTimeZone::Enabled())
				//	$result += CTimeZone::GetOffset();
				//$result = date($DB->DateFormatToPHP(CSite::GetDateFormat("FULL")), $result);
			}
			elseif ($systemField == 'date')
			{
				$result = new Bizproc\BaseType\Value\Date();
				//$result = date($DB->DateFormatToPHP(CSite::GetDateFormat("SHORT")));
				$property = array('Type' => 'date');
			}
			if ($result === null)
			{
				$return = false;
			}
		}
		elseif ($objectName)
		{
			$activity = $this->workflow->GetActivityByName($objectName);
			if ($activity)
			{
				$result = $activity->__get($fieldName);
				//if mapping is set, we can apply modifiers (type converting & formatting like `printable`, `bool` etc.)
				if (isset($activity->arPropertiesTypes[$fieldName]))
				{
					$property = $activity->arPropertiesTypes[$fieldName];
				}
			}
			else
				$return = false;
		}
		else
			$return = false;

		if ($property && $result)
		{
			$fieldTypeObject = $documentService->getFieldTypeObject($this->GetDocumentType(), $property);
			if ($fieldTypeObject)
			{
				$fieldTypeObject->setDocumentId($this->GetDocumentId());
				$result = $fieldTypeObject->internalizeValue($objectName, $result);
			}
		}

		if ($return)
			$result = $this->applyPropertyValueModifiers($fieldName, $property, $result, $modifiers);
		return $return;
	}

	private function applyPropertyValueModifiers($fieldName, $property, $value, array $modifiers)
	{
		if (empty($property) || empty($modifiers) || !is_array($property))
			return $value;

		$typeName = $typeClass = $format = null;

		$rootActivity = $this->GetRootActivity();
		$documentId = $rootActivity->GetDocumentId();
		/** @var CBPDocumentService $documentService */
		$documentService = $this->workflow->GetService("DocumentService");
		$documentType = $this->GetDocumentType();

		$typesMap = $documentService->getTypesMap($documentType);
		foreach ($modifiers as $m)
		{
			$m = strtolower($m);
			if (isset($typesMap[$m]))
			{
				$typeName = $m;
				$typeClass = $typesMap[$m];
			}
			else
			{
				$format = $m;
			}
		}

		if ($typeName === \Bitrix\Bizproc\FieldType::STRING && $format === 'printable')
		{
			$typeClass = null;
		}

		if ($typeClass || $format)
		{
			$fieldTypeObject = $documentService->getFieldTypeObject($documentType, $property);

			if ($fieldTypeObject)
			{
				$fieldTypeObject->setDocumentId($documentId);
				if ($typeClass)
					$value = $fieldTypeObject->convertValue($value, $typeClass);
				if ($format)
					$value = $fieldTypeObject->formatValue($value, $format);
			}
			elseif ($format == 'printable') // compatibility: old printable style
			{
				$value = $documentService->GetFieldValuePrintable(
					$documentId,
					$fieldName,
					$property['Type'],
					$value,
					$property
				);

				if (is_array($value))
					$value = implode(", ", CBPHelper::MakeArrayFlat($value));
			}
		}
		return $value;
	}

	private function ParseStringParameter($matches)
	{
		$result = "";
		$modifiers = array();
		if (!empty($matches['mod1']))
			$modifiers[] = $matches['mod1'];
		if (!empty($matches['mod2']))
			$modifiers[] = $matches['mod2'];

		if (empty($modifiers))
			$modifiers[] = \Bitrix\Bizproc\FieldType::STRING;

		if ($this->GetRealParameterValue($matches['object'], $matches['field'], $result, $modifiers))
		{
			if (is_array($result))
				$result = implode(", ", CBPHelper::MakeArrayFlat($result));
		}
		else
		{
			$result = $matches[0];
		}

		return $result;
	}

	/**
	 * @param $text
	 * @return array|bool
	 */
	private function parseValueExpression($text)
	{
		$matches = null;
		if (is_string($text) && preg_match(static::ValuePattern, $text, $matches))
		{
			$result = array(
				'object' => $matches['object'],
				'field' => $matches['field'],
				'modifiers' => array()
			);
			if (!empty($matches['mod1']))
				$result['modifiers'][] = $matches['mod1'];
			if (!empty($matches['mod2']))
				$result['modifiers'][] = $matches['mod2'];

			return $result;
		}
		return false;
	}

	public function ParseValue($value, $convertToType = null)
	{
		list($t, $r) = $this->GetPropertyValueRecursive($value, $convertToType);
		return $r;
	}

	protected function getRawProperty($name)
	{
		if (isset($this->arProperties[$name]))
		{
			return $this->arProperties[$name];
		}
		return null;
	}

	public function __get($name)
	{
		$property = $this->getRawProperty($name);
		if ($property !== null)
		{
			list($t, $r) = $this->GetPropertyValueRecursive($property);
			return $r;
		}
		return null;
	}

	public function __set($name, $val)
	{
		if (array_key_exists($name, $this->arProperties))
		{
			$this->arProperties[$name] = $val;
		}
	}

	public function IsPropertyExists($name)
	{
		return array_key_exists($name, $this->arProperties);
	}

	public function CollectNestedActivities()
	{
		return null;
	}

	/************************  CONSTRUCTORS  *****************************************************/

	public function __construct($name)
	{
		$this->name = $name;
	}

	/************************  DEBUG  ***********************************************************/

	public function ToString()
	{
		return $this->name.
			" [".get_class($this)."] (status=".
			CBPActivityExecutionStatus::Out($this->executionStatus).
			", result=".
			CBPActivityExecutionResult::Out($this->executionResult).
			", count(ClosedEvent)=".
			count($this->arStatusChangeHandlers[self::ClosedEvent]).
			")";
	}

	public function Dump($level = 3)
	{
		$result = str_repeat("	", $level).$this->ToString()."\n";

		if (is_subclass_of($this, "CBPCompositeActivity"))
		{
			/** @var CBPActivity $activity */
			foreach ($this->arActivities as $activity)
				$result .= $activity->Dump($level + 1);
		}

		return $result;
	}

	/************************  PROCESS  ***********************************************************/

	public function Initialize()
	{
	}

	public function Finalize()
	{
	}

	public function Execute()
	{
		return CBPActivityExecutionStatus::Closed;
	}

	protected function ReInitialize()
	{
		$this->executionStatus = CBPActivityExecutionStatus::Initialized;
		$this->executionResult = CBPActivityExecutionResult::None;
	}

	public function Cancel()
	{
		return CBPActivityExecutionStatus::Closed;
	}

	public function HandleFault(Exception $exception)
	{
		return CBPActivityExecutionStatus::Closed;
	}

	/************************  LOAD / SAVE  *******************************************************/

	public function FixUpParentChildRelationship(CBPActivity $nestedActivity)
	{
		$nestedActivity->parent = $this;
	}

	public static function Load($stream)
	{
		if (strlen($stream) <= 0)
			throw new Exception("stream");

		$pos = strpos($stream, ";");
		$strUsedActivities = substr($stream, 0, $pos);
		$stream = substr($stream, $pos + 1);

		$runtime = CBPRuntime::GetRuntime();
		$arUsedActivities = explode(",", $strUsedActivities);

		foreach ($arUsedActivities as $activityCode)
		{
			$runtime->IncludeActivityFile($activityCode);
		}

		return unserialize($stream);
	}

	protected function GetACNames()
	{
		return array(substr(get_class($this), 3));
	}

	private static function SearchUsedActivities(CBPActivity $activity, &$arUsedActivities)
	{
		$arT = $activity->GetACNames();
		foreach ($arT as $t)
		{
			if (!in_array($t, $arUsedActivities))
			{
				$arUsedActivities[] = $t;
			}
		}

		if ($arNestedActivities = $activity->CollectNestedActivities())
		{
			foreach ($arNestedActivities as $nestedActivity)
			{
				self::SearchUsedActivities($nestedActivity, $arUsedActivities);
			}
		}
	}

	public function Save()
	{
		$usedActivities = [];
		self::SearchUsedActivities($this, $usedActivities);
		$strUsedActivities = implode(",", $usedActivities);
		return $strUsedActivities.";".serialize($this);
	}

	/************************  STATUS CHANGE HANDLERS  **********************************************/

	public function AddStatusChangeHandler($event, $eventHandler)
	{
		if (!is_array($this->arStatusChangeHandlers))
			$this->arStatusChangeHandlers = array();

		if (!array_key_exists($event, $this->arStatusChangeHandlers))
			$this->arStatusChangeHandlers[$event] = array();

		$this->arStatusChangeHandlers[$event][] = $eventHandler;
	}

	public function RemoveStatusChangeHandler($event, $eventHandler)
	{
		if (!is_array($this->arStatusChangeHandlers))
			$this->arStatusChangeHandlers = array();

		if (!array_key_exists($event, $this->arStatusChangeHandlers))
			$this->arStatusChangeHandlers[$event] = array();

		$index = array_search($eventHandler, $this->arStatusChangeHandlers[$event], true);

		if ($index !== false)
			unset($this->arStatusChangeHandlers[$event][$index]);
	}

	/************************  EVENTS  **********************************************************************/

	private function FireStatusChangedEvents($event, $arEventParameters = array())
	{
		if (array_key_exists($event, $this->arStatusChangeHandlers) && is_array($this->arStatusChangeHandlers[$event]))
		{
			foreach ($this->arStatusChangeHandlers[$event] as $eventHandler)
				call_user_func_array(array($eventHandler, "OnEvent"), array($this, $arEventParameters));
		}
	}

	public function SetStatus($newStatus, $arEventParameters = array())
	{
		$this->executionStatus = $newStatus;
		$this->FireStatusChangedEvents(self::StatusChangedEvent, $arEventParameters);

		switch ($newStatus)
		{
			case CBPActivityExecutionStatus::Executing:
				$this->FireStatusChangedEvents(self::ExecutingEvent, $arEventParameters);
				break;

			case CBPActivityExecutionStatus::Canceling:
				$this->FireStatusChangedEvents(self::CancelingEvent, $arEventParameters);
				break;

			case CBPActivityExecutionStatus::Closed:
				$this->FireStatusChangedEvents(self::ClosedEvent, $arEventParameters);
				break;

			case CBPActivityExecutionStatus::Faulting:
				$this->FireStatusChangedEvents(self::FaultingEvent, $arEventParameters);
				break;

			default:
				return;
		}
	}

	/************************  CREATE  *****************************************************************/

	public static function IncludeActivityFile($code)
	{
		$runtime = CBPRuntime::GetRuntime();
		return $runtime->IncludeActivityFile($code);
	}

	public static function CreateInstance($code, $data)
	{
		if (preg_match("#[^a-zA-Z0-9_]#", $code))
			throw new Exception("Activity '".$code."' is not valid");

		$classname = 'CBP'.$code;
		if (class_exists($classname))
			return new $classname($data);
		else
			return null;
	}

	public static function CallStaticMethod($code, $method, $arParameters = array())
	{
		$runtime = CBPRuntime::GetRuntime();
		if (!$runtime->IncludeActivityFile($code))
			return array(array("code" => "ActivityNotFound", "parameter" => $code, "message" => GetMessage("BPGA_ACTIVITY_NOT_FOUND", array('#ACTIVITY#' => htmlspecialcharsbx($code)))));

		if (preg_match("#[^a-zA-Z0-9_]#", $code))
			throw new Exception("Activity '".$code."' is not valid");

		$classname = 'CBP'.$code;

		if (method_exists($classname,$method))
			return call_user_func_array(array($classname, $method), $arParameters);
		return false;
	}

	public function InitializeFromArray($arParams)
	{
		if (is_array($arParams))
		{
			foreach ($arParams as $key => $value)
			{
				if (array_key_exists($key, $this->arProperties))
					$this->arProperties[$key] = $value;
			}
		}
	}

	/************************  MARK  ****************************************************************/

	public function MarkCanceled($arEventParameters = array())
	{
		if ($this->executionStatus != CBPActivityExecutionStatus::Closed)
		{
			if ($this->executionStatus != CBPActivityExecutionStatus::Canceling)
				throw new Exception("InvalidCancelActivityState");

			$this->executionResult = CBPActivityExecutionResult::Canceled;
			$this->MarkClosed($arEventParameters);
		}
	}

	public function MarkCompleted($arEventParameters = array())
	{
		$this->executionResult = CBPActivityExecutionResult::Succeeded;
		$this->MarkClosed($arEventParameters);
	}

	public function MarkFaulted($arEventParameters = array())
	{
		$this->executionResult = CBPActivityExecutionResult::Faulted;
		$this->MarkClosed($arEventParameters);
	}

	private function MarkClosed($arEventParameters = array())
	{
		switch ($this->executionStatus)
		{
			case CBPActivityExecutionStatus::Executing:
			case CBPActivityExecutionStatus::Canceling:
			case CBPActivityExecutionStatus::Faulting:
			{
				if (is_subclass_of($this, "CBPCompositeActivity"))
				{
					foreach ($this->arActivities as $activity)
					{
						if (($activity->executionStatus != CBPActivityExecutionStatus::Initialized) 
							&& ($activity->executionStatus != CBPActivityExecutionStatus::Closed))
						{
							throw new Exception("ActiveChildExist");
						}
					}
				}

				/** @var CBPTrackingService $trackingService */
				$trackingService = $this->workflow->GetService("TrackingService");
				$trackingService->Write($this->GetWorkflowInstanceId(), CBPTrackingType::CloseActivity, $this->name, $this->executionStatus, $this->executionResult, ($this->IsPropertyExists("Title") ? $this->Title : ""));
				$this->SetStatus(CBPActivityExecutionStatus::Closed, $arEventParameters);

				return;
			}
		}

		throw new Exception("InvalidCloseActivityState");
	}

	protected function WriteToTrackingService($message = "", $modifiedBy = 0, $trackingType = -1)
	{
		/** @var CBPTrackingService $trackingService */
		$trackingService = $this->workflow->GetService("TrackingService");
		if ($trackingType < 0)
			$trackingType = CBPTrackingType::Custom;
		$trackingService->Write($this->GetWorkflowInstanceId(), $trackingType, $this->name, $this->executionStatus, $this->executionResult, ($this->IsPropertyExists("Title") ? $this->Title : ""), $message, $modifiedBy);
	}

	public static function ValidateProperties($arTestProperties = array(), CBPWorkflowTemplateUser $user = null)
	{
		return array();
	}

	public static function ValidateChild($childActivity, $bFirstChild = false)
	{
		return array();
	}

	public static function &FindActivityInTemplate(&$arWorkflowTemplate, $activityName)
	{
		return CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
	}

	public static function isExpression($text)
	{
		if (is_string($text))
		{
			$text = trim($text);
			if (preg_match(static::CalcPattern, $text) || preg_match(static::ValuePattern, $text))
				return true;
		}
		return false;
	}
}