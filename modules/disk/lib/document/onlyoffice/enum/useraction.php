<?php

namespace Bitrix\Disk\Document\OnlyOffice\Enum;

final class UserAction
{
	/**
	 * @see \Bitrix\Disk\Controller\OnlyOffice::ACTION_TYPE_DISCONNECT
	 */
	public const DISCONNECT = 0;
	/**
	 * @see \Bitrix\Disk\Controller\OnlyOffice::ACTION_TYPE_CONNECT
	 */
	public const CONNECT = 1;
	/**
	 * @see \Bitrix\Disk\Controller\OnlyOffice::ACTION_TYPE_FORCE_SAVE
	 */
	public const FORCE_SAVE = 2;
}