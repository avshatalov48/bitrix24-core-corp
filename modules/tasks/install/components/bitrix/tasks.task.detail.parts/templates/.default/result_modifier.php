<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

// some interface calculations for right_sidebar.php
if(is_array($arResult['BLOCKS']) && in_array('right_sidebar', $arResult['BLOCKS']))
{
	$arResult['TABLE_ROWS_MAP'] =		array('STATUS', 'PRIORITY', 'DEADLINE', 'TIME_ESTIMATE', 'DATE_PLAN', 'MARK', 'IN_REPORT', 'TEMPLATE', 'STOP_WATCH');
	$arResult['ROLES_MAP'] =			array('ACCOMPLICES', 'AUDITORS', 'CREATOR', 'RESPONSIBLE');

	if(!is_array($arParams['DISPLAY_DATA']))
		$arParams['DISPLAY_DATA'] = array_merge($arResult['TABLE_ROWS_MAP'], $arResult['ROLES_MAP']);

	$arResult['SHOW_ACCOMPLICES'] = in_array('ACCOMPLICES', $arParams['DISPLAY_DATA']);
	$arResult['SHOW_AUDITORS'] = in_array('AUDITORS', $arParams['DISPLAY_DATA']);

	// avatars
	if ($arResult['TASK']["CREATED_BY_PHOTO"] > 0)
    {
    	$imageFile = CFile::GetFileArray($arResult['TASK']["CREATED_BY_PHOTO"]);
    	if ($imageFile !== false)
    	{
    		$arFileTmp = CFile::ResizeImageGet(
				$imageFile,
				array("width" => 100, "height" => 100),
				BX_RESIZE_IMAGE_EXACT,
				false
    		);
    		$arResult['TASK']["CREATED_BY_PHOTO"] = $arFileTmp["src"];
    	}
    	else
		{
			$arResult['TASK']["CREATED_BY_PHOTO"] = false;
		}
    }

    if ($arResult['TASK']["RESPONSIBLE_PHOTO"] > 0)
    {
    	$imageFile = CFile::GetFileArray($arResult['TASK']["RESPONSIBLE_PHOTO"]);
    	if ($imageFile !== false)
    	{
    		$arFileTmp = CFile::ResizeImageGet(
					$imageFile,
					array("width" => 100, "height" => 100),
					BX_RESIZE_IMAGE_EXACT,
					false
    		);

    		$arResult['TASK']["RESPONSIBLE_PHOTO"] = $arFileTmp["src"];
    	}
    	else
		{
			$arResult['TASK']["RESPONSIBLE_PHOTO"] = false;
		}
    }
}

