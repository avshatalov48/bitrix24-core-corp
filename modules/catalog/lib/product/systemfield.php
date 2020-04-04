<?php
namespace Bitrix\Catalog\Product;

use Bitrix\Main,
	Bitrix\Main\Loader,
	Bitrix\Main\Localization\LanguageTable,
	Bitrix\Main\Localization\Loc,
	Bitrix\Main\ModuleManager,
	Bitrix\Main\TaskTable,
	Bitrix\Main\Text,
	Bitrix\Catalog,
	Bitrix\Highloadblock as Highload;

final class SystemField
{
	const STORAGE_TABLE_NAME_PREFIX = 'b_hlsys_';

	const STORAGE_NAME_PREFIX = 'PRODUCT_';

	const FIELD_PREFIX = 'UF_';

	const CODE_MARKING_CODE_GROUP = 'MARKING_CODE_GROUP';

	/** @var bool */
	private static $highloadInclude = null;

	/** @var bool */
	private static $bitrix24Include = null;

	private static $storageList = [];

	private static $languages = [];

	private static $dictionary = [];

	/** @var array */
	private static $currentFieldSet = null;

	/**
	 * @return string
	 */
	public static function execAgent()
	{
		if (!self::isExistHighloadBlock())
			return '';
		if (!self::checkHighloadBlock())
			return '\Bitrix\Catalog\Product\SystemField::execAgent();';
		self::create();
		return '';
	}

	/**
	 * @return void
	 */
	public static function create()
	{
		self::$currentFieldSet = null;
		self::createMarkingCodeGroup();
	}

	/**
	 * @return void
	 */
	public static function delete()
	{
		self::$currentFieldSet = null;
	}

	/**
	 * @return array
	 */
	public static function getFieldList()
	{
		if (self::$currentFieldSet === null)
		{
			self::$currentFieldSet = [];

			self::initStorageList();

			$userField = new \CUserTypeEntity();
			$iterator = $userField->GetList(
				[],
				[
					'ENTITY_ID' => Catalog\ProductTable::getUfId(),
					'FIELD_NAME' => self::$storageList[self::CODE_MARKING_CODE_GROUP]['UF_FIELD']
				]
			);
			$row = $iterator->Fetch();
			unset($iterator, $userField);
			if (!empty($row))
			{
				self::$currentFieldSet['MARKING_CODE_GROUP'] = self::$storageList[self::CODE_MARKING_CODE_GROUP]['UF_FIELD'];
			}
			unset($row);

		}
		return self::$currentFieldSet;
	}

	/**
	 * @param array &$row
	 * @return void
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function convertRow(array &$row)
	{
		if (!isset($row['MARKING_CODE_GROUP']))
			return;
		if (!isset(self::$dictionary[self::CODE_MARKING_CODE_GROUP]))
			self::$dictionary[self::CODE_MARKING_CODE_GROUP] = [];
		$id = (int)$row['MARKING_CODE_GROUP'];
		if ($id <= 0)
			return;
		if (!isset(self::$dictionary[self::CODE_MARKING_CODE_GROUP][$id]))
		{
			self::$dictionary[self::CODE_MARKING_CODE_GROUP][$id] = false;
			$storage = self::$storageList[self::CODE_MARKING_CODE_GROUP];
			$entity = Highload\HighloadBlockTable::compileEntity($storage['NAME']);
			$entityDataClass = $entity->getDataClass();
			$iterator = $entityDataClass::getList([
				'select' => ['*'],
				'filter' => ['=ID' => $id]
			]);
			$data = $iterator->fetch();
			if (!empty($data) && isset($data['UF_XML_ID']))
			{
				self::$dictionary[self::CODE_MARKING_CODE_GROUP][$id] = $data['UF_XML_ID'];
			}
			unset($data, $iterator);
		}
		if (self::$dictionary[self::CODE_MARKING_CODE_GROUP][$id] !== false)
		{
			$row['MARKING_CODE_GROUP'] = self::$dictionary[self::CODE_MARKING_CODE_GROUP][$id];
		}
		else
		{
			$row['MARKING_CODE_GROUP'] = null;
		}
		unset($id);
	}

	/**
	 * @return bool
	 */
	private static function isExistHighloadBlock()
	{
		return Main\IO\Directory::isDirectoryExists(
			Main\Application::getDocumentRoot().'/bitrix/modules/highloadblock/'
		);
	}

