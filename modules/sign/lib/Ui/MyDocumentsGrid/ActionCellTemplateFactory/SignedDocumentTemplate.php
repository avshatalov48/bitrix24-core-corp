<?php

namespace Bitrix\Sign\Ui\MyDocumentsGrid\ActionCellTemplateFactory;

use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sign\Contract\Grid\MyDocuments\ActionCellTemplate;

class SignedDocumentTemplate implements ActionCellTemplate
{
	use ActionDateTrait;

	public function __construct(
		private readonly ?DateTime $signDate,
	)
	{}

	public function render(): string
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