<?php

namespace Bitrix\Sign\Ui\Member;

use Bitrix\Main\Localization\Loc;
use Bitrix\Sign\Type\MemberStatus;

class Status
{
	/**
	 * @param string $status
	 * @return string
	 */
	public static function getCaption(string $status): string
	{
		return match ($status)
		{
			MemberStatus::DONE => Loc::getMessage('SIGN_DOCUMENT_LIST_STATUS_DONE'),
			MemberStatus::READY,
			MemberStatus::STOPPABLE_READY => Loc::getMessage('SIGN_DOCUMENT_LIST_STATUS_READY'),
			MemberStatus::WAIT => Loc::getMessage('SIGN_DOCUMENT_LIST_STATUS_WAIT'),
			MemberStatus::REFUSED => Loc::getMessage('SIGN_DOCUMENT_LIST_STATUS_REFUSED'),
			MemberStatus::STOPPED => Loc::getMessage('SIGN_DOCUMENT_LIST_STATUS_STOPPED'),
			default => Loc::getMessage('SIGN_DOCUMENT_LIST_STATUS_UNDEFINED'),
		};
	}
}