<?php

namespace Bitrix\AI\SharePrompt\Components\Grid\Row\Field\Assembler;

use Bitrix\Main\Grid\Row\FieldAssembler;
use Bitrix\Main\Text\HtmlFilter;
use CUtil;

class SharePromptIsActiveFieldAssembler extends FieldAssembler
{
	protected function prepareColumn($value): string
	{
		$isActive = $value['isActive'];
		$promptCode = $value['promptCode'];
		$promptName = CUtil::JSEscape($value['promptName']);

		if ($isActive)
		{
			$onclick = HtmlFilter::encode(
				"BX.AI.SharePrompt.Library.Controller.handleClickOnDeactivatePromptMenuItem(event, '$promptCode', '$promptName')"
			);
		}
		else
		{
			$onclick = HtmlFilter::encode(
				"BX.AI.SharePrompt.Library.Controller.handleClickOnActivatePromptMenuItem(event, '$promptCode', '$promptName')"
			);
		}

		$className = 'ui-switcher ui-switcher-color-green';

		if ($isActive === false)
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

	/**
	 * @param array $row
	 *
	 * @return array
	 */
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
				'isActive' => $row['data'][$columnId] ?? null,
				'promptCode' => $row['data']['ID'],
				'promptName' => $row['data']['DATA']['NAME']
			];

			$row['columns'][$columnId] = $this->prepareColumn($data);
		}

		return $row;
	}
}
