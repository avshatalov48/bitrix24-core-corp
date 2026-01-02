<?php

namespace Bitrix\BIConnector\Superset\Grid\Row\Assembler\Field\Base;

use Bitrix\Main\Grid\Row\FieldAssembler;

trait FieldAssemblerPencilTrait
{
	private function addPencil(string $fieldName, $fieldValue, $rowId)
	{
		$fieldName = \CUtil::JSEscape($fieldName);
		$rowId = \CUtil::JSEscape($rowId);

		return <<<HTML
			<div class="editable-column-wrapper">
				<div class="editable-column-wrapper__item editable-column-preview">
					<span class="editable-column-content">{$fieldValue}</span>
					<div class="editable-column-buttons">
						<a
							onclick="event.stopPropagation(); BX.BIConnector.Grid.EditableColumnManager.editCell('{$fieldName}', '{$rowId}')"
						>
							<i
								class="ui-icon-set --pencil-60"
								style="--ui-icon-set__icon-size: 21px; --ui-icon-set__icon-color: none"
							></i>
						</a>
					</div>
				</div>
			</div>
		HTML;
	}
}
