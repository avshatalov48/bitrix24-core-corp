<?php

namespace Bitrix\Sign\Service\Providers;

use Bitrix\Main\Application;
use Bitrix\Main\Entity\Base;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UserTable;
use Bitrix\Sign\Config\LegalInfo;
use Bitrix\UI\Form\EntityEditorConfigScope;
use Bitrix\UI\Form\EntityEditorConfiguration;
use CLanguage;
use CUserTypeEntity;

class LegalInfoProviderAgentService
{
	public static function installLegalConfig(): bool
	{
		self::installUserFields();
		self::setConfigForProfileLegalEntityEditor();

		return '';
	}

	private static function installUserFields(): void
	{
		$userType = new CUserTypeEntity();
		foreach (self::getLegalProperties() as $property)
		{
			$dbRes = CUserTypeEntity::GetList(
				[],
				[
					'ENTITY_ID' => $property['ENTITY_ID'],
					'FIELD_NAME' => $property['FIELD_NAME'],
				]
			);

			if ($dbRes->Fetch())
			{
				continue;
			}

			$langsDb = CLanguage::GetList('', '');
			while ($arLang = $langsDb->Fetch())
			{
				$phrase = Loc::getMessage(code: $property['FIELD_NAME'], language: $arLang['LID']);
				$property['EDIT_FORM_LABEL'][$arLang['LID']] = $phrase;
				$property['LIST_COLUMN_LABEL'][$arLang['LID']] = $phrase;
				$property['LIST_FILTER_LABEL'][$arLang['LID']] = $phrase;
			}

			$userType->Add($property);
		}

		Base::destroy(UserTable::getEntity());
	}

	private static function setConfigForProfileLegalEntityEditor(): void
	{
		$editorConfig = new EntityEditorConfiguration(LegalInfo::EDITOR_CONFIG_CATEGORY_NAME);
		$savedConfiguration = $editorConfig->get(
			LegalInfo::EDITOR_CONFIG_CONFIG_ID,
			EntityEditorConfigScope::COMMON
		);

		if (!$savedConfiguration)
		{
			$elements = [];
			$elements[] = [
				'name' => 'legal',
				'title' => Loc::getMessage('SIGN_USER_PROFILE_LEGAL_SECTION_CONTACT_TITLE'),
				'type' => 'section',
				'data' => [
					'isChangeable' => true,
					'isRemovable' => false,
				],
				'elements' => self::getElementsForEntityEditor(self::getLegalProperties()),
			];

			$config = [];
			$config[] = [
				'name' => 'default_column',
				'type' => 'column',
				'elements' => $elements,
			];

			$editorConfig->set(
				LegalInfo::EDITOR_CONFIG_CONFIG_ID,
				$config,
				['scope' => EntityEditorConfigScope::COMMON]
			);
		}
	}

	private static function getElementsForEntityEditor($userFields): array
	{
		return array_map(
			static fn($property) => [
				'name' => $property['FIELD_NAME'],
				'optionFlags' => 0,
			],
			$userFields
		);
	}

	private static function getLegalProperties(): array
	{
		$fields = [
			'UF_LEGAL_NAME' => [
				'ENTITY_ID' => 'USER_LEGAL',
				'FIELD_NAME' => 'UF_LEGAL_NAME',
				'USER_TYPE_ID' => 'string',
				'XML_ID' => null,
				'SORT' => 100,
				'MULTIPLE' => 'N',
				'MANDATORY' => 'N',
				'SHOW_FILTER' => 'N',
				'SHOW_IN_LIST' => 'Y',
				'EDIT_IN_LIST' => 'Y',
				'IS_SEARCHABLE' => 'N',
			],
			'UF_LEGAL_LAST_NAME' => [
				'ENTITY_ID' => 'USER_LEGAL',
				'FIELD_NAME' => 'UF_LEGAL_LAST_NAME',
				'USER_TYPE_ID' => 'string',
				'XML_ID' => null,
				'SORT' => 100,
				'MULTIPLE' => 'N',
				'MANDATORY' => 'N',
				'SHOW_FILTER' => 'N',
				'SHOW_IN_LIST' => 'Y',
				'EDIT_IN_LIST' => 'Y',
				'IS_SEARCHABLE' => 'N',
			],
			'UF_LEGAL_PATRONYMIC_NAME' => [
				'ENTITY_ID' => 'USER_LEGAL',
				'FIELD_NAME' => 'UF_LEGAL_PATRONYMIC_NAME',
				'USER_TYPE_ID' => 'string',
				'XML_ID' => null,
				'SORT' => 100,
				'MULTIPLE' => 'N',
				'MANDATORY' => 'N',
				'SHOW_FILTER' => 'N',
				'SHOW_IN_LIST' => 'Y',
				'EDIT_IN_LIST' => 'Y',
				'IS_SEARCHABLE' => 'N',
			],
			'UF_LEGAL_POSITION' => [
				'ENTITY_ID' => 'USER_LEGAL',
				'FIELD_NAME' => 'UF_LEGAL_POSITION',
				'USER_TYPE_ID' => 'string',
				'XML_ID' => null,
				'SORT' => 100,
				'MULTIPLE' => 'N',
				'MANDATORY' => 'N',
				'SHOW_FILTER' => 'N',
				'SHOW_IN_LIST' => 'Y',
				'EDIT_IN_LIST' => 'Y',
				'IS_SEARCHABLE' => 'N',
			],
			'UF_LEGAL_ADDRESS' => [
				'ENTITY_ID' => 'USER_LEGAL',
				'FIELD_NAME' => 'UF_LEGAL_ADDRESS',
				'USER_TYPE_ID' => 'string',
				'XML_ID' => null,
				'SORT' => 100,
				'MULTIPLE' => 'N',
				'MANDATORY' => 'N',
				'SHOW_FILTER' => 'N',
				'SHOW_IN_LIST' => 'Y',
				'EDIT_IN_LIST' => 'Y',
				'IS_SEARCHABLE' => 'N',
			],
		];

		if (Application::getInstance()->getLicense()->getRegion() === 'ru')
		{
			$fields['UF_LEGAL_SNILS'] = [
				'ENTITY_ID' => 'USER_LEGAL',
				'FIELD_NAME' => 'UF_LEGAL_SNILS',
				'USER_TYPE_ID' => 'snils',
				'XML_ID' => null,
				'SORT' => 100,
				'MULTIPLE' => 'N',
				'MANDATORY' => 'N',
				'SHOW_FILTER' => 'N',
				'SHOW_IN_LIST' => 'Y',
				'EDIT_IN_LIST' => 'Y',
				'IS_SEARCHABLE' => 'N',
			];
			$fields['UF_LEGAL_INN'] = [
				'ENTITY_ID' => 'USER_LEGAL',
				'FIELD_NAME' => 'UF_LEGAL_INN',
				'USER_TYPE_ID' => 'string',
				'XML_ID' => null,
				'SORT' => 100,
				'MULTIPLE' => 'N',
				'MANDATORY' => 'N',
				'SHOW_FILTER' => 'N',
				'SHOW_IN_LIST' => 'Y',
				'EDIT_IN_LIST' => 'Y',
				'IS_SEARCHABLE' => 'N',
			];
		}

		return $fields;
	}

}
