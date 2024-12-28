<?php

namespace Bitrix\BIConnector\Superset\Grid\Row\Assembler\Field\Source;

use Bitrix\BIConnector\Superset\Grid\Row\Assembler\Field\Base\DetailLinkFieldAssembler;
use CUtil;

class NameFieldAssembler extends DetailLinkFieldAssembler
{
	protected function prepareColumn($value): string
	{
		$id = (int)$value['ID'];
		$title = htmlspecialcharsbx($value['TITLE']);
		$moduleId = CUtil::JSEscape($value['MODULE']);

		$link = "
				<a 
					style='cursor: pointer' 
					onclick='BX.BIConnector.ExternalSourceManager.Instance.openSourceDetail({$id}, \"{$moduleId}\")'
				>{$title}</a>
			";

		return <<<HTML
			<div class="source-title-wrapper">
				{$link}
			</div>
		HTML;
	}
}
