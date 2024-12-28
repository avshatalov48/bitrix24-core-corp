<?php

namespace Bitrix\Sign\Ui\MyDocumentsGrid\ActionCellTemplateFactory;

use Bitrix\Sign\Type\Member\Role;
use Bitrix\Sign\Type\MemberStatus;
use Bitrix\Sign\Type\DocumentStatus;
use Bitrix\Sign\Type\MyDocumentsGrid\Action;
use Bitrix\Sign\Type\Document\InitiatedByType;

class ActionCellTemplateFactory
{
	public static function create(
		?Action $actionStatus,
		array $myMember,
		array $document,
		?string $downloadFileLink,
		?string $textForActionColumn,
		array $secondSideMember,
		array $initiator,
	): ActionCellTemplate
	{
		$isActionDownloadOrView = in_array($actionStatus, [Action::DOWNLOAD, Action::VIEW]);
		$isMyMemberRoleSigner = $myMember['role'] == Role::SIGNER;
		$initiatedByEmployee = $document['initiatedByType'] === InitiatedByType::EMPLOYEE;
		$initiatedByCompany = $document['initiatedByType'] === InitiatedByType::COMPANY;
		$isMyMemberStatusDone = $myMember['status'] === MemberStatus::DONE;
		$isDocumentStatusStoppedForEmployeeScenario = $initiatedByEmployee && $document['status'] === DocumentStatus::STOPPED;
		$actionDate = $document['editDate']
			?? $document['approvedDate']
			?? $document['cancelledDate']
			?? $document['signDate']
			?? null
		;

		if (($initiatedByEmployee && $isActionDownloadOrView) || ($initiatedByCompany && $isMyMemberRoleSigner))
		{
			if ($actionStatus === Action::DOWNLOAD && $isMyMemberRoleSigner)
			{
				if ($isDocumentStatusStoppedForEmployeeScenario && $downloadFileLink !== null)
				{
					return new CompositeTemplate([
						new RefusedDocumentTemplate($actionDate),
						new DownloadLinkTemplate(
							$downloadFileLink,
							$textForActionColumn,
						)
					]);
				}

				if ($isMyMemberStatusDone)
				{
					return new CompositeTemplate([
						new SignedDocumentTemplate($document['signDate']),
						new DownloadLinkTemplate(
							$downloadFileLink,
							$textForActionColumn,
						)
					]);
				}
			}

			if ($downloadFileLink !== null && $actionStatus === Action::VIEW)
			{
				return new ViewCellTemplate(
					$document['sendDate'],
					$downloadFileLink,
					$textForActionColumn,
				);
			}

			return new CompletedActionTextTemplate(
				$myMember['role'],
				$document['initiatedByType'],
				$myMember['status'],
				$actionDate,
			);
		}

		return new DefaultCompletedActionTextTemplate($textForActionColumn, $actionDate);
	}
}