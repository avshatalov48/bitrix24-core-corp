<?php
namespace Bitrix\ImBot\Itr;

class Item
{
	const TYPE_VOID = 'VOID';
	const TYPE_TEXT = 'TEXT';
	const TYPE_MENU = 'MENU';
	const TYPE_USER = 'USER';
	const TYPE_BOT = 'BOT';
	const TYPE_QUEUE = 'QUEUE';
	const TYPE_FINISH = 'FINISH';
	const TYPE_FUNCTION = 'FUNCTION';

	public static function void($hideMenu = true)
	{
		return Array(
			'TYPE' => self::TYPE_VOID,
			'HIDE_MENU' => $hideMenu? true: false
		);
	}

	public static function sendText($text = '', $hideMenu = false)
	{
		return Array(
			'TYPE' => self::TYPE_TEXT,
			'TEXT' => $text,
			'HIDE_MENU' => $hideMenu? true: false
		);
	}

	public static function openMenu($menuId)
	{
		return Array(
			'TYPE' => self::TYPE_MENU,
			'MENU' => $menuId
		);
	}

	public static function transferToQueue($text = '', $hideMenu = true)
	{
		return Array(
			'TYPE' => self::TYPE_QUEUE,
			'TEXT' => $text,
			'HIDE_MENU' => $hideMenu? true: false
		);
	}

	public static function transferToUser($userId, $leave = false, $text = '', $hideMenu = true)
	{
		return Array(
			'TYPE' => self::TYPE_USER,
			'TEXT' => $text,
			'HIDE_MENU' => $hideMenu? true: false,
			'USER_ID' => $userId,
			'LEAVE' => $leave? true: false,
		);
	}

	public static function transferToBot($botCode, $leave = true, $text = '', $errorText = '')
	{
		return Array(
			'TYPE' => self::TYPE_BOT,
			'TEXT' => $text,
			'ERROR_TEXT' => $errorText,
			'HIDE_MENU' => true,
			'BOT_CODE' => $botCode,
			'LEAVE' => $leave? true: false,
		);
	}

	public static function finishSession($text = '')
	{
		return Array(
			'TYPE' => self::TYPE_FINISH,
			'TEXT' => $text,
			'HIDE_MENU' => true
		);
	}

	public static function execFunction($function, $text = '', $hideMenu = false)
	{
		return Array(
			'TYPE' => self::TYPE_FUNCTION,
			'FUNCTION' => $function,
			'TEXT' => $text,
			'HIDE_MENU' => $hideMenu? true: false
		);
	}
}