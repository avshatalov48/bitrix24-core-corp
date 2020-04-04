<?
class CMeetingEventHandlers
{
	public static function OnTaskDelete($task_id)
	{
		global $DB;

		$task_id = intval($task_id);

		$DB->Query("UPDATE b_meeting_instance SET TASK_ID=NULL WHERE TASK_ID='".$task_id."'");
		$DB->Query("DELETE FROM b_meeting_item_tasks WHERE TASK_ID='".$task_id."'");
	}

	public static function OnAfterCalendarConvert()
	{
		$GLOBALS['DB']->Query('UPDATE b_meeting SET EVENT_ID=NULL');
	}

	public static function OnBeforeUserDelete($user_id)
	{
		global $DB;

		$user_id = intval($user_id);

		if($user_id > 0)
		{
			$dbRes = $DB->Query("SELECT COUNT(MEETING_ID) CNT FROM b_meeting_users WHERE USER_ID='".$user_id."'");
			$arRes = $dbRes->Fetch();
			if($arRes['CNT'] > 0)
			{
				$GLOBALS['APPLICATION']->ThrowException(GetMessage('MEETING_USER_ERROR'));
				return false;
			}
		}

		return true;
	}
}


class CMeetingForumHandlers
{
	protected $arEntity = null;
	protected $entityType = null;
	protected $forumId = null;

	protected $type = MEETING_COMMENTS_ENTITY_TYPE;
	protected $forumPath = "/services/meeting/meeting/#PARAM2#/#message#MESSAGE_ID#";

	public function __construct($forumId, &$arEntity)
	{
		$this->arEntity = &$arEntity;

		$this->ProcessForum($forumId);
		$this->SetHandlers();
	}

	public function GetForumID()
	{
		return $this->forumId;
	}

	public function onAfterMessageAdd($ID, $arPost)
	{
		if ($arPost['FORUM_ID'] == $this->forumId && $arPost['PARAM2'] == $this->arEntity['ID'])
		{
			if (is_array($arPost['ATTACHED_FILES']) && count($arPost['ATTACHED_FILES']) > 0)
			{
				$arFiles = array();
				foreach ($arPost['ATTACHED_FILES'] as $fileID)
				{
					$fileID = CFile::SaveFile(CFile::MakeFileArray($fileID), 'meeting');
					if ($fileID > 0)
						$arFiles[] = $fileID;
				}

				if (count($arFiles) > 0)
				{
					CMeeting::SetFiles($this->arEntity['ID'], $arFiles, $ID);
				}
			}
		}
	}

	public function onAfterMessageDelete($ID, $arPost)
	{
		if ($arPost['FORUM_ID'] == $this->forumId && $arPost['PARAM2'] == $this->arEntity['ID'])
		{
			CMeeting::DeleteFilesBySrc($arPost['ID']);
		}
	}

	public function OnCommentTopicAdd($entityType, $entityID, $arPost, &$arTopic)
	{
		global $USER;
		if ($entityType !== $this->type)
			return;

		$arTopic = array(
			'AUTHOR_ID' => $USER->GetID(),
			'TITLE' => $this->arEntity['TITLE'],
			'TAGS' => '',
			'MESSAGE' => $this->arEntity['TITLE']
		);
		return true;
	}

	protected function ProcessForum($forumId)
	{
		if (!CModule::IncludeModule('forum'))
			return false;

		$forumId = intval($forumId);
		if ($forumId <= 0)
		{
			$forumId = COption::GetOptionInt('meeting', 'comments_forum_id', 0, SITE_ID);
			$forumId = ($forumId > 0 ? $forumId : COption::GetOptionInt('meeting', 'comments_forum_id', 0));
			$bNeedCreate = $forumId <= 0 || !CForumNew::GetByID($forumId);

			if ($bNeedCreate)
			{
				$arForumFields = array(
					'NAME' => GetMessage('MEETING_FORUM_NAME'),
					'ACTIVE' => 'Y',
					'INDEXATION' => 'N',
					'ALLOW_HTML' => 'N',
					'ALLOW_UPLOAD' => 'A',
					'MODERATION' => 'N',
					'SITES' => array(SITE_ID => $this->forumPath), //tmp!
					'GROUP_ID' => array(1 => 'Y'),
				);
				if ($dbRes = CSite::GetList($sBy = "sort", $sOrder = "asc"))
				{
					while ($res = $dbRes->Fetch())
					{
						$arForumFields["SITES"][$res["LID"]] = $this->forumPath;
					}
				}
				$forumId = CForumNew::Add($arForumFields);
				if ($forumId > 0)
				{
					COption::SetOptionInt('meeting', 'comments_forum_id', $forumId);
				}
			}
		}
		$this->forumId = $forumId;
	}

	protected function SetHandlers()
	{
		AddEventHandler("forum", "onAfterMessageAdd", array($this, "onAfterMessageAdd"));
		AddEventHandler("forum", "onAfterMessageDelete", array($this, "onAfterMessageDelete"));
		AddEventHandler("forum", "OnCommentTopicAdd", array($this, "OnCommentTopicAdd"));
	}
}

class CMeetingItemForumHandlers extends CMeetingForumHandlers
{
	protected $type = MEETING_ITEMS_COMMENTS_ENTITY_TYPE;
	protected $forumPath = "/services/meeting/item/#PARAM2#/#message#MESSAGE_ID#";

	public function onAfterMessageAdd($ID, $arPost)
	{
		if ($arPost['FORUM_ID'] == $this->forumId && $arPost['PARAM2'] == $this->arEntity['ID'])
		{
			if (is_array($arPost['ATTACHED_FILES']) && count($arPost['ATTACHED_FILES']) > 0)
			{
				$arFiles = array();
				foreach ($arPost['ATTACHED_FILES'] as $fileID)
				{
					$fileID = CFile::SaveFile(CFile::MakeFileArray($fileID), 'meeting');
					if ($fileID > 0)
						$arFiles[] = $fileID;
				}

				if (count($arFiles) > 0)
				{
					CMeetingItem::SetFiles($this->arEntity['ID'], $arFiles, $ID);
				}
			}
		}
	}

	public function onAfterMessageDelete($ID, $arPost)
	{
		if ($arPost['FORUM_ID'] == $this->forumId && $arPost['PARAM2'] == $this->arEntity['ID'])
		{
			CMeetingItem::DeleteFilesBySrc($arPost['ID']);
		}
	}
}
?>