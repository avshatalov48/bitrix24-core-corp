<?php

namespace Bitrix\BIConnector\Superset\Grid\Row\Assembler\Field\Dataset;

use Bitrix\BIConnector\Superset\Grid\Row\Assembler\Field\Base\DetailLinkFieldAssembler;

class NameFieldAssembler extends DetailLinkFieldAssembler
{
	protected function prepareColumn($value): string
	{
		$id = (int)$value['ID'];
		$title = htmlspecialcharsbx($value['NAME']);
		$type = $value['TYPE'];

		$link = "
				<a 
					style='cursor: pointer' 
					onclick='BX.BIConnector.DatasetImport.Slider.open(\"$type\", $id)' 
				>{$title}</a>
			";

		return <<<HTML
			<div class="dataset-title-wrapper">
				{$link}
			</div>
		HTML;
	}
}
