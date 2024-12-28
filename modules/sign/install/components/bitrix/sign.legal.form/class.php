<?php

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Intranet\ActionFilter\IntranetUser;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Loader;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UserField\Dispatcher;
use Bitrix\Main\UserTable;
use Bitrix\Sign\Config\LegalInfo;

if(!Bitrix\Main\Loader::includeModule('ui'))
{
	ShowError(Bitrix\Main\Localization\Loc::getMessage('UI_MODULE_NOT_INSTALLED'));
	return;
}

if (!Bitrix\Main\Loader::includeModule('sign'))
{
	ShowError('Sign module is not installed.');
	return;
}

CBitrixComponent::includeComponentClass("bitrix:ui.form");

class SignLegalFormComponent extends UIFormComponent implements \Bitrix\Main\Engine\Contract\Controllerable
{
	private const GUID = 'legal_info_form';

	protected ?ErrorCollection $errorCollection = null;
	protected Main\Engine\CurrentUser $currentUser;

	public function configureActions(): array
	{
		$prefilters = [];
		if (Loader::includeModule('intranet'))
		{
			$prefilters['save'] = [
				'+prefilters' => [
					new IntranetUser(),
				],
				'-prefilters' => [
					Main\Engine\ActionFilter\Csrf::class,
					Main\Engine\ActionFilter\Authentication::class,
				],
			];
		}

		return $prefilters;
	}

	public function saveAction(array $data): array
	{
		global $USER_FIELD_MANAGER;

		$currentUser = Main\Engine\CurrentUser::get();
		if(!LegalInfo::canEdit((int)$currentUser->getId()))
		{
			return [];
		}

		$profileId = (int)$this->arParams['PROFILE_ID'];

		if (
			!Loader::includeModule('sign')
			|| empty($data)
			|| $profileId === 0
		)
		{
			return [];
		}

		$newFields = [];

		$USER_FIELD_MANAGER->EditFormAddFields(LegalInfo::USER_FIELD_ENTITY_ID, $newFields, [ 'FORM' => $data ]);
		$USER_FIELD_MANAGER->Update(LegalInfo::USER_FIELD_ENTITY_ID, $profileId, $newFields);

		return [
			'ENTITY_DATA' => $this->getFieldData($this->getUserFields($this->arParams['PROFILE_ID'])),
			'SUCCESS' => 'Y'
		];
	}

	protected function initialize()
	{
		$this->errorCollection = new ErrorCollection();

		$this->currentUser = Main\Engine\CurrentUser::get();

		parent::initialize();

		$this->arResult['CAN_UPDATE_COMMON_CONFIGURATION'] = true;
		$this->arResult['TITLE'] = $this->getTitle();

		$userId = (int)$this->currentUser->getId();

		if (!LegalInfo::canView($userId))
		{
			$this->errorCollection->setError(new \Bitrix\Main\Error(Loc::getMessage('SIGN_USER_PROFILE_LEGAL_PERMISSION_DENIED')));
		}

		if (!$this->errorCollection->isEmpty())
		{
			$this->arParams['SKIP_TEMPLATE'] = true;
			$this->includeComponentTemplate('error');
		}
	}

	protected function emitOnUIFormInitializeEvent(): void
	{
		return;
	}

	protected function listKeysSignedParameters(): array
	{
		return [
			'PROFILE_ID',
		];
	}

