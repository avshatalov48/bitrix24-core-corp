<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage intranet
 * @copyright 2001-2016 Bitrix
 */

namespace Bitrix\Crm\SiteButton;

use Bitrix\Main\Context;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Entity\Result as EntityResult;

Loc::loadMessages(__FILE__);

/**
 * Class Button
 * @package Bitrix\Crm\SiteButton
 */
class Button
{
	/** @var  integer|null */
	protected $id;

	/** @var array  */
	protected $data = array(
		'ACTIVE' => 'Y',
		'IS_SYSTEM' => 'N',
		'LOCATION' => Internals\ButtonTable::ENUM_LOCATION_BOTTOM_RIGHT,
		'DELAY' => 0,
		'LANGUAGE_ID' => '',
		'ITEMS' => array(),
		'SETTINGS' => array(
			'HELLO' => array(),
			'COPYRIGHT_REMOVED' => 'N',
		),
	);

	/** @var WorkTime|null  */
	protected $workTime = null;

	/** @var array  */
	protected $errors = array();

	/** @var bool  */
	protected $fileErrors = false;

	public function __construct($id = null)
	{
		$this->workTime = new WorkTime();

		if ($id)
		{
			$this->load($id);
		}
	}

	public function isSystem()
	{
		return $this->data['IS_SYSTEM'] == 'Y';
	}

	public function setSystem()
	{
		$this->data['IS_SYSTEM'] = 'Y';
	}

	public function getLanguageId()
	{
		return ($this->data['LANGUAGE_ID'] ? $this->data['LANGUAGE_ID'] : Context::getCurrent()->getLanguage());
	}

	public static function copy($buttonId, $userId = null)
	{
		// copy button
		$button = Internals\ButtonTable::getRowById($buttonId);
		if(!$button)
		{
			return null;
		}

		unset($button['ID'], $button['DATE_CREATE'], $button['ACTIVE_CHANGE_DATE'], $button['SECURITY_CODE'], $button['XML_ID']);
		$button['NAME'] = Loc::getMessage('CRM_BUTTON_COPY_NAME_PREFIX') . ' ' . $button['NAME'];
		$button['ACTIVE'] = 'N';
		$button['IS_SYSTEM'] = 'N';
		$button['ACTIVE_CHANGE_BY'] = $userId;
		$button['LOCATION'] = (int) $button['LOCATION'];
		$button['DATE_CREATE'] = new DateTime();
		$resultAdd = Internals\ButtonTable::add($button);
		if(!$resultAdd->isSuccess())
		{
			return null;
		}
		$newFormId = $resultAdd->getId();
		Script::saveCache(new static($newFormId));

		return $newFormId;
	}

	public static function activate($buttonId, $isActivate = true, $changeUserBy = null)
	{
		$updateFields = array('ACTIVE' => $isActivate ? 'Y' : 'N');
		if($changeUserBy)
		{
			$updateFields['ACTIVE_CHANGE_DATE'] = new DateTime();
			$updateFields['ACTIVE_CHANGE_BY'] = $changeUserBy;
		}

		$button = new Button();
		$button->load($buttonId);
		$button->mergeData($updateFields);
		$updateResult = Internals\ButtonTable::update($buttonId, $updateFields);
		if($updateResult->isSuccess())
		{
			Script::saveCache($button);
			return true;
		}
		else
		{
			return false;
		}
	}

	public static function delete($buttonId, $forceSystem = false)
	{
		$button = Internals\ButtonTable::getRowById($buttonId);
		if(!$button || (!$forceSystem && $button['IS_SYSTEM'] == 'Y'))
		{
			return false;
		}

		Script::removeCache(new Button($buttonId));
		$deleteResult = Internals\ButtonTable::delete($buttonId);
		if($deleteResult->isSuccess())
		{

			return true;
		}
		else
		{
			return false;
		}
	}

	public function getId()
	{
		return $this->id;
	}

	public function getItemByType($type)
	{
		if (!is_array($this->data['ITEMS']) || !isset($this->data['ITEMS'][$type]))
		{
			return null;
		}

		$result = $this->data['ITEMS'][$type];
		$result['PATH_EDIT'] = '';
		$result['EXTERNAL_NAME'] = null;

		$resultItemType = null;
		$itemTypeList = Manager::getWidgetList();
		foreach ($itemTypeList as $itemType)
		{
			if ($itemType['TYPE'] == $type)
			{
				$resultItemType = $itemType;
			}
		}

		if ($resultItemType)
		{
			$resultExternalIds = explode(',', $result['EXTERNAL_ID']);
			foreach ($resultItemType['LIST'] as $external)
			{
				if (in_array($external['ID'], $resultExternalIds))
				{
					$result['EXTERNAL_NAME'] = $external['NAME'];
					break;
				}
			}

			$result['PATH_EDIT'] = str_replace(
				array($resultItemType['PATH_EDIT']['id']),
				array($result['EXTERNAL_ID']),
				$resultItemType['PATH_EDIT']['path']
			);
		}

		return $result;
	}

