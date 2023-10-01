<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

class CBPCodeCondition extends CBPActivityCondition
{
	public $condition = '';

	public function __construct($condition)
	{
		$this->condition = $condition;
	}

	public function Evaluate(CBPActivity $ownerActivity)
	{
		@eval("\$result = ".$this->condition.";");

		return $result;
	}

	public static function validateProperties($value = null, CBPWorkflowTemplateUser $user = null)
	{
		$arErrors = [];

		if ($user == null || !$user->isAdmin())
		{
			$arErrors[] = [
				'code' => 'perm',
				'message' => Loc::getMessage('BPCC_NO_PERMS'),
			];
		}

		return array_merge($arErrors, parent::validateProperties($value, $user));
	}

	public static function GetPropertiesDialog(
		$documentType,
		$arWorkflowTemplate,
		$arWorkflowParameters,
		$arWorkflowVariables,
		$defaultValue,
		$arCurrentValues = null
	)
	{
		$runtime = CBPRuntime::GetRuntime();

		if (!is_array($arCurrentValues))
		{
			$arCurrentValues = ['php_code_condition' => ($defaultValue == null ? '' : $defaultValue)];
		}

		return $runtime->ExecuteResourceFile(
			__FILE__,
			'properties_dialog.php',
			['arCurrentValues' => $arCurrentValues]
		);
	}

	public static function GetPropertiesDialogValues(
		$documentType,
		$arWorkflowTemplate,
		$arWorkflowParameters,
		$arWorkflowVariables,
		$arCurrentValues,
		&$arErrors
	)
	{
		$arErrors = [];

		if (
			!array_key_exists('php_code_condition', $arCurrentValues)
			|| $arCurrentValues['php_code_condition'] == ''
		)
		{
			$arErrors[] = [
				'code' => '',
				'message' => Loc::getMessage('BPCC_EMPTY_CODE'),
			];

			return null;
		}

		$arErrors = self::validateProperties(
			$arCurrentValues['php_code_condition'],
			new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser)
		);

		if (count($arErrors) > 0)
		{
			return null;
		}

		return $arCurrentValues['php_code_condition'];
	}
}