	protected function getDefaultParameters(): array
	{
		$profileFields = $this->getUserFields($this->arParams['PROFILE_ID']);
		return array_merge(
			parent::getDefaultParameters(),
			[
				'GUID' => self::GUID,
				'ENTITY_DATA' => $this->getFieldData($profileFields),
				'ENTITY_FIELDS' => $this->getFieldInfo($profileFields),
				'ENTITY_CONFIG' => $this->getFieldConfig($profileFields),
				'INITIAL_MODE' => $this->calculateInitialMode($profileFields),
				'ENTITY_ID' => (int)$this->arParams['PROFILE_ID'],
				'ENTITY_TYPE_ID' => LegalInfo::USER_FIELD_ENTITY_ID,
				'USER_FIELD_ENTITY_ID' => LegalInfo::USER_FIELD_ENTITY_ID,
				'USER_FIELD_PREFIX' => LegalInfo::USER_FIELD_ENTITY_ID,
				'ENTITY_CONFIG_CATEGORY_NAME' => LegalInfo::EDITOR_CONFIG_CATEGORY_NAME,
				'USER_FIELD_AVAILABLE_TYPE_FIELDS' => $this->getAvailableTypeFields(),
				'ENABLE_PAGE_TITLE_CONTROLS' => true,
				'ENABLE_COMMUNICATION_CONTROLS' => true,
				'ENABLE_REQUIRED_FIELDS_INJECTION' => true,
				'ENABLE_SHOW_ALWAYS_FEATURE' => false,
				'ENABLE_CONFIG_CONTROL' => false,
				'CAN_HIDE_FIELD' => false,
				'ENABLE_AVAILABLE_FIELDS_INJECTION' => true,
				'ENABLE_EXTERNAL_LAYOUT_RESOLVERS' => false,
				'CAN_UPDATE_COMMON_CONFIGURATION' => true,
				'ENABLE_USER_FIELD_CREATION' => LegalInfo::canAdd($this->currentUser->getId()),
				'ENABLE_USER_FIELD_SELECTION' => LegalInfo::canEdit($this->currentUser->getId()),
				'ENABLE_BOTTOM_PANEL' => true,
				'ENABLE_SECTION_DRAG_DROP' => false,
				'ENABLE_FIELD_DRAG_DROP' => false,
				'SHOW_EMPTY_FIELDS' => false,
				'ENABLE_USER_FIELD_MANDATORY_CONTROL' => false,
				'DUPLICATE_CONTROL' => [],
				'ENTITY_TYPE_TITLE' => '',
				'ADDITIONAL_FIELDS_DATA' => [],
				'CUSTOM_TOOL_PANEL_BUTTONS' => [],
				'READ_ONLY' => !LegalInfo::canEdit($this->currentUser->getId()),
				'USER_FIELD_CREATE_SIGNATURE' => Dispatcher::instance()->getCreateSignature(['ENTITY_ID' => LegalInfo::USER_FIELD_ENTITY_ID]),
				'COMPONENT_AJAX_DATA' => [
					'COMPONENT_NAME' => $this->getName(),
					'ACTION_NAME' => "save",
					'SIGNED_PARAMETERS' => $this->getSignedParameters()
				],
				'SCOPE' => \Bitrix\UI\Form\EntityEditorConfigScope::COMMON,
			]
		);
	}

	public function getFieldInfo($userFields): array
	{
		return array_values($this->getUserFieldInfos($userFields));
	}

	public function getFieldConfig($userFields): array
	{
		$title = Loc::getMessage("SIGN_USER_PROFILE_LEGAL_SECTION_CONTACT_TITLE");
		return [
			[
				'name' => 'legal',
				'title' => $title,
				'type' => 'section',
				'elements' => LegalInfo::getElementsForEntityEditor($userFields),
				'data' => ['isChangeable' => true, 'isRemovable' => false]
			]
		];
	}

