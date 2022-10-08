<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

\Bitrix\Main\UI\Extension::load(['ui.emptystate', 'ui.design-tokens', 'ui.fonts.opensans']);

?>
<div class="tasks-interface__emptystate" data-role="tasks-interface__emptystate">
	<div class="tasks-interface__emptystate-title" ><?= str_replace("#BR#", "<br/>", $arParams['TITLE']); ?></div>
	<div class="ui-emptystate --search" style="width: 195px; height: 195px; margin-bottom: 60px">
		<i></i>
	</div>
	<div class="ui-typography-text-lg"><?= str_replace("#BR#", "<br/>", $arParams['TEXT']); ?></div>
</div>