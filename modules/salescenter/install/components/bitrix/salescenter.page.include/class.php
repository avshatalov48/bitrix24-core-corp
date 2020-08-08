<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\SystemException;
use Bitrix\Main\IO;

class SalesCenterAdminPageInclude extends CBitrixComponent
{
	/**
	 * Load language file.
	 */
	public function onIncludeComponentLang()
	{
		$this->includeComponentLang(basename(__FILE__));
		Loc::loadMessages(__FILE__);
	}

	/**
	 * @param $params
	 * @return array
	 */
	public function onPrepareComponentParams($params)
	{
		$params["SEF_FOLDER"] = (!empty($params["SEF_FOLDER"]) ? $params["SEF_FOLDER"] : "/bitrix/admin/");
		$params["PAGE_PATH"] = (!empty($params["PAGE_PATH"]) ? $params["PAGE_PATH"] : "");
		if (mb_strpos($params["PAGE_PATH"], $params["SEF_FOLDER"]) === false)
		{
			$params["PAGE_PATH"] = $params["SEF_FOLDER"].$params["PAGE_PATH"];
		}
		$params["PAGE_PARAMS"] = (!empty($params["PAGE_PARAMS"]) ? $params["PAGE_PARAMS"] : "");
		$params["INTERNAL_PAGE"] = (!empty($params["INTERNAL_PAGE"]) ? $params["INTERNAL_PAGE"] : "N");

		$params["IS_SIDE_PANEL"] = ($_REQUEST["IFRAME"] == "Y") && ($_REQUEST["IFRAME_TYPE"] == "SIDE_SLIDER");

		return $params;
	}

	/**
	 * Check that pages exists.
	 *
	 * @throws SystemException
	 */
	protected function checkPage()
	{
		$page = new IO\File($_SERVER['DOCUMENT_ROOT'].$this->arParams["PAGE_PATH"]);
		if (!$page->isExists())
		{
			throw new SystemException("Page not found");
		}
	}

	protected function setSettings()
	{
		$_REQUEST["public"] = "Y";

		if ($this->arParams["PAGE_PARAMS"])
		{
			foreach(explode("&", $this->arParams["PAGE_PARAMS"]) as $param)
			{
				$explode = explode("=", $param);
				if (!isset($_REQUEST[$explode[0]]))
				{
					$_REQUEST[$explode[0]] = $explode[1];
				}
				if (!isset($_GET[$explode[0]]))
				{
					$_GET[$explode[0]] = $explode[1];
				}
			}
		}

		$_REQUEST["lang"] = LANGUAGE_ID;

		define("SELF_FOLDER_URL", $this->arParams["SEF_FOLDER"]);
	}

	protected function formatResult()
	{
		$this->arResult = array();
		$this->arResult["PAGE_PATH"] = $this->arParams["PAGE_PATH"];
		$this->arResult["PAGE_PARAMS"] = $this->arParams["PAGE_PARAMS"];
		$this->arResult["IS_SIDE_PANEL"] = $this->arParams["IS_SIDE_PANEL"];
		$this->arResult["INTERNAL_PAGE"] = $this->arParams["INTERNAL_PAGE"] == "Y";
		$pagePath = $this->arResult["PAGE_PATH"]."?".$this->arResult["PAGE_PARAMS"];

		if ($this->arResult["IS_SIDE_PANEL"])
		{
			$this->arResult["REDIRECT_URL"] = \CHTTP::urlAddParams($pagePath,
				array("IFRAME" => "Y", "IFRAME_TYPE" => "SIDE_SLIDER"));
		}
		elseif($this->arResult["INTERNAL_PAGE"])
		{
			$this->arResult["FRAME_URL"] = \CHTTP::urlAddParams($pagePath,
				[
					"IFRAME" => "Y",
					"IFRAME_TYPE" => "PUBLIC_FRAME",
				]
			);
		}
	}

	/**
	 * @return mixed|void
	 * @throws Exception
	 */
	public function executeComponent()
	{
		try
		{
			$this->checkPage();
			$this->setSettings();
			$this->formatResult();

			$this->includeComponentTemplate();
		}
		catch(SystemException $e)
		{
			ShowError($e->getMessage());
		}
	}
}