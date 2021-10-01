<?php

namespace Bitrix\ImOpenlines\QuickAnswers;

use Bitrix\ImOpenLines\Config;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Text\Emoji;
use Bitrix\Main\UserTable;

Loc::loadLanguageFile(__FILE__);

class ListsDataManager extends DataManager
{
	const CACHE_TTL = 7200;
	const CACHE_PATH = '/bx/imopenlines/quick/main/';

	const TYPE = 'lists';
	const IBLOCK_CODE = 'IMOP_QUICK_ANSWERS';

	const RIGHTS_IBLOCK_FOR_LINE_QUEUE = 'iblock_limited_edit';
	const RIGHTS_XML_ID = 'LINE_QUEUE_AND_HEADS';

	protected $iblockId;
	protected $accessDenied = false;

	/**
	 * ListsDataManager constructor.
	 * @param int $lineId
	 * @throws ArgumentNullException
	 */
	public function __construct($lineId)
	{
		$lineId = intval($lineId);
		if($lineId > 0)
		{
			$iblock = $this->getStorageByLineId($lineId);
			if($iblock)
			{
				$this->iblockId = $iblock['ID'];
			}
			else
			{
				$this->iblockId = self::createStorage($lineId);
			}
		}
		else
		{
			throw new ArgumentNullException('lineId');
		}
	}

	/**
	 * Returns true if this datamanager is ready to work
	 *
	 * @return bool
	 */
	protected function isReady()
	{
		return ($this->iblockId > 0);
	}

	/**
	 * Adds new record to the Iblock.
	 *
	 * @param array $data
	 * @return bool
	 */
	public function add($data)
	{
		if(!self::initModules())
		{
			return false;
		}
		$item = new \CIBlockElement();

		$data = $this->prepareData($data, true);
		return $item->add($data);
	}

	/**
	 * Returns list of records from the iblock.
	 *
	 * @param array $filter
	 * @param int $offset
	 * @param int $limit
	 * @return array|bool
	 */
	public function getList($filter = array(), $offset = 0, $limit = 0)
	{
		if(!self::initModules())
		{
			return false;
		}
		$filter = $this->prepareData($filter);
		$navigationParams = false;
		if($offset > 0)
		{
			if(!$limit)
			{
				$limit = 10;
			}
			$navigationParams = array('iNumPage' => ($offset / $limit + 1), 'nPageSize' => $limit);
		}
		elseif($limit > 0)
		{
			$navigationParams = array('nTopCount' => $limit);
		}
		return $this->getArrayFromResult(\CIBlockElement::getList(array('sort' => 'desc'), $filter, false, $navigationParams));
	}

	/**
	 * Updates record.
	 *
	 * @param $id
	 * @param $data
	 * @return bool
	 */
	public function update($id, $data)
	{
		if(!self::initModules())
		{
			return false;
		}
		if(self::getById($id))
		{
			$item = new \CIBlockElement();
			return $item->update($id, $this->prepareData($data));
		}
		return false;
	}

	/**
	 * Delete record on id.
	 *
	 * @param $id
	 * @return bool
	 */
	public function delete($id)
	{
		if(!self::initModules())
		{
			return false;
		}
		if(self::getById($id))
		{
			$item = new \CIBlockElement();
			return $item->delete($id);
		}

		return false;
	}

	/**
	 * Returns record from iblock on id.
	 *
	 * @param $id
	 * @return bool|mixed|null
	 */
	public function getById($id)
	{
		if(!self::initModules())
		{
			return false;
		}
		if($id > 0)
		{
			return reset(self::getList(array('ID' => $id), 1));
		}

		return null;
	}

