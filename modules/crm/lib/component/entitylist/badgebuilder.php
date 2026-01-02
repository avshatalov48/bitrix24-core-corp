<?php

namespace Bitrix\Crm\Component\EntityList;

final class BadgeBuilder
{
	private static bool $isHintInitiated = false;

	public static function render(array $badges): string
	{
		$badge = current($badges);
		$titleText = htmlspecialcharsbx($badge['fieldName']);
		$fieldClass = 'crm-kanban-item-badges-item-value crm-kanban-item-badges-status';
		$hint = htmlspecialcharsbx($badge['hint'] ?? '');

		$backgroundColor = $badge['backgroundColor'];
		$textColor = $badge['textColor'];
		$style = "background-color: $backgroundColor;border-color:$backgroundColor;color:$textColor;";
		$text = htmlspecialcharsbx($badge['textValue']);

		$html = <<<HTML
			<div class="crm-kanban-item-badges">
				<div class="crm-kanban-item-badges-item-title">
					<div class="crm-kanban-item-badges-item-title-text">$titleText</div>
				</div>
				<div class="crm-kanban-item-badges-item">
					<div class="$fieldClass" style="$style" data-badgehint="$hint">$text</div>
				</div>
			</div>
HTML;

		$js = '';
		if (!empty($hint) && !self::$isHintInitiated)
		{
			self::$isHintInitiated = true;

			$js = <<<JS
				<script type="text/javascript">
    				BX.ready(() => {
						document.querySelectorAll('.crm-kanban-item-badges-item-value').forEach((item) => {
							const badge = new BX.Crm.Badge(item);
							badge.init();
						});
    				});
				</script>
JS;
		}

		return $html . PHP_EOL . $js;
	}
}