<?php

namespace Bitrix\BIConnector\Superset\Grid\Row\Assembler\Field\Dataset;

use Bitrix\BIConnector\Superset\Grid\Row\Assembler\Field\Base\DetailLinkFieldAssembler;

class IdFieldAssembler extends DetailLinkFieldAssembler
{
	protected function prepareColumn($value): string
	{
		$id = (int)$value['ID'];
		$type = $value['TYPE'];

		$link = "
				<a 
					style='cursor: pointer' 
					onclick='BX.BIConnector.DatasetImport.Slider.open(\"$type\", $id)' 
				>
					{$id}
				</a>
			";

		return $link;
	}
}
