<?php

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

Loc::loadMessages(__FILE__);

class TasksTaskPreviewComponent extends \CBitrixComponent
{
	protected function prepareParams()
	{
		$this->arParams["AVATAR_SIZE"] = $this->arParams["AVATAR_SIZE"] ?: 40;
		if(Main\Loader::includeModule('socialnetwork'))
		{
			CSocNetLogComponent::processDateTimeFormatParams($this->arParams);
		}
	}

	protected function prepareData()
	{
		$this->arResult['TASK'] = CTasks::getById(
			$this->arParams['taskId'],
			false,
			array(
				'returnAsArray'  => true,
				'bSkipExtraData' => true
			)
		);
		if($this->arResult['TASK'] === false)
			return false;

		$this->arResult['TASK']['CREATED_BY_FORMATTED'] = tasksFormatName(
			$this->arResult["TASK"]["CREATED_BY_NAME"],
			$this->arResult["TASK"]["CREATED_BY_LAST_NAME"],
			$this->arResult["TASK"]["CREATED_BY_LOGIN"],
			$this->arResult["TASK"]["CREATED_BY_SECOND_NAME"],
			$this->arParams["NAME_TEMPLATE"],
			false
		);
		$this->arResult['TASK']['RESPONSIBLE_FORMATTED'] = tasksFormatName(
			$this->arResult["TASK"]["RESPONSIBLE_NAME"],
			$this->arResult["TASK"]["RESPONSIBLE_LAST_NAME"],
			$this->arResult["TASK"]["RESPONSIBLE_LOGIN"],
			$this->arResult["TASK"]["RESPONSIBLE_SECOND_NAME"],
			$this->arParams["NAME_TEMPLATE"],
			false
		);

		if(Main\Loader::includeModule('socialnetwork'))
		{
			$this->arResult["TASK"]["CREATED_DATE_FORMATTED"] = CSocNetLogComponent::getDateTimeFormatted(
					MakeTimeStamp($this->arResult["TASK"]["CREATED_DATE"]),
					array(
							"DATE_TIME_FORMAT" => $this->arParams["DATE_TIME_FORMAT"],
							"DATE_TIME_FORMAT_WITHOUT_YEAR" => $this->arParams["DATE_TIME_FORMAT_WITHOUT_YEAR"],
							"TIME_FORMAT" => $this->arParams["TIME_FORMAT"]
					));
		}
		else
		{
			$this->arResult["TASK"]["CREATED_DATE_FORMATTED"] = FormatDateFromDB($this->arResult["TASK"]["CREATED_DATE"], "SHORT");
		}

		if($this->arResult['TASK']['CREATED_BY'] > 0)
		{
			$this->arResult['TASK']['CREATED_BY_PROFILE'] = CComponentEngine::MakePathFromTemplate(
				$this->arParams["PATH_TO_USER_PROFILE"],
				array("user_id" => $this->arResult['TASK']["CREATED_BY"])
			);
			$this->arResult['TASK']['CREATED_BY_UNIQID'] = 'u_'.$this->randString();
		}
		if($this->arResult['TASK']['RESPONSIBLE_ID'] > 0)
		{
			$this->arResult['TASK']['RESPONSIBLE_PROFILE'] = CComponentEngine::MakePathFromTemplate(
				$this->arParams["PATH_TO_USER_PROFILE"],
				array("user_id" => $this->arResult["TASK"]["RESPONSIBLE_ID"])
			);
			$this->arResult['TASK']['RESPONSIBLE_UNIQID'] = 'u_'.$this->randString();
		}

		// avatars
		if ($this->arResult['TASK']["CREATED_BY_PHOTO"] > 0)
		{
			$imageFile = CFile::GetFileArray($this->arResult['TASK']["CREATED_BY_PHOTO"]);
			if ($imageFile !== false)
			{
				$arFileTmp = CFile::ResizeImageGet(
						$imageFile,
						array(
							"width" => $this->arParams["AVATAR_SIZE"],
							"height" => $this->arParams["AVATAR_SIZE"]
						),
						BX_RESIZE_IMAGE_EXACT,
						false
				);
				$this->arResult['TASK']["CREATED_BY_PHOTO"] = $arFileTmp["src"];
			}
			else
				$this->arResult['TASK']["CREATED_BY_PHOTO"] = false;
		}
		if ($this->arResult['TASK']["RESPONSIBLE_PHOTO"] > 0)
		{
			$imageFile = CFile::GetFileArray($this->arResult['TASK']["RESPONSIBLE_PHOTO"]);
			if ($imageFile !== false)
			{
				$arFileTmp = CFile::ResizeImageGet(
						$imageFile,
						array(
							"width" => $this->arParams["AVATAR_SIZE"],
							"height" => $this->arParams["AVATAR_SIZE"]
						),
						BX_RESIZE_IMAGE_EXACT,
						false
				);
				$this->arResult['TASK']["RESPONSIBLE_PHOTO"] = $arFileTmp["src"];
			}
			else
				$this->arResult['TASK']["RESPONSIBLE_PHOTO"] = false;
		}

		if (defined("BX_COMP_MANAGED_CACHE"))
		{
			$GLOBALS['CACHE_MANAGER']->RegisterTag('tasks_'.$this->arParams['taskId']);
		}
		return true;
	}

	public function executeComponent()
	{
		$this->prepareParams();
		if($this->prepareData())
		{
			$this->includeComponentTemplate();
		}
	}
}