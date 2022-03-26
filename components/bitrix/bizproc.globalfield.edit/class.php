<?php

use Bitrix\Main\LoaderException;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

class BizprocGlobalFieldEditComponent extends CBitrixComponent
{
	private const VAR_MODE = 'variable';
	private const CONST_MODE = 'constant';

	private $mode;

	public function onPrepareComponentParams($arParams): array
	{
		if (isset($arParams['DOCUMENT_TYPE_SIGNED']))
		{
			$arParams['DOCUMENT_TYPE_SIGNED'] = htmlspecialcharsback($arParams['DOCUMENT_TYPE_SIGNED']);
			$arParams['DOCUMENT_TYPE'] = CBPDocument::unSignDocumentType($arParams['DOCUMENT_TYPE_SIGNED']);
		}

		$arParams['FIELD_ID'] = $arParams['FIELD_ID'] ? htmlspecialcharsback($arParams['FIELD_ID']) : null;
		$arParams['MODE'] = in_array($arParams['MODE'], ['constant', 'variable']) ? $arParams['MODE'] : null;
		$arParams['SET_TITLE'] = ($arParams["SET_TITLE"] === 'N' ? 'N' : 'Y');
		$arParams['NAME'] = ($arParams['NAME']) ? htmlspecialcharsback($arParams['NAME']) : null;

		return $arParams;
	}

	private function getTitle(string $id): ?string
	{
		if ($this->mode === self::VAR_MODE)
		{
			if (!\Bitrix\Bizproc\Workflow\Type\GlobalVar::getById($id))
			{
				return \Bitrix\Main\Localization\Loc::getMessage('BIZPROC_GLOBALFIELDS_EDIT_TITLE_VARIABLE_CREATE');
			}

			return \Bitrix\Main\Localization\Loc::getMessage('BIZPROC_GLOBALFIELDS_EDIT_TITLE_VARIABLE_EDIT');
		}
		elseif ($this->mode === self::CONST_MODE)
		{
			if (!\Bitrix\Bizproc\Workflow\Type\GlobalConst::getById($id))
			{
				return \Bitrix\Main\Localization\Loc::getMessage('BIZPROC_GLOBALFIELDS_EDIT_TITLE_CONSTANT_CREATE');
			}

			return \Bitrix\Main\Localization\Loc::getMessage('BIZPROC_GLOBALFIELDS_EDIT_TITLE_CONSTANT_EDIT');
		}
		else
		{
			return '';
		}
	}

	/**
	 * @throws LoaderException
	 */
	public function executeComponent()
	{
		global $APPLICATION;

		if (!\Bitrix\Main\Loader::includeModule('bizproc'))
		{
			static::showError(\Bitrix\Main\Localization\Loc::getMessage('BIZPROC_MODULE_NOT_INSTALLED'));

			return false;
		}

		$this->initMode();

		if ($this->arParams['SET_TITLE'] === 'Y')
		{
			$id = (string)$this->arParams['~FIELD_ID'];
			$title = static::getTitle($id);
			$APPLICATION->SetTitle($title);
		}

		$this->arResult = [
			'fieldTypes' => $this->getFieldsTypes(),
			'fieldInfo' => $this->getFieldInfo(),
			'visibilityTypes' => $this->getVisibilityTypes(),
			'disabled' => $this->arParams['FIELD_ID'] ? 'disabled' : '',
			'mode' => $this->mode,
		];

		return $this->includeComponentTemplate();
	}

	private static function showError(string $message)
	{
		echo <<<HTML
			<div class="ui-alert ui-alert-danger ui-alert-icon-danger">
				<span class="ui-alert-message">{$message}</span>
			</div>
HTML;
	}

	private function initMode()
	{
		if ($this->arParams['MODE'] === self::CONST_MODE)
		{
			$this->mode = self::CONST_MODE;
		}
		elseif ($this->arParams['MODE'] === self::VAR_MODE)
		{
			$this->mode = self::VAR_MODE;
		}
		else
		{
			$this->mode = null;
		}
	}

	private function getFieldsTypes(): array
	{
		$baseTypes = \Bitrix\Bizproc\FieldType::getBaseTypesMap();
		unset($baseTypes[\Bitrix\Bizproc\FieldType::INTERNALSELECT]);
		unset($baseTypes[\Bitrix\Bizproc\FieldType::FILE]);

		$runtime = CBPRuntime::GetRuntime();
		$runtime->StartRuntime();
		$documentService = $runtime->GetService("DocumentService");
		$documentType = $this->arParams['DOCUMENT_TYPE'];
		$documentTypes = $documentService->GetDocumentFieldTypes($documentType);

		$fieldTypes = [];

		foreach ($documentTypes as $key => $value)
		{
			if ($key == 'UF:date')
			{
				$key = 'date';
			}
			if (!isset($baseTypes[$key]))
			{
				continue;
			}

			$fieldTypes[$key] = $value['Name'];
		}

		$availableTypes = $this->arParams['TYPES'];

		if (!$availableTypes)
		{
			return $fieldTypes;
		}

		if (!is_array($availableTypes))
		{
			$availableTypes = [$availableTypes];
		}

		$types = [];
		foreach ($availableTypes as $type)
		{
			if (array_key_exists($type, $fieldTypes))
			{
				$types[$type] = $fieldTypes[$type];
			}
		}

		return $types;
	}

	private function getVisibilityTypes(): array
	{
		$documentType = $this->arParams['DOCUMENT_TYPE'];

		if ($this->mode === self::VAR_MODE)
		{
			return \Bitrix\Bizproc\Workflow\Type\GlobalVar::getVisibilityShortNames($documentType);
		}
		elseif ($this->mode === self::CONST_MODE)
		{
			return \Bitrix\Bizproc\Workflow\Type\GlobalConst::getVisibilityShortNames($documentType);
		}
		else
		{
			return [];
		}
	}

	private function getFieldInfo(): array
	{
		$id = (string)$this->arParams['~FIELD_ID'];
		if (!$id)
		{
			$newProperty = [];
			if ($this->arParams['~NAME'])
			{
				$newProperty['Name'] = (string)$this->arParams['~NAME'];
			}
			if ($this->arParams['VISIBILITY'])
			{
				$newProperty['Visibility'] = (string)$this->arParams['VISIBILITY'];
			}

			return $newProperty;
		}

		if ($this->mode === self::VAR_MODE)
		{
			$table = \Bitrix\Bizproc\Workflow\Type\GlobalVar::class;
		}
		elseif ($this->mode === self::CONST_MODE)
		{
			$table = \Bitrix\Bizproc\Workflow\Type\GlobalConst::class;
		}
		else
		{
			return [];
		}

		if (method_exists($table, 'getById'))
		{
			$property = $table::getById($id);
			if ($property)
			{
				if ($property['Type'] === 'user')
				{
					$property['Default'] = CBPHelper::UsersArrayToString(
						$property['Default'],
						null,
						$this->arParams['DOCUMENT_TYPE']
					);
				}

				return array_merge($property, ['id' => $id]);
			}
		}

		return [];
	}

}

