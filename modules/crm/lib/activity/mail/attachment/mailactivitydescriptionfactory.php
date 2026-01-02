<?php

namespace Bitrix\Crm\Activity\Mail\Attachment;

use Bitrix\Crm\Activity\Mail\Attachment\Dto\MailActivityDescription;

class MailActivityDescriptionFactory
{
	public function makeFromBody(string $textBody, string $htmlBody): MailActivityDescription
	{
		if (!empty($htmlBody))
		{
			return new MailActivityDescription(
				description: $htmlBody,
				mayContainInlineFiles: true,
			);
		}

		return new MailActivityDescription(
			description: preg_replace('/\r\n|\n|\r/', '<br>', htmlspecialcharsbx($textBody)),
			mayContainInlineFiles: false,
		);
	}

	public function makeFromMessageFieldsArray(array $message): MailActivityDescription
	{
		return $this->makeFromBody(
			textBody: (string)($message['BODY'] ?? ''),
			htmlBody: (string)($message['BODY_HTML'] ?? ''),
		);
	}
}