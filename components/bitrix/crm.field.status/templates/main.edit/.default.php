<?php

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Text\HtmlFilter;
use Bitrix\Crm\UserField\Types\StatusType;

/**
 * @var StatusUfComponent $component
 * @var array $arResult
 */
$component = $this->getComponent();

$postfix = $this->randString();
if ($component->isAjaxRequest())
{
	$postfix .= time();
}
$arResult['valueContainerId'] .= $postfix;
$arResult['spanAttrList']['id'] = $arResult['valueContainerId'];
$arResult['controlNodeId'] .= $postfix;
$defaultFieldName = $arResult['fieldName'].'_default_'.$postfix;
?>

<span class="fields crm_status field-wrap" data-has-input="no">
	<input
		type="hidden"
		value=""
		id="<?= $defaultFieldName ?>"
	>
	<span <?= $component->getHtmlBuilder()->buildTagAttributes($arResult['spanAttrList']) ?>>
		<?php
		if (count($arResult['attrList']))
		{
			foreach ($arResult['attrList'] as $attrList)
			{
				?>
				<input <?= $component->getHtmlBuilder()->buildTagAttributes($attrList) ?>>
				<?php
			}
		}
		?>
	</span>
	<span id="<?= $arResult['controlNodeId'] ?>"></span>
</span>

<script>
	BX.ready(function ()
	{
		/**
		 * @todo Remove this in the future. Made so that there is no hard dependence on the main
		 * Need to leave only BX.Desktop.Field.Enum.Ui
		 */
		<?php

		$params = CUtil::PhpToJSObject([
			'defaultFieldName' => $defaultFieldName,
			'fieldName' => $arResult['fieldNameJs'],
			'container' => $arResult['controlNodeId'],
			'valueContainerId' => $arResult['valueContainerId'],
			'block' => $arResult['block'],
			'value' => $arResult['currentValue'],
			'items' => $arResult['items'],
			'params' => $arResult['params']
		]);

		if (defined('\Bitrix\Main\UserField\Types\EnumType::DISPLAY_DIALOG'))
		{
			?>
			new BX.Desktop.Field.Enum.Ui(
				<?= $params ?>
			);
		<?php
		}
		else
		{
			?>
			new BX.Desktop.Field.Enum(
				<?= $params ?>
			);
		<?php
		}
		?>
	});
</script>
