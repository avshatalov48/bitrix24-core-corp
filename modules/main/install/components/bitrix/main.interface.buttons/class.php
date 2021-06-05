<?php
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Text;
use Bitrix\Main;


if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();


Loc::loadMessages(__FILE__);



class CMainInterfaceButtons
	extends CBitrixComponent
	implements Main\Engine\Contract\Controllerable, Main\Errorable
{
	/**
	 * First argument for CUserOptions::GetOption
	 * this is Options Category
	 * @var string
	 */
	protected $userOptionsCategory = "ui";
	/**

	 * @var int
	 */
	protected $maxCounterSize = 99;

	/**
	 * User options settings key
	 * @var string
	 */
	protected $userOptionsKey = "settings";


	/**
	 * Component items settings
	 * @var array
	 */
	protected $settings = [];

	/**
	 * User options expanded items
	 * @var array
	 */
	private $expandedLists = [];


	/**
	 * More item class
	 * @var string
	 */
	protected $defaultMoreItemClass = "main-buttons-item-more-default";

	/**
	 * @var Main\ErrorCollection
	 */
	protected $errorCollection;

	protected const EDIT_MODE_PERSONAL = 'PERSONAL';
	protected const EDIT_MODE_COMMON = 'COMMON';
	protected const EDIT_MODE_DISABLE = 'DISABLE';

	public function onPrepareComponentParams($arParams)
	{
		$this->errorCollection = new Main\ErrorCollection();

		if (empty($arParams["ID"]))
		{
			$this->errorCollection->setError(new Main\Error(Loc::getMessage("MIB_ID_NOT_SET"), "unknown"));
		}
		else
		{
			$arParams["ID"] = $this->prepareContainerId($arParams["ID"]);
			$arParams["EDIT_MODE"] = $this->prepareSaveMode(
				array_key_exists("EDIT_MODE", $arParams) ? $arParams["EDIT_MODE"] : null,
				$arParams["DISABLE_SETTINGS"]
			);
			$arParams["DISABLE_SETTINGS"] = $this->prepareDisableSettings($arParams["EDIT_MODE"]);
		}

		return $arParams;
	}


	protected function setStyles()
	{
		global $APPLICATION;

		if (isset($this->arParams["INCLUDE_CSS_FILE"]) && !empty($this->arParams["INCLUDE_CSS_FILE"]))
		{
			$APPLICATION->SetAdditionalCSS($this->arParams["INCLUDE_CSS_FILE"]);
		}
	}


	/**
	 * Prepares params
	 * @return object $this
	 */
	protected function prepareParams()
	{
		$this->arParams["CLASS_ITEM_ACTIVE"] = $this->prepareItemClass($this->arParams["CLASS_ITEM_ACTIVE"]);
		$this->arParams["CLASS_ITEM"] = $this->prepareItemClass($this->arParams["CLASS_ITEM"]);
		$this->arParams["CLASS_ITEM_LINK"] = $this->prepareItemClass($this->arParams["CLASS_ITEM_LINK"]);
		$this->arParams["CLASS_ITEM_ICON"] = $this->prepareItemClass($this->arParams["CLASS_ITEM_ICON"]);
		$this->arParams["CLASS_ITEM_TEXT"] = $this->prepareItemClass($this->arParams["CLASS_ITEM_TEXT"]);
		$this->arParams["CLASS_ITEM_COUNTER"] = $this->prepareItemClass($this->arParams["CLASS_ITEM_COUNTER"]);
		$this->arParams["ITEMS"] = $this->prepareItems($this->arParams["ITEMS"]);
		$this->arParams["MORE_BUTTON"] = $this->prepareMoreItem($this->arParams["MORE_BUTTON"]);

		return $this;
	}

	protected function prepareDisableSettings(string $mode): bool
	{
		return $mode === self::EDIT_MODE_DISABLE;
	}

	protected function prepareSaveMode($mode = null, $disableSettings = false): string
	{
		$result = self::EDIT_MODE_PERSONAL;
		if (is_string($mode))
		{
			$mode = mb_strtoupper($mode);
			if (in_array($mode, [self::EDIT_MODE_COMMON, self::EDIT_MODE_DISABLE]))
			{
				$result = $mode;
			}
		}
		else if (is_bool($mode))
		{
			$result = $mode === false ? self::EDIT_MODE_DISABLE : self::EDIT_MODE_PERSONAL;
		}
		else if ($disableSettings === true)
		{
			$result = self::EDIT_MODE_DISABLE;
		}
		return $result;
	}

	/**
	 * Gets user options as is
	 * @return array|bool
	 */
	protected function getUserOptions()
	{
		return CUserOptions::GetOption($this->userOptionsCategory, $this->arParams["ID"]);
	}


	/**
	 * Prepares container id
	 * @param string $id
	 * @return string Container id
	 */
	protected function prepareContainerId($id)
	{
		$id = $this->safeString($id);
		$id = preg_replace("/[^a-z0-9_-]/i", "", $id);
		$id = mb_strtolower($id);

		return $id;
	}


	/**
	 * Prepares user options
	 * @param array $userOptions
	 * @param $userOptionsKey
	 * @return array User options
	 */
	protected function prepareUserOptions($userOptions, $userOptionsKey)
	{
		$userOptionsSettings = array();

		if (is_array($userOptions) &&
			isset($userOptions[$userOptionsKey]) &&
			!empty($userOptions[$userOptionsKey]))
		{
			$userOptionsSettings = json_decode($userOptions[$userOptionsKey], true);
		}

		return $userOptionsSettings;
	}


	/**
	 * Prepares settings
	 */
	protected function prepareSettings()
	{
		$userOptionsRaw = $this->getUserOptions();
		$settings = $this->prepareUserOptions($userOptionsRaw, $this->userOptionsKey);
		$this->settings = $settings;

		$this->expandedLists = $this->prepareUserOptions($userOptionsRaw, 'expanded_lists');
	}


	/**
	 * Gets item settings by item id
	 * @param  string $id
	 * @return array
	 */
	protected function getItemSettingsByItemId($id)
	{
		$result = array();

		if (!empty($id) && is_array($this->settings) && !empty($this->settings[$id]))
		{
			$result = $this->settings[$id];
		}

		return $result;
	}


	/**
	 * Prepares item text value
	 * @param  string $text Text string
	 * @return string
	 */
	protected function prepareItemText($text)
	{
		return $this->safeString($text);
	}


	/**
	 * Prepares item html
	 * @param  string $html
	 * @return string
	 */
	protected function prepareItemHtml($html)
	{
		return Text\Converter::getHtmlConverter()->decode($html);
	}


	/**
	 * Prepares item url
	 * @param  string $url
	 * @return string
	 */
	protected function prepareItemUrl($url)
	{
		return $this->safeString($url);
	}


	/**
	 * Prepares item class
	 * @param  string $class
	 * @return string
	 */
	protected function prepareItemClass($class)
	{
		return $this->safeString($class);
	}


	/**
	 * Prepares item id
	 * @param  string $id
	 * @return string
	 */
	protected function prepareItemId($id)
	{
		$result = "";

		if (!empty($id))
		{
			$result = $this->safeString($id);
			$result = str_replace('-', '_', $result);
			$result = preg_replace("/[^a-z0-9_\/]/i", "", $result);
			$result = join("_", array($this->arParams["ID"], $result));
			$result = mb_strtolower($result);
		}

		return $result;
	}


	/**
	 * Prepares item onclick action string
	 * @param  string $onClickString
	 * @return string
	 */
	protected function prepareItemOnClickString($onClickString)
	{
		return $this->safeString($onClickString);
	}


	/**
	 * Prepares item counter value
	 * @param  integer $counter
	 * @return integer|boolean
	 */
	protected function prepareItemCounter($counter)
	{
		$result = false;

		if (is_float($counter) || is_int($counter))
		{
			$result = $counter;
		}

		return $result;
	}


	/**
	 * Prepares item is locked value
	 * @param  boolean $isLocked
	 * @return boolean json_encode'd
	 */
	protected function prepareItemIsLocked($isLocked)
	{
		$result = "false";

		if (!empty($isLocked) && is_bool($isLocked))
		{
			$result = json_encode($isLocked);
		}

		return $result;
	}


	/**
	 * Prepares item is disabled value
	 * @param  boolean $isDisabled
	 * @param  string $id
	 * @return boolean json_encode'd
	 */
	protected function prepareItemIsDisabled($isDisabled, $id)
	{
		$result = "false";
		$settings = $this->getItemSettingsByItemId($id);

		if (!empty($isDisabled) && is_bool($isDisabled))
		{
			$result = $isDisabled;
		}

		if (is_array($settings) && is_bool($settings["isDisabled"]))
		{
			$result = $settings["isDisabled"];
		}

		$result = json_encode($result);


		return $result;
	}


	/**
	 * Prepares item sublink array
	 * @param  array $sublink
	 * @return array|boolean return false if sublink not set
	 */
	protected function prepareItemSublink($sublink)
	{
		$result = false;

		if (!empty($sublink) && is_array($sublink))
		{
			if (!empty($sublink["URL"]))
			{
				$sublink["URL"] = $this->prepareItemUrl($sublink["URL"]);
			}

			if (!empty($sublink["CLASS"]))
			{
				$sublink["CLASS"] = $this->prepareItemClass($sublink["CLASS"]);
			}

			$result = $sublink;
		}

		return $result;
	}


	/**
	 * Prepares item sort index value
	 * @param  string $id
	 * @param  integer $key
	 * @return integer Sort index
	 */
	protected function prepareItemSort($id, $key)
	{
		$result = $key;
		$settings = $this->getItemSettingsByItemId($id);

		if (!empty($settings) && is_array($settings))
		{
			if (is_int($settings["sort"]))
			{
				$result = $settings["sort"];
			}
		}

		return $result;
	}


	/**
	 * Prepares item is active value
	 * @param array $item
	 * @return boolean
	 */
	protected function prepareItemIsActive($item)
	{
		$result = false;

		if (!isset($item["IS_ACTIVE"]))
		{
			$requestUri = $this->request->getRequestUri();

			if ($requestUri == $item["URL"])
			{
				$result = true;
			}

			if (!$result && isset($item["ADDITIONAL_URL"]) && is_array($item["ADDITIONAL_URL"]))
			{
				$result = array_search($requestUri, $item["ADDITIONAL_URL"]);
				$result = !is_null($result) ? true : false;
			}
		}
		else
		{
			$result = $item["IS_ACTIVE"];
		}

		return $result;
	}


	/**
	 * Prepares item
	 * @param  array $item
	 * @param  integer $key
	 * @return array Prepared $item
	 */
	protected function prepareItem($item, $key = 0)
	{
		$item["TEXT"] = $this->prepareItemText($item["TEXT"]);
		$item["HTML"] = $this->prepareItemHtml($item["HTML"]);
		$item["URL"] = $this->prepareItemUrl($item["URL"]);
		$item["CLASS"] = $this->prepareItemClass($item["CLASS"]);
		$item["CLASS_SUBMENU_ITEM"] = $this->prepareItemClass($item["CLASS_SUBMENU_ITEM"]);
		$item["DATA_ID"] = $item["ID"];
		$item["ID"] = $this->prepareItemId($item["ID"]);

		$item["MAX_COUNTER_SIZE"] = array_key_exists('MAX_COUNTER_SIZE', $item)
			? $item["MAX_COUNTER_SIZE"]
			: $this->maxCounterSize ;


		$item["ON_CLICK"] = $this->prepareItemOnClickString($item["ON_CLICK"]);

		if (isset($item["COUNTER"]))
		{
			$counter = $this->prepareItemCounter($item["COUNTER"]);

			if ($counter !== false && !empty($counter))
			{
				$item["COUNTER"] = $counter;
			}
			else
			{
				unset($item["COUNTER"]);
			}
		}

		if (isset($item["COUNTER_ID"]))
		{
			$counterId = $this->prepareItemCounterId($item);

			if (!empty($counterId) && is_string($counterId))
			{
				$item["COUNTER_ID"] = $counterId;
			}
			else
			{
				unset($item["COUNTER_ID"]);
			}
		}

		$item["IS_LOCKED"] = $this->prepareItemIsLocked($item["IS_LOCKED"]);
		$item["IS_DISABLED"] = $this->prepareItemIsDisabled($item["IS_DISABLED"], $item["ID"]);
		$item["SUB_LINK"] = $this->prepareItemSublink($item["SUB_LINK"]);
		$item["SORT"] = $this->prepareItemSort($item["ID"], $key);
		$item["IS_ACTIVE"] = $this->prepareItemIsActive($item);

		return $item;
	}

	protected function prepareItemCounterId($item)
	{
		$id = '';

		if (isset($item["COUNTER_ID"]) && is_string($item["COUNTER_ID"]) && !empty($item["COUNTER_ID"]))
		{
			$id = $item["COUNTER_ID"];
		}

		return $id;
	}


	protected function prepareMoreItem($item)
	{
		$html = $this->prepareItemHtml($item["HTML"]);
		$class = $this->prepareItemClass($item["CLASS"]);

		$item["TEXT"] = Loc::getMessage("MIB_DEFAULT_MORE_ITEM_TEXT");
		$item["HTML"] = $html;
		$item["CLASS"] = !empty($class) ? $class : $this->defaultMoreItemClass;

		return $item;
	}


	protected function filterItems()
	{
		$items = array_filter($this->arParams["ITEMS"], function($item)
		{
			return is_array($item);
		});

		$itemsCount = count($items);

		if (!$itemsCount)
		{
			ShowError(Loc::getMessage("MIB_ITEMS_NOT_FOUND"));
			return 0;
		}

		$this->arParams["ITEMS"] = $items;

		return $itemsCount;
	}


	protected function prepareItems($items = array())
	{
		foreach ($items as $key => $item)
		{
			$items[$key] = $this->prepareItem($item, $key);
		}

		$items = $this->sortBySortIndex($items);

		foreach ($items as $key => $item)
		{
			$childItems = array_filter($items, function($currentItem) use ($item) {
				return isset($currentItem['PARENT_ITEM_ID']) && $currentItem['PARENT_ITEM_ID'] === $item['DATA_ID'];
			});

			$items[$key]['HAS_CHILD'] = is_array($childItems) && count($childItems) > 0;

			if ($items[$key]['HAS_CHILD'] === true)
			{
				if (array_key_exists($item['DATA_ID'], $this->expandedLists)
					&& $this->expandedLists[$item['DATA_ID']] == 'Y')
				{
					$items[$key]['EXPANDED'] = true;
				}
				else
				{
					$items[$key]['EXPANDED'] = false;
				}

				$items[$key]['CHILD_ITEMS'] = [];
				foreach ($childItems as $currentItem)
				{
					$items[$key]['CHILD_ITEMS'][] = $currentItem;
				}
			}
		}

		return $items;
	}


	/**
	 * Sorts array bi sort index
	 * @param  array $array
	 * @return array Sorted array
	 */
	protected function sortBySortIndex($array = array())
	{
		usort($array, function($a, $b)
		{
			return $a["SORT"] - $b["SORT"];
		});

		return $array;
	}


	protected function safeString($string)
	{
		$string = trim($string);
		$string = Text\Converter::getHtmlConverter()->encode($string);
		return (String)$string;
	}


	/**
	 * Prepares arResult
	 */
	protected function prepareResult()
	{
		$this->arResult = $this->arParams;
	}


	public function executeComponent()
	{
		if ($this->errorCollection->isEmpty())
		{
			if ($this->filterItems())
			{
				$this->prepareSettings();
				$this->prepareParams();
				$this->prepareResult();
				$this->setStyles();
				$this->includeComponentTemplate();
			}
		}
	}

	public function configureActions()
	{
		return [];
	}

	public function getErrors()
	{
		return $this->errorCollection->toArray();
	}

	public function getErrorByCode($code)
	{
		return $this->errorCollection->getErrorByCode($code);
	}

	protected function listKeysSignedParameters()
	{
		return [
			'ID',
			'EDIT_MODE',
			'DISABLE_SETTINGS',
		];
	}

	public function saveAction(array $options)
	{
		if ($this->errorCollection->isEmpty()
			&& ($this->arParams['EDIT_MODE'] !== self::EDIT_MODE_DISABLE)
		)
		{
			$value = array_merge(
				CUserOptions::GetOption($this->userOptionsCategory, $this->arParams['ID'], []),
				$options
			);
			CUserOptions::SetOption(
				$this->userOptionsCategory,
				$this->arParams['ID'],
				$value,
				$this->arParams['EDIT_MODE'] === self::EDIT_MODE_COMMON);
		}
	}
}
