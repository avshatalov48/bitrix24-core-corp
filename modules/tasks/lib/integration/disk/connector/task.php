<?
/**
 * Note: this is an internal class for disk module. It wont work without the module installed.
 * @internal
 */

namespace Bitrix\Tasks\Integration\Disk\Connector;

use Bitrix\Main\Localization\Loc;

use Bitrix\Disk\Ui;
use Bitrix\Disk\Uf\StubConnector;

Loc::loadMessages(__FILE__);

class Task extends StubConnector
{
	protected static $pathToTask  = '/company/personal/user/#user_id#/tasks/task/view/#task_id#/';
	protected $canRead = null;
	protected $taskPostData;

	public function canRead($userId)
	{
		if($this->canRead !== null)
		{
			return $this->canRead;
		}

		$data = $this->loadTaskData($userId);
		$this->canRead = !empty($data);

		return $this->canRead;
	}

	public function canUpdate($userId)
	{
		return $this->canRead($userId);
	}

	public function getDataToShow()
	{
		$data = $this->loadTaskData($this->getUser()->getId());
		if(!$data)
		{
			return null;
		}
		return array(
			'TITLE' => $this->getTitle(),
			'DETAIL_URL' => \CComponentEngine::makePathFromTemplate($this->getPathToTask(), array(
				"user_id" => $data['RESPONSIBLE_ID'],
				"task_id" => $data['ID'],
			)),
			'DESCRIPTION' => Ui\Text::killTags($data['TITLE']),
			'MEMBERS' => $this->getDestinations(),
		);
	}

	protected function getTitle()
	{
		return Loc::getMessage('DISK_UF_TASK_CONNECTOR_TITLE', array('#ID#' => $this->entityId));
	}

	public function getPathToTask()
	{
		return $this::$pathToTask;
	}

	protected function getDestinations()
	{
		if($this->taskPostData === null)
		{
			return array();
		}
		$members = array();

		if(!empty($this->taskPostData['RESPONSIBLE_ID']))
		{
			$members[] = array(
				"NAME" => \CUser::formatName('#NAME# #LAST_NAME#', array(
					'NAME' => $this->taskPostData['RESPONSIBLE_NAME'],
					'LAST_NAME' => $this->taskPostData['RESPONSIBLE_LAST_NAME'],
					'SECOND_NAME' => $this->taskPostData['RESPONSIBLE_SECOND_NAME'],
					'ID' => $this->taskPostData['RESPONSIBLE_ID'],
					'LOGIN' => $this->taskPostData['RESPONSIBLE_LOGIN'],
				), true, false),
				"LINK" => \CComponentEngine::makePathFromTemplate($this->getPathToUser(), array("user_id" => $this->taskPostData['RESPONSIBLE_ID'])),
				'AVATAR_SRC' => Ui\Avatar::getPerson($this->taskPostData['RESPONSIBLE_PHOTO']),
				"IS_EXTRANET" => "N",
			);
		}
		if(!empty($this->taskPostData['CREATED_BY']))
		{
			$members[] = array(
				"NAME" => \CUser::formatName('#NAME# #LAST_NAME#', array(
					'NAME' => $this->taskPostData['CREATED_BY_NAME'],
					'LAST_NAME' => $this->taskPostData['CREATED_BY_LAST_NAME'],
					'SECOND_NAME' => $this->taskPostData['CREATED_BY_SECOND_NAME'],
					'ID' => $this->taskPostData['CREATED_BY'],
					'LOGIN' => $this->taskPostData['CREATED_BY_LOGIN'],
				), true, false),
				"LINK" => \CComponentEngine::makePathFromTemplate($this->getPathToUser(), array("user_id" => $this->taskPostData['CREATED_BY'])),
				'AVATAR_SRC' => Ui\Avatar::getPerson($this->taskPostData['CREATED_BY_PHOTO']),
				"IS_EXTRANET" => "N",
			);
		}

		return $members;
	}

	protected function loadTaskData($userId)
	{
		if($this->taskPostData === null)
		{
			try
			{
				$task = \CTaskItem::getInstance($this->entityId, $userId);
				$this->taskPostData = $task->getData(false);
			}
			catch(\TasksException $e)
			{
				return array();
			}
		}
		return $this->taskPostData;
	}

	public function addComment($authorId, array $data)
	{
		$fields = array(
			"ANCILLARY" => true,
			"POST_MESSAGE" => $data['text']
		);
		if(!empty($data['fileId']))
		{
			$fields['UF_FORUM_MESSAGE_DOC'] = array($data['fileId']);
		}
		elseif(!empty($data['versionId']))
		{
			$fields['UF_FORUM_MESSAGE_VER'] = $data['versionId'];
		}

		// todo: move to \Bitrix\Tasks\Item\Task
		$task = \CTaskItem::getInstance($this->entityId, $authorId);
		\CTaskCommentItem::add($task, $fields);
	}

	/**
	 * Get files count from task.
	 * @param int|array $id One or array task id.
	 * @return int|array
	 */
	public static function getFilesCount($id)
	{
		$result = array();

		if (empty($id))
		{
			return $id;
		}

		$res = \Bitrix\Disk\Internals\AttachedObjectTable::getList(array(
			'select' => array(
				'TASK_ID' => 'ENTITY_ID',
				new \Bitrix\Main\Entity\ExpressionField('CNT', 'COUNT(ENTITY_ID)')
			),
			'filter' => array(
				'=ENTITY_TYPE' => self::className(),
				'ENTITY_ID' => $id
			),
			'group' => array(
				'ENTITY_ID'
			)
		));
		while ($row = $res->fetch())
		{
			$result[$row['TASK_ID']] = $row['CNT'];
		}

		return is_array($id) ? $result : $result[$id];
	}

	/**
	 * Get task's image cover.
	 * @param int|array $id One or array task id.
	 * @param int $width Width for cover.
	 * @param int $height Height for cover.
	 * @return int|array
	 */
	public static function getCover($id, $width=0, $height=0)
	{
		$result = array();
		$width = intval($width);
		$height = intval($height);

		if (empty($id))
		{
			return $id;
		}

		$res = \Bitrix\Disk\Internals\AttachedObjectTable::getList(array(
			'select' => array(
				'ID',
				'TASK_ID' => 'ENTITY_ID',
			),
			'runtime' => array(
				'FILE' => new \Bitrix\Main\Entity\ReferenceField(
					'FILE',
					'\Bitrix\Main\FileTable',
					array('=this.OBJECT.FILE_ID' => 'ref.ID')
				),
			),
			'filter' => array(
				'=ENTITY_TYPE' => self::className(),
				'ENTITY_ID' => $id,
				'OBJECT.TYPE_FILE' => \Bitrix\Disk\TypeFile::IMAGE,
				//'>=FILE.WIDTH' => $width,
				//'>=FILE.HEIGHT' => $height
			),
			'order' => array(
				'ID' => 'ASC'
			),
			'group' => array(
				'ENTITY_ID'
			)
		));
		while ($row = $res->fetch())
		{
			$result[$row['TASK_ID']] = \Bitrix\Disk\UrlManager::getUrlToActionShowUfFile(
				$row['ID'],
				$width*$height > 0
				? array(
					'width' => $width,
					'height' => $height,
					//'exact' => 'Y'
				)
				: array()
			);
		}

		return is_array($id) ? $result : $result[$id];
	}
}