	/**
	 * @return bool
	 */
	private static function checkHighloadBlock()
	{
		$result = self::initHighloadBlock();
		if (!$result)
			self::highloadBlockAlert();
		return $result;
	}

	/**
	 * @return bool
	 */
	private static function initHighloadBlock()
	{
		if (self::$highloadInclude === null)
			self::$highloadInclude = Loader::includeModule('highloadblock');
		return self::$highloadInclude;
	}

	/**
	 * @return void
	 *
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private static function highloadBlockAlert()
	{
		if (
			!self::initBitrix24()
			&& self::isExistHighloadBlock()
			&& !ModuleManager::isModuleInstalled('highloadblock')
		)
		{
			$iterator = \CAdminNotify::GetList([], ['MODULE_ID' => 'catalog', 'TAG' => 'HIGHLOADBLOCK_ABSENT']);
			while ($row = $iterator->Fetch())
			{
				\CAdminNotify::Delete($row['ID']);
			}
			unset($row, $iterator);

			$defaultLang = '';
			$messages = [];
			$iterator = LanguageTable::getList([
				'select' => ['ID', 'DEF'],
				'filter' => ['=ACTIVE' => 'Y']
			]);
			while ($row = $iterator->fetch())
			{
				if ($defaultLang == '')
					$defaultLang = $row['ID'];
				if ($row['DEF'] == 'Y')
					$defaultLang = $row['ID'];
				$languageId = $row['ID'];
				Loc::loadLanguageFile(
					__FILE__,
					$languageId
				);
				$messages[$languageId] = Loc::getMessage(
					'BX_CATALOG_PRODUCT_SYSTEMFIELD_ERR_HIGHLOADBLOCK_ABSENT',
					['#LANGUAGE_ID#' => $languageId],
					$languageId
				);
			}
			unset($languageId, $row, $iterator);

			if (!empty($messages))
			{
				\CAdminNotify::Add([
					'MODULE_ID' => 'catalog',
					'TAG' => 'HIGHLOADBLOCK_ABSENT',
					'ENABLE_CLOSE' => 'Y',
					'NOTIFY_TYPE' => \CAdminNotify::TYPE_ERROR,
					'MESSAGE' => $messages[$defaultLang],
					'LANG' => $messages
				]);
			}
			unset($messages, $defaultLang);
		}
	}

	/**
	 * @return bool
	 */
	private static function initBitrix24()
	{
		if (self::$bitrix24Include === null)
			self::$bitrix24Include = Loader::includeModule('bitrix24');
		return self::$bitrix24Include;
	}

	/**
	 * @return void
	 */
	private static function initStorageList()
	{
		if (!empty(self::$storageList))
			return;
		self::$storageList[self::CODE_MARKING_CODE_GROUP] = [
			'TABLE_NAME' => self::getStorageTableName(self::CODE_MARKING_CODE_GROUP),
			'NAME' => self::getStorageName(self::CODE_MARKING_CODE_GROUP),
			'UF_FIELD' => self::FIELD_PREFIX.'PRODUCT_GROUP'
		];
	}

	/**
	 * @return array
	 */
	private static function getLanguages()
	{
		if (empty(self::$languages))
		{
			$iterator = LanguageTable::getList([
				'select' => ['ID'],
				'filter' => ['=ACTIVE' => 'Y']
			]);
			while ($row = $iterator->fetch())
			{
				self::$languages[] = $row['ID'];
			}
			unset($row, $iterator);
		}
		return self::$languages;
	}

	/**
	 * @param string $code
	 * @return string
	 */
	private static function getStorageTableName(string $code)
	{
		return self::STORAGE_TABLE_NAME_PREFIX.''.strtolower($code);
	}

	/**
	 * @param string $code
	 * @return string
	 */
	private static function getStorageName(string $code)
	{
		return Text\StringHelper::snake2camel(self::STORAGE_NAME_PREFIX.$code);
	}

