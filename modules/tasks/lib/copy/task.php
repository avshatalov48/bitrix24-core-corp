<?
namespace Bitrix\Tasks\Copy;

use Bitrix\Main\Copy\EntityCopier;
use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Copy\Implement\Task as TaskImplementer;

Loc::loadMessages(__FILE__);

class Task extends EntityCopier
{
	protected $implementer;

	public function __construct(TaskImplementer $implementer)
	{
		$implementer->setTaskCopier($this);

		parent::__construct($implementer);
	}
}