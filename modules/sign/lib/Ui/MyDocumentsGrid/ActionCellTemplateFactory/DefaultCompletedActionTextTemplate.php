<?php

namespace Bitrix\Sign\Ui\MyDocumentsGrid\ActionCellTemplateFactory;

use Bitrix\Main\Type\DateTime;
use Bitrix\Sign\Contract\Grid\MyDocuments\ActionCellTemplate;

class DefaultCompletedActionTextTemplate implements ActionCellTemplate
{
	use ActionDateTrait;

	public function __construct(
		private readonly ?string $textForActionColumn,
		private readonly ?DateTime $actionDate
	)
	{}

	public function render(): string
	{
		$formattedDate = self::getFormattedDate($this->actionDate);

		return <<<HTML
			<div class="sign-grid-my-action-in-process">
				$this->textForActionColumn
			</div>
			<span class="sign-grid-action-date" title="$this->actionDate">
				$formattedDate
			</span>
		HTML;
	}
}