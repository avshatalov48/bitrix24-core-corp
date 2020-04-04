<?php
##############################################
# Bitrix Site Manager						 #
# Copyright (c) 2002-2012 Bitrix			 #
# http://www.bitrixsoft.com					 #
# mailto:admin@bitrixsoft.com				 #
##############################################
IncludeModuleLangFile(__FILE__);

class CWebDavInterface
{
	static public function UserFieldEdit(&$arParams, &$arResult, $component=null)
	{
		global $APPLICATION;

		$APPLICATION->IncludeComponent(
			'bitrix:webdav.user.field',
			($arParams["MOBILE"] == "Y" ? 'mobile' : ''),
			array(
				'EDIT' => 'Y',
				'PARAMS' => $arParams,
				'RESULT' => $arResult,
			),
			$component,
			array( "HIDE_ICONS" => "Y")
		);
	}

	static public function UserFieldViewThumb(&$arParams, &$arResult, $component=null, $size = array())
	{
		global $APPLICATION;
		$APPLICATION->IncludeComponent(
			'bitrix:webdav.user.field',
			(array_key_exists("MOBILE", $arParams) && $arParams["MOBILE"] == "Y" ? 'mobile' : ''),
			array_merge(
				(is_array($arParams["arSettings"]) ? $arParams["arSettings"] : array()),
				array(
					'VIEW_THUMB' => "Y",
					'SIZE' => $size,
					'PARAMS' => $arParams,
					'RESULT' => $arResult,
				)
			)
			,
			$component,
			array( "HIDE_ICONS" => "Y")
		);
	}

	static public function UserFieldView(&$arParams, &$arResult, $component=null)
	{
		global $APPLICATION;

		$APPLICATION->IncludeComponent(
			'bitrix:webdav.user.field',
			($arParams["MOBILE"] == "Y" ? 'mobile' : ''),
			array(
				'PARAMS' => $arParams,
				'RESULT' => $arResult,
			),
			$component,
			array( "HIDE_ICONS" => "Y")
		);
	}
}