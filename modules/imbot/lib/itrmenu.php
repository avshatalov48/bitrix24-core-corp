<?php declare(strict_types=1);

namespace Bitrix\ImBot;

use Bitrix\Main;
use Bitrix\Im\Bot\Keyboard;

/**
 * Interactive Bot Menu.
 * @package \Bitrix\Imbot
 */
class ItrMenu
{
	/** @var int */
	protected $botId;

	/** @var string */
	protected $dialogId;

	/** @var int */
	protected $messageId = 0;

	/** @var array */
	protected $menuState;

	/** @var array */
	protected $structure;

	/** @var DialogSession */
	protected $dialogSession;

	public const
		COMMAND_MENU = 'menu',
		MENU_EXIT_ID = 'exit',
		MENU_ENTRANCE_ID = 'default',
		MENU_ACTION_NEXT = 'MENU',
		MENU_ACTION_LINK = 'LINK',
		MENU_ACTION_HELP = 'HELPCODE',
		MENU_ACTION_QUEUE = 'QUEUE',
		MENU_BUTTON_ACTIVE = "#29619b"
	;

	public function __construct(int $botId)
	{
		$this->botId = $botId;
	}

	/**
	 * @param string $source Json serialized source data.
	 * @return bool
	 */
	public function loadSource(string $source): bool
	{
		try
		{
			$structure = Main\Web\Json::decode($source);
			if (is_array($structure))
			{
				$this->setStructure($structure);
				return true;
			}
		}
		catch (Main\ArgumentException $e)
		{
		}

		return false;
	}

	/**
	 * @return array{start: string, elements: array}|null
	 */
	public function getStructure(): ?array
	{
		return $this->structure;
	}

	/**
	 * @param array $structure
	 * @return self
	 */
	public function setStructure(array $structure): self
	{
		$this->structure = $structure;
		return $this;
	}

	/**
	 * @return string|null
	 */
	public function getDialogId(): ?string
	{
		return $this->dialogId;
	}

	/**
	 * @param string $dialogId
	 * @return self
	 */
	public function setDialogId(string $dialogId): self
	{
		$this->dialogId = $dialogId;
		return $this;
	}

	/**
	 * @param string|null $dialogId
	 * @return DialogSession
	 */
	public function getDialogSession(string $dialogId = null): DialogSession
	{
		if ($dialogId)
		{
			$this->dialogId = $dialogId;
		}

		if (!($this->dialogSession instanceof DialogSession))
		{
			$this->dialogSession = new DialogSession($this->botId, $this->dialogId);
		}

		return $this->dialogSession;
	}

	/**
	 * @param DialogSession $session
	 * @return self
	 */
	public function setDialogSession(DialogSession $session): self
	{
		$this->dialogSession = $session;
		return $this;
	}


	/**
	 * Returns user's menu track.
	 * @param string|null $dialogId
	 * @return array{menu_action: string, track: array, message_id: int}|null
	 */
	public function getState(string $dialogId = null): ?array
	{
		if ($dialogId)
		{
			$this->dialogId = $dialogId;
		}

		if ($this->menuState === null)
		{
			$sessData = $this->getDialogSession($this->dialogId)->load();
			if ($sessData && !empty($sessData['MENU_STATE']))
			{
				try
				{
					$menuState = Main\Web\Json::decode($sessData['MENU_STATE']);
					if (is_array($menuState))
					{
						$this->setState($menuState);
						if (isset($menuState['message_id']))
						{
							$this->setMessageId((int)$menuState['message_id']);
						}
					}
				}
				catch (Main\ArgumentException $e)
				{
				}
			}
			if ($this->menuState === null)
			{
				$this->menuState = ['message_id' => $this->messageId, 'track' => []];
			}
		}

		return $this->menuState;
	}

	/**
	 * Sets new menu track.
	 *
	 * @param array $menuState
	 * @return $this
	 */
	public function setState(array $menuState): self
	{
		$this->menuState = $menuState;
		return $this;
	}

	/**
	 * Clear menu state.
	 * @return $this
	 */
	public function resetState(): self
	{
		$this->menuState = null;
		$this->messageId = 0;
		return $this;
	}

	/**
	 * Append data to menu state.
	 *
	 * @param array $data Data to append
	 * @return $this
	 */
	public function addStateData(array $data): self
	{
		$this->menuState = array_merge_recursive($this->menuState, $data);
		return $this;
	}

