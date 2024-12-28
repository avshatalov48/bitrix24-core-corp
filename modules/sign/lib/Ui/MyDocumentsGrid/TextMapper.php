<?php

namespace Bitrix\Sign\Ui\MyDocumentsGrid;

use Bitrix\Main\Localization\Loc;
use Bitrix\Sign\Type\Document\InitiatedByType;
use Bitrix\Sign\Type\DocumentStatus;
use Bitrix\Sign\Type\Member\Role;
use Bitrix\Sign\Type\MemberStatus;
use Bitrix\Sign\Type\MyDocumentsGrid\Action;

class TextMapper
{
	public static function getMyRoleInProcessText(
		string $role,
		InitiatedByType $initiatedByType,
		bool $initiatorIsCurrentUser,
		bool $secondSideIsCurrentUser,
	): ?string
	{
		$roleMessage = match ($role) {
			'editor' => Loc::getMessage('SIGN_MEMBER_ROLE_IN_PROCESS_EDITOR'),
			'reviewer' => Loc::getMessage('SIGN_MEMBER_ROLE_IN_PROCESS_REVIEWER'),
			'assignee' => Loc::getMessage('SIGN_MEMBER_ROLE_IN_PROCESS_ASSIGNEE'),
			'signer' => Loc::getMessage('SIGN_MEMBER_ROLE_IN_PROCESS_SIGNER_B2E'),
		};

		//TODO: Fix $secondSideIsCurrentUser to $myMember, but for now this logic is correct here
		if ($initiatedByType === InitiatedByType::EMPLOYEE)
		{
			if ($initiatorIsCurrentUser && $secondSideIsCurrentUser)
			{
				return $roleMessage;
			}

			return Loc::getMessage('SIGN_MEMBER_ROLE_IN_PROCESS_SIGNER_E2B');
		}

		return $roleMessage;
	}

	public static function getActionText(
		?Action $action,
		array $myMember,
		array $document,
	): ?string
	{
		$isMemberStatusRefused = $myMember['status'] === MemberStatus::REFUSED;
		$isInitiatedByTypeCompany = $document['initiatedByType'] === InitiatedByType::COMPANY;
//		$stoppedByMultipleCancellations = $document['stoppedById'] === null && $document['cancellationInitiatorId'] === $myMember['memberId'];

		if ($isInitiatedByTypeCompany && ($isMemberStatusRefused))
		{
			return Loc::getMessage('SIGN_MEMBER_STOPPED_WITH_YOU_MSGVER_1');
		}

		if ($myMember['status'] !== MemberStatus::DONE)
		{
			return self::getTextByAction($action);
		}

		if ($isInitiatedByTypeCompany)
		{
			return self::getTextForCompanyScenario($myMember['role']);
		}

		return self::getTextForEmployeeScenario($myMember['role'], $action);
	}

	private static function getTextByAction(?Action $action): ?string
	{
		return match ($action)
		{
			Action::VIEW => Loc::getMessage('SIGN_MEMBER_ACTION_VIEW'),
			Action::APPROVE => Loc::getMessage('SIGN_MEMBER_ACTION_APPROVE'),
			Action::EDIT => Loc::getMessage('SIGN_MEMBER_ACTION_EDIT'),
			Action::SIGN => Loc::getMessage('SIGN_MEMBER_ACTION_SIGN'),
			Action::DOWNLOAD => Loc::getMessage('SIGN_MEMBER_ACTION_DOWNLOAD'),
			default => Loc::getMessage('SIGN_MEMBER_STOPPED_WITH_YOU_MSGVER_1'),
		};
	}

	private static function getTextForCompanyScenario(?string $role): ?string
	{
		return match ($role)
		{
			'reviewer' => Loc::getMessage('SIGN_MEMBER_APPROVED_WITH_YOU_MSGVER_1'),
			'editor' => Loc::getMessage('SIGN_MEMBER_EDITED_WITH_YOU_MSGVER_1'),
			'assignee' => Loc::getMessage('SIGN_MEMBER_SIGNED_WITH_YOU_MSGVER_1'),
			'signer' => Loc::getMessage('SIGN_MEMBER_ACTION_DOWNLOAD'),
		};
	}

	private static function getTextForEmployeeScenario(
		?string $role,
		?Action $action,
	): ?string
	{
		if ($role === 'reviewer')
		{
			return Loc::getMessage('SIGN_MEMBER_APPROVED_WITH_YOU_MSGVER_1');
		}

		if ($role === 'editor')
		{
			return Loc::getMessage('SIGN_MEMBER_EDITED_WITH_YOU_MSGVER_1');
		}

		if ($role === 'signer' || $role === 'assignee')
		{
			return match ($action)
			{
				Action::DOWNLOAD => Loc::getMessage('SIGN_MEMBER_ACTION_DOWNLOAD'),
				Action::VIEW => Loc::getMessage('SIGN_MEMBER_ACTION_VIEW'),
				default => Loc::getMessage('SIGN_MEMBER_SIGNED_WITH_YOU_MSGVER_1'),
			};
		}

		return Loc::getMessage('SIGN_MEMBER_ACTION_VIEW');
	}

