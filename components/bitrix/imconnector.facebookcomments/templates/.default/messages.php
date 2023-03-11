<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if (!empty($arResult['messages']))
{
	echo '<div class="imconnector-field-container">'.
		 '<div class="imconnector-field-section imconnector-settings-message imconnector-settings-message-success">';
	foreach ($arResult['messages'] as $value)
	{
		echo '<div>' . $value . '</div>';
	}
	echo '</div>'.
		 '</div>';
}
if (!empty($arResult['error']))
{
	echo '<div class="imconnector-field-container">'.
		 '<div class="imconnector-field-section imconnector-settings-message imconnector-settings-message-error">';
	foreach ($arResult['error'] as $value)
	{
		echo '<div>' . $value . '</div>';
	}
	echo '</div>'.
		 '</div>';
}
