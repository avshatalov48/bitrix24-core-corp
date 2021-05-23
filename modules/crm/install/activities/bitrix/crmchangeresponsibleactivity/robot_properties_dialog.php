<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @var array $arResult */

/** @var \Bitrix\Bizproc\Activity\PropertiesDialog $dialog */

$map = $dialog->getMap();

foreach (['Responsible', 'ModifiedBy'] as $propertyKey):

	if (!isset($map[$propertyKey]))
	{
		continue;
	}
	?>
	<div class="bizproc-automation-popup-settings">
		<span class="bizproc-automation-popup-settings-title bizproc-automation-popup-settings-title-autocomplete">
			<?=htmlspecialcharsbx($map[$propertyKey]['Name'])?>:
		</span>
		<?=$dialog->renderFieldControl($map[$propertyKey])?>
	</div>
<?endforeach;?>