	/**
	 * Saves user's menu track.
	 *
	 * @return bool
	 */
	public function saveState(): bool
	{
		if (
			!isset($this->menuState['message_id'])
			|| ($this->menuState['message_id'] !== $this->getMessageId())
		)
		{
			if ($this->getMessageId() > 0)
			{
				$this->menuState['message_id'] = $this->getMessageId();
			}
		}
		$this->getDialogSession($this->dialogId)->start([
			'GREETING_SHOWN' => 'Y',
			'MENU_STATE' => $this->menuState,
		]);

		return true;
	}

	/**
	 * @return string[]
	 */
	public function getTrack(): array
	{
		$menuState = $this->getState();
		return isset($menuState['track']) && is_array($menuState['track']) ? $menuState['track'] : [];
	}

	/**
	 * @return string|null
	 */
	public function getLastTrackItemId(): ?string
	{
		$track = $this->getTrack();
		return !empty($track) ? end($track) : null;
	}

	/**
	 * @return string|null
	 */
	public function getLastExistTrackItemId(): ?string
	{
		$track = $this->getTrack();
		if (!empty($track))
		{
			$lastMenuItemId = end($track);
			do
			{
				if (
					$lastMenuItemId !== null
					&& $this->getItem($lastMenuItemId) !== null
				)
				{
					return $lastMenuItemId;
				}
				$lastMenuItemId = prev($track);
			}
			while ($lastMenuItemId !== null);
		}

		return null;
	}

	/**
	 * @return string
	 */
	public function getStartItemId(): string
	{
		return $this->structure['start'] ?? self::MENU_ENTRANCE_ID;
	}

	/**
	 * Checks if menu track has been completed.
	 *
	 * @return bool
	 */
	public function isTrackFinished(): bool
	{
		return $this->getLastTrackItemId() === self::MENU_EXIT_ID;
	}

	/**
	 * Checks if menu track has been started.
	 *
	 * @return bool
	 */
	public function isTrackStarted(): bool
	{
		return $this->getLastTrackItemId() !== null;
	}

	/**
	 * Append track.
	 *
	 * @param string $itemId
	 * @return self
	 */
	public function appendTrack(string $itemId): self
	{
		if ($itemId !== $this->getLastTrackItemId())
		{
			$this->menuState['track'][] = $itemId;
		}
		return $this;
	}

	/**
	 * Stops show menu to user.
	 * @return self
	 */
	public function stopTrack(): self
	{
		//do not show menu
		$this->appendTrack(self::MENU_EXIT_ID)->saveState();
		return $this;
	}

	/**
	 * @return int
	 */
	public function getMessageId(): int
	{
		return $this->messageId;
	}

	/**
	 * @return self
	 */
	public function setMessageId(int $messageId): self
	{
		$this->messageId = $messageId;
		$this->menuState['message_id'] = $messageId;
		return $this;
	}

	/**
	 * Returns menu item.
	 *
	 * @param string $itemId Item id.
	 *
	 * @return array{buttons: array}|null
	 */
	public function getItem(string $itemId): ?array
	{
		$structure = $this->getStructure();
		if (isset($structure, $structure['elements']) && is_array($structure['elements']))
		{
			foreach ($structure['elements'] as $item)
			{
				if ($item['id'] === $itemId)
				{
					return $item;
				}
			}
		}

		return null;
	}

