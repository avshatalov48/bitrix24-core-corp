<?php

namespace Bitrix\Tasks\Flow\Control\Middleware\Implementation;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\AbstractCommand;
use Bitrix\Tasks\Flow\Control\Command\AddCommand;
use Bitrix\Tasks\Flow\Control\Command\UpdateCommand;
use Bitrix\Tasks\Flow\Control\Exception\MiddlewareException;
use Bitrix\Tasks\Flow\Control\Middleware\AbstractMiddleware;
use Bitrix\Tasks\Flow\Provider\Exception\ProviderException;
use Bitrix\Tasks\Flow\Provider\FlowProvider;

class OriginalNameMiddleware extends AbstractMiddleware
{
	/**
	 * @throws MiddlewareException
	 * @throws ProviderException
	 */
	public function handle(AbstractCommand $request)
	{
		if (
			!$request instanceof UpdateCommand
			&& !$request instanceof AddCommand
		)
		{
			return parent::handle($request);
		}

		$isExists = $this->isSameFlowExists($request);

		if ($isExists)
		{
			throw new MiddlewareException(Loc::getMessage('TASKS_FLOW_ORIGINAL_NAME_MIDDLEWARE'));
		}

		return parent::handle($request);
	}

	/**
	 * @throws ProviderException
	 */
	private function isSameFlowExists(UpdateCommand|AddCommand $request): bool
	{
		$flowId = $request->hasId() ? $request->id : 0;

		if ($request instanceof UpdateCommand && !isset($request->name))
		{
			return false;
		}

		return (new FlowProvider())->isSameFlowExists($request->name, $flowId);
	}
}