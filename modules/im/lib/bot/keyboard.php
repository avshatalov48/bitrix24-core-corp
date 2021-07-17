<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage im
 * @copyright 2001-2016 Bitrix
 */

namespace Bitrix\Im\Bot;

class Keyboard
{
	private $botId = 0;
	private $colors = Array();
	private $buttons = Array();
	private $voteMode = false;

	function __construct($botId = 0, array $colors = Array(), $voteMode = false)
	{
		$this->botId = intval($botId);
		$this->voteMode = $voteMode? true: false;

		$this->setDefaultColor($colors);
	}

	private function setDefaultColor(array $colors)
	{
		if (isset($colors['BG_COLOR']) && preg_match('/^#([a-fA-F0-9]){3}(([a-fA-F0-9]){3})?\b$/D', $colors['BG_COLOR']))
		{
			$this->colors['BG_COLOR'] = $colors['BG_COLOR'];
		}

		if(isset($colors['TEXT_COLOR']) && preg_match('/^#([a-fA-F0-9]){3}(([a-fA-F0-9]){3})?\b$/D', $colors['TEXT_COLOR']))
		{
			$this->colors['TEXT_COLOR'] = $colors['TEXT_COLOR'];
		}

		if(isset($colors['OFF_BG_COLOR']) && preg_match('/^#([a-fA-F0-9]){3}(([a-fA-F0-9]){3})?\b$/D', $colors['OFF_BG_COLOR']))
		{
			$this->colors['OFF_BG_COLOR'] = $colors['OFF_BG_COLOR'];
		}

		if(isset($colors['OFF_TEXT_COLOR']) && preg_match('/^#([a-fA-F0-9]){3}(([a-fA-F0-9]){3})?\b$/D', $colors['OFF_TEXT_COLOR']))
		{
			$this->colors['OFF_TEXT_COLOR'] = $colors['OFF_TEXT_COLOR'];
		}
	}

