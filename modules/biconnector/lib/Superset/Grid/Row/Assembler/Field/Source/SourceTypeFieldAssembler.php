<?php

namespace Bitrix\BIConnector\Superset\Grid\Row\Assembler\Field\Source;

use Bitrix\BIConnector\ExternalSource\Type;
use Bitrix\BIConnector\Superset\Grid\ExternalSourceRepository;
use Bitrix\Main\Grid\Row\FieldAssembler;

class SourceTypeFieldAssembler extends FieldAssembler
{
	protected function prepareColumn($value)
	{
		if (Type::tryFrom($value['TYPE']) === Type::Rest)
		{
			$connectorOfSource = ExternalSourceRepository::getRestLogoBySourceId((int)$value['ID']);
			$logo = htmlspecialcharsbx($connectorOfSource['LOGO'] ?? '');
			$title = htmlspecialcharsbx($connectorOfSource['TITLE'] ?? '');

			return <<<HTML
					<span class="biconnector-grid-type-cell" >
						<div style="background-image: url('$logo'); min-width: 24px; min-height: 24px" class="biconnector-superset-source-list__icon_image"></div>
						<span class="biconnector-grid-type-cell-title">$title</span>
					</span>
				HTML;
		}

		$listSource = ExternalSourceRepository::getStaticSourceList();

		$source = current(array_filter($listSource, static function($source) use ($value) {
			return $source['CODE'] === $value['TYPE'];
		}));

		if ($source)
		{
			$style = "--ui-icon-size: 24px; min-width: 24px; min-height: 24px";
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

	protected function prepareRow(array $row): array
	{
		if (empty($this->getColumnIds()))
		{
			return $row;
		}

		$row['columns'] ??= [];

		foreach ($this->getColumnIds() as $columnId)
		{
			$value = $row['data'];
			$row['columns'][$columnId] = $this->prepareColumn($value);
		}

		return $row;
	}
}
