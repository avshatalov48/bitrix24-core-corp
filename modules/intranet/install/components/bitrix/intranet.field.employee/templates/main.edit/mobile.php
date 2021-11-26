<?php

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Text\HtmlFilter;

/**
 * @var EmployeeUfComponent $component
 * @var array $arResult
 */

$component = $this->getComponent();

$nodes = [];
?>
<select
	class="mobile-grid-data-select"
	name="<?= $arResult['fieldName'] ?>"
	data-bx-type="select-user"
	bx-can-drop="true"
	id="<?= $arResult['userField']['~id'] ?>"
	<?= ($arResult['userField']['MULTIPLE'] === 'Y' ? ' multiple' : '') ?>
>
	<?php
	foreach($arResult['value'] as $item)
	{
		?>
		<option value="<?= $item['userId'] ?>" selected="selected"><?= $item['userId'] ?></option>
		<?php
	}
	$nodes[] = $arResult['userField']['~id'];
	?>
</select>

<div class="mobile-grid-field-select-user-item-container">
	<?php
	if ($arResult['value'])
	{
		foreach($arResult['value'] as $item)
		{
			$avatarStyle = '';

			if(!empty($item['personalPhoto']))
			{
				$avatarStyle = ' style="background-image:url(' . HtmlFilter::encode($item['personalPhoto']) . ')"';
			}
			?>
			<div class="mobile-grid-field-select-user-item-outer">
				<div class="mobile-grid-field-select-user-item">

					<del
						id="<?= $arResult['userField']['~id'] ?>_del_<?= $item['userId'] ?>"
					>
					</del>

					<div class="avatar" <?= $avatarStyle ?>></div>
					<span
						onclick="BXMobileApp.Events.postToComponent('onUserProfileOpen', [<?= $item['userId'] ?>], 'communication');"
					>
						<?= $item['name'] ?>
					</span>
				</div>
			</div>
			<?php
		}
	}
	?>
</div>

<a
	class="mobile-grid-button select-user add"
	id="<?= $arResult['userField']['~id'] ?>_select"
	href="#"
>
	<?= (
	empty($arResult['value'][0])
		?
		Loc::getMessage('interface_form_select')
		:
		Loc::getMessage('interface_form_change')
	) ?>
</a>

<script>
	BX.ready(function ()
	{
		new BX.Mobile.Field.SelectUser(
			<?=CUtil::PhpToJSObject([
				'name' => 'BX.Mobile.Field.SelectUser',
				'nodes' => $nodes,
				'restrictedMode' => true,
				'formId' => $arParams['additionalParameters']['formId'],
				'gridId' => $arParams['additionalParameters']['gridId'],
				'useOnChangeEvent' => false,
			])?>
		);
	});
</script>
