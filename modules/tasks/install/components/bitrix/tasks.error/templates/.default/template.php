<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

\Bitrix\Main\UI\Extension::load(['ui.design-tokens', 'ui.fonts.opensans']);
?>


<div class="task-no-access">
	<div class="task-no-access-inner">
		<div class="task-no-access-title"><?= $arResult['TITLE'] ?></div>
		<div class="task-no-access-subtitle"><?= $arResult['DESCRIPTION'] ?></div>
		<div class="task-no-access-img">
			<div class="task-no-access-img-inner"></div>
		</div>
	</div>
</div>
