<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$APPLICATION->AddHeadString('<link href="'.CUtil::GetAdditionalFileURL(BX_PERSONAL_ROOT.'/js/im/css/common.css').'" type="text/css" rel="stylesheet" />');

$composite = \Bitrix\Main\Page\Frame::getInstance();
$composite->setEnable();
$composite->setUseAppCache();
?>
<div id="im-dialog-wrap"></div>
<div id="im-dialog-invite"></div>
<div id="im-dialog-form"></div>
<script>
	BXIM = new BX.ImMobile();
</script>
<?
$frame = $this->createFrame("im_component_dialog_v7_".$USER->GetId())->begin();
$frame->setBrowserStorage(true);
?>
<script>
	BX.ready(function() {
		BXIM.initParams(<?=$arResult['TEMPLATE']?>);
	});
</script>
<?
$frame->beginStub();
?>
<script>
	BXMobileApp.UI.Page.TopBar.title.setText('');
	BXMobileApp.UI.Page.TopBar.title.setDetailText('');
</script>
<?
$frame->end();
?>
