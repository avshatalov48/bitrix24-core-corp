<?
namespace Bitrix\Crm\Order;

use \Bitrix\Main\Error;
use Bitrix\Main\Loader;
use \Bitrix\Sale\Result;
use \Bitrix\Main\Localization\Loc;
use Bitrix\Main\NotImplementedException;
use Bitrix\Main\ArgumentOutOfRangeException;

Loc::loadMessages(__FILE__);

if (!Loader::includeModule('sale'))
{
	return;
}
/** @internal */
class AjaxProcessor
{
	/** @var int  */
	protected $userId = 0;
	/** @var \CCrmPerms */
	protected $userPermissions = null;
	/** @var array  */
	protected $request = [];
	/** @var Result */
	protected $result = null;
	/** @var Result */
	protected $showWarnings = true;

	/**
	 * AjaxProcessor constructor.
	 * @param array $request
	 */
	public function __construct(array $request)
	{
		$this->request = $request;
		$this->result = new Result();
	}

	/**
	 * @return Result
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function checkConditions()
	{
		$result = new Result();

		if(!isset($this->request["ACTION"]) || $this->request["MODE"])
		{
			$result->addError(new Error(Loc::getMessage('CRM_ORDER_AJAX_ERROR')));
			$result->setData(["SYSTEM_ERROR" => "REQUEST[action] not defined!"]);
		}
		elseif(!Loader::includeModule('crm'))
		{
			$result->addError(new Error(Loc::getMessage('CRM_ORDER_AJAX_ERROR')));
			$result->setData(["SYSTEM_ERROR" => "Error! Can't include module \"crm\"!"]);
		}
		elseif(!Loader::includeModule('sale'))
		{
			$result->addError(new Error(Loc::getMessage('CRM_ORDER_AJAX_ERROR')));
			$result->setData(["SYSTEM_ERROR" => "Error! Can't include module \"sale\"!"]);
		}
		elseif(!\CCrmSecurityHelper::IsAuthorized() || !check_bitrix_sessid())
		{
			$result->addError(new Error(Loc::getMessage('CRM_ORDER_AJAX_ERROR_AD')));
			$result->setData(["SYSTEM_ERROR" => "Access denied!"]);
		}

		return $result;
	}

	/**
	 * @return Result
	 * @throws ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function processRequest()
	{
		$this->userId = \CCrmSecurityHelper::GetCurrentUserID();
		$this->userPermissions =  \CCrmPerms::GetCurrentUserPermissions();

		$action = '';

		if(!empty($this->request['ACTION']))
		{
			$action = trim($this->request['ACTION']);
			if ($action == 'SAVE')
			{
				$this->showWarnings = false;
			}
		}
		elseif(!empty($this->request['MODE']))
		{
			$action = trim($this->request['MODE']);
		}

		if(empty($action))
		{
			throw new \Bitrix\Main\SystemException("Undefined \"action\"");
		}

		if(!empty($this->request['ACTION_BEFORE']))
		{
			$this->executeAction(trim($this->request['ACTION_BEFORE']));
		}

		$this->executeAction($action);

		if(!empty($this->request['ACTION_AFTER']))
		{
			$this->executeAction(trim($this->request['ACTION_AFTER']));
		}

		return $this->result;
	}

	protected function executeAction($actionName)
	{
		$methodName = $this->getActionMethodName($actionName);

		if(!method_exists($this, $methodName))
		{
			throw new ArgumentOutOfRangeException($methodName);
		}

		call_user_func([$this, $methodName]);
	}

	public function sendResponse(Result $result)
	{
		$GLOBALS['APPLICATION']->RestartBuffer();
		Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
		$response = $result->getData();

		$error = $this->prepareResponseError($result);
		if ($error)
		{
			$response['ERROR'] = $error;
		}

		if(!empty($response))
		{
			echo \CUtil::PhpToJSObject($response);
		}
	}

	protected function prepareResponseError(Result $result): string
	{
		$response = '';

		if (!$result->isSuccess())
		{
			$response = implode(', ', $result->getErrorMessages());
		}

		if ($result->hasWarnings() && $this->showWarnings)
		{
			$warningString = implode(', ', $result->getWarningMessages());

			if (empty($response))
			{
				$response = $warningString;
			}
			else
			{
				$response .= ', '.$warningString;
			}
		}

		return $response;
	}

	protected function getActionMethodName($action)
	{
		if(empty($action))
		{
			throw new \Bitrix\Main\ArgumentNullException('action');
		}

		if($action == 'SAVE' || $action == 'DELETE')
		{
			$action = ToLower($action);
		}
		elseif($action == 'GET_FORMATTED_SUM')
		{
			$action = 'getFormattedSum';
		}

		return $action.'Action';
	}

	protected function saveAction()
	{
		throw new NotImplementedException('Not implemented yet');
	}

	protected function deleteProductAction()
	{
		throw new NotImplementedException('Not implemented yet');
	}

	protected function addError($message)
	{
		$this->result->addError(new \Bitrix\Main\Error($message));
	}

	protected function addErrors(array $errors)
	{
		$this->result->addErrors($errors);
	}

	protected function addWarning($message)
	{
		$this->result->addWarning(new \Bitrix\Sale\ResultWarning($message));
	}

	protected function addWarnings(array $errors)
	{
		$this->result->addWarnings($errors);
	}

	protected function addData(array $data)
	{
		$resData = $this->result->getData();
		$this->result->setData(array_merge($resData, $data));
	}

	protected function getFormattedSumAction()
	{
		$sum = isset($this->request['SUM']) ? $this->request['SUM'] : 0.0;
		$currencyID = isset($this->request['CURRENCY_ID']) ? $this->request['CURRENCY_ID'] : '';

		if($currencyID === '')
		{
			$currencyID = \CCrmCurrency::GetBaseCurrencyID();
		}

		$this->addData(
			[
				'FORMATTED_SUM' => \CCrmCurrency::MoneyToString($sum, $currencyID, '#'),
				'FORMATTED_SUM_WITH_CURRENCY' => \CCrmCurrency::MoneyToString($sum, $currencyID, '')
			]
		);
	}
}