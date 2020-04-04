<?if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

CJSCore::Init(["sidepanel"]);
?>

<div class="bx-vi-phones-title"><?=GetMessage('VI_PHONES_TITLE')?></div>
<div class="bx-vi-phones-block-1"><?=GetMessage('VI_PHONES_HELP_1')?></div>
<div class="bx-vi-phones-block-2"><?=GetMessage('VI_PHONES_HELP_2', Array(
	'#LINK_USERS_START#' => '<a href="'.CVoxImplantMain::GetPublicFolder().'users.php" onclick="BX.SidePanel.Instance.open(this.href);return false;">',
	'#LINK_COURSE_1_START#' => '<a href="'.$arResult['LINK_TO_DOC'].'" target="_blank">',
	'#LINK_COURSE_2_START#' => '<a href="'.$arResult['LINK_TO_DOC'].'" target="_blank">',
	'#LINK_END#' => '</a>',
	'#IMAGE_CONNECT#' => '<div class="bx-vi-phones-connect bx-vi-phones-connect-'.LANGUAGE_ID.'"></div>',
	'#IMAGE_CALL_WITHOUT_BROWER#' => '<div class="bx-vi-phones-call-without-browser bx-vi-phones-call-without-browser-'.LANGUAGE_ID.'"></div>',
));?></div>
