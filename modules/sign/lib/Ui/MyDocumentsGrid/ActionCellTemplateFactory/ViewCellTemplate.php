<?php

namespace Bitrix\Sign\Ui\MyDocumentsGrid\ActionCellTemplateFactory;

use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sign\Contract\Grid\MyDocuments\ActionCellTemplate;

class ViewCellTemplate implements ActionCellTemplate
{
	use ActionDateTrait;

	public function __construct(
		private readonly ?DateTime $sendDate,
		private readonly ?string $downloadFileLink,
		private readonly ?string $textForActionColumn,
	)
	{}

	public function render(): string
	{
		$downloadFileLink = htmlspecialcharsbx($this->downloadFileLink);
		$formattedDate = self::getFormattedDate($this->sendDate);
		$message = Loc::getMessage('SIGN_MEMBER_ACTION_INVITE_WITH_YOU_MSGVER_1');

		return <<<HTML
			<div class="sign-grid-action-signed-info">
			<span class="sign-grid-action-with-me-text">
				$message
			</span>
			<span class="sign-grid-action-date" title="$this->sendDate">
				$formattedDate
			</span>
			<span>
				<a class="sign-grid-download-link" href="$downloadFileLink">
					<span class="sign-grid-download-link-text">
						$this->textForActionColumn
					</span>
				</a>
			</span>
		</div>
		HTML;
	}
}