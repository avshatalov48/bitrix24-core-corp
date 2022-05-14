<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Voximplant\Security\Permissions;

Loc::loadMessages(__FILE__);

class CVoximplantIvrEditComponent extends \CBitrixComponent
{
	const MAX_DEPTH = 20;
	//const MAX_FILE_SIZE = 2097152; //2 Mb
	const MAX_FILE_SIZE = 209715200;
	protected $id = 0;

	protected static $itemsToDelete = array();

	/** @var int Used for saving reference in new items */
	static $ivrId;


	public function executeComponent()
	{
		if(!\Bitrix\Main\Loader::includeModule('voximplant'))
			return false;

		if(!$this::checkAccess())
			return false;

		$this->init();
		$this->arResult = $this->prepareData();
		if(isset($_REQUEST['html']))
			$this->includeComponentTemplate('template.html');
		else
			$this->includeComponentTemplate();
		return $this->arResult;
	}

	protected static function checkAccess()
	{
		$userPermissions = Permissions::createWithCurrentUser();
		return $userPermissions->canPerform(Permissions::ENTITY_SETTINGS, Permissions::ACTION_MODIFY);
	}

	public static function saveIvr(array $ivr)
	{
		$result = new \Bitrix\Main\Result();

		if(!\Bitrix\Voximplant\Ivr\Ivr::isEnabled())
		{
			$result->addError(new \Bitrix\Main\Error(GetMessage('VOX_IVR_EDIT_ERROR_IVR_NOT_AVAILABLE')));
			return $result;
		}

		if(!static::checkAccess())
		{
			$result->addError(new \Bitrix\Main\Error(GetMessage('VOX_IVR_EDIT_ERROR_ACCESS_DENIED')));
			return $result;
		}

		$ivrFields = array(
			'NAME' => $ivr['NAME'],
		);

		if($ivr['ID'] > 0)
		{
			\Bitrix\Voximplant\Model\IvrTable::update($ivr['ID'], $ivrFields);
		}
		else
		{
			$addResult = \Bitrix\Voximplant\Model\IvrTable::add($ivrFields);
			$ivr['ID'] = $addResult->getId();
		}
		static::$ivrId = $ivr['ID'];

		$cursor = \Bitrix\Voximplant\Model\IvrItemTable::getList(array(
			'select' => array('ID'),
			'filter' => array(
				'IVR_ID' => $ivr['ID']
			)
		));
		self::$itemsToDelete = array();
		while($row = $cursor->fetch())
		{
			self::$itemsToDelete[$row['ID']] = true;
		}

		$ivr['ROOT_ITEM']['IVR_ID'] = $ivr['ID'];
		$itemSaveResult = static::saveItem($ivr['ROOT_ITEM'], 0);
		if(!$itemSaveResult->isSuccess())
		{
			$result->addErrors($itemSaveResult->getErrors());
			return $result;
		}

		$rootItemData = $itemSaveResult->getData();
		$rootItemId = $rootItemData['ID'];

		\Bitrix\Voximplant\Model\IvrTable::update($ivr['ID'], array(
			'FIRST_ITEM_ID' => $rootItemId
		));

		foreach (self::$itemsToDelete as $itemIdToDelete => $v)
		{
			\Bitrix\Voximplant\Model\IvrActionTable::deleteByItemId($itemIdToDelete);
			\Bitrix\Voximplant\Model\IvrItemTable::delete($itemIdToDelete);
		}

		$result->setData(array(
			'ID' => $ivr['ID'],
			'IVR' => (new \Bitrix\Voximplant\Ivr\Ivr($ivr['ID']))->toArray()
		));

		return $result;
	}

