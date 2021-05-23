<?

namespace
{
	define('NO_KEEP_STATISTIC', 'Y');
	define('NO_AGENT_STATISTIC','Y');
	define('NO_AGENT_CHECK', true);
	define('STOP_STATISTICS', true);
	define('BX_SECURITY_SHOW_MESSAGE', true);

	define('DisableEventsCheck', true);

	require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

	global $APPLICATION;

	CUtil::JSPostUnescape();
	$APPLICATION->RestartBuffer();

	$processor = new Bitrix\Components\Crm\OrderShipment\ProductList\Ajax\AjaxProcessor($_REQUEST);
	$result = $processor->checkConditions();

	if($result->isSuccess())
	{
		$result = $processor->processRequest();
	}

	$processor->sendResponse($result);

	if(!defined('PUBLIC_AJAX_MODE'))
	{
		define('PUBLIC_AJAX_MODE', true);
	}

	\CMain::FinalActions();

	die();
}

namespace Bitrix\Components\Crm\OrderShipment\ProductList\Ajax
{
	use \Bitrix\Main\Localization\Loc;
	use Bitrix\Main\NotImplementedException;

	Loc::loadMessages(__FILE__);

	/** @internal  */
	final class AjaxProcessor extends \Bitrix\Crm\Order\AjaxProcessor
	{
		protected function getActionMethodName($action)
		{
			if($action == 'SAVE' || $action == 'DELETE')
			{
				$action = ToLower($action);
			}

			return parent::getActionMethodName($action);
		}

		protected function saveAction()
		{
			throw new NotImplementedException('Not implemented yet');
		}

		protected function deleteAction()
		{
			throw new NotImplementedException('Not implemented yet');
		}
	}
}