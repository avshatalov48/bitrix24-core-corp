<?php

namespace Bitrix\Sign\Ui\MyDocumentsGrid\ActionCellTemplateFactory;

use Bitrix\Main\Type\DateTime;

class DefaultCompletedActionTextTemplate implements ActionCellTemplate
{
	use ActionDateTrait;

	private ?string $textForActionColumn;
	private ?DateTime $actionDate;

	public function __construct(?string $textForActionColumn, ?DateTime $actionDate)
	{
		$this->textForActionColumn = $textForActionColumn;
		$this->actionDate = $actionDate;
	}

	public function get(): string
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