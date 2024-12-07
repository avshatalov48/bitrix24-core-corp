<?php

namespace Bitrix\AI\SharePrompt\Components\Grid\Row\Field\Assembler;

use Bitrix\Main\Grid\Row\FieldAssembler;
use Bitrix\Main\Text\HtmlFilter;
use CUtil;

class SharePromptIsDeletedFieldAssembler extends FieldAssembler
{
	protected function prepareColumn($value): string
	{
		$isDeleted = $value['isDeleted'];
		$promptCode = $value['promptCode'];
		$promptName = CUtil::JSEscape($value['promptName']);

		if ($isDeleted)
		{
			$onclick = HtmlFilter::encode(
				"BX.AI.SharePrompt.Library.Controller.handleClickOnUndoDeletePromptSwitcher(event, '$promptCode', '$promptName')"
			);
		}
		else
		{
			$onclick = HtmlFilter::encode(
				"BX.AI.SharePrompt.Library.Controller.handleClickOnDeletePromptSwitcher(event, '$promptCode', '$promptName')"
			);
		}

		$className = 'ui-switcher ui-switcher-color-green';

		if ($isDeleted === true)
		{
			$className .= ' ui-switcher-off';
		}

		return '
			<div class="' . $className . '" onclick="' . $onclick . '">
				<span class="ui-switcher-cursor"></span>
				<span class="ui-switcher-disabled"></span>
				<span class="ui-switcher-enabled"></span>
			</div>
		';
	}

	protected function prepareRow(array $row): array
	{
		if (empty($this->getColumnIds()))
		{
			return $row;
		}

		$row['columns'] ??= [];

		foreach ($this->getColumnIds() as $columnId)
		{
			$data = [
				'isDeleted' => $row['data'][$columnId] ?? null,
				'promptCode' => $row['data']['ID'],
				'promptName' => $row['data']['DATA']['NAME']
			];

			$row['columns'][$columnId] = $this->prepareColumn($data);
		}

		return $row;
	}
}
