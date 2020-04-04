<?
namespace Bitrix\Tasks\Dispatcher;

use Bitrix\Tasks\Item;
use Bitrix\Tasks\Util\Collection;
use Bitrix\Tasks\Processor\Task\Result\Impact;

final class ExecutionResult extends \Bitrix\Tasks\Util\Result
{
	public function isSuccess()
	{
		return $this->getErrors()->checkNoFatals() && !$this->getErrors()->checkHasErrorOfType(\Bitrix\Tasks\Dispatcher::ERROR_TYPE_PARSE);
	}
}