	/**
	 * Collects result of the user interaction with ITR menu.
	 *
	 * @return array{id: string, name: string, value: string}|null
	 */
	public function printTrack(): ?array
	{
		$menuState = $this->getState();
		if (!is_array($menuState) || !array_key_exists('track', $menuState))
		{
			return null;
		}

		$menuData = $this->getStructure();
		if (!is_array($menuData))
		{
			return null;
		}

		$startMenuItemId = $this->getStartItemId();
		$previousMenuItem = $this->getItem($startMenuItemId);

		$blocks = [];
		foreach ($menuState['track'] as $itemId)
		{
			$menuItem = $this->getItem($itemId);
			if (!$menuItem && $itemId != self::MENU_EXIT_ID)
			{
				continue;
			}
			if ($itemId == $startMenuItemId)
			{
				$previousMenuItem = $menuItem;
				continue;
			}

			$buttonData = $this->detectClickedButton($itemId, $previousMenuItem, $menuState);
			$answer = $buttonData['text'] ?? '';
			if (isset($previousMenuItem['buttons']))
			{
				foreach ($previousMenuItem['buttons'] as $buttonData)
				{
					if ($buttonData['action'] == self::MENU_ACTION_NEXT && $buttonData['action_value'] == $itemId)
					{
						$answer = $buttonData['text'];
						break;
					}
					if ($buttonData['action'] == self::MENU_ACTION_QUEUE)
					{
						if (
							isset($menuState['menu_action'])
							&& !empty($menuState['menu_action'])
							&& isset($buttonData['action_value'])
							&& !empty($buttonData['action_value'])
							&& $menuState['menu_action'] === $buttonData['action_value']
						)
						{
							$answer = $buttonData['text'];
							break;
						}
						if (
							(
								!isset($menuState['menu_action'])
								|| empty($menuState['menu_action'])
							)
							&&
							(
								!isset($buttonData['action_value'])
								|| empty($buttonData['action_value'])
							)
						)
						{
							$answer = $buttonData['text'];
							break;
						}
					}
					elseif ($buttonData['action_value'] == self::MENU_EXIT_ID)
					{
						$answer = $buttonData['text'];
						break;
					}
				}
			}

			$blocks[] = [
				'id' => $itemId,
				'name' => $previousMenuItem['text'],
				'value' => $answer,
			];

			$previousMenuItem = $menuItem;
		}

		return $blocks;
	}

	/**
	 * Returns menu item's clicked button.
	 *
	 * @param string $itemId Item id.
	 * @param array $menuItem
	 * @param array $menuState
	 *
	 * @return array|null
	 */
	private function detectClickedButton(string $itemId, array $menuItem, array $menuState): ?array
	{
		$clickedButton = null;
		if (isset($menuItem['buttons']))
		{
			foreach ($menuItem['buttons'] as $buttonData)
			{
				if ($buttonData['action'] == self::MENU_ACTION_NEXT && $buttonData['action_value'] == $itemId)
				{
					$clickedButton = $buttonData;
					break;
				}
				if ($buttonData['action'] == self::MENU_ACTION_QUEUE)
				{
					if (
						isset($menuState['menu_action'])
						&& !empty($menuState['menu_action'])
						&& isset($buttonData['action_value'])
						&& !empty($buttonData['action_value'])
						&& $menuState['menu_action'] === $buttonData['action_value']
					)
					{
						$clickedButton = $buttonData;
						break;
					}
					if (
						(
							!isset($menuState['menu_action'])
							|| empty($menuState['menu_action'])
						)
						&&
						(
							!isset($buttonData['action_value'])
							|| empty($buttonData['action_value'])
						)
					)
					{
						$clickedButton = $buttonData;
						break;
					}
				}
				elseif ($buttonData['action_value'] == self::MENU_EXIT_ID)
				{
					$clickedButton = $buttonData;
					break;
				}
			}
		}

		return $clickedButton;
	}

	/**
	 * Generates message to display ITR menu.
	 *
	 * @param string $itemId Item id.
	 * @param array $params Command arguments.
	 * <pre>
	 * [
	 *   (string) USER_LEVEL User access level.
	 *   (string) SUPPORT_LEVEL Support level.
	 * ]
	 * </pre>.
	 *
	 * @return array|null
	 */
	public function makeItemKeyboard(string $itemId, array $params): ?Keyboard
	{
		$keyboard = null;

		$userAccess = $params['USER_LEVEL'] ?? null;
		$supportLevel = $params['SUPPORT_LEVEL'] ?? null;

		$menuItem = $this->getItem($itemId);

		if (isset($menuItem['buttons']))
		{
			$keyboard = new Keyboard($this->botId);

			foreach ($menuItem['buttons'] as $buttonData)
			{
				// check access
				if ($userAccess && isset($buttonData['access']))
				{
					$buttonAccess = preg_split("/[\s,]+/", $buttonData['access']);
					if ($buttonAccess && !in_array($userAccess, $buttonAccess, true))
					{
						continue;
					}
				}

				// check support level
				if ($supportLevel && isset($buttonData['support_level']))
				{
					$buttonSupportLevel = preg_split("/[\s,]+/", $buttonData['support_level']);
					if ($buttonSupportLevel && !in_array($supportLevel, $buttonSupportLevel, true))
					{
						continue;
					}
				}

				$button = [
					'TEXT' => $buttonData['text'],
					'DISPLAY' => ($buttonData['display'] ?? "BLOCK"),
					'BG_COLOR' => ($buttonData['back_color'] ??  self::MENU_BUTTON_ACTIVE),
					'TEXT_COLOR' => ($buttonData['text_color'] ?? "#fff"),
					'BLOCK' => "Y",
				];
				switch ($buttonData['action'])
				{
					case self::MENU_ACTION_NEXT:
						$button["COMMAND"] = self::COMMAND_MENU;
						$button["COMMAND_PARAMS"] = $buttonData['action_value'];
						break;

					case self::MENU_ACTION_LINK:
						$button["LINK"] = $buttonData['action_value'];
						break;

					case self::MENU_ACTION_HELP:
						if ($buttonData['action_value'] && !empty($buttonData['action_value']))
						{
							$button["FUNCTION"] = "BX.Helper.show(\'redirect=detail&HD_ID=".$buttonData['action_value']."\')";
						}
						else
						{
							$button["FUNCTION"] = "BX.Helper.show()";
						}
						break;

					case self::MENU_ACTION_QUEUE:
						$button["COMMAND"] = self::COMMAND_MENU;
						$button["COMMAND_PARAMS"] = self::MENU_EXIT_ID;
						// add some additional action params
						if ($buttonData['action_value'] && !empty($buttonData['action_value']))
						{
							$button["COMMAND_PARAMS"] .= ';'.$buttonData['action_value'];
						}
						break;
				}
				$keyboard->addButton($button);
			}
		}

		return $keyboard;
	}

