<?
namespace Bitrix\Tasks\Copy\Entity;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\CheckList\Task\TaskCheckListFacade;

Loc::loadMessages(__FILE__);

class TaskCheckList extends CheckList
{
	const CHECKLIST_COPY_ERROR = "TASK_CHECKLIST_COPY_ERROR";

	public function __construct()
	{
		parent::__construct();

		$this->facade = TaskCheckListFacade::class;
	}
}