	/**
	 * Creates new list and iblock.
	 *
	 * @param int $lineId
	 * @param int $userId
	 * @return bool
	 */
	public static function createStorage($lineId, $userId = 0)
	{
		if(!self::initModules())
		{
			return false;
		}

		$configManager = new Config();
		if($config = $configManager->get($lineId, true))
		{
			$name = $config['LINE_NAME'];
			$description = Loc::getMessage('IMOL_QA_IBLOCK_DESCRIPTION').' '.$config['LINE_NAME'];
			$queue = $config['QUEUE'];
		}
		else
		{
			return false;
		}

		$iblockFields = array(
			'NAME' => $name,
			'CODE' => self::IBLOCK_CODE,
			'DESCRIPTION' => $description,
			'IBLOCK_TYPE_ID' => self::TYPE,
			'WORKFLOW' => 'N',
			'ELEMENTS_NAME' => Loc::getMessage('IMOL_QA_IBLOCK_ELEMENTS_NAME'),
			'ELEMENT_NAME' => Loc::getMessage('IMOL_QA_IBLOCK_ELEMENT_NAME'),
			'ELEMENT_ADD' => Loc::getMessage('IMOL_QA_IBLOCK_ELEMENT_ADD'),
			'ELEMENT_EDIT' => Loc::getMessage('IMOL_QA_IBLOCK_ELEMENT_EDIT'),
			'ELEMENT_DELETE' => Loc::getMessage('IMOL_QA_IBLOCK_ELEMENT_DELETE'),
			'SECTIONS_NAME' => Loc::getMessage('IMOL_QA_IBLOCK_SECTIONS_NAME'),
			'SECTION_NAME' => Loc::getMessage('IMOL_QA_IBLOCK_SECTION_NAME'),
			'SECTION_ADD' => Loc::getMessage('IMOL_QA_IBLOCK_SECTION_ADD'),
			'SECTION_EDIT' => Loc::getMessage('IMOL_QA_IBLOCK_SECTION_EDIT'),
			'SECTION_DELETE' => Loc::getMessage('IMOL_QA_IBLOCK_SECTION_DELETE'),
			//'SOCNET_GROUP_ID' => '',
			'BIZPROC' => 'N',
			'SITE_ID' => array(self::getDefaultSiteID()),
			'RIGHTS_MODE' => 'E',
			'RIGHTS' => self::getRights(self::RIGHTS_IBLOCK_FOR_LINE_QUEUE, $queue, $userId),
		);
		$iblock = new \CIBlock();
		$iblockId = $iblock->Add($iblockFields);
		if($iblockId > 0)
		{
			$list = new \CList($iblockId);
			$list->AddField(array(
				'SORT' => 20,
				'NAME' => GetMessage('IMOL_QA_IBLOCK_NAME_FIELD'),
				'IS_REQUIRED' => 'Y',
				'MULTIPLE' => 'N',
				'TYPE' => 'NAME',
				'DEFAULT_VALUE' => '',
			));
			$list->AddField(array(
				'SORT' => 30,
				'NAME' => GetMessage('IMOL_QA_IBLOCK_TEXT_FIELD'),
				'IS_REQUIRED' => 'Y',
				'MULTIPLE' => 'N',
				'TYPE' => 'DETAIL_TEXT',
				'DEFAULT_VALUE' => '',
			));
			$list->AddField(array(
				'SORT' => 40,
				'NAME' => GetMessage('IMOL_QA_IBLOCK_RATING_FIELD'),
				'IS_REQUIRED' => 'N',
				'MULTIPLE' => 'N',
				'TYPE' => 'SORT',
				'DEFAULT_VALUE' => '1',
			));
			$list->Save();

			global $CACHE_MANAGER;
			$CACHE_MANAGER->ClearByTag('lists_list_any');
			$CACHE_MANAGER->CleanDir('menu');

			$section = new \CIBlockSection();
			foreach(self::getSectionNames() as $code => $sectionDesc)
			{
				$section->add(array(
					'SORT' => $sectionDesc['SORT'],
					'ACTIVE' => 'Y',
					'NAME' => $sectionDesc['NAME'],
					'CODE' => $code,
					'IBLOCK_ID' => $iblockId,
				));
			}

			$gridId = 'lists_list_elements_'.$iblockId;
			$gridOption = new \Bitrix\Main\Grid\Options($gridId);
			$gridOption->SetSorting('SORT', 'desc');
			$gridOption->save();
			$gridOption->SetVisibleColumns(array('NAME', 'DETAIL_TEXT', 'SORT', 'IBLOCK_SECTION_ID'));
			$gridOption->SetDefaultView($gridOption->getCurrentOptions());

			$configManager->update($lineId, [
				'QUICK_ANSWERS_IBLOCK_ID' => $iblockId,
				'SKIP_MODIFY_MARK' => 'Y',
			]);

			return $iblockId;
		}
		/*else
		{
			echo $iblock->LAST_ERROR;
		}*/

		return false;
	}

