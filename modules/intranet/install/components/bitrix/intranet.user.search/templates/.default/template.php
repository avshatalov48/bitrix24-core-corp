<?

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

$name_x = CUtil::JSEscape($arParams['NAME']);

$arParams['FORM_NAME'] = CUtil::JSEscape($arParams['FORM_NAME']);
$arParams['INPUT_NAME'] = CUtil::JSEscape($arParams['INPUT_NAME']);

?>

<? if ($arParams['SHOW_INPUT'] == 'Y') { ?>
<input type="text" id="<?=htmlspecialcharsex($arParams['~INPUT_NAME']); ?>" name="<?=htmlspecialcharsex($arParams['~INPUT_NAME']); ?>" value="<?=$arParams['INPUT_VALUE']; ?>" size="3" />
<? } ?>

<? if ($arParams['SHOW_BUTTON'] == 'Y') { ?>
<input type="button" onclick="<?=$name_x; ?>.Show()" value="<?=($arParams['BUTTON_CAPTION'] ? htmlspecialcharsex($arParams['BUTTON_CAPTION']) : '...'); ?>" />
<? } ?>

<script type="text/javascript">

if (window.top != window.self)
{
	if (typeof BX == 'undefined')
		BX = top.BX;
	if (typeof JCEmployeeSelectControl == 'undefined')
		JCEmployeeSelectControl = top.JCEmployeeSelectControl;
}

<? if ($arParams['INPUT_NAME']) { ?>

function GetInput_<?=$name_x; ?>(doc)
{
	if (typeof doc == 'undefined')
		doc = document;
	<? if ($arParams['FORM_NAME']) { ?>
	return doc.forms['<?=$arParams['FORM_NAME']; ?>']['<?=$arParams['INPUT_NAME']; ?>'];
	<? } else { ?>
	return doc.getElementById('<?=$arParams['INPUT_NAME']; ?>');
	<? } ?>
}

<? if (!$arParams['ONSELECT']) { ?>

function OnSelect_<?=$name_x; ?>(value)
{
	var q;
	if (BX.SidePanel && BX.SidePanel.Instance)
		q = GetInput_<?=$name_x; ?>();
	else
		q = window.top != window.self ? GetInput_<?=$name_x; ?>(top.document) : GetInput_<?=$name_x; ?>();
	q.value = value;
	if (BX)
		BX.fireEvent(q, 'change');
}

<? $arParams['ONSELECT'] = 'OnSelect_'.$name_x; ?>

<? } ?>
<? } ?>

var <?=$name_x; ?> = new JCEmployeeSelectControl({
	MULTIPLE: <?=($arParams['MULTIPLE'] == 'Y' ? 'true' : 'false'); ?>,
	LANGUAGE_ID:'<?=LANGUAGE_ID; ?>',
	GET_FULL_INFO: <?=($arParams['GET_FULL_INFO'] == 'Y' ? 'true' : 'false'); ?>,
	ONSELECT: function(v) {
		<?=$arParams['ONSELECT']; ?>(v);
	},
	SITE_ID: '<?=$arParams['SITE_ID']; ?>',
	IS_EXTRANET: '<?=$arParams['IS_EXTRANET']; ?>',
	SESSID: '<?=bitrix_sessid(); ?>',
	NAME_TEMPLATE: '<?=urlencode($arParams['NAME_TEMPLATE']); ?>'});

if (window.top != window.self)
	top.<?=$name_x; ?> = <?=$name_x; ?>;

<? if ($arParams['INPUT_NAME']) { ?>

BX.ready(function() {
	var input = GetInput_<?=$name_x; ?>();
	<?=$name_x; ?>.SetValue(input.value);
	BX.adjust(input, {attrs: {'onchange': '<?=$name_x; ?>.SetValue(this.value)'}});
});

<? } ?>

<? if (defined('ADMIN_SECTION')) { ?>
BX.loadCSS("/bitrix/components/bitrix/intranet.user.search/templates/.default/style.css");
<? } ?>

</script>