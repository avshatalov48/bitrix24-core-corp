<?php

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

\Bitrix\Main\UI\Extension::load(['ui.design-tokens', 'ui.fonts.opensans', 'ui.feedback.form']);

if($this->getComponent()->getErrors())
{
	foreach($this->getComponent()->getErrors() as $error)
	{
		/** @var \Bitrix\Main\Error $error */
		?>
		<div><?=htmlspecialcharsbx($error->getMessage());?></div>
		<?php
	}

	return;
}

?>

<script>
	BX.ready(() => {
		BX.UI.Feedback.Form.open(
			{
				id: Math.random() + '',
				forms: <?=\Bitrix\Main\Web\Json::encode($arResult['forms'])?>,
			}
		);
		BX.SidePanel.Instance.getSliderByWindow(window)?.close();
	})
</script>
