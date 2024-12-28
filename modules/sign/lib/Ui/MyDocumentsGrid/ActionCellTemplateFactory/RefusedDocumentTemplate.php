<?php

namespace Bitrix\Sign\Ui\MyDocumentsGrid\ActionCellTemplateFactory;

use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Localization\Loc;

class RefusedDocumentTemplate implements ActionCellTemplate
{
	use ActionDateTrait;

	private ?DateTime $refusedDate;

	public function __construct(?DateTime $refusedDate = null)
	{
		$this->refusedDate = $refusedDate;
	}

	public function get(): string
	{
		$message = Loc::getMessage('SIGN_B2E_MY_DOCUMENTS_REFUSED');
		$formattedDate = self::getFormattedDate($this->refusedDate);

		return <<<HTML
			<div class="sign-grid-action-signed-info sign-grid-download-background-stopped">
				<span class="sign-grid-action-stopped-text">
					$message
				</span>
				<span class="sign-grid-action-date" title="$this->refusedDate">
					$formattedDate
				</span>
			</div>
		HTML;
	}
}