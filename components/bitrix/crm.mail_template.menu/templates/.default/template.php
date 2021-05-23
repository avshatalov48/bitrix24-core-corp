<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if (empty($arResult['BUTTONS']))
	return;

if ($arParams['TYPE'] == 'list')
{
	if (SITE_TEMPLATE_ID == 'bitrix24')
	{
		$bodyClass = $APPLICATION->getPageProperty('BodyClass', false);
		$APPLICATION->setPageProperty('BodyClass', trim(sprintf('%s %s', $bodyClass, 'pagetitle-toolbar-field-view')));

		$this->setViewTarget('inside_pagetitle');
		?><div class="pagetitle-container pagetitle-flexible-space"></div><span class="pagetitle-container pagetitle-align-right-container"><?
	}

	foreach ($arResult['BUTTONS'] as $item)
	{
		if ('btn-new' == $item['ICON'])
		{
			?>
			<a class="webform-small-button webform-small-button-blue webform-small-button-add"
				href="<?=htmlspecialcharsbx($item['LINK']) ?>" title="<?=htmlspecialcharsbx($item['TITLE']) ?>">
				<span class="webform-small-button-icon"></span>
				<span class="webform-small-button-text">
					<?=htmlspecialcharsbx($item['TEXT']) ?>
				</span>
			</a>
			<?

			break;
		}
	}

	if (SITE_TEMPLATE_ID == 'bitrix24')
	{
		$this->endViewTarget();
		?></span><?
	}

	?>
	<script type="text/javascript">

		BX.SidePanel.Instance.bindAnchors({
			rules: [
				{
					condition: [
						'/crm/configs/mailtemplate/add/',
						'/crm/configs/mailtemplate/edit/',
					],
					options: {
						cacheable: false,
						width: 1080
					}
				}
			]
		});

	</script>
	<?
}
else
{
	global $APPLICATION;
	$APPLICATION->includeComponent(
		'bitrix:main.interface.toolbar',
		'',
		array(
			'BUTTONS' => $arResult['BUTTONS']
		),
		$component,
		array(
			'HIDE_ICONS' => 'Y'
		)
	);
}