	protected static function saveItem(array $fields, $depth)
	{
		$result = new \Bitrix\Main\Result();

		if($depth >= static::getMaxDepth())
		{
			$result->addError(new \Bitrix\Main\Error(GetMessage('VOX_IVR_EDIT_ERROR_IVR_DEPTH_TOO_LARGE')));
			return $result;
		}

		$itemFields = array(
			'IVR_ID' => $fields['IVR_ID'] ?: static::$ivrId,
			'TYPE' => $fields['TYPE'],
			'TIMEOUT' => (int)$fields['TIMEOUT'] > 0 ? (int)$fields['TIMEOUT'] : 15,
			'TIMEOUT_ACTION' => $fields['TIMEOUT_ACTION'] ?: \Bitrix\Voximplant\Ivr\Item::TIMEOUT_ACTION_EXIT,
			'MESSAGE' => $fields['MESSAGE'],
			'TTS_VOICE' => $fields['TTS_VOICE'],
			'TTS_SPEED' => $fields['TTS_SPEED'],
			'TTS_VOLUME' => $fields['TTS_VOLUME'],
			'URL' => $fields['URL'],
			'FILE_ID' => $fields['FILE_ID'],
		);

		if($fields['ID'] > 0)
		{
			\Bitrix\Voximplant\Model\IvrItemTable::update((int)$fields['ID'], $itemFields);
			$itemFields['ID'] = (int)$fields['ID'];
			unset(static::$itemsToDelete[$itemFields['ID']]);
		}
		else
		{
			$addResult = \Bitrix\Voximplant\Model\IvrItemTable::add($itemFields);
			$itemFields['ID'] = $addResult->getId();
		}

		$actionsToDelete = array();
		$cursor = \Bitrix\Voximplant\Model\IvrActionTable::getList(array(
			'select' => array('ID'),
			'filter' => array('ITEM_ID' => $itemFields['ID'])
		));
		while ($row = $cursor->fetch())
		{
			$actionsToDelete[$row['ID']] = true;
		}

		if(is_array($fields['ACTIONS']))
		{
			foreach ($fields['ACTIONS'] as $action)
			{
				if($action['ACTION'] == '')
					continue;

				if($action['ACTION'] == \Bitrix\Voximplant\Ivr\Action::ACTION_ITEM && !is_array($action['ITEM']))
					continue;

				$action['ITEM_ID'] = $itemFields['ID'];
				$actionSaveResult = static::saveAction($action, $depth + 1);
				if(!$actionSaveResult->isSuccess())
				{
					$result->addErrors($actionSaveResult->getErrors());
					return $result;
				}
				if($action['ID'] > 0)
				{
					unset($actionsToDelete[$action['ID']]);
				}
			}
		}

		foreach ($actionsToDelete as $actionId => $value)
		{
			\Bitrix\Voximplant\Model\IvrActionTable::delete($actionId);
		}

		$result->setData(array(
			'ID' => $itemFields['ID']
		));

		return $result;
	}

	protected static function saveAction(array $fields, $depth)
	{
		$result = new \Bitrix\Main\Result();
		$actionFields = array(
			'ITEM_ID' => $fields['ITEM_ID'],
			'ACTION' => $fields['ACTION'],
			'DIGIT' => $fields['DIGIT'],
			'PARAMETERS' => array(),
		);
		switch ($fields['ACTION'])
		{
			case \Bitrix\Voximplant\Ivr\Action::ACTION_ITEM:
				$itemSaveResult = static::saveItem($fields['ITEM'], $depth);
				if(!$itemSaveResult->isSuccess())
				{
					$result->addErrors($itemSaveResult->getErrors());
					return $result;
				}
				$itemFields = $itemSaveResult->getData();
				$subItemId = $itemFields['ID'];
				$actionFields['PARAMETERS']['ITEM_ID'] = $subItemId;
				break;
			case \Bitrix\Voximplant\Ivr\Action::ACTION_PHONE:
				$actionFields['PARAMETERS']['PHONE_NUMBER'] = $fields['PARAMETERS']['PHONE_NUMBER'];
				break;
			case \Bitrix\Voximplant\Ivr\Action::ACTION_QUEUE:
				$actionFields['PARAMETERS']['QUEUE_ID'] = $fields['PARAMETERS']['QUEUE_ID'];
				break;
			case \Bitrix\Voximplant\Ivr\Action::ACTION_USER:
				$actionFields['PARAMETERS']['USER_ID'] = $fields['PARAMETERS']['USER_ID'];
				break;
			case \Bitrix\Voximplant\Ivr\Action::ACTION_VOICEMAIL:
				$actionFields['PARAMETERS']['USER_ID'] = $fields['PARAMETERS']['USER_ID'];
				break;
		}

		if($fields['ID'] > 0)
		{
			\Bitrix\Voximplant\Model\IvrActionTable::update((int)$fields['ID'], $actionFields);
			$actionFields['ID'] = (int)$fields['ID'];
		}
		else
		{
			$addResult = \Bitrix\Voximplant\Model\IvrActionTable::add($actionFields);
			$actionFields['ID'] = $addResult->getId();
		}

		$result->setData($actionFields);
		return $result;
	}