	public function getOpenLine()
	{
		return $this->getItemByType(Manager::ENUM_TYPE_OPEN_LINE);
	}

	public function getCrmForm()
	{
		return $this->getItemByType(Manager::ENUM_TYPE_CRM_FORM);
	}

	public function getWebFormIdList()
	{
		$list = [];
		$item = $this->getCrmForm();
		if ($item && !empty($item['EXTERNAL_ID']))
		{
			$list[] = (int) $item['EXTERNAL_ID'];
		}
		$item = $this->getCallback();
		if ($item && !empty($item['EXTERNAL_ID']))
		{
			$list[] = (int) $item['EXTERNAL_ID'];
		}

		return $list;
	}

	public function getCallback()
	{
		return $this->getItemByType(Manager::ENUM_TYPE_CALLBACK);
	}

	public function hasActiveItem($type)
	{
		$item = $this->getItemByType($type);
		return $item && ($item['ACTIVE'] == 'Y') && $item['EXTERNAL_ID'];
	}

	public function hasItem($type)
	{
		$item = $this->getItemByType($type);
		return $item && $item['EXTERNAL_ID'];
	}

	public function hasItemPages($type)
	{
		$item = $this->getItemByType($type);
		if (!$item || !$item['EXTERNAL_ID'] || !is_array($item['PAGES']) || !is_array($item['PAGES']['LIST']))
		{
			return false;
		}

		$pages = $item['PAGES']['LIST'][$item['PAGES']['MODE']];
		trimArr($pages);
		return (is_array($pages) && count($pages) > 0);
	}

	public function isCopyrightRemoved()
	{
		return $this->data['SETTINGS']['COPYRIGHT_REMOVED'] == 'Y';
	}

	public function save()
	{
		$fields = $this->data;
		unset($fields['ID']);

		if(!$this->check($fields))
		{
			return false;
		}

		if($this->id)
		{
			$resultSave = Internals\ButtonTable::update($this->id, $fields);
		}
		else
		{
			$resultSave = Internals\ButtonTable::add($fields);
			if($resultSave->isSuccess())
			{
				$this->load($resultSave->getId());
			}
		}

		$this->errors = $resultSave->getErrorMessages();

		if(!Script::saveCache($this))
		{
			$this->fileErrors = true;
		}
		/*
		$script = new Script($this);
		if(!$script->saveCache())
		{
			$this->errors = array_merge(
				$this->errors,
				$script->getErrors()
			);
		}
		*/

		return $this->id;
	}

	public function check($fields)
	{

		$resultCheck = new EntityResult;
		Internals\ButtonTable::checkFields($resultCheck, $this->id, $fields);
		$this->errors = $resultCheck->getErrorMessages();
		$isSuccess = $resultCheck->isSuccess();

		$haveItems = false;
		$typeList = Manager::getTypeList();
		foreach ($typeList as $typeId => $typeName)
		{
			if (!$this->hasActiveItem($typeId))
			{
				continue;
			}

			$haveItems = true;
			break;
		}
		if (!$haveItems)
		{
			$this->errors[] = Loc::getMessage('CRM_BUTTON_ERROR_WIDGET_NOT_SELECTED');
			$isSuccess = false;
		}

		return $isSuccess;
	}

	public function loadByData(array $data)
	{
		$this->id = $data['ID'];
		$this->setData($data);
	}

	public function load($id)
	{
		$this->id = $id;
		if ($data = Internals\ButtonTable::getRowById($id))
		{
			if (!isset($data['SETTINGS']))
			{
				$data['SETTINGS'] = array(
					'HELLO' => array(),
					'COPYRIGHT_REMOVED' => 'N',
				);
			}
			$this->setData($data);
		}
	}

	public function generateCache()
	{

	}

	public function getCacheLink()
	{

	}

	/**
	 * Returns work time settings
	 *
	 * @return array|null
	 */
	public function getItemWorkTime($typeId)
	{
		$item = $this->getItemByType($typeId);
		if(!$item || !is_array($item['WORK_TIME']))
		{
			$item['WORK_TIME'] = array();
		}

		$this->workTime->setArray($item['WORK_TIME']);
		$workTime = $this->workTime->getArray();
		if ($workTime['ACTION_RULE'] == 'text' && !$workTime['ACTION_TEXT'])
		{
			$dict = WorkTime::getDictionaryArray();
			$workTime['ACTION_TEXT'] = isset($dict['ACTION_TEXT'][$typeId]) ? $dict['ACTION_TEXT'][$typeId] : '';
		}
		return $workTime;
	}

	public function getData()
	{
		return $this->data;
	}

	public function setData(array $data)
	{
		$this->data = $data;
		unset($this->data['ID']);
	}

	public function mergeData(array $data)
	{
		if (is_array($this->data))
		{
			$this->setData($data + $this->data);
		}
		else
		{
			$this->setData($data);
		}
	}

	public function getErrors()
	{
		return $this->errors;
	}

	public function hasErrors()
	{
		return count($this->errors) > 0;
	}

	public function hasFileErrors()
	{
		return $this->fileErrors;
	}
}
