<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$results = $arResult['RESULT_LIST'];

\Bitrix\Main\UI\Extension::load('ui.fonts.opensans');
?>

<?php if (!empty($results)): ?>
	<div class="tasks-widget-result tasks-widget-result__scope">
		<div class="tasks-widget-result--content" data-role="tasks-widget--content">

			<?php
			$result = array_shift($results);
			include($_SERVER["DOCUMENT_ROOT"].$this->getFolder().'/_result.php');
			?>

			<?php if (!empty($results)): ?>
				<div class="tasks-widget-result__item-more" data-role="tasks-widget--wrapper">
					<?php

					foreach ($results as $result)
					{
						include($_SERVER["DOCUMENT_ROOT"].$this->getFolder().'/_result.php');
					}
					?>
				</div>
			<?php endif; ?>

		</div>
		<?php if (count($results)): ?>
			<div class="tasks-widget-result--btn" data-role="tasks-widget--btn-down"><?= \Bitrix\Main\Localization\Loc::getMessage('TASKS_RESULT_EXPAND_BUTTON', ['#NUM#' => count($results)]); ?></div>
		<?php endif; ?>
		<div class="tasks-widget-result--btn" data-role="tasks-widget--btn-up"><?= \Bitrix\Main\Localization\Loc::getMessage('TASKS_RESULT_COLLAPSE_BUTTON'); ?></div>
	</div>
<?php endif; ?>