	/**
	 * Returns array of rights for iblock.
	 *
	 * @param $rightCode
	 * @param array $users
	 * @param int $userWithNoXmlId
	 * @return array
	 */
	protected static function getRights($rightCode, array $users, $userWithNoXmlId = 0)
	{
		$result = array();
		$userWithNoXmlId = intval($userWithNoXmlId);
		if($userWithNoXmlId > 0)
		{
			$users[] = $userWithNoXmlId;
		}
		$rightsList = \CIBlockRights::GetRightsList(false);
		$rightTaskId = array_search($rightCode, $rightsList);
		if($rightTaskId)
		{
			$i = 0;
			foreach($users as $user)
			{
				$code = $user;
				if(intval($user) == $user)
				{
					$code = 'U'.$user;
				}
				$result['n' . $i] = array(
					'TASK_ID' => $rightTaskId,
					'GROUP_CODE' => $code,
				);
				if($user != $userWithNoXmlId)
				{
					$result['n' . $i]['XML_ID'] = self::RIGHTS_XML_ID;
				}
				$i++;
			}
		}
		return $result;
	}

	/**
	 * Returns array of iblock description on openline id.
	 *
	 * @param int $lineId
	 * @return array|false|null
	 * @throws ArgumentNullException
	 * @throws ArgumentOutOfRangeException
	 */
	protected function getStorageByLineId($lineId)
	{
		if(empty($lineId))
		{
			throw new ArgumentNullException('lineId');
		}
		if(intval($lineId) == 0)
		{
			throw new ArgumentOutOfRangeException('lineId', '1');
		}
		if(!self::initModules())
		{
			return false;
		}

		$configManager = new Config();
		$config = $configManager->get($lineId);
		if($config && $config['QUICK_ANSWERS_IBLOCK_ID'] > 0)
		{
			$iblockWithRights = \CIBlock::getList(array(), array('ID' => $config['QUICK_ANSWERS_IBLOCK_ID'], 'ACTIVE' => 'Y', 'TYPE' => self::TYPE))->fetch();
			if(!$iblockWithRights)
			{
				$iblockWithoutRights = \CIBlock::getList(array(), array('ID' => $config['QUICK_ANSWERS_IBLOCK_ID'], 'ACTIVE' => 'Y', 'TYPE' => self::TYPE, 'CHECK_PERMISSIONS' => 'N'))->fetch();
				if($iblockWithoutRights)
				{
					$this->accessDenied = true;
					return $iblockWithoutRights;
				}
			}
			return $iblockWithRights;
		}

		return false;
	}

	/**
	 * Init required modules.
	 *
	 * @return bool
	 */
	protected static function initModules()
	{
		return (Loader::includeModule('lists') && Loader::includeModule('iblock'));
	}

	/**
	 * Returns id of the default site.
	 *
	 * @return string
	 */
	protected static function getDefaultSiteID()
	{
		$siteEntity = new \CSite();
		$dbSites = $siteEntity->GetList('sort', 'desc', array('DEFAULT' => 'Y', 'ACTIVE' => 'Y'));
		$defaultSite = is_object($dbSites) ? $dbSites->Fetch() : null;
		if(is_array($defaultSite))
		{
			return $defaultSite['LID'];
		}

		return 's1';
	}

	/**
	 * Returns array with fields description.
	 *
	 * @return array
	 */
	protected function getMapFields()
	{
		return array(
			'TEXT' => array(
				'NAME' => 'DETAIL_TEXT',
				'REQUIRED' => true,
			),
			'RATING' => array(
				'NAME' => 'SORT',
				'REQUIRED' => false,
				'DEFAULT_VALUE' => '1',
			),
			'ACTIVE' => array(
				'DEFAULT_VALUE' => 'Y',
			),
			'CATEGORY' => array(
				'NAME' => 'IBLOCK_SECTION_ID',
			),
			'MESSAGEID' => array(
				'NAME' => 'CODE',
			),
		);
	}