	/**
	 * @param string $code
	 * @return array|null
	 */
	private static function getStorageDescription(string $code)
	{
		self::initStorageList();
		return (isset(self::$storageList[$code]) ? self::$storageList[$code] : null);
	}

	/**
	 * @param string $code
	 * @return array
	 */
	private static function getStorageLangTitles(string $code)
	{
		$result = [];

		$languages = self::getLanguages();
		if (!empty($languages))
		{
			$messageId = 'STORAGE_'.$code.'_TITLE';
			foreach ($languages as $languageId)
			{
				$message = (string)Loc::getMessage($messageId, null, $languageId);
				if ($message !== '')
				{
					$result[$languageId] = $message;
				}
			}
			unset($message, $languageId);
		}
		unset($languages);

		return $result;
	}

	/**
	 * @return array
	 */
	private static function getStorageDefaultRights()
	{
		$result = [];
		if (self::initHighloadBlock())
		{
			$iterator = TaskTable::getList([
				'select' => ['ID', 'LETTER'],
				'filter' => ['@LETTER' => ['R', 'W'], '=MODULE_ID' => 'highloadblock', '=SYS' => 'Y']
			]);
			while ($row = $iterator->fetch())
			{
				$result[$row['LETTER']] = $row['ID'];
			}
			unset($row, $iterator);
			if (count($result) != 2)
				$result = [];
		}
		return $result;
	}

	private static function createMarkingCodeGroup()
	{
		if (!self::allowedMarkingCodeGroup())
			return;

		if (!self::checkHighloadBlock())
			return;

		$storage = self::createMarkingCodeGroupStorage();
		if (!empty($storage))
		{
			self::createMarkingCodeGroupField($storage);
		}
	}

	/**
	 * @return bool
	 */
	private static function allowedMarkingCodeGroup()
	{
		if (!self::initBitrix24())
		{
			$iterator = LanguageTable::getList([
				'select' => ['ID'],
				'filter' => ['=ID' => 'ru', '=ACTIVE' => 'Y']
			]);
			$row = $iterator->fetch();
			unset($iterator);
			if (empty($row))
				return false;
			$iterator = LanguageTable::getList([
				'select' => ['ID'],
				'filter' => ['@ID' => ['ua', 'by', 'kz'], '=ACTIVE' => 'Y'],
				'limit' => 1
			]);
			$row = $iterator->fetch();
			unset($iterator);
			if (!empty($row))
				return false;
			return true;
		}
		else
		{
			return (\CBitrix24::getPortalZone() === 'ru');
		}
	}

	private static function createMarkingCodeGroupStorage()
	{
		if (!self::checkHighloadBlock())
			return null;

		$storage = self::getStorageDescription(self::CODE_MARKING_CODE_GROUP);
		if (empty($storage))
			return null;

		$iterator = Highload\HighloadBlockTable::getList([
			'select' => ['ID', 'NAME', 'TABLE_NAME'],
			'filter' => ['=TABLE_NAME' => $storage['TABLE_NAME']]
		]);
		$row = $iterator->fetch();
		unset($iterator);
		if (!empty($row))
		{
			$row['UF_FIELD'] = $storage['UF_FIELD'];
			$storage = $row;
		}
		else
		{
			$result = Highload\HighloadBlockTable::add($storage);
			if (!$result->isSuccess())
				return null;
			$storage['ID'] = $result->getId();
			unset($result);
		}
		unset($row);

		$titleList = self::getStorageLangTitles(self::CODE_MARKING_CODE_GROUP);
		if (!empty($titleList))
		{
			Highload\HighloadBlockLangTable::delete($storage['ID']);
			foreach ($titleList as $languageId => $title)
			{
				Highload\HighloadBlockLangTable::add([
					'ID' => $storage['ID'],
					'LID' => $languageId,
					'NAME' => $title
				]);
			}
			unset($languageId, $title);
		}
		unset($titleList);

		$rights = self::getStorageDefaultRights();
		if (!empty($rights))
		{
			$accessList = [
				[
					'HL_ID' => $storage['ID'],
					'ACCESS_CODE' => 'G1',
					'TASK_ID' => $rights['W']
				],
				[
					'HL_ID' => $storage['ID'],
					'ACCESS_CODE' => 'G2',
					'TASK_ID' => $rights['R']
				]
			];
			foreach ($accessList as $access)
			{
				$iterator = Highload\HighloadBlockRightsTable::getList([
					'select' => ['ID'],
					'filter' => ['=HL_ID' => $storage['ID'], '=ACCESS_CODE' => $access['ACCESS_CODE']]
				]);
				$row = $iterator->fetch();
				if (!empty($row))
				{
					Highload\HighloadBlockRightsTable::update($row['ID'], $access);
				}
				else
				{
					Highload\HighloadBlockRightsTable::add($access);
				}
			}
			unset($row, $iterator);
			unset($access, $accessList);
		}
		unset($rights);

		$storage = self::createMarkingCodeGroupStoreageFields($storage);

		self::fillMarkingCodeGroups($storage);

		return $storage;
	}

