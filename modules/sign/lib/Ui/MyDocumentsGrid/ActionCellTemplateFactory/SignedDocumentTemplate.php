<?php

namespace Bitrix\Sign\Ui\MyDocumentsGrid\ActionCellTemplateFactory;

use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Localization\Loc;

class SignedDocumentTemplate implements ActionCellTemplate
{
	use ActionDateTrait;

	private ?DateTime $signDate;

	public function __construct(?DateTime $signDate)
	{
		$this->signDate = $signDate;
	}

	public function get(): string
	{
		$formattedDate = self::getFormattedDate($this->signDate);
		$message = Loc::getMessage('SIGN_B2E_MY_DOCUMENTS_SIGNED');

		return <<<HTML
			<div class="sign-grid-action-signed-info sign-grid-download-background-done">
				<span class="sign-grid-action-signed-text">
					$message
				</span>
				<span class="sign-grid-action-date" title="$this->signDate">
					$formattedDate
				</span>
		</div>
		HTML;
	}
}