	/**
	 * Returns array ready to work with iblocks.
	 *
	 * @param $data
	 * @param bool $fillDefault
	 * @return array
	 */
	protected function prepareData($data, $fillDefault = false)
	{
		$mapFields = $this->getMapFields();
		foreach($mapFields as $fieldName => $fieldDescription)
		{
			/*if($fieldDescription['REQUIRED'] && !isset($data[$fieldName]))
			{
			}*/
			if($fillDefault && isset($fieldDescription['DEFAULT_VALUE']) && !isset($data[$fieldName]))
			{
				$data[$fieldName] = $fieldDescription['DEFAULT_VALUE'];
			}
			if(isset($data[$fieldName]))
			{
				if(isset($fieldDescription['NAME']))
				{
					$data[$fieldDescription['NAME']] = $data[$fieldName];
					unset($data[$fieldName]);
				}
			}
		}
		$data['IBLOCK_ID'] = $this->iblockId;
		if (isset($data['DETAIL_TEXT']))
		{
			$data['DETAIL_TEXT'] = Emoji::encode($data['DETAIL_TEXT']);
		}
		if (isset($data['NAME']))
		{
			$data['NAME'] = Emoji::encode($data['NAME']);
		}

		return $data;
	}

	/**
	 * Parse data to get it back to QuickAnswer object.
	 *
	 * @param array $data
	 * @return array
	 */
	protected function parseData($data)
	{
		$mapFields = $this->getMapFields();
		foreach($mapFields as $fieldName => $fieldDescription)
		{
			if(isset($fieldDescription['NAME']) && isset($data[$fieldDescription['NAME']]))
			{
				$data[$fieldName] = $data[$fieldDescription['NAME']];
				unset($data[$fieldDescription['NAME']]);
			}
		}

		if (isset($data['TEXT']))
		{
			$data['TEXT'] = Emoji::decode($data['TEXT']);
		}
		if (isset($data['NAME']))
		{
			$data['NAME'] = Emoji::decode($data['NAME']);
		}

		return $data;
	}

	/**
	 * Returns array from CDBResult.
	 *
	 * @param \CDBResult|null $result
	 * @return array
	 */
	protected function getArrayFromResult(\CDBResult $result = null)
	{
		$array = array();
		if($result)
		{
			while($item = $result->fetch())
			{
				$array[] = $this->parseData($item);
			}
		}

		return $array;
	}

	/**
	 * Return url to the manage List in public.
	 *
	 * @return string
	 */
	public function getUrlToList()
	{
		if(self::initModules() && $this->isReady())
		{
			$list = new \CList($this->iblockId);
			$urlTemplate = $list->getUrlByIblockId($this->iblockId);
			if($urlTemplate == '')
			{
				if(file_exists($_SERVER['DOCUMENT_ROOT'].'/company/lists'))
					$urlTemplate = '/company/lists/'.$this->iblockId.'/element/#section_id#/#element_id#/';
				else
					$urlTemplate = '/services/lists/'.$this->iblockId.'/element/#section_id#/#element_id#/';
			}
			$urlTemplate = str_replace(array('#section_id#', '#element_id#/', 'element'), array(0, '', 'view'), $urlTemplate);
			return $urlTemplate;
		}

		return '';
	}

	/**
	 * Returns list of default section names for iblock.
	 *
	 * @return array
	 */
	protected static function getSectionNames()
	{
		return array(
			'GREETING' => array('NAME' => Loc::getMessage('IMOL_QA_IBLOCK_GREETING_SECTION'), 'SORT' => 10),
			'PAYMENT' => array('NAME' => Loc::getMessage('IMOL_QA_IBLOCK_PAYMENT_SECTION'), 'SORT' => 20),
			'DELIVERY' => array('NAME' => Loc::getMessage('IMOL_QA_IBLOCK_DELIVERY_SECTION'), 'SORT' => 30),
			'COMMON' => array('NAME' => Loc::getMessage('IMOL_QA_IBLOCK_COMMON_SECTION'), 'SORT' => 40),
		);
	}

