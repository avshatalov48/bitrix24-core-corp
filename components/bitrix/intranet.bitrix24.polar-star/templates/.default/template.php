<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * @var CBitrixComponentTemplate $this
 * @var array $arResult
 */
$frame = $this->createFrame()->begin('');

if ($arResult['show_time']): ?>
<script>
	(new BX.Intranet.Bitrix24.PolarStar(<?= CUtil::PhpToJSObject($arResult['options'], false, false, true) ?>))
	.show('<?= CUtil::JSEscape($arResult['mode']) ?>');
</script>
<?
endif;

$frame->end();
