<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var array $arParams */
/** @var array $arResult */
/** @global CAllMain $APPLICATION */
/** @global CAllUser $USER */
/** @global CAllDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */

use Bitrix\Main\Web\Json;

\CJSCore::Init(
	[
		'applayout',
	]
);
$this->setFrameMode(true);

$containerId = 'intranet-placement-' . $arParams['PLACEMENT_CODE'];
$frame = $this->createFrame($containerId)->begin('');
?>
<div id="<?=$containerId?>" style="display: none;"></div>
<script type="text/javascript">
	BX.ready(function () {
		var placement = new BX.Intranet.Placement();
		placement.init(<?=Json::encode(
			[
				'containerId' => $containerId,
				'maxLoadDelay' => $arResult['MAX_LOAD_DELAY'],
				'serviceUrl' => $arResult['SERVICE_URL'],
				'items' => $arResult['ITEMS'],
				'placementCode' => $arParams['PLACEMENT_CODE'],
				'signedParameters' => $this->getComponent()->getSignedParameters(),
			]
		)?>);
	});
</script>
<?php
$frame->end();
