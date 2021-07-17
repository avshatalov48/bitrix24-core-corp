<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var CMain $APPLICATION */
/** @var  array $arResult */
/** @var \CBitrixComponentTemplate $this */
/** @var \IntranetCustomSectionComponent $component */
$component = $this->getComponent();

\Bitrix\Main\UI\Extension::load([
	'ui.alert',
]);

if ($component->hasErrors()):
	?>
	<div class="ui-alert ui-alert-danger">
		<?php foreach($component->getErrors() as $error):?>
			<span class="ui-alert-message"><?= htmlspecialcharsbx($error->getMessage()) ?></span>
		<?php endforeach;?>
	</div>
	<?php

	return;
endif;

if (!$component->isIframe())
{
	$this->SetViewTarget('above_pagetitle');
	$APPLICATION->IncludeComponent(
		'bitrix:main.interface.buttons',
		'',
		$arResult['interfaceButtonsParams']
	);
	$this->EndViewTarget();
}

/** @var \Bitrix\Intranet\CustomSection\Provider\Component $componentToInclude */
$componentToInclude = $arResult['componentToInclude'] ?? null;

if ($componentToInclude)
{
	$APPLICATION->IncludeComponent(
		$componentToInclude->getComponentName(),
		$componentToInclude->getComponentTemplate(),
		$componentToInclude->getComponentParams()
	);
}
