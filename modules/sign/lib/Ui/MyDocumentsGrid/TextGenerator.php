<?php

namespace Bitrix\Sign\Ui\MyDocumentsGrid;

use Bitrix\Main\Localization\Loc;
use Bitrix\Sign\Type\Member\Role;
use Bitrix\Sign\Type\MemberStatus;
use Bitrix\Sign\Item\MyDocumentsGrid\Row;
use Bitrix\Sign\Type\MyDocumentsGrid\Action;

class TextGenerator
{
	public function __construct(
		private readonly Row $row
	)
	{}

	public function getMyRoleInProcessText(): ?string
	{
		$initiatorIsCurrentUser = $this->row->document->initiator->isCurrentUser;
		$myMemberInProcessIsCurrentUser = $this->row->myMemberInProcess->isCurrentUser;

		if ($this->row->document->isInitiatedByEmployee() && !($initiatorIsCurrentUser && $myMemberInProcessIsCurrentUser))
		{
			return Loc::getMessage('SIGN_MEMBER_ROLE_IN_PROCESS_SIGNER_E2B');
		}

		return match ($this->row->myMemberInProcess->role)
		{
			Role::EDITOR => Loc::getMessage('SIGN_MEMBER_ROLE_IN_PROCESS_EDITOR'),
			Role::REVIEWER => Loc::getMessage('SIGN_MEMBER_ROLE_IN_PROCESS_REVIEWER'),
			Role::ASSIGNEE => Loc::getMessage('SIGN_MEMBER_ROLE_IN_PROCESS_ASSIGNEE'),
			Role::SIGNER => Loc::getMessage('SIGN_MEMBER_ROLE_IN_PROCESS_SIGNER_B2E'),
		};
	}

	public function getActionText(): ?string
	{
		$isStoppedByMe = $this->row->document->stoppedById === $this->row->myMemberInProcess->userId;
		if ($this->row->document->isInitiatedByCompany() && $this->row->document->isDocumentStopped() && $isStoppedByMe)
		{
			return $this->getMessageBySomeoneSigned(
				'SIGN_MEMBER_SIGNED_WITH_YOU_MSGVER_1',
				'SIGN_MEMBER_STOPPED_WITH_YOU_MSGVER_1',
			);
		}

		if ($this->row->document->isInitiatedByCompany() && $this->row->myMemberInProcess->isRefused())
		{
			return Loc::getMessage('SIGN_MEMBER_STOPPED_WITH_YOU_MSGVER_1');
		}

		if ($this->row->myMemberInProcess->status !== MemberStatus::DONE)
		{
			return $this->getTextByAction($this->row->action);
		}

		if ($this->row->document->isInitiatedByCompany())
		{
			return $this->getTextForCompanyScenario($this->row->myMemberInProcess->role);
		}

		return $this->getTextForEmployeeScenario($this->row->myMemberInProcess->role, $this->row->action);
	}

	private function getTextByAction(?Action $action): ?string
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

	private function getTextForCompanyScenario(?string $role): ?string
	{
		return match ($role)
		{
			Role::REVIEWER => Loc::getMessage('SIGN_MEMBER_APPROVED_WITH_YOU_MSGVER_1'),
			Role::EDITOR => Loc::getMessage('SIGN_MEMBER_EDITED_WITH_YOU_MSGVER_1'),
			Role::ASSIGNEE => Loc::getMessage('SIGN_MEMBER_SIGNED_WITH_YOU_MSGVER_1'),
			Role::SIGNER => Loc::getMessage('SIGN_MEMBER_ACTION_DOWNLOAD'),
			default => null,
		};
	}

	private function getTextForEmployeeScenario(
		?string $role,
		?Action $action,
	): ?string
	{
		return match ($role)
		{
			Role::REVIEWER => Loc::getMessage('SIGN_MEMBER_APPROVED_WITH_YOU_MSGVER_1'),
			Role::EDITOR => Loc::getMessage('SIGN_MEMBER_EDITED_WITH_YOU_MSGVER_1'),
			Role::SIGNER, Role::ASSIGNEE => match ($action)
			{
				Action::DOWNLOAD => Loc::getMessage('SIGN_MEMBER_ACTION_DOWNLOAD'),
				Action::VIEW => Loc::getMessage('SIGN_MEMBER_ACTION_VIEW'),
				default => Loc::getMessage('SIGN_MEMBER_SIGNED_WITH_YOU_MSGVER_1'),
			},
			default => Loc::getMessage('SIGN_MEMBER_ACTION_VIEW'),
		};
	}

