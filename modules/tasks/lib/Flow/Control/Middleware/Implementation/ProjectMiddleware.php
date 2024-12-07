<?php

namespace Bitrix\Tasks\Flow\Control\Middleware\Implementation;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\AbstractCommand;
use Bitrix\Tasks\Flow\Control\Exception\MiddlewareException;
use Bitrix\Tasks\Flow\Control\Middleware\AbstractMiddleware;
use Bitrix\Tasks\Internals\Log\Logger;
use Bitrix\Tasks\Internals\Registry\GroupRegistry;

class ProjectMiddleware extends AbstractMiddleware
{
	/**
	 * @throws MiddlewareException
	 */
	public function handle(AbstractCommand $request)
	{
		$projectIds = $request->getProjectIdList();

		try
		{
			$this->load(...$projectIds);
		}
		catch (SystemException|LoaderException $e)
		{
			Logger::logThrowable($e);
			throw new MiddlewareException("Error");
		}

		foreach ($projectIds as $projectId)
		{
			if ($projectId > 0 && false === $this->has($projectId))
			{
				throw new MiddlewareException("Project {$projectId} doesn't exists");
			}
		}

		return parent::handle($request);
	}

	private function has(int $projectId): bool
	{
		$project = GroupRegistry::getInstance()->get($projectId);
		if (null === $project)
		{
			return false;
		}

		if (array_key_exists('TASKS_ENABLED', $project) && count($project) === 1)
		{
			return false;
		}

		return true;
	}

	/**
	 * @throws LoaderException
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	private function load(int ...$projectIds): void
	{
		GroupRegistry::getInstance()->load($projectIds);
	}
}