	/**
	 * Display ITR menu.
	 *
	 * @param array $params Command arguments.
	 * <pre>
	 * [
	 *   (string) COMMAND
	 *   (string) COMMAND_PARAMS
	 *   (bool) FULL_REDRAW Drop previous menu block.
	 * ]
	 * </pre>.
	 *
	 * @return array|null
	 */
	public function generateNextMessage(array $params): ?array
	{
		if ($this->isTrackFinished())
		{
			// Nothing to do. The finish has been reached.
			return null;
		}

		if ($this->getMessageId() <= 0 && isset($params['MESSAGE_ID']))
		{
			$this->setMessageId((int)$params['MESSAGE_ID']);
		}

		$menuData = $this->getStructure();
		if (!is_array($menuData))
		{
			return null;
		}

		$currentMenuItemId = $this->getStartItemId();

		// go to next menu level
		if (
			!empty($params['COMMAND'])
			&& $params['COMMAND'] === self::COMMAND_MENU
			&& !empty($params['COMMAND_PARAMS'])
			&& is_string($params['COMMAND_PARAMS'])
			&& $this->getItem($params['COMMAND_PARAMS'])
		)
		{
			$currentMenuItemId = $params['COMMAND_PARAMS'];
		}
		// redraw menu
		elseif ($this->isTrackStarted())
		{
			$lastMenuItemId = $this->getLastTrackItemId();
			if (
				$lastMenuItemId !== null
				&& $this->getItem($lastMenuItemId) !== null
			)
			{
				$currentMenuItemId = $lastMenuItemId;
			}
			else
			{
				$lastMenuItemId = $this->getLastExistTrackItemId();
				if (
					$lastMenuItemId !== null
					&& $this->getItem($lastMenuItemId) !== null
				)
				{
					$currentMenuItemId = $lastMenuItemId;
				}
			}
		}

		$message = null;
		$menuItem = $this->getItem($currentMenuItemId);
		if ($menuItem)
		{
			//proceed to the next item
			$this->appendTrack($currentMenuItemId);

			$message = [
				'MESSAGE' => $menuItem['text'],
			];
			if (isset($menuItem['buttons']))
			{
				$message['KEYBOARD'] = $this->makeItemKeyboard($currentMenuItemId, [
					'USER_LEVEL' => $params['USER_LEVEL'],
					'SUPPORT_LEVEL' => $params['SUPPORT_LEVEL'],
				]);
			}
		}

		return $message;
	}

	/**
	 * Returns forward text regarding the last clicked button.
	 * @return string|null
	 */
	public function getForwardText(): ?string
	{
		$forwardMessage = null;
		$lastItemId = $this->getLastTrackItemId();
		if ($lastItemId)
		{
			if ($lastItem = $this->getItem($lastItemId))
			{
				$buttonData = $this->detectClickedButton($lastItemId, $lastItem, $this->getState());
				//
				if (!empty($buttonData['forward_text']))
				{
					$forwardMessage = $buttonData['forward_text'];
				}
			}
		}

		return $forwardMessage;
	}
}
