<?php

namespace Bitrix\AI\SharePrompt\Components\Grid\Row\Field\Assembler;

use Bitrix\Main\Grid\Row\FieldAssembler;

class SharePromptCategoriesFieldAssembler extends FieldAssembler
{
	protected function prepareColumn($value): string
	{
		$promptCategories = $value['categories'];
		$promptCode = $value['promptCode'];

		if (count($promptCategories) === 0)
		{
			return '';
		}

		return <<<HTML
			<div class="ai__prompt-library-grid__categories-cell">
				<div class="ui-label ui-label-fill ui-label-light">
					<div class="ui-label-inner">{$promptCategories[0]['name']}</div>
				</div>
				{$this->getContent($promptCategories, $promptCode)}
			</div>
		HTML;
	}

	private function getContent(array $promptCategories, string $promptCode): string
	{
		if (count($promptCategories) < 2) {
			return '';
		}

		$etcPromptCategoriesCount = count($promptCategories) - 1;

		return <<<HTML
					<div
						class="ui-label ui-label --cursor-pointer --etc-items-label" 
						onclick="BX.AI.SharePrompt.Library.Controller.handleClickOnCategoriesCell(
							event, '$promptCode', '{$promptCategories[0]['code']}'
						)"
					>
						<div class="ui-label-inner">+$etcPromptCategoriesCount</div>
					</div>
		HTML;
	}
}
