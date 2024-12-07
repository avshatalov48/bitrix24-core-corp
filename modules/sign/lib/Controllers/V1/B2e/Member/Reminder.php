<?php

namespace Bitrix\Sign\Controllers\V1\B2e\Member;

use Bitrix\Sign\Access\ActionDictionary;
use Bitrix\Sign\Attribute;
use Bitrix\Sign\Operation;
use Bitrix\Sign\Engine\Controller;
use Bitrix\Sign\Type\Access\AccessibleItemType;
use Bitrix\Sign\Type\Member\Notification\ReminderType;
use Bitrix\Sign\Type\Member\Role;

final class Reminder extends Controller
{
	#[Attribute\ActionAccess(ActionDictionary::ACTION_B2E_DOCUMENT_EDIT, AccessibleItemType::DOCUMENT, 'documentUid')]
	public function setAction(string $documentUid, string $memberRole, string $type): array
	{
		$reminderType = ReminderType::tryFrom($type);
		if ($reminderType === null)
		{
			$this->addErrorByMessage("Invalid reminder type: {$type}");

			return [];
		}
		if (!Role::isValid($memberRole))
		{
			$this->addErrorByMessage("Invalid member role: {$memberRole}");

			return [];
		}

		$document = $this->container->getDocumentRepository()->getByUid($documentUid);
		if ($document === null)
		{
			$this->addErrorByMessage("Document not found: {$documentUid}");

			return [];
		}
		$result = (new Operation\Member\Reminder\Set($document, $memberRole, $reminderType))->launch();
		$this->addErrorsFromResult($result);

		return [];
	}
}