<?

namespace Bitrix\Tasks\Integration\Disk\Connector\Task;

use Bitrix\Main\Localization\Loc;

use Bitrix\Disk\Ui;
use Bitrix\Tasks\Integration\Disk\Connector\Task;

Loc::loadMessages(__FILE__);

final class Template extends Task
{
	protected function getTitle()
	{
		return Loc::getMessage('DISK_UF_TASK_TEMPLATE_CONNECTOR_TITLE', array('#ID#' => $this->entityId));
	}

	protected function loadTaskData($userId)
	{
		if($this->taskPostData === null)
		{
			try
			{
				// todo: move to \Bitrix\Tasks\Item\Task\Template::getInstance(), with access check
				$template = \CTaskTemplates::getList(Array(), Array("ID" => $this->entityId), array(), array(
					'USER_ID' => $userId
				), array('*', 'UF_*'))->fetch();

				$this->taskPostData = is_array($template) ? $template : array();
			}
			catch(\TasksException $e)
			{
				return array();
			}
		}
		return $this->taskPostData;
	}

	/**
	 * No comments for templates
	 *
	 * @param $authorId
	 * @param array $data
	 */
	public function addComment($authorId, array $data)
	{
		return;
	}
}