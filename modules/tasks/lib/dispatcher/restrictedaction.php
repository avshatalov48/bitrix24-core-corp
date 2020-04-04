<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 *
 * @access private
 */

namespace Bitrix\Tasks\Dispatcher;

use Bitrix\Main\Localization\Loc;

abstract class RestrictedAction extends PublicAction
{
	public function canExecute()
	{
		if(!\Bitrix\Tasks\Util\Restriction::canManageTask())
		{
			$this->errors->add('ACTION_NOT_ALLOWED.RESTRICTED', Loc::getMessage('TASKS_RESTRICTED'));
			return false;
		}

		return true;
	}
}