	public function addButton($params)
	{
		$button = Array();
		$button['BOT_ID'] = $this->botId;
		$button['TYPE'] = 'BUTTON';

		if (!isset($params['TEXT']) || trim($params['TEXT']) == '')
			return false;

		if (isset($params['LINK']) && preg_match('#^(?:/|https?://)#', $params['LINK']))
		{
			$button['LINK'] = htmlspecialcharsbx($params['LINK']);
		}
		else if (isset($params['FUNCTION']))
		{
			$button['FUNCTION'] = htmlspecialcharsbx($params['FUNCTION']);
		}
		else if (isset($params['APP_ID']))
		{
			$button['APP_ID'] = intval($params['APP_ID']);
			if (isset($params['APP_PARAMS']) && trim($params['APP_PARAMS']) <> '')
			{
				$button['APP_PARAMS'] = $params['APP_PARAMS'];
			}
		}
		else if (
			isset($params['ACTION'])
			&& in_array($params['ACTION'], ['PUT', 'SEND', 'COPY', 'CALL', 'DIALOG', 'LIVECHAT'])
			&& trim($params['ACTION_VALUE']) <> ''
		)
		{
			$button['ACTION'] = $params['ACTION'];
			$button['ACTION_VALUE'] = $params['ACTION_VALUE'];
		}
		else if ($this->botId > 0 && isset($params['COMMAND']) && trim($params['COMMAND']) <> '')
		{
			$button['COMMAND'] = mb_substr($params['COMMAND'], 0, 1) == '/'? mb_substr($params['COMMAND'], 1) : $params['COMMAND'];
			$button['COMMAND_PARAMS'] = isset($params['COMMAND_PARAMS']) && trim($params['COMMAND_PARAMS']) <> ''? $params['COMMAND_PARAMS']: '';
		}
		else
		{
			return false;
		}

		$button['TEXT'] = trim($params['TEXT']);

		$button['VOTE'] = $this->voteMode? 'Y': 'N';

		$button['BLOCK'] = $params['BLOCK'] == 'Y'? 'Y': 'N';

		$button['WAIT'] = 'N';

		$button['CONTEXT'] = in_array($params['CONTEXT'], Array('MOBILE', 'DESKTOP'))? $params['CONTEXT']: 'ALL';

		$button['DISABLED'] = $params['DISABLED'] == 'Y'? 'Y': 'N';

		$button['DISPLAY'] = in_array($params['DISPLAY'], Array('BLOCK', 'LINE'))? $params['DISPLAY']: 'BLOCK';

		if (isset($params['WIDTH']) && intval($params['WIDTH']) > 0)
		{
			$button['WIDTH'] = intval($params['WIDTH']);
		}

		if (isset($params['BG_COLOR']) && preg_match('/^#([a-fA-F0-9]){3}(([a-fA-F0-9]){3})?\b$/D', $params['BG_COLOR']))
		{
			$button['BG_COLOR'] = $params['BG_COLOR'];
		}
		else if (isset($this->colors['BG_COLOR']))
		{
			$button['BG_COLOR'] = $this->colors['BG_COLOR'];
		}

		if(isset($params['TEXT_COLOR']) && preg_match('/^#([a-fA-F0-9]){3}(([a-fA-F0-9]){3})?\b$/D', $params['TEXT_COLOR']))
		{
			$button['TEXT_COLOR'] = $params['TEXT_COLOR'];
		}
		else if (isset($this->colors['TEXT_COLOR']))
		{
			$button['TEXT_COLOR'] = $this->colors['TEXT_COLOR'];
		}

		if(isset($params['OFF_BG_COLOR']) && preg_match('/^#([a-fA-F0-9]){3}(([a-fA-F0-9]){3})?\b$/D', $params['OFF_BG_COLOR']))
		{
			$button['OFF_BG_COLOR'] = $params['OFF_BG_COLOR'];
		}
		else if (isset($this->colors['OFF_BG_COLOR']))
		{
			$button['OFF_BG_COLOR'] = $this->colors['OFF_BG_COLOR'];
		}

		if(isset($params['OFF_TEXT_COLOR']) && preg_match('/^#([a-fA-F0-9]){3}(([a-fA-F0-9]){3})?\b$/D', $params['OFF_TEXT_COLOR']))
		{
			$button['OFF_TEXT_COLOR'] = $params['OFF_TEXT_COLOR'];
		}
		else if (isset($this->colors['OFF_TEXT_COLOR']))
		{
			$button['OFF_TEXT_COLOR'] = $this->colors['OFF_TEXT_COLOR'];
		}

		$this->buttons[] = $button;

		return false;
	}

	public function addNewLine()
	{
		$button['TYPE'] = 'NEWLINE';
		$this->buttons[] = $button;
	}

	public static function getKeyboardByJson($params, $textReplace = array(), $options = Array())
	{
		if (is_string($params))
		{
			$params = \CUtil::JsObjectToPhp($params);
		}
		if (!is_array($params))
		{
			return null;
		}

		$colors = is_array($params['COLORS'])? $params['COLORS']: Array();
		$voteMode = isset($params['VOTE']) && $params['VOTE'] == 'Y';

		$keyboard = new self($params['BOT_ID'], $colors, $voteMode);
		foreach ($params['BUTTONS'] as $button)
		{
			if (isset($button['TYPE']) && $button['TYPE'] == 'NEWLINE')
			{
				$keyboard->addNewLine();
			}
			else if (isset($button['FUNCTION']) && $options['ENABLE_FUNCTIONS'] != 'Y')
			{
			}
			else
			{
				if (isset($button['TEXT']))
				{
					foreach ($textReplace as $key => $value)
					{
						$button['TEXT'] = str_replace($key, $value, $button['TEXT']);
					}
				}
				$keyboard->addButton($button);
			}
		}

		return $keyboard->isEmpty()? null: $keyboard;
	}

	public function isEmpty()
	{
		return empty($this->buttons);
	}

	public function isAllowSize()
	{
		return $this->getJson()? true: false;
	}

	public function getArray()
	{
		return $this->buttons;
	}

	public function getJson()
	{
		$result = \Bitrix\Im\Common::jsonEncode($this->buttons);
		return mb_strlen($result) < 60000? $result: "";
	}
}