	private static function createMarkingCodeGroupField(array $storage)
	{
		$userField = new \CUserTypeEntity();

		$settings = [
			'HLBLOCK_ID' => $storage['ID'],
			'HLFIELD_ID' => $storage['FIELDS']['NAME'],
			'DEFAULT_VALUE' => '',
			'DISPLAY' => \CUserTypeHlblock::DISPLAY_LIST,
			'LIST_HEIGHT' => 1
		];
		$languages = self::getLanguages();
		$messageList = [
			'EDIT_FORM_LABEL' => [],
			'LIST_COLUMN_LABEL' => [],
			'LIST_FILTER_LABEL' => []
		];
		foreach ($languages as $languageId)
		{
			$message = (string)Loc::getMessage('MARKING_CODE_GROUP_FIELD_TITLE', null, $languageId);
			if ($message !== '')
			{
				$messageList['EDIT_FORM_LABEL'][$languageId] = $message;
				$messageList['LIST_COLUMN_LABEL'][$languageId] = $message;
				$messageList['LIST_FILTER_LABEL'][$languageId] = $message;
			}
		}
		unset($message, $languageId, $languages);

		$description = [
			'ENTITY_ID' => Catalog\ProductTable::getUfId(),
			'FIELD_NAME' => $storage['UF_FIELD'],
			'USER_TYPE_ID' => \CUserTypeHlblock::USER_TYPE_ID,
			'XML_ID' => self::CODE_MARKING_CODE_GROUP,
			'SORT' => 100,
			'MULTIPLE' => 'N',
			'MANDATORY' => 'N',
			'SHOW_FILTER' => 'S',
			'SHOW_IN_LIST' => 'Y',
			'EDIT_IN_LIST' => 'Y',
			'IS_SEARCHABLE' => 'N',
			'SETTINGS' => $settings,
			'EDIT_FORM_LABEL' => $messageList['EDIT_FORM_LABEL'],
			'LIST_COLUMN_LABEL' => $messageList['LIST_COLUMN_LABEL'],
			'LIST_FILTER_LABEL' => $messageList['LIST_FILTER_LABEL']
		];

		$iterator = $userField->GetList(
			[],
			[
				'ENTITY_ID' => $description['ENTITY_ID'],
				'FIELD_NAME' => $description['FIELD_NAME']
			]
		);
		$row = $iterator->Fetch();
		unset($iterator);
		$id = 0;
		if (!empty($row))
		{
			if ($userField->Update($row['ID'], $description))
			{
				$id = $row['ID'];
			}
		}
		else
		{
			$id = (int)$userField->Add($description);
		}
		unset($id);
		unset($row);
	}

