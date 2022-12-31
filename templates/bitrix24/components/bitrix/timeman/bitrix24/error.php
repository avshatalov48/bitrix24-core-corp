<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>

<div class="timeman-container timeman-container-<?=LANGUAGE_ID?><?=(IsAmPmMode() ? " am-pm-mode" : "")?>" id="timeman-container">
	<div class="timeman-wrap">
		<span id="timeman-block" class="timeman-block tm-error">
			<span class="bx-time"></span>
			<span><?=GetMessage('TM_ERROR_'.$arResult['ERROR']);?></span>
		</span>
	</div>
</div>