	public static function uploadFile($fields)
	{
		$result = new \Bitrix\Main\Result();

		if(!isset($_FILES['FILE']))
		{
			$result->addError(new \Bitrix\Main\Error(Loc::getMessage('VOX_IVR_FILE_UPLOAD_ERROR')));
			return $result;
		}

		if(!static::checkAccess())
		{
			$result->addError(new \Bitrix\Main\Error(GetMessage('VOX_IVR_EDIT_ERROR_ACCESS_DENIED')));
			return $result;
		}

		$uploadedFileDescriptor = $_FILES['FILE'];
		$messageFile = new Bitrix\Main\IO\File($uploadedFileDescriptor['tmp_name']);
		$messageFile->open('rb');

		if($messageFile->getSize() > self::MAX_FILE_SIZE)
		{
			$result->addError(new \Bitrix\Main\Error(Loc::getMessage('VOX_IVR_FILE_TOO_LARGE')));
			return $result;
		}

		$fileId = CFile::SaveFile($uploadedFileDescriptor, 'voximplant');
		if($fileId === false)
		{
			$result->addError(new \Bitrix\Main\Error(Loc::getMessage('VOX_IVR_FILE_UPLOAD_ERROR')));
			return $result;
		}

		$fileRecord = CFile::GetFileArray($fileId);
		$result->setData(array(
			'FILE_ID' => $fileId,
			'FILE_SRC' => $fileRecord['SRC']
		));

		return $result;
	}

	protected function init()
	{
		$this->id = (int)$_REQUEST['ID'];
	}

	protected function prepareData()
	{
		$result = array();
		if($this->id > 0)
		{
			$ivr = new \Bitrix\Voximplant\Ivr\Ivr($this->id);
			$result['IVR'] = $ivr->toTree(true);
			$result['NEW'] = false;
		}
		else
		{
			$result['NEW'] = true;
		}

		$result['TELEPHONY_GROUPS'] = \Bitrix\Voximplant\Model\QueueTable::getList(array(
			'select' => array('ID', 'NAME')
		))->fetchAll();

		if(\Bitrix\Main\Loader::includeModule('socialnetwork'))
		{
			$result['STRUCTURE'] = CSocNetLogDestination::GetStucture();
		}
		if(isset($ivr) && $ivr instanceof \Bitrix\Voximplant\Ivr\Ivr)
		{
			$result['USERS'] = $this->resolveUsers($ivr->toArray());
		}
		$result['TTS_DISCLAIMER'] = \Bitrix\Voximplant\Tts\Disclaimer::getHtml();
		
		return $result;
	}

	protected function resolveUsers(array $ivrStructure)
	{
		$userIds = array();

		if(!is_array($ivrStructure['ITEMS']))
			return array();

		foreach ($ivrStructure['ITEMS'] as $item)
		{
			if(is_array($item['ACTIONS']))
			{
				foreach ($item['ACTIONS'] as $action)
				{
					if(isset($action['PARAMETERS']['USER_ID']))
					{
						$userIds[] = $action['PARAMETERS']['USER_ID'];
					}
				}
			}
		}

		if(!empty($userIds))
		{
			return CSocNetLogDestination::GetUsers(array('id' => $userIds));
		}
	}

	protected static function getMaxDepth()
	{
		$licenseMaxDepth = \Bitrix\Voximplant\Limits::getIvrDepth();
		return  $licenseMaxDepth > 0 ? $licenseMaxDepth : static::MAX_DEPTH;
	}
}