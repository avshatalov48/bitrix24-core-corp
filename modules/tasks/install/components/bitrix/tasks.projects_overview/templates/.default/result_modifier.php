<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Uri;

\Bitrix\Main\UI\Extension::load("ui.icons");

// create template controller with js-dependency injections
$arResult['HELPER'] = $helper = require(__DIR__.'/helper.php');
$arParams =& $helper->getComponent(
)->arParams; // make $arParams the same variable as $this->__component->arParams, as it really should be

if ($helper->checkHasFatals())
{
	return;
}

//region TITLE
$sTitle = $sTitleShort = GetMessage("TASKS_TITLE_PROJECTS_OVERVIEW");
$APPLICATION->SetPageProperty("title", $sTitle);
$APPLICATION->SetTitle($sTitleShort);
//endregion TITLE

$arResult['TEMPLATE_DATA'] = array(// contains data generated in result_modifier.php
);
//$arResult['JS_DATA'] = array(// everything you put here, will be accessible inside js controller through this.option('keyName')
//);

if (!function_exists('formatDateTasks'))
{
	function formatDateTasks($date)
	{
		$curTimeFormat = "HH:MI:SS";
		$format = 'j F';
		if (LANGUAGE_ID == "en")
		{
			$format = "F j";
		}
		if (LANGUAGE_ID == "de")
		{
			$format = "j. F";
		}

		if (date('Y') != date('Y', strtotime($date)))
		{
			if (LANGUAGE_ID == "en")
			{
				$format .= ",";
			}

			$format .= ' Y';
		}

		$rsSite = CSite::GetByID(SITE_ID);
		if ($arSite = $rsSite->Fetch())
		{
			$curDateFormat = $arSite["FORMAT_DATE"];
			$curTimeFormat = str_replace($curDateFormat." ", "", $arSite["FORMAT_DATETIME"]);
		}

		if ($curTimeFormat == "HH:MI:SS")
		{
			$currentDateTimeFormat = " G:i";
		}
		else //($curTimeFormat == "H:MI:SS TT")
		{
			$currentDateTimeFormat = " g:i a";
		}

		if (date('Hi', strtotime($date)) > 0)
		{
			$format .= ', '.$currentDateTimeFormat;
		}

		$str = (!$date
			? GetMessage('TASKS_NOT_PRESENT')
			: \Bitrix\Tasks\UI::formatDateTime(
				MakeTimeStamp($date),
				$format
			));

		return $str;
	}
}

if (!function_exists('prepareTaskRow'))
{
	function prepareProjectRow($row, $arParams)
	{
		$resultRow = array(
			'ID' => $row['GROUP_ID'],
			'PROJECT' => prepareProjectRowTitle($row, $arParams),
			'PROJECT_DATE_START' => $row['PROJECT_DATE_START'] ? formatDateTasks($row['PROJECT_DATE_START']) : '',
			'PROJECT_DATE_FINISH' => $row['PROJECT_DATE_START'] ? formatDateTasks($row['PROJECT_DATE_FINISH']) : '',

			'IN_WORK' => prepareProjectRowInWork($row, $arParams),
			'COMPLETE' => '<a href="'.$row['PATHES']['COMPLETE'].'">'.$row['COUNTERS']['COMPLETE'].'</a>',
			'ALL' => '<a href="'.$row['PATHES']['ALL'].'">'.$row['COUNTERS']['ALL'].'</a>',
			'EFFECTIVE' =>$row['COUNTERS']['EFFECTIVE'].'%',
		);

		return $resultRow;
	}
}

if (!function_exists('prepareProjectRowTitle'))
{
	function prepareProjectRowTitle($row, $arParams)
	{
		$image = \Bitrix\Tasks\UI::getAvatarFile($row['IMAGE_ID'], ['WIDTH' => 50, 'HEIGHT' => 50]);
		$heads = ($row['MEMBERS']['HEADS'] ?? []);
		$members = ($row['MEMBERS']['MEMBERS'] ?? []);

		$out =
			'<div class="tasks-project-owerview-container">'
			.'<div class="tasks-project-overview-group-avatar">'
			.'<a class="tasks-proj-avatar" href="'.$row['PATHES']['TO_GROUP'].'">'
			.($image ? '<img src="'.$image['RESIZED']['SRC'].'"/>' : '')
			.'</a>'
			.'</div>'
			.'<div class="tasks-project-overview-title">'
			.'<a class="tasks-project-overview-title-link" target="_top"  href="'.$row['PATHES']['TO_GROUP'].'">'.htmlspecialcharsbx($row['NAME']).'</a>'
			.'<span class="tasks-project-overview-members">'
			.'<span class="tasks-project-overview-members-text">'.GetMessage('TASKS_PROJECTS_OVERVIEW_HEADS_'.(int)(count($heads) > 1)).'</span>'
		;
		foreach ($heads as $head)
		{
			$photoSrc = getUserPictureSrc($head['PHOTO_ID'], $head['USER_GENDER'], 25, 25);
			$out .=
				'<a href="'.$head['HREF'].'" class="ui-icon ui-icon-common-user tasks-project-overview-member-avatar">'
				.($photoSrc ? '<i style="background-image: url('.Uri::urnEncode($photoSrc).')"></i>' : '')
				.'</a>';
		}

		if (($countMembers = count($members)) > 0)
		{
			$countMembersText = Loc::getMessagePlural(
				'TASKS_PROJECT_OVERVIEW_MEMBERS_COUNT',
				$countMembers,
				[
					'#ID#' => 'tasks-project-overviews-'.$row['GROUP_ID'],
					'#COUNT#' => $countMembers,
					'#GROUP_ID#' => $row['GROUP_ID'],
				]
			);
			$out .= '<span class="tasks-project-overview-members-additional-popup">'.$countMembersText.'</span>';
		}
		$out .= '</span></div></div>';

		return $out;
	}
}

if (!function_exists('getUserPictureSrc'))
{
	function getUserPictureSrc($photoId, $gender = '?', $width = 100, $height = 100)
	{
		static $cache = [];

		$key = "{$photoId}.{$width}.{$height}";

		if (!array_key_exists($key, $cache))
		{
			$src = false;

			if ($photoId > 0)
			{
				$imageFile = CFile::GetFileArray($photoId);
				if ($imageFile !== false)
				{
					$tmpImage = CFile::ResizeImageGet(
						$imageFile,
						["width" => $width, "height" => $height],
						BX_RESIZE_IMAGE_EXACT,
						false
					);
					$src = $tmpImage["src"];
				}

				$cache[$key] = $src;
			}
		}

		return $cache[$key];
	}
}

if (!function_exists('prepareProjectRowInWork'))
{
	function prepareProjectRowInWork($row, $arParams)
	{
		return '<a href="'.
			$row['PATHES']['IN_WORK'].
			'">'.
			$row['COUNTERS']['IN_WORK'].
			'</a>'.
			($row['COUNTERS']['EXPIRED'] > 0 ? '<span class="tasks-project-overview-expired">'.
				$row['COUNTERS']['EXPIRED'].
				'</span>' : '');
	}
}

$arResult['ROWS'] = array();
if (!empty($arResult['GROUPS']))
{
	foreach ($arResult['GROUPS'] as $row)
	{
		$rowItem = array(
			"id" => $row["GROUP_ID"],
			'columns' => prepareProjectRow($row, $arParams)
		);

		$arResult['ROWS'][] = $rowItem;
	}
}
