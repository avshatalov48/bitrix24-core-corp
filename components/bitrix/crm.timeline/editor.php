<?php
/**
 * @deprecated
 *
 * File was left for compatibility
 *
 * Use BX.ajax.runAction("crm.api.timeline.loadEditor", { data: { $id: 0, name: "" } }) for loading timeline comment editor
 */

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');
global $APPLICATION;

if (!check_bitrix_sessid())
	return false;

if (! \Bitrix\Main\Loader::includeModule('crm'))
	return false;

$id = (int)($_REQUEST['id']) > 0 ? (int)$_REQUEST['id'] : 0;
$editorName = isset($_REQUEST['name']) > 0 ? htmlspecialcharsbx($_REQUEST['name']) : "CrmTimeLineComment{$id}";
$formId = "crm-timeline-comment-{$id}";

$text = "";
if ($id)
{
	$timelineBinding =  Bitrix\Crm\Timeline\Entity\TimelineBindingTable::getList(
		array(
			"filter" => array('OWNER_ID' => $id)
		)
	);
	$isAllowed = false;
	while($bind = $timelineBinding->fetch())
	{
		$isAllowed =  Bitrix\Crm\Security\EntityAuthorization::checkUpdatePermission($bind['ENTITY_TYPE_ID'], $bind['ENTITY_ID']);
		if ($isAllowed)
		{
			break;
		}
	}

	if (!$isAllowed)
	{
		$id = 0;
	}
	else
	{
		$timelineData = Bitrix\Crm\Timeline\Entity\TimelineTable::getById($id);
		$comment = $timelineData->fetch();
		$text = $comment['COMMENT'];
	}

}

$allowVideo = COption::GetOptionString("blog","allow_video", "Y");

$fileFields = $GLOBALS['USER_FIELD_MANAGER']->GetUserFields(\Bitrix\Crm\Timeline\CommentController::UF_FIELD_NAME, $id);
if (isset($fileFields['UF_CRM_COMMENT_FILES']))
{
	$fileFields['UF_CRM_COMMENT_FILES']['~EDIT_FORM_LABEL'] = \Bitrix\Crm\Timeline\CommentController::UF_COMMENT_FILE_NAME;
	$fileFields['UF_CRM_COMMENT_FILES']['TAG'] = 'DOCUMENT ID';
}

$APPLICATION->includeComponent(
	'bitrix:main.post.form', '',
	array(
		'SELECTOR_VERSION' => 2,
		'FORM_ID' => $formId,
		'SHOW_MORE' => 'N',
		'PARSER' => array(
			'Bold', 'Italic', 'Underline', 'Strike',
			'ForeColor', 'FontList', 'FontSizeList', 'RemoveFormat',
			'Quote', 'Code', 'InsertCut',
			'CreateLink', 'Image', 'Table', 'Justify',
			'InsertOrderedList', 'InsertUnorderedList',
			'SmileList', 'Source', 'UploadImage', 'InputVideo', 'MentionUser'
		),
		'BUTTONS' => array(
			'UploadImage',
			"CreateLink",
			"InputVideo",
			"Quote",
			"MentionUser"
		),
		'TEXT' => array(
			'NAME' => 'MESSAGE',
			'VALUE' => $text,
			'HEIGHT' => '120px'
		),
		'LHE' => array(
			'id' => $editorName,
			'documentCSS' => 'body {color:#434343;background:#F7FBE9}',
			'ctrlEnterHandler' => "CrmTimeLineComment{$id}FormSendHandler",
			'jsObjName' => $editorName,
			'width' => '100%',
			'minBodyWidth' => '100%',
			'normalBodyWidth' => '100%',
			'height' => 100,
			'minBodyHeight' => 100,
			'showTaskbars' => false,
			'showNodeNavi' => false,
			'autoResize' => true,
			'autoResizeOffset' => 50,
			'bbCode' => true,
			'saveOnBlur' => false,
			'bAllowPhp' => false,
			'lazyLoad' => true,
			'limitPhpAccess' => false,
			'setFocusAfterShow' => true,
			'askBeforeUnloadPage' => false,
			'useFileDialogs' => false,
			'controlsMap' => array(
				array('id' => 'Bold',  'compact' => true, 'sort' => 10),
				array('id' => 'Italic',  'compact' => true, 'sort' => 20),
				array('id' => 'Underline',  'compact' => true, 'sort' => 30),
				array('id' => 'Strikeout',  'compact' => true, 'sort' => 40),
				array('id' => 'RemoveFormat',  'compact' => true, 'sort' => 50),
				array('id' => 'Color',  'compact' => true, 'sort' => 60),
				array('id' => 'FontSelector',  'compact' => false, 'sort' => 70),
				array('id' => 'FontSize',  'compact' => false, 'sort' => 80),
				array('separator' => true, 'compact' => false, 'sort' => 90),
				array('id' => 'OrderedList',  'compact' => true, 'sort' => 100),
				array('id' => 'UnorderedList',  'compact' => true, 'sort' => 110),
				array('id' => 'AlignList', 'compact' => false, 'sort' => 120),
				array('separator' => true, 'compact' => false, 'sort' => 130),
				array('id' => 'InsertLink',  'compact' => true, 'sort' => 140, 'wrap' => 'bx-b-link-'.$formId),
				array('id' => 'InsertImage',  'compact' => false, 'sort' => 150),
				array('id' => 'InsertVideo',  'compact' => true, 'sort' => 160, 'wrap' => 'bx-b-video-'.$formId),
				array('id' => 'InsertTable',  'compact' => false, 'sort' => 170),
				array('id' => 'Code',  'compact' => true, 'sort' => 180),
				array('id' => 'Quote',  'compact' => true, 'sort' => 190, 'wrap' => 'bx-b-quote-'.$formId),
				array('separator' => true, 'compact' => false, 'sort' => 200),
				array('id' => 'BbCode',  'compact' => true, 'sort' => 220),
				array('id' => 'More',  'compact' => true, 'sort' => 230),
			),
		),
		"USE_CLIENT_DATABASE" => "Y",
		"FILES" => Array(
			"VALUE" => array(),
			"DEL_LINK" => '',
			"SHOW" => "N"
		),
		"UPLOAD_FILE" => true,
		"UPLOAD_FILE_PARAMS" => array('width' => 400, 'height' => 400),
		'UPLOAD_WEBDAV_ELEMENT' => isset($fileFields['UF_CRM_COMMENT_FILES']) ? $fileFields['UF_CRM_COMMENT_FILES'] : false,
		"ALLOW_CRM_EMAILS" => "Y"
	),
	false,
	array('HIDE_ICONS' => 'Y')
);
require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');