	private static function createMarkingCodeGroupStoreageFields(array $storage)
	{
		$entityId = 'HLBLOCK_'.$storage['ID'];
		$fieldSettings = [
			'XML_ID' => [
				'DEFAULT_VALUE' => '',
				'SIZE' => 16,
				'ROWS' => 1,
				'MIN_LENGTH' => 0,
				'MAX_LENGTH' => 0,
				'REGEXP' => '/^[0-9]{1,16}$/'
			],
			'NAME' => [
				'DEFAULT_VALUE' => '',
				'SIZE' => 100,
				'ROWS' => 1,
				'MIN_LENGTH' => 1,
				'MAX_LENGTH' => 255,
				'REGEXP' => ''
			]
		];

		$languages = self::getLanguages();

		$userField = new \CUserTypeEntity();

		$storage['FIELDS'] = [];

		$sort = 100;
		foreach (array_keys($fieldSettings) as $fieldId)
		{
			$messageList = [
				'EDIT_FORM_LABEL' => [],
				'LIST_COLUMN_LABEL' => [],
				'LIST_FILTER_LABEL' => []
			];
			foreach ($languages as $languageId)
			{
				$message = (string)Loc::getMessage('MARKING_CODE_GROUP_UF_FIELD_'.$fieldId, null, $languageId);
				if ($message !== '')
				{
					$messageList['EDIT_FORM_LABEL'][$languageId] = $message;
					$messageList['LIST_COLUMN_LABEL'][$languageId] = $message;
					$messageList['LIST_FILTER_LABEL'][$languageId] = $message;
				}
			}
			unset($message, $languageId);

			$storage['FIELDS'][$fieldId] = null;

			$description = [
				'ENTITY_ID' => $entityId,
				'FIELD_NAME' => self::FIELD_PREFIX.$fieldId,
				'USER_TYPE_ID' => \CUserTypeString::USER_TYPE_ID,
				'XML_ID' => $fieldId,
				'SORT' => $sort,
				'MULTIPLE' => 'N',
				'MANDATORY' => 'Y',
				'SHOW_FILTER' => 'S',
				'SHOW_IN_LIST' => 'Y',
				'EDIT_IN_LIST' => 'N',
				'IS_SEARCHABLE' => 'N',
				'SETTINGS' => $fieldSettings[$fieldId],
				'EDIT_FORM_LABEL' => $messageList['EDIT_FORM_LABEL'],
				'LIST_COLUMN_LABEL' => $messageList['LIST_COLUMN_LABEL'],
				'LIST_FILTER_LABEL' => $messageList['LIST_FILTER_LABEL']
			];

			$iterator = $userField->GetList(
				[],
				[
					'ENTITY_ID' => $description['ENTITY_ID'],
					'FIELD_NAME' => $description['FIELD_NAME']
				]
			);
			$row = $iterator->Fetch();
			$id = 0;
			if (!empty($row))
			{
				if ($userField->Update($row['ID'], $description))
				{
					$id = $row['ID'];
				}
			}
			else
			{
				$id = (int)$userField->Add($description);
			}
			if ($id > 0)
			{
				$storage['FIELDS'][$fieldId] = $id;
			}

			$sort += 100;
		}

		unset($userField);

		return $storage;
	}

	private static function fillMarkingCodeGroups(array $storage)
	{
		$groupList = [
			[
				'UF_XML_ID' => '02',
				'UF_NAME' => Loc::getMessage('MARKING_CODE_GROUP_TYPE_02', '', 'ru')
			],
			[
				'UF_XML_ID' => '03',
				'UF_NAME' => Loc::getMessage('MARKING_CODE_GROUP_TYPE_03', '', 'ru')
			],
			[
				'UF_XML_ID' => '05',
				'UF_NAME' => Loc::getMessage('MARKING_CODE_GROUP_TYPE_05', '', 'ru')
			],
			[
				'UF_XML_ID' => '5048',
				'UF_NAME' => Loc::getMessage('MARKING_CODE_GROUP_TYPE_5048', '', 'ru')
			]
		];

		$entity = Highload\HighloadBlockTable::compileEntity($storage);
		$entityDataClass = $entity->getDataClass();

		foreach ($groupList as $group)
		{
			$iterator = $entityDataClass::getList([
				'select' => ['ID'],
				'filter' => ['=UF_XML_ID' => $group['UF_XML_ID']]
			]);
			$row = $iterator->fetch();
			if (!empty($row))
			{
				$entityDataClass::update($row['ID'], $group);
			}
			else
			{
				$entityDataClass::add($group);
			}
		}
		unset($row, $iterator);
		unset($entityDataClass, $entity);
		unset($group, $groupList);
	}
}