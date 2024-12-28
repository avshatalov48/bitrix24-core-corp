<?php

namespace Bitrix\Sign\Ui\MyDocumentsGrid;

use Bitrix\Main\Web\Uri;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sign\Type\DocumentStatus;
use Bitrix\Sign\Type\MemberStatus;
use Bitrix\Sign\Type\MyDocumentsGrid\Action;
use Bitrix\Sign\Ui\MyDocumentsGrid\ActionCellTemplateFactory\ActionCellTemplateFactory;
use Bitrix\UI\Buttons\Button;

class Template
{
	public static function getDocumentTitle(
		string $documentTitle,
		?string $role
	): string
	{
		ob_start();
		?>
		<div class="sign-grid-document-title">
			<div class="sign-grid-document-title_text">
				<div class="sign-grid-document-title">
					<?= htmlspecialcharsbx($documentTitle) ?>
				</div>
				<div class="sign-grid-document-title_my_role">
					<div class="sign-grid-document-title_text_my_role">
						<?= Loc::getMessage('SIGN_B2E_MY_DOCUMENTS_MY_ROLE_IN_PROCESS',
							[
								'#ROLE#' => htmlspecialcharsbx($role)
							]);
						?>
					</div>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	public static function getParticipants(
		array $initiator,
		array $secondSideMember,
		array $document,
		array $myMember,
	): string
	{
		ob_start();

		$initiatorIcon = Uri::urnEncode($initiator['icon'] ?? '');
		$iconStylesForInitiator = !empty($initiatorIcon) ? "background-image: url('". $initiatorIcon . "');" : "";

		$secondSideMemberIcon = Uri::urnEncode($secondSideMember['icon'] ?? '');
		$iconStylesForSecondSideMember = !empty($secondSideMemberIcon) ? "background-image: url('". $secondSideMemberIcon . "');" : "";
		?>
		<div class="sign-grid-document-users-container">
			<div class="sign-grid-document-user-container">
				<a
					class="sign-grid-document-user"
					target="_top"
					onclick="event.stopPropagation();"
					href="/company/personal/user/<?= $initiator['id']?>/">
					<div class="sign-grid-document-user-role-title-from">
						<?= Loc::getMessage('SIGN_B2E_MY_DOCUMENTS_FROM_WHOM') ?>
					</div>
					<div class="ui-icon ui-icon-common-user" title="<?= htmlspecialcharsbx($initiator['fullName']) ?>">
						<i style=" <?= $iconStylesForInitiator ?>">
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
					href="/company/personal/user/<?= $secondSideMember['id'] ?>/">
					<div class="sign-grid-document-user-role-title-to">
						<?= TextMapper::getSecondSideMemberRoleText(
							$secondSideMember,
							$document,
							$myMember,
						) ?>
					</div>
					<div class="ui-icon ui-icon-common-user" title="<?= htmlspecialcharsbx($secondSideMember['fullName']) ?>">
						<i style=" <?= $iconStylesForSecondSideMember ?>">
						</i>
					</div>
				</a>
			</div>
		</div>
		<?php
		return (string)ob_get_clean();
	}

	public static function getAction(
		int $memberId,
		?string $textForActionColumn,
		array $myMember,
		?Action $actionStatus,
		array $document,
		array $secondSideMember,
		array $initiator,
		?string $downloadFileLink = null,
	): ?string
	{
		$isMyMemberCurrentUser = $myMember['isCurrentUser'];
		$isMyMemberReady = MemberStatus::isReadyForSigning($myMember['status']);

		if ($actionStatus !== null && ($isMyMemberCurrentUser && $isMyMemberReady) && $document['status'] !== DocumentStatus::STOPPED)
		{
			return self::getActionButton(
				$textForActionColumn,
				$downloadFileLink,
				$isMyMemberReady,
				$memberId,
			);
		}

		$template = ActionCellTemplateFactory::create(
			$actionStatus,
			$myMember,
			$document,
			$downloadFileLink,
			$textForActionColumn,
			$secondSideMember,
			$initiator
		);

		return $template->get();
	}

	private static function getActionButton(
		?string $textForActionColumn,
		?string $downloadFileLink,
		bool $isMyMemberReady,
		int $memberId,
	): ?string
	{
		$button = new Button([
			'text' => $textForActionColumn,
		]);

		$button->setColor(\Bitrix\UI\Buttons\Color::SUCCESS);
		$button->setSize(\Bitrix\UI\Buttons\Size::SMALL);
		$button->setStyles([
			'width' => '160px',
			'border-radius' => '20px',
		]);
		$button->addDataAttribute('member-id', $memberId);

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
}