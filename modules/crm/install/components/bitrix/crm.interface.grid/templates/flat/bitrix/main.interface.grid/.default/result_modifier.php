<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
die();

/** @var array $arParams */
/** @var array $arResult */

if (is_array($arParams['CUSTOM_EDITABLE_COLUMNS'])
	&& count($arParams['CUSTOM_EDITABLE_COLUMNS']) > 0
	&& is_array($arResult['DATA_FOR_EDIT']))
{
	foreach (array_keys($arResult['DATA_FOR_EDIT']) as $kd)
	{
		foreach ($arParams['CUSTOM_EDITABLE_COLUMNS'] as $prefix => $editableColumns)
		{
			$idLenght = strlen($kd);
			$prefixLength = strlen($prefix);
			if ($idLenght >= $prefixLength && substr($kd, 0, $prefixLength) === $prefix)
			{
				foreach (array_keys($arResult['DATA_FOR_EDIT'][$kd]) as $kc)
				{
					if (!in_array($kc, $editableColumns, true))
						$arResult['DATA_FOR_EDIT'][$kd][$kc] = false;
				}
			}
		}
	}
}