<?php

namespace Bitrix\Crm\Controller;

use Bitrix\Crm\Security\EntityAuthorization;
use Bitrix\Crm\Timeline\CommentController;
use Bitrix\Crm\Timeline\Entity\TimelineBindingTable;
use Bitrix\Crm\Timeline\Entity\TimelineTable;
use Bitrix\Crm\Timeline\ExternalNoticeController;
use Bitrix\Main;
use Bitrix\Main\Engine\Response\Component;
use Bitrix\Main\Engine\Response\HtmlContent;
use Bitrix\Main\Text\Emoji;

class Timeline extends Main\Engine\Controller
{
	/**
	 * BX.ajax.runAction('crm.api.timeline.loadEditor', { data: { $id: 0, name: '' } });
	 *
	 * @param mixed $id
	 * @param mixed $name
	 */
	final public function loadEditorAction($id = null, $name = null)
	{
		$id = isset($id) ? (int)$id : 0;
		$editorName = empty($name) ? "CrmTimeLineComment{$id}" : htmlspecialcharsbx($name);
		$formId = "crm-timeline-comment-{$id}";

		$text = '';
		if ($id > 0)
		{
			$isAllowed = false;
			$timelineBinding = TimelineBindingTable::getList(['filter' => ['OWNER_ID' => $id]]);
			while($bind = $timelineBinding->fetch())
			{
				$isAllowed = EntityAuthorization::checkUpdatePermission(
					$bind['ENTITY_TYPE_ID'],
					$bind['ENTITY_ID']
				);
				if ($isAllowed)
				{
					break;
				}
			}

			if ($isAllowed)
			{
				$timelineData = TimelineTable::getById($id);
				$comment = $timelineData->fetch() ?? [];
				$text = Emoji::decode($comment['COMMENT']);
			}
			else
			{
				return HtmlContent::createDenied();
			}
		}

		$fileFields = $GLOBALS['USER_FIELD_MANAGER']->GetUserFields(CommentController::UF_FIELD_NAME, $id);
		if (isset($fileFields['UF_CRM_COMMENT_FILES']))
		{
			$fileFields['UF_CRM_COMMENT_FILES']['~EDIT_FORM_LABEL'] = CommentController::UF_COMMENT_FILE_NAME;
			$fileFields['UF_CRM_COMMENT_FILES']['TAG'] = 'DOCUMENT ID';
		}

		$editorParameters = [
			'SELECTOR_VERSION' => 2,
			'FORM_ID' => $formId,
			'SHOW_MORE' => 'N',
			'PARSER' => [
				'Bold', 'Italic', 'Underline', 'Strike',
				'ForeColor', 'FontList', 'FontSizeList', 'RemoveFormat',
				'Quote', 'Code', 'InsertCut',
				'CreateLink', 'Image', 'Table', 'Justify',
				'InsertOrderedList', 'InsertUnorderedList',
				'SmileList', 'Source', 'UploadImage', 'InputVideo', 'MentionUser'
			],
			'BUTTONS' => [
				'UploadImage',
				'CreateLink',
				'InputVideo',
				'MentionUser'
			],
			'TEXT' => [
				'NAME' => 'MESSAGE',
				'VALUE' => $text,
				'HEIGHT' => '120px'
			],
			'LHE' => [
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
				'controlsMap' => [
					['id' => 'Bold',  'compact' => true, 'sort' => 10],
					['id' => 'Italic',  'compact' => true, 'sort' => 20],
					['id' => 'Underline',  'compact' => true, 'sort' => 30],
					['id' => 'Strikeout',  'compact' => true, 'sort' => 40],
					['separator' => true, 'compact' => false, 'sort' => 90],
					['id' => 'UnorderedList',  'compact' => true, 'sort' => 100],
					['id' => 'OrderedList',  'compact' => true, 'sort' => 110],
					['separator' => true, 'compact' => false, 'sort' => 120],
					['id' => 'InsertLink',  'compact' => true, 'sort' => 130, 'wrap' => 'bx-b-link-' . $formId],
				],
			],
			'USE_CLIENT_DATABASE' => 'Y',
			'FILES' => [
				'VALUE' => [],
				'DEL_LINK' => '',
				'SHOW' => 'N'
			],
			'UPLOAD_FILE' => true,
			'UPLOAD_FILE_PARAMS' => ['width' => 400, 'height' => 400],
			'UPLOAD_WEBDAV_ELEMENT' => $fileFields['UF_CRM_COMMENT_FILES'] ?? false,
			'ALLOW_CRM_EMAILS' => 'Y'
		];

		return new Component(
			'bitrix:main.post.form',
			'',
			$editorParameters
		);
	}

	final public function onReceiveAction($entityId, $entityTypeId, $settings): void
	{
		ExternalNoticeController::getInstance()->onReceive(
			$entityId,
			$entityTypeId,
			$settings
		);
	}
}
