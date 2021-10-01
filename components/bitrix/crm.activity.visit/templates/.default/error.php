<?php
/**
 * @global $APPLICATION
 * @global $arResult
 */
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();
?>

<div class="crm-activity-visit-wrapper">
	<div class="crm-activity-visit-container">
		<?
		foreach ($this->__component->getErrors() as $error)
		{
			\ShowError($error);
		}
		unset($error);?>
	</div>
</div>