	public function getSecondSideMemberRoleText(): ?string
	{
		$isStoppedByMe = $this->row->document->stoppedById === $this->row->myMemberInProcess->userId;
		if ($this->row->document->isInitiatedByCompany() && $this->row->document->isDocumentStopped() && $isStoppedByMe)
		{
			return $this->getMessageBySomeoneSigned(
				'SIGN_MEMBER_SIGNED',
				'SIGN_MEMBER_STOPPED',
			);
		}

		if ($this->isStoppedScenario())
		{
			return Loc::getMessage('SIGN_MEMBER_STOPPED');
		}

		$secondSideMember = $this->row->members->getFirst();
		if ($secondSideMember->isDone())
		{
			return $this->getDoneStatusMessage($secondSideMember->role);
		}

		return $this->getPendingStatusMessage($secondSideMember->role);
	}

	public function getTextByRoleForCompleteAction(): ?string
	{
		if ($this->row->document->isInitiatedByCompany() && $this->row->myMemberInProcess->isRefused())
		{
			return Loc::getMessage('SIGN_MEMBER_STOPPED_WITH_YOU_MSGVER_1');
		}

		return match ($this->row->myMemberInProcess->role)
		{
			Role::REVIEWER => Loc::getMessage('SIGN_MEMBER_APPROVED_WITH_YOU_MSGVER_1'),
			Role::EDITOR => Loc::getMessage('SIGN_MEMBER_EDITED_WITH_YOU_MSGVER_1'),
			Role::SIGNER, Role::ASSIGNEE => Loc::getMessage('SIGN_MEMBER_SIGNED_WITH_YOU_MSGVER_1'),
			default => null,
		};
	}

	private function getDoneStatusMessage(string $role): ?string
	{
		return match ($role)
		{
			Role::REVIEWER => Loc::getMessage('SIGN_MEMBER_APPROVED'),
			Role::EDITOR => Loc::getMessage('SIGN_MEMBER_EDITED'),
			Role::ASSIGNEE, Role::SIGNER => Loc::getMessage('SIGN_MEMBER_SIGNED'),
			default => null,
		};
	}

	private function getPendingStatusMessage(string $role): ?string
	{
		return match ($role)
		{
			Role::REVIEWER => Loc::getMessage('SIGN_MEMBER_APPROVES'),
			Role::EDITOR => Loc::getMessage('SIGN_MEMBER_EDIT'),
			Role::SIGNER, Role::ASSIGNEE => Loc::getMessage('SIGN_MEMBER_SIGNS'),
			default => null,
		};
	}

	private function getMessageBySomeoneSigned(string $signedMessageCode, string $stoppedMessageCode): string
	{
		return $this->row->document->someoneSigned
			? Loc::getMessage($signedMessageCode)
			: Loc::getMessage($stoppedMessageCode)
			;
	}

	private function isStoppedScenario(): bool
	{
		$secondSideMember = $this->row->members->getFirst();

		return $this->isStoppedForEmployeeScenario()
			|| $this->isStoppedForCompanyScenario()
			|| $secondSideMember->isStopped;
	}

	private function isStoppedForCompanyScenario(): bool
	{
		$secondSideMember = $this->row->members->getFirst();

		return $this->row->document->isInitiatedByCompany()
			&& ($secondSideMember->isRefused() || $secondSideMember->isStopped());
	}

	private function isStoppedForEmployeeScenario(): bool
	{
		$secondSideMember = $this->row->members->getFirst();

		return $this->row->document->isInitiatedByEmployee()
			&& ($secondSideMember->isStopped()
				|| ($secondSideMember->isStoppableReady()
					&& $secondSideMember->isSigner()
					&& $this->row->document->isDocumentStopped()));
	}
}