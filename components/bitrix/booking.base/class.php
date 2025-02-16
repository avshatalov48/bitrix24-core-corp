<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Main;

Loc::loadMessages(__FILE__);

abstract class BookingBaseComponent extends \CBitrixComponent
{
	/**
	 * Required params of component.
	 * If not specified, will be set to null.
	 * @var string[]
	 */
	protected static array $requiredParams = [];

	/**
	 * Base module will be required to be installed.
	 * @var string
	 */
	protected string $baseModuleName = 'booking';

	/**
	 * Errors
	 * @var Main\ErrorCollection
	 */
	protected Main\ErrorCollection $errorCollection;

	/**
	 * Current template page.
	 * @var string
	 */
	private string $template = '';

	/**
	 * Current request.
	 *
	 * @var \Bitrix\Main\HttpRequest|null
	 */
	private ?Main\HttpRequest $currentRequest = null;

	public function __construct($component = null)
	{
		parent::__construct($component);

		$this->errorCollection = new Main\ErrorCollection();
	}

	public function getErrors(): array
	{
		return $this->errorCollection->getValues();
	}

	/**
	 * Executing before actions.
	 * @return void
	 */
	protected function beforeActions(): void
	{
	}

	/**
	 * Executes component.
	 * @return void
	 */
	protected function exec(): void
	{
	}

	/**
	 * Sets new view template.
	 * @param string $template Template name.
	 * @return void
	 */
	protected function setTemplate(string $template): void
	{
		$this->template = $template;
	}

	/**
	 * Returns parameter value by code.
	 * @param string $code Parameter code.
	 * @return mixed|null
	 */
	protected function getParam(string $code)
	{
		return $this->arParams[$code] ?? null;
	}

	/**
	 * Returns parameter value as array by code.
	 * @param string $code Parameter code.
	 * @return array
	 */
	protected function getArrayParam(string $code): array
	{
		if (isset($this->arParams[$code]) && is_array($this->arParams[$code]))
		{
			return $this->arParams[$code];
		}

		return [];
	}

	/**
	 * Returns parameter value as string by code.
	 * @param string $code Parameter code.
	 * @return string
	 */
	protected function getStringParam(string $code): string
	{
		if (isset($this->arParams[$code]) && (is_string($this->arParams[$code]) || is_integer($this->arParams[$code])))
		{
			return $this->arParams[$code];
		}

		return '';
	}

	/**
	 * Returns parameter value as number by code.
	 * @param string $code Parameter code.
	 * @return int
	 */
	protected function getIntParam(string $code): int
	{
		if (isset($this->arParams[$code]) && (is_string($this->arParams[$code]) || is_integer($this->arParams[$code])))
		{
			return $this->arParams[$code];
		}

		return 0;
	}

	/**
	 * For each key reset this key's param as string.
	 * @param array $keys Keys of params.
	 * @return void
	 */
	protected function resetParamsAsString(array $keys): void
	{
		foreach ($keys as $code)
		{
			$this->setParam($code, $this->getStringParam($code));
		}
	}

	/**
	 * Sets new parameter value by code.
	 * @param string $code Parameter code.
	 * @param mixed $value Parameter value.
	 * @return void
	 */
	protected function setParam(string $code, $value): void
	{
		$this->arParams[$code] = $value;
	}

	/**
	 * Returns result value by code.
	 * @param string $code Result code.
	 * @return mixed
	 */
	protected function getResult(string $code)
	{
		return $this->arResult[$code] ?? null;
	}

	/**
	 * Sets new result value by code.
	 * @param string $code Result code.
	 * @param mixed $value Result value.
	 * @return void
	 */
	protected function setResult(string $code, $value): void
	{
		$this->arResult[$code] = $value;
	}

	/**
	 * Returns requested page of current hit.
	 * @param array $addParams Optional get-params to add to page.
	 * @param array $delParams Optional get-params to remove from page.
	 * @return string
	 */
	public function getRequestedPage(array $addParams = [], array $delParams = []): string
	{
		$page = $this->currentRequest->getRequestUri();
		$curUri = null;

		if ($addParams)
		{
			$curUri = new \Bitrix\Main\Web\Uri($page);
			$page = $curUri->addParams($addParams)->getUri();
		}

		if ($delParams)
		{
			if (!$curUri)
			{
				$curUri = new \Bitrix\Main\Web\Uri($page);
			}
			$page = $curUri->deleteParams($delParams)->getUri();
		}

		return $page;
	}

	/**
	 * Returns request's value by code.
	 * @param string $code Request code.
	 * @return string | array | null
	 */
	public function getRequest(string $code)
	{
		return $this->currentRequest->get($code);
	}

