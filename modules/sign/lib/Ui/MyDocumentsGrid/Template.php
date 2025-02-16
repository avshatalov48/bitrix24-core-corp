<?php

namespace Bitrix\Sign\Ui\MyDocumentsGrid;

use Bitrix\Main\Web\Uri;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sign\Item\MyDocumentsGrid\Row;
use Bitrix\Sign\Type\DocumentStatus;
use Bitrix\Sign\Type\MemberStatus;
use Bitrix\UI\Buttons\Button;
use Bitrix\Sign\Ui\MyDocumentsGrid\ActionCellTemplateFactory\Factory;

class Template
{
	public function __construct(
		private readonly TextGenerator $textGenerator,
		private readonly Row $row,
	)
	{}

	public function getDocumentTitle(): string
	{
		ob_start();
		?>
		<div class="sign-grid-document-title">
			<div class="sign-grid-document-title_text">
				<div class="sign-grid-document-title">
					<?= htmlspecialcharsbx($this->row->document->title) ?>
				</div>
				<div class="sign-grid-document-title_my_role">
					<div class="sign-grid-document-title_text_my_role">
						<?= Loc::getMessage('SIGN_B2E_MY_DOCUMENTS_MY_ROLE_IN_PROCESS',
							[
								'#ROLE#' => htmlspecialcharsbx($this->textGenerator->getMyRoleInProcessText())
							]);
						?>
					</div>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	public function getParticipants(): string
	{
		ob_start();

		$initiator = $this->row->document->initiator;
		$secondSideMember = $this->row->members->getFirst();
		?>
		<div class="sign-grid-document-users-container">
			<div class="sign-grid-document-user-container">
				<a
					class="sign-grid-document-user"
					target="_top"
					onclick="event.stopPropagation();"
					href="/company/personal/user/<?= $initiator->userId ?>/">
					<div class="sign-grid-document-user-role-title-from">
						<?= Loc::getMessage('SIGN_B2E_MY_DOCUMENTS_FROM_WHOM') ?>
					</div>
					<div class="ui-icon ui-icon-common-user" title="<?= htmlspecialcharsbx($initiator->fullName) ?>">
						<i style=" <?= $this->getIconStyle($initiator) ?>">
						</i>
					</div>
				</a>
			</div>
			<div class="line-between-users"></div>
			<div class="sign-grid-document-user-container">
				<a
					class="sign-grid-document-user"
					target="_top"
					onclick="event.stopPropagation();"
					href="/company/personal/user/<?= $secondSideMember->userId ?>/">
					<div class="sign-grid-document-user-role-title-to">
						<?= $this->textGenerator->getSecondSideMemberRoleText(); ?>
					</div>
					<div class="ui-icon ui-icon-common-user" title="<?= htmlspecialcharsbx($secondSideMember->fullName) ?>">
						<i style=" <?= $this->getIconStyle($secondSideMember) ?>">
						</i>
					</div>
				</a>
			</div>
		</div>
		<?php
		return (string)ob_get_clean();
	}

	public function getAction(): ?string
	{
		$document = $this->row->document;
		$myMember = $this->row->myMemberInProcess;
		$isMyMemberCurrentUser = $myMember->isCurrentUser;
		$isMyMemberReady = MemberStatus::isReadyForSigning($myMember->status);
		$textForActionColumn = $this->textGenerator->getActionText();

		if ($this->row->action !== null && $isMyMemberCurrentUser && $isMyMemberReady && $document->status !== DocumentStatus::STOPPED)
		{
			return $this->getButtonForSignProcessAction();
		}

		return (new Factory())
			->create(
				$this->row,
				$textForActionColumn,
			)
			->render()
			;
	}

	private function getButtonForSignProcessAction(): ?string
	{
		$myMember = $this->row->myMemberInProcess;
		$downloadFileLink = $this->row->file->url ?? null;
		$isMyMemberReady = MemberStatus::isReadyForSigning($myMember->status);
		$button = new Button([
			'text' => $this->textGenerator->getActionText(),
		]);

		$button->setColor(\Bitrix\UI\Buttons\Color::SUCCESS);
		$button->setSize(\Bitrix\UI\Buttons\Size::SMALL);
		$button->setStyles([
			'width' => '160px',
			'border-radius' => '20px',
		]);
		$button->addDataAttribute('member-id', $myMember->id);

		if ($downloadFileLink !== null && !$isMyMemberReady)
		{
			$button->setLink($downloadFileLink);
		}
		else
		{
			$button->addClass('sign-action-button');
		}

		return $button->render();
	}

	private function getIconStyle($member): string
	{
		$icon = Uri::urnEncode($member->icon ?? '');
		return !empty($icon) ? "background-image: url('" . $icon . "');" : "";
	}
}