	public function getFieldData(array $fields): array
	{
		$result = [];
		$userFieldDispatcher = Dispatcher::instance();

		$userFieldInfos = $this->getUserFieldInfos($fields);
		foreach($fields as $fieldName => $userField)
		{
			$fieldValue = isset($userField['VALUE']) ? $userField['VALUE'] : '';
			$fieldData = isset($userFieldInfos[$fieldName]) ? $userFieldInfos[$fieldName] : null;

			if(!is_array($fieldData))
			{
				continue;
			}

			$isEmptyField = true;
			$fieldParams = $fieldData['data']['fieldInfo'];
			if((is_string($fieldValue) && $fieldValue !== '')
				|| (is_array($fieldValue) && !empty($fieldValue))
			)
			{
				$fieldParams['VALUE'] = $fieldValue;
				$isEmptyField = false;
			}

			$fieldSignature = $userFieldDispatcher->getSignature($fieldParams);
			if($isEmptyField)
			{
				$result[$fieldName] = array(
					'SIGNATURE' => $fieldSignature,
					'IS_EMPTY' => true
				);
			}
			else
			{
				$result[$fieldName] = array(
					'VALUE' => $fieldValue,
					'SIGNATURE' => $fieldSignature,
					'IS_EMPTY' => false
				);
			}
		}

		return $result;
	}

	public function getUserFields(int $userId)
	{
		global $USER_FIELD_MANAGER;
		return $USER_FIELD_MANAGER->GetUserFields(LegalInfo::USER_FIELD_ENTITY_ID, $userId, LANGUAGE_ID);
	}

	private function getUserFieldInfos($userFields): array
	{
		$result = [];
		foreach($userFields as $userField)
		{
			$fieldName = $userField['FIELD_NAME'];
			$fieldInfo = array(
				'USER_TYPE_ID' => $userField['USER_TYPE_ID'],
				'ENTITY_ID' => LegalInfo::USER_FIELD_ENTITY_ID,
				'ENTITY_VALUE_ID' => $this->arParams['PROFILE_ID'],
				'FIELD' => $fieldName,
				'MULTIPLE' => $userField['MULTIPLE'],
				'MANDATORY' => $userField['MANDATORY'],
				'SETTINGS' => $userField['SETTINGS'] ?? null
			);

			$result[$fieldName] = array(
				'name' => $fieldName,
				'title' => $userField['EDIT_FORM_LABEL'] ?? $fieldName,
				'type' => 'userField',
				'data' => ['fieldInfo' => $fieldInfo],
				'editable' => $userField['EDIT_IN_LIST'] === "Y"
			);

			if(isset($userField['MANDATORY']) && $userField['MANDATORY'] === 'Y')
			{
				$result[$fieldName]['required'] = true;
			}
		}

		return $result;
	}

	protected function getConfigurationCategoryName(): string
	{
		return 'sign.legal.form.editor';
	}

	protected function getConfigurationOptionCategoryName(): string
	{
		return 'sign.legal.form.editor';
	}

	private function calculateInitialMode(iterable $fields): string
	{
		foreach ($fields as $field)
		{
			if (!empty($field['VALUE']))
			{
				return 'view';
			}
		}

		return 'edit';
	}

	private function getTitle(): string
	{
		$userNameTemplate = empty($this->arParams['USER_NAME_TEMPLATE'])
			? CSite::GetNameFormat(false)
			: str_replace(["#NOBR#", "#/NOBR#"], ["", ""], $this->arParams["USER_NAME_TEMPLATE"])
		;

		$userTableQueryResult = UserTable::getRowById((int)$this->arParams['PROFILE_ID']);

		$name =  (string)\CUser::FormatName(
			$userNameTemplate,
			[
				'LOGIN' => $userTableQueryResult['LOGIN'],
				'NAME' => $userTableQueryResult['NAME'],
				'LAST_NAME' => $userTableQueryResult['LAST_NAME'],
				'SECOND_NAME' => $userTableQueryResult['SECOND_NAME'],
			],
			false,
			false
		);

		return $name . '. ' . Loc::getMessage('SIGN_USER_PROFILE_LEGAL_SECTION_CONTACT_TITLE');
	}

	public function getErrors(): ?ErrorCollection
	{
		return $this->errorCollection;
	}

	public function getErrorByCode($code)
	{
		$this->errorCollection->getErrorByCode($code);
	}

	private function getAvailableTypeFields(): array
	{
		return [
			'string',
			'date',
			'address',
			'url',
			'enumeration',
		];
	}
}