	/**
	 * Returns list of sections for current iblock.
	 *
	 * @return array
	 */
	public function getSectionList()
	{
		static $result = false;
		if($result === false)
		{
			$result = array();
			if(self::initModules() && $this->isReady())
			{
				$filter = array();
				$filter['IBLOCK_ID'] = $this->iblockId;
				$filter['ACTIVE'] = 'Y';
				if(!isset($filter['SECTION_ID']))
				{
					$filter['SECTION_ID'] = false;
				}
				$sections = \CIBlockSection::getList(array('sort' => 'asc'), $filter);
				while($section = $sections->fetch())
				{
					$result[$section['ID']] = $section;
				}
			}
		}
		return $result;
	}

	/**
	 * Returns count of all records
	 *
	 * @param array $filter
	 * @return int
	 */
	public function getCount($filter = array())
	{
		$filter = $this->prepareData($filter);
		return \CIBlockElement::getList(array(), $filter, array());
	}

	/**
	 * Returns array of iblock descriptions that can be used as list for quick answers
	 *
	 * @return array
	 */
	public static function getStorageList()
	{
		$result = array();
		$iblocks = \CIBlock::getList(array('SORT' => 'ASC'), array('ACTIVE' => 'Y', 'TYPE' => self::TYPE, 'CODE' => self::IBLOCK_CODE, 'CHECK_PERMISSIONS' => 'N'));
		while($iblock = $iblocks->fetch())
		{
			$result[$iblock['ID']] = array('NAME' => $iblock['NAME']);
		}

		return $result;
	}

	/**
	 * Updates rights to the iblock with ID=$iblockId.
	 * Users from all lines with this iblockId as a storage for quick answers get edit rights.
	 *
	 * @param int $iblockId
	 * @return bool
	 */
	public static function updateIblockRights($iblockId)
	{
		$storages = self::getStorageList();
		if(intval($iblockId) <= 0)
		{
			return false;
		}
		if(isset($storages[$iblockId]))
		{
			$users = array();
			$configManager = new Config();
			$configs = $configManager->getList(array(
				'filter' => array(
					'QUICK_ANSWERS_IBLOCK_ID' => $iblockId,
				),
			), array('QUEUE' => 'Y'));

			foreach($configs as $config)
			{
				$users = array_merge($users, $config['QUEUE']);
			}
			$departments = array();
			// get all departments of the users
			$usersData = UserTable::getList(array(
				'select' => array('UF_DEPARTMENT'),
				'filter' => array(
					'=ID' => $users,
				),
			))->fetchAll();
			foreach($usersData as $userData)
			{
				if(!is_array($userData['UF_DEPARTMENT']) && $userData['UF_DEPARTMENT'] > 0)
				{
					$departments[] = $userData['UF_DEPARTMENT'];
				}
				else
				{
					$departments = array_merge($departments, $userData['UF_DEPARTMENT']);
				}
			}
			$departments = array_unique($departments);
			foreach($departments as $department)
			{
				$users[] = \CIntranetUtils::GetDepartmentManagerID($department);
			}
			$users = array_unique($users);

			$fields = array(
				'RIGHTS_MODE' => 'E',
				'RIGHTS' => self::getRights(self::RIGHTS_IBLOCK_FOR_LINE_QUEUE, $users)
			);
			$rightsManager = new \CIBlockRights($iblockId);
			$currentRights = $rightsManager->GetRights();
			foreach($currentRights as $id => $right)
			{
				if($right['XML_ID'] != self::RIGHTS_XML_ID)
				{
					$fields['RIGHTS'][$id] = $right;
				}
			}
			$iblock = new \CIBlock();

			$iblock->Update($iblockId, $fields);

			return true;
		}

		return false;
	}

	/**
	 * Returns true ic current user has rights to work with current list.
	 *
	 * @return bool
	 */
	public function isHasRights()
	{
		return $this->accessDenied !== true;
	}

	public function getIblockId()
	{
		return $this->iblockId;
	}
}