	public static function getSecondSideMemberRoleText(
		array $secondSideMember,
		array $document,
		array $myMember,
	): ?string
	{
		$isMemberStatusReady = in_array($secondSideMember['status'], [MemberStatus::READY, MemberStatus::STOPPABLE_READY]);
		$isMemberStatusDone = $secondSideMember['status'] === MemberStatus::DONE;
		$isMemberStatusRefused = $secondSideMember['status'] === MemberStatus::REFUSED;
		$isMemberStatusStopped = $secondSideMember['status'] === MemberStatus::STOPPED;
		$isMemberStatusProcessing = $secondSideMember['status'] === MemberStatus::PROCESSING;
		$isMemberRoleSigner = $secondSideMember['role'] === Role::SIGNER;
		$isMemberStatusStoppableReady = $secondSideMember['status'] === MemberStatus::STOPPABLE_READY;
		$isDocumentStatusDone = $document['status'] === DocumentStatus::DONE;
		$isDocumentStatusStopped = $document['status'] === DocumentStatus::STOPPED;
		$isInitiatedByTypeEmployee = $document['initiatedByType'] === InitiatedByType::EMPLOYEE;
		$isInitiatedByTypeCompany = $document['initiatedByType']  === InitiatedByType::COMPANY;
		$secondSideMemberIsStopped = $secondSideMember['isStopped'] === true;
//		$stoppedByMultipleCancellations = $document['stoppedById'] === null && $document['cancellationInitiatorId'] === $myMember['memberId'];

		$isStoppedForCompanyScenario = $isInitiatedByTypeCompany && ($isMemberStatusRefused || $isMemberStatusStopped);
		$isStoppedForEmployeeScenario =
			$isInitiatedByTypeEmployee
			&& (
				$isMemberStatusStopped
				|| ($isMemberStatusStoppableReady && $isMemberRoleSigner && $isDocumentStatusStopped)
			);

		if ($isStoppedForEmployeeScenario || $isStoppedForCompanyScenario || $secondSideMemberIsStopped)
		{
			return Loc::getMessage('SIGN_MEMBER_STOPPED');
		}

		if (($isMemberStatusReady || $isMemberStatusProcessing) && !$isDocumentStatusDone)
		{
			if ($secondSideMember['role'] === Role::REVIEWER)
			{
				return Loc::getMessage('SIGN_MEMBER_APPROVES');
			}
			if ($secondSideMember['role'] === Role::SIGNER || $secondSideMember['role'] === Role::ASSIGNEE)
			{
				return Loc::getMessage('SIGN_MEMBER_SIGNS');
			}
			if ($secondSideMember['role'] === Role::EDITOR)
			{
				return Loc::getMessage('SIGN_MEMBER_EDIT');
			}
		}

		if ($isMemberStatusDone)
		{
			if ($secondSideMember['role'] === Role::REVIEWER)
			{
				return Loc::getMessage('SIGN_MEMBER_APPROVED');
			}
			if ($secondSideMember['role'] === Role::ASSIGNEE || $secondSideMember['role'] === Role::SIGNER)
			{
				return Loc::getMessage('SIGN_MEMBER_SIGNED');
			}
			if ($secondSideMember['role'] === Role::EDITOR)
			{
				return Loc::getMessage('SIGN_MEMBER_EDITED');
			}
		}

		return null;
	}

	public static function getTextByRoleForCompleteAction(
		?string $role,
		InitiatedByType $initiatedByType,
		string $myMemberStatus,

	): ?string
	{
		if ($initiatedByType === InitiatedByType::COMPANY && $myMemberStatus === MemberStatus::REFUSED)
		{
			return Loc::getMessage('SIGN_MEMBER_STOPPED_WITH_YOU_MSGVER_1');
		}

		return match ($role)
		{
			'reviewer' => Loc::getMessage('SIGN_MEMBER_APPROVED_WITH_YOU_MSGVER_1'),
			'editor' => Loc::getMessage('SIGN_MEMBER_EDITED_WITH_YOU_MSGVER_1'),
			'signer', 'assignee' => Loc::getMessage('SIGN_MEMBER_SIGNED_WITH_YOU_MSGVER_1'),
			default => null,
		};
	}
}