<?php

namespace Bitrix\Sign\Ui\MyDocumentsGrid\ActionCellTemplateFactory;

use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Localization\Loc;

class ViewCellTemplate implements ActionCellTemplate
{
	use ActionDateTrait;

	private ?DateTime $sendDate;
	private ?string $downloadFileLink;
	private ?string $textForActionColumn;

	public function __construct(?DateTime $sendDate, ?string $downloadFileLink, ?string $textForActionColumn)
	{
		$this->sendDate = $sendDate;
		$this->downloadFileLink = $downloadFileLink;
		$this->textForActionColumn = $textForActionColumn;
	}

	public function get(): string
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