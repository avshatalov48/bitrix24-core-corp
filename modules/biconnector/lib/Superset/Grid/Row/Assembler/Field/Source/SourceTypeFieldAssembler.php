<?php

namespace Bitrix\BIConnector\Superset\Grid\Row\Assembler\Field\Source;

use Bitrix\BIConnector\Superset\Grid\ExternalSourceRepository;
use Bitrix\Main\Grid\Row\FieldAssembler;

class SourceTypeFieldAssembler extends FieldAssembler
{
	protected function prepareColumn($value)
	{
		$listSource = ExternalSourceRepository::getStaticSourceList();

		$source = current(array_filter($listSource, static function($source) use ($value) {
			return $source['CODE'] === $value;
		}));

		if ($source)
		{
			$style = "--ui-icon-size: 24px;";
			$icon = "<div class='{$source['ICON_CLASS']}' style='{$style}'><i></i></div>";
			$name = htmlspecialcharsbx($source['NAME']);

			return <<<HTML
					<span class="biconnector-grid-type-cell" >
						$icon
						<span class="biconnector-grid-type-cell-title">{$name}</span>
					</span>
				HTML;
		}

		return null;
	}
}
