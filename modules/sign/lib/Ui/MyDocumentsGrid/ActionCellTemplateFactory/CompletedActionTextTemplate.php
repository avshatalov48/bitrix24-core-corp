<?php

namespace Bitrix\Sign\Ui\MyDocumentsGrid\ActionCellTemplateFactory;

use Bitrix\Main\Type\DateTime;
use Bitrix\Sign\Contract\Grid\MyDocuments\ActionCellTemplate;
use Bitrix\Sign\Item\MyDocumentsGrid\Row;
use Bitrix\Sign\Ui\MyDocumentsGrid\TextGenerator;

class CompletedActionTextTemplate implements ActionCellTemplate
{
	use ActionDateTrait;
	private TextGenerator $textGenerator;

	public function __construct(
		private readonly Row $row,
		private readonly ?DateTime $actionDate,
	)
	{
		$this->textGenerator = new TextGenerator($this->row);
	}

	public function render(): string
	{
		$completedActionText = $this->textGenerator->getTextByRoleForCompleteAction();
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