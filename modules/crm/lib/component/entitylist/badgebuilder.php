<?php

namespace Bitrix\Crm\Component\EntityList;

final class BadgeBuilder
{
	public static function render(array $badges): string
	{
		$badge = current($badges);
		$titleText = htmlspecialcharsbx($badge['fieldName']);
		$fieldClass = 'crm-kanban-item-badges-item-value crm-kanban-item-badges-status';
		$backgroundColor = $badge['backgroundColor'];
		$textColor = $badge['textColor'];
		$style = "background-color: $backgroundColor;border-color:$backgroundColor;color:$textColor;";
		$text = htmlspecialcharsbx($badge['textValue']);

		return <<<HTML
			<div class="crm-kanban-item-badges">
				<div class="crm-kanban-item-badges-item-title">
					<div class="crm-kanban-item-badges-item-title-text">$titleText</div>
				</div>
				<div class="crm-kanban-item-badges-item">			
					<div class="$fieldClass" style="$style">$text</div>
				</div>
			</div>
HTML;
	}
}