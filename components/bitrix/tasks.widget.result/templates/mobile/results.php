<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var array $arParams */
/** @var array $arResult */

\Bitrix\Main\UI\Extension::load('tasks.result');

$results = $arResult['RESULT_LIST'];
?>

<?php if (!empty($results)): ?>
	<div class="mobile-tasks-widget-result mobile-tasks-widget-result__scope">
		<div class="mobile-tasks-widget-result--content" data-role="mobile-tasks-widget--content">

			<?php
			$result = array_shift($results);
			include($_SERVER["DOCUMENT_ROOT"].$this->getFolder().'/_result.php');
			?>

			<?php if (!empty($results)): ?>
				<div class="mobile-tasks-widget-result__item-results" data-role="mobile-tasks-widget--wrapper">
					<?php

					foreach ($results as $result)
					{
						include($_SERVER["DOCUMENT_ROOT"].$this->getFolder().'/_result.php');
					}
					?>
				</div>
			<?php endif; ?>

		</div>
	</div>
<?php endif; ?>
