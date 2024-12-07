<?php

namespace Bitrix\Tasks\Flow\Control\Middleware\Implementation;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\AbstractCommand;
use Bitrix\Tasks\Flow\Control\Exception\MiddlewareException;
use Bitrix\Tasks\Flow\Control\Middleware\AbstractMiddleware;
use Bitrix\Tasks\Internals\Log\Logger;
use Bitrix\Tasks\Internals\Task\TemplateTable;

class TemplateMiddleware extends AbstractMiddleware
{
	use CacheTrait;

	private static array $cache = [];

	/**
	 * @throws MiddlewareException
	 */
	public function handle(AbstractCommand $request)
	{
		$templateIds = $request->getTemplateIdList();

		try
		{
			$this->load(...$templateIds);
		}
		catch (SystemException $e)
		{
			Logger::logThrowable($e);
			throw new MiddlewareException("Error");
		}

		foreach ($templateIds as $templateId)
		{
			if ($templateId > 0 && false === $this->has($templateId))
			{
				throw new MiddlewareException("Template {$templateId} doesn't exists");
			}
		}

		return parent::handle($request);
	}

	/**
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	private function load(int ...$templateIds): void
	{
		$notLoaded = $this->getNotLoaded(...$templateIds);
		if (empty($notLoaded))
		{
			return;
		}

		$templates = TemplateTable::query()
			->setSelect(['ID'])
			->whereIn('ID', $notLoaded)
			->exec()
			->fetchCollection();

		$this->store(...$templates->getIdList());
	}
}