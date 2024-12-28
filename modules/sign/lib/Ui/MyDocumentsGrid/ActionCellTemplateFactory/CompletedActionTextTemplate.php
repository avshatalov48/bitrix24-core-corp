<?php

namespace Bitrix\Sign\Ui\MyDocumentsGrid\ActionCellTemplateFactory;

use Bitrix\Main\Type\DateTime;
use Bitrix\Sign\Type\Document\InitiatedByType;
use Bitrix\Sign\Ui\MyDocumentsGrid\TextMapper;

class CompletedActionTextTemplate implements ActionCellTemplate
{
	use ActionDateTrait;

	private string $secondSideMemberRole;
	private InitiatedByType $initiatedByType;
	private string $myMemberStatus;

	private ?DateTime $actionDate;

	public function __construct(
		string $secondSideMemberRole,
		InitiatedByType $initiatedByType,
		string $myMemberStatus,
		?DateTime $actionDate,
	)
	{
		$this->secondSideMemberRole = $secondSideMemberRole;
		$this->initiatedByType = $initiatedByType;
		$this->myMemberStatus = $myMemberStatus;
		$this->actionDate = $actionDate;
	}

	public function get(): string
	{
		$completedActionText = TextMapper::getTextByRoleForCompleteAction(
			$this->secondSideMemberRole,
			$this->initiatedByType,
			$this->myMemberStatus,
		);

		$formattedDate = self::getFormattedDate($this->actionDate);

		return <<<HTML
			<div class="sign-grid-action-signed-info">
				<span class="sign-grid-action-with-me-text">
					$completedActionText
				</span>
				<span class="sign-grid-action-date" title="$this->actionDate">
					$formattedDate
				</span>
			</div>
		HTML;
	}
}