<?php
namespace Bitrix\ImBot\Itr;

class Designer
{
	public $botId = 0;
	public $userId = 0;
	public $dialogId = '';
	public $portalId = '';

	private $cacheId = '';
	private static $executed = false;

	private $menuItems = Array();
	private $menuText = Array();

	private $currentMenu = 0;
	private $skipShowMenu = false;

	public function __construct($portalId, $dialogId, $botId, $userId)
	{
		\Bitrix\Main\Loader::includeModule('im');
		\Bitrix\Main\Loader::includeModule('imopenlines');

		$this->portalId = $portalId;
		$this->userId = $userId;
		$this->botId = $botId;
		$this->dialogId = $dialogId;

		$this->userData = \Bitrix\Im\User::getInstance($userId);

		$this->getCurrentMenu();
	}

	public function addMenu(Menu $items)
	{
		$this->menuText[$items->getId()] = $items->getText();
		$this->menuItems[$items->getId()] = $items->getItems();

		return true;
	}

	private function getCurrentMenu()
	{
		$this->cacheId = md5($this->portalId.$this->botId.$this->dialogId);

		if (\Bitrix\Main\IO\File::isFileExists($_SERVER['DOCUMENT_ROOT'].'/upload/imopenlines/itr/'.$this->cacheId.'.cache'))
		{
			$this->currentMenu = intval(\Bitrix\Main\IO\File::getFileContents($_SERVER['DOCUMENT_ROOT'].'/upload/imopenlines/itr/'.$this->cacheId.'.cache'));
		}
	}

	private function setCurrentMenu($id)
	{
		$this->currentMenu = intval($id);
		\Bitrix\Main\IO\File::putFileContents($_SERVER['DOCUMENT_ROOT'].'/upload/imopenlines/itr/'.$this->cacheId.'.cache', $this->currentMenu);
	}

	private function execMenuItem($itemId = '')
	{
		if ($itemId === '')
		{
			return true;
		}
		else if ($itemId === "0")
		{
			$this->skipShowMenu = true;
		}

		if (!isset($this->menuItems[$this->currentMenu][$itemId]))
		{
			return false;
		}

		$menuItemAction = $this->menuItems[$this->currentMenu][$itemId]['ACTION'];

		if ($menuItemAction['HIDE_MENU'])
		{
			$this->skipShowMenu = true;
		}

		if (isset($menuItemAction['TEXT']))
		{
			$messageText = str_replace('#USER_NAME#', $this->userData->getName(), $menuItemAction['TEXT']);

			\Bitrix\Im\Bot::addMessage(Array('BOT_ID' => $this->botId), Array(
				'DIALOG_ID' => $this->dialogId,
				'MESSAGE' => $messageText
			));
		}

		if ($menuItemAction['TYPE'] == Item::TYPE_MENU)
		{
			$this->setCurrentMenu($menuItemAction['MENU']);
		}
		else if ($menuItemAction['TYPE'] == Item::TYPE_QUEUE)
		{
			$chat = new \Bitrix\Imopenlines\Chat(substr($this->dialogId, 4));
			$chat->endBotSession();
		}
		else if ($menuItemAction['TYPE'] == Item::TYPE_USER)
		{
			$chat = new \Bitrix\Imopenlines\Chat(substr($this->dialogId, 4));
			$chat->transfer(Array(
				'FROM' => $this->botId,
				'TO' => $menuItemAction['USER_ID'],
				'MODE' => \Bitrix\Imopenlines\Chat::TRANSFER_MODE_BOT,
				'LEAVE' => $menuItemAction['LEAVE']? 'Y': 'N'
			));
		}
		else if ($menuItemAction['TYPE'] == Item::TYPE_BOT)
		{
			$botId = 0;
			$bots = \Bitrix\Im\Bot::getListCache();
			foreach ($bots as $botData)
			{
				if ($botData['CODE'] == $menuItemAction['BOT_CODE'] && $botData['OPENLINE'] == 'Y')
				{
					$botId = $botData['BOT_ID'];
					break;
				}
			}
			if ($botId)
			{
				$chat = new \CIMChat($this->botId);
				$chat->AddUser(substr($this->dialogId, 4), $botId);
				$chat->DeleteUser(substr($this->dialogId, 4), $this->botId);
			}
			else if ($menuItemAction['ERROR_TEXT'])
			{
				$messageText = str_replace('#USER_NAME#', $this->userData->getName(), $menuItemAction['ERROR_TEXT']);
				\Bitrix\Im\Bot::addMessage(Array('BOT_ID' => $this->botId), Array(
					'DIALOG_ID' => $this->dialogId,
					'MESSAGE' => $messageText
				));
				$this->skipShowMenu = false;
			}
		}
		else if ($menuItemAction['TYPE'] == Item::TYPE_FINISH)
		{
			$chat = new \Bitrix\Imopenlines\Chat(substr($this->dialogId, 4));
			$chat->answer($this->botId);
			$chat->finish();
		}
		else if ($menuItemAction['TYPE'] == Item::TYPE_FUNCTION)
		{
			$menuItemAction['FUNCTION']($this);
		}

		return true;
	}

	private function getMenuItems()
	{
		$messageText = '';
		if ($this->skipShowMenu)
		{
			$this->skipShowMenu = false;
			return $messageText;
		}

		if (isset($this->menuText[$this->currentMenu]))
		{
			$messageText = $this->menuText[$this->currentMenu].'[br]';
		}

		foreach ($this->menuItems[$this->currentMenu] as $itemId => $data)
		{
			$messageText .= '[send='.$itemId.']'.$itemId.'. '.$data['TITLE'].'[/send][br]';
		}

		$messageText = str_replace('#USER_NAME#', $this->userData->getName(), $messageText);

		\Bitrix\Im\Bot::addMessage(Array('BOT_ID' => $this->botId), Array(
			'DIALOG_ID' => $this->dialogId,
			'MESSAGE' => $messageText
		));

		return true;
	}

	public function run($text)
	{
		if (self::$executed)
			return false;
		
		list($itemId) = explode(" ", $text);

		$this->execMenuItem($itemId);

		$this->getMenuItems();
		
		self::$executed = true;

		return true;
	}
}