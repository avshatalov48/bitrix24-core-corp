<?php

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

/**
 * @var array $arResult
 */

$name = $arResult['additionalParameters']['NAME'];
?>

<tr valign="top">
	<td><?= Loc::getMessage('USER_TYPE_CRM_ENTITY_TYPE') ?>:</td>
	<td>
		<input
			type="checkbox"
			name="<?= $name ?>[LEAD]"
			value="Y"
			<?= ($arResult['entityTypeLead'] === 'Y' ? 'checked="checked"' : '') ?>
		>
		<?= Loc::getMessage('USER_TYPE_CRM_ENTITY_TYPE_LEAD') ?>
		<br/>
		<input
			type="checkbox"
			name="<?= $name ?>[CONTACT]"
			value="Y"
			<?= ($arResult['entityTypeContact'] === 'Y' ? 'checked="checked"' : '') ?>
		>
		<?= Loc::getMessage('USER_TYPE_CRM_ENTITY_TYPE_CONTACT') ?>
		<br/>
		<input
			type="checkbox"
			name="<?= $name ?>[COMPANY]"
			value="Y"
			<?= ($arResult['entityTypeCompany'] === 'Y' ? 'checked="checked"' : '') ?>
		>
		<?= Loc::getMessage('USER_TYPE_CRM_ENTITY_TYPE_COMPANY') ?>
		<br/>
		<input
			type="checkbox"
			name="<?= $name ?>[DEAL]"
			value="Y"
			<?= ($arResult['entityTypeDeal'] === 'Y' ? 'checked="checked"' : '') ?>
		>
		<?= Loc::getMessage('USER_TYPE_CRM_ENTITY_TYPE_DEAL') ?>
		<br/>
		<input
			type="checkbox"
			name="<?= $name ?>[ORDER]"
			value="Y"
			<?= ($arResult['entityTypeOrder'] === 'Y' ? 'checked="checked"' : '') ?>
		>
		<?= Loc::getMessage('USER_TYPE_CRM_ENTITY_TYPE_ORDER') ?>
	</td>
</tr>