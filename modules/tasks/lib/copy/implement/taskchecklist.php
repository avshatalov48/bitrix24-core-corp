<?
namespace Bitrix\Tasks\Copy\Implement;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\CheckList\Task\TaskCheckListFacade;
use Bitrix\Tasks\Copy\Implement\CheckList;

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