	/**
	 * Returns request's value by code.
	 * @return bool
	 */
	public function isPostRequest(): bool
	{
		return $this->currentRequest->isPost();
	}

	/**
	 * Returns true if hit is within frame.
	 * @return bool
	 */
	public function isFrame(): bool
	{
		return $this->currentRequest->get('IFRAME') === 'Y';
	}

	/**
	 * Returns request's file by code.
	 * @param string $code File code.
	 * @return array | null
	 */
	protected function getFile(string $code): ?array
	{
		return $this->currentRequest->getFileList()->get($code);
	}

	/**
	 * Checks required params of child classes and fill they with null.
	 * @return void
	 */
	private function fillRequiredParamsWithNull(): void
	{
		foreach (static::$requiredParams as $key)
		{
			if (!array_key_exists($key, $this->arParams))
			{
				$this->arParams[$key] = null;
			}
		}
	}

	/**
	 * Adds one more error.
	 * @param string $code Error code.
	 * @param string $message Error message.
	 * @return void
	 */
	protected function addError(string $code, string $message): void
	{
		$this->errorCollection->setError(new Main\Error($message, $code));
	}

	/**
	 * Http request initialization.
	 * @throws \Bitrix\Main\SystemException
	 * @return void
	 */
	private function initRequest(): void
	{
		if ($this->currentRequest !== null)
		{
			return;
		}

		$context = \Bitrix\Main\Application::getInstance()->getContext();
		$this->currentRequest = $context->getRequest();
	}

	/**
	 * Checks current request for action and call it.
	 * @throws ReflectionException
	 * @return void
	 */
	private function processingAction(): void
	{
		$action = $this->getRequest('actionName');

		if (is_callable([$this, 'action' . $action]))
		{
			$action = 'action' . $action;
			$vars = [];

			$reflection = new \ReflectionMethod($this, $action);
			foreach ($reflection->getParameters() as $param)
			{
				$varName = $param->getName();
				$varValue = $this->getRequest($varName);

				if (!$varValue)
				{
					$varValue = $this->getFile($varName);
				}

				// all params is necessary
				if (!$varValue && !$param->isOptional())
				{
					return;
				}

				$expectedType = (string)$param->getType();
				$expectedType = ltrim($expectedType, '?');
				$typeIsCorrect = true;
				if ($varValue)
				{
					$typeIsCorrect = $expectedType === 'string' && is_string($varValue)
						|| $expectedType === 'int' && (is_string($varValue) || is_int($varValue))
						|| $expectedType === 'array' && is_array($varValue);
				}

				if (!$typeIsCorrect)
				{
					return;
				}

				if ($expectedType === 'int')
				{
					$varValue = (int)$varValue;
				}

				$vars[] = $varValue;
			}

			if (!check_bitrix_sessid())
			{
				$this->addError(
					'INCORRECT_SESSID',
					Loc::getMessage('BOOKING_CMP_BASE_ERROR_INCORRECT_SESSID')
				);
				return;
			}

			$this->{$action}(...$vars);
		}
	}

	/**
	 * Check required parameters and modules.
	 * @return bool
	 */
	private function init(): bool
	{
		static $return = null;

		$this->initRequest();

		if ($return !== null)
		{
			return $return;
		}

		$return = true;

		if (!Main\Loader::includeModule($this->baseModuleName))
		{
			showError(Loc::getMessage('BOOKING_CMP_BASE_ERROR_MODULE_BOOKING_NOT_INSTALLED', [
				'#MODULE_NAME#' => $this->baseModuleName
			]));
			$return = false;
		}

		return $return;
	}

	protected function includeTemplate(string $template = ''): void
	{
		if (!empty($this->getErrors()))
		{
			$this->setParam('SKIP_TEMPLATE', true);
			$this->includeComponentTemplate('error');
			return;
		}

		$this->includeComponentTemplate($template);
	}

	/**
	 * Base executable method.
	 * @return void
	 */
	public function executeComponent(): void
	{
		if (!Main\Loader::includeModule($this->baseModuleName))
		{
			showError(Loc::getMessage('BOOKING_CMP_BASE_ERROR_MODULE_BOOKING_NOT_INSTALLED', [
				'#MODULE_NAME#' => $this->baseModuleName
			]));
			return;
		}

		if ($this->init())
		{
			$this->fillRequiredParamsWithNull();
			$this->beforeActions();
			$this->processingAction();
			$this->exec();

			$this->includeComponentTemplate($this->template);
		}

		$this->showErrors();
	}

	protected function getAction(): array
	{
		return [];
	}

	protected function getCallbackAction(): array
	{
		return [];
	}

	protected function showErrors(): void
	{
		foreach ($this->getErrors() as $error)
		{
			ShowError($error->getMessage());
		}
	}
}
