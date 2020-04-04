<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

class CBPFieldCondition
	extends CBPActivityCondition
{
	const CONDITION_JOINER_AND = 0;
	const CONDITION_JOINER_OR = 1;

	public $condition = null;

	public function __construct($condition)
	{
		$this->condition = $condition;
	}

	public function Evaluate(CBPActivity $ownerActivity)
	{
		if ($this->condition == null || !is_array($this->condition) || count($this->condition) <= 0)
			return true;

		if (!is_array($this->condition[0]))
			$this->condition = array($this->condition);

		$rootActivity = $ownerActivity->GetRootActivity();
		$documentId = $rootActivity->GetDocumentId();
		$documentType = $rootActivity->GetDocumentType();

		$documentService = $ownerActivity->workflow->GetService("DocumentService");
		$document = $documentService->GetDocument($documentId, $documentType);
		$documentFields = $documentService->GetDocumentFields($documentType);
		$documentFieldsAliasesMap = CBPDocument::getDocumentFieldsAliasesMap($documentFields);

		$result = array(0 => true);
		$i = 0;
		foreach ($this->condition as $cond)
		{
			$r = true;
			$joiner = empty($cond[3])? static::CONDITION_JOINER_AND : static::CONDITION_JOINER_OR;

			if (!isset($document[$cond[0]]) && substr($cond[0], -strlen('_PRINTABLE')) == '_PRINTABLE')
				$cond[0] = substr($cond[0], 0, strlen($cond[0]) - strlen('_PRINTABLE'));

			if (!isset($document[$cond[0]]) && isset($documentFieldsAliasesMap[$cond[0]]))
				$cond[0] = $documentFieldsAliasesMap[$cond[0]];

			if (array_key_exists($cond[0], $document))
			{
				$fld = isset($document[$cond[0]."_XML_ID"]) ? $document[$cond[0]."_XML_ID"] : $document[$cond[0]];
				$baseType = $documentFields[$cond[0]]["BaseType"];
				$type = $documentFields[$cond[0]]["Type"];
				if ($type === 'UF:boolean')
				{
					$baseType = 'bool';
				}

				if (!$this->CheckCondition($cond[0], $fld, $cond[1], $cond[2], $baseType, $type, $rootActivity))
				{
					$r = false;
				}
			}
			else
				throw new Exception("Field '".$cond[0]."' is not found in document (if/else condition)");

			if ($joiner == static::CONDITION_JOINER_OR)
			{
				++$i;
				$result[$i] = $r;
			}
			elseif (!$r)
				$result[$i] = false;
		}
		$result = array_filter($result);
		return sizeof($result) > 0 ? true : false;
	}

	public function collectUsages(CBPActivity $ownerActivity)
	{
		$usages = [];
		foreach ($this->condition as $cond)
		{
			$usages[] = [\Bitrix\Bizproc\Workflow\Template\SourceType::DocumentField, $cond[0]];
		}
		return $usages;
	}

	/**
	 * @param $fieldName
	 * @param $field
	 * @param $operation
	 * @param $value
	 * @param $baseType
	 * @param $type
	 * @param CBPActivity $rootActivity
	 * @return bool
	 */
	private function CheckCondition($fieldName, $field, $operation, $value, $baseType, $type, $rootActivity)
	{
		if ($operation === 'empty')
		{
			return CBPHelper::isEmptyValue($field);
		}
		elseif ($operation === '!empty')
		{
			return !CBPHelper::isEmptyValue($field);
		}

		$result = false;

		$value = $rootActivity->ParseValue($value, $baseType);
		if ($baseType == "user")
		{
			$field = CBPHelper::ExtractUsersFromUserGroups($field, $rootActivity);
			$value = CBPHelper::ExtractUsersFromUserGroups($value, $rootActivity);
		}
		elseif ($baseType == "select")
		{
			if (is_array($field) && CBPHelper::IsAssociativeArray($field))
				$field = array_keys($field);
		}

		if (!is_array($field))
			$field = array($field);

		if ($operation == "in")
		{
			foreach ($field as $f)
			{
				if (is_array($value))
					$result = in_array($f, $value);
				else
					$result = (strpos($value, $f) !== false);

				if (!$result)
					break;
			}

			return $result;
		}

		if ($operation == "contain")
		{
			if (!is_array($value))
				$value = array($value);
			foreach ($value as $v)
			{
				foreach ($field as $f)
				{
					if (is_array($f))
						$result = in_array($v, $f);
					else
						$result = (strpos($f, $v) !== false);

					if ($result)
						break;
				}
				if (!$result)
					break;
			}

			return $result;
		}

		if ($operation == 'modified')
		{
			$modified = $rootActivity->{CBPDocument::PARAM_MODIFIED_DOCUMENT_FIELDS};
			if (!is_array($modified))
				return true;
			return in_array($fieldName, $modified);
		}

		if (!is_array($value))
			$value = array($value);

		if (CBPHelper::IsAssociativeArray($field))
			$field = array_keys($field);
		if (CBPHelper::IsAssociativeArray($value))
			$value = array_keys($value);

		if (sizeof($field) === 0)
			$field = array(null);
		if (sizeof($value) === 0)
			$value = array(null);

		$i = 0;
		$fieldCount = count($field);
		$valueCount = count($value);
		$iMax = max($fieldCount, $valueCount);
		while ($i < $iMax)
		{
			$f1 = ($fieldCount > $i) ? $field[$i] : $field[$fieldCount - 1];
			$v1 = ($valueCount > $i) ? $value[$i] : $value[$valueCount - 1];

			if ($baseType == "datetime" || $baseType == "date")
			{
				$f1 = \CBPHelper::makeTimestamp($f1);
				$v1 = \CBPHelper::makeTimestamp($v1);
			}

			if ($baseType === 'bool')
			{
				$f1 = CBPHelper::getBool($f1);
				$v1 = CBPHelper::getBool($v1);
			}

			//normalize "0" == "" comparing
			if ($v1 === '' && $f1 === '0' || $f1 === '' && $v1 === '0')
			{
				$f1 = $v1 = null;
			}

			/** @var \Bitrix\Bizproc\BaseType\Base $classType */
			$classType = \Bitrix\Bizproc\BaseType\Base::class;
			if ($type)
			{
				$fieldType = $rootActivity->workflow
					->GetService('DocumentService')
					->getFieldTypeObject($rootActivity->GetDocumentType(), ['Type' => $type]);
				if ($fieldType)
				{
					$classType = $fieldType->getTypeClass();
				}
			}
			$compareResult = $classType::compareValues($f1, $v1);

			switch ($operation)
			{
				case ">":
					$result = ($compareResult === 1);
					break;
				case ">=":
					$result = ($compareResult >= 0);
					break;
				case "<":
					$result = ($compareResult === -1);
					break;
				case "<=":
					$result = ($compareResult <= 0);
					break;
				case "!=":
					$result = ($compareResult !== 0);
					break;
				default:
					$result = ($compareResult === 0);
			}

			if (!$result)
				break;

			$i++;
		}

		return $result;
	}

	public static function GetPropertiesDialog($documentType, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $defaultValue, $arCurrentValues = null, $formName = "")
	{
		$runtime = CBPRuntime::GetRuntime();

		$documentService = $runtime->GetService("DocumentService");
		$arDocumentFieldsTmp = $documentService->GetDocumentFields($documentType);
		$documentFieldsAliasesMap = CBPDocument::getDocumentFieldsAliasesMap($arDocumentFieldsTmp);

		$arFieldTypes = $documentService->GetDocumentFieldTypes($documentType);

		if (!is_array($arCurrentValues))
		{
			$arCurrentValues = array();
			if (is_array($defaultValue))
			{
				$i = 0;
				foreach ($defaultValue as $value)
				{
					if (!isset($arDocumentFieldsTmp[$value[0]]) && isset($documentFieldsAliasesMap[$value[0]]))
						$value[0] = $documentFieldsAliasesMap[$value[0]];

					if (strlen($arCurrentValues["field_condition_count"]) > 0)
						$arCurrentValues["field_condition_count"] .= ",";
					$arCurrentValues["field_condition_count"] .= $i;

					$arCurrentValues["field_condition_field_".$i] = $value[0];
					$arCurrentValues["field_condition_condition_".$i] = $value[1];
					$arCurrentValues["field_condition_value_".$i] = $value[2];
					$arCurrentValues["field_condition_joiner_".$i] = $value[3];

					$i++;
				}
			}
		}
		else
		{
			$arFieldConditionCount = explode(",", $arCurrentValues["field_condition_count"]);
			foreach ($arFieldConditionCount as $i)
			{
				if (intval($i)."!" != $i."!")
					continue;

				$i = intval($i);

				if (!array_key_exists("field_condition_field_".$i, $arCurrentValues) || strlen($arCurrentValues["field_condition_field_".$i]) <= 0)
					continue;

				$arErrors = array();
				$arCurrentValues["field_condition_value_".$i] = $documentService->GetFieldInputValue(
					$documentType,
					$arDocumentFieldsTmp[$arCurrentValues["field_condition_field_".$i]],
					"field_condition_value_".$i,
					$arCurrentValues,
					$arErrors
				);
			}
		}

		$arDocumentFields = array();
		foreach ($arDocumentFieldsTmp as $key => $value)
		{
			//if (!$value["Filterable"])
			//	continue;
			$arDocumentFields[$key] = $value;
		}

		$javascriptFunctions = $documentService->GetJSFunctionsForFields($documentType, "objFieldsFC", $arDocumentFields, $arFieldTypes);

		return $runtime->ExecuteResourceFile(
			__FILE__,
			"properties_dialog.php",
			array(
				"arDocumentFields" => $arDocumentFields,
				"arCurrentValues" => $arCurrentValues,
				"formName" => $formName,
				"arFieldTypes" => $arFieldTypes,
				"javascriptFunctions" => $javascriptFunctions,
				'documentService' => $documentService,
				'documentType' => $documentType,
			)
		);
	}

	public static function GetPropertiesDialogValues($documentType, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $arCurrentValues, &$arErrors)
	{
		$runtime = CBPRuntime::GetRuntime();
		$arErrors = array();

		if (!array_key_exists("field_condition_count", $arCurrentValues) || strlen($arCurrentValues["field_condition_count"]) <= 0)
		{
			$arErrors[] = array(
				"code" => "",
				"message" => GetMessage("BPFC_NO_WHERE"),
			);
			return null;
		}

		$documentService = $runtime->GetService("DocumentService");
		$arDocumentFieldsTmp = $documentService->GetDocumentFields($documentType);

		$arResult = array();

		$arFieldConditionCount = explode(",", $arCurrentValues["field_condition_count"]);
		foreach ($arFieldConditionCount as $i)
		{
			if (intval($i)."!" != $i."!")
				continue;

			$i = intval($i);

			if (!array_key_exists("field_condition_field_".$i, $arCurrentValues) || strlen($arCurrentValues["field_condition_field_".$i]) <= 0)
				continue;

			$arErrors = array();
			$arCurrentValues["field_condition_value_".$i] = $documentService->GetFieldInputValue(
				$documentType,
				$arDocumentFieldsTmp[$arCurrentValues["field_condition_field_".$i]],
				"field_condition_value_".$i,
				$arCurrentValues,
				$arErrors
			);

			$arResult[] = array(
				$arCurrentValues["field_condition_field_".$i],
				htmlspecialcharsback($arCurrentValues["field_condition_condition_".$i]),
				$arCurrentValues["field_condition_value_".$i],
				(int) $arCurrentValues["field_condition_joiner_".$i],
			);
		}

		if (count($arResult) <= 0)
		{
			$arErrors[] = array(
				"code" => "",
				"message" => GetMessage("BPFC_NO_WHERE"),
			);
			return null;
		}

		return $arResult;
	}
}