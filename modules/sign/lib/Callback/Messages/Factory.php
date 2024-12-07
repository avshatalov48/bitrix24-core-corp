<?php

namespace Bitrix\Sign\Callback\Messages;

use Bitrix\Sign\Callback\Message;
use Bitrix\Sign\Callback\Messages\Member\InviteToSign;
use Bitrix\Sign\Callback\Messages\Member\MemberPrintVersionFile;
use Bitrix\Sign\Callback\Messages\Member\MemberResultFile;
use Bitrix\Sign\Callback\Messages\Member\MemberStatusChanged;
use Bitrix\Sign\Callback\Messages\Mobile\SigningConfirm;

class Factory
{
	public static function createMessage(string $type, array $data): Message
	{
		$message = match($type)
		{
			DocumentStatus::Type => new DocumentStatus(),
			TimelineEvent::Type => new TimelineEvent(),
			ResultFile::Type => new ResultFile(),
			ReadyLayoutCommand::Type => new ReadyLayoutCommand(),
			DocumentOperation::Type => new DocumentOperation(),
			FieldSet::Type => new FieldSet(),
			InviteToSign::Type => new InviteToSign(),
			MemberStatusChanged::Type => new MemberStatusChanged(),
			MemberResultFile::Type => new MemberResultFile(),
			MemberPrintVersionFile::Type => new MemberPrintVersionFile(),
			SigningConfirm::Type => new SigningConfirm(),
			default => new Message(),
		};

		return $message->setData($data);
	}
}
