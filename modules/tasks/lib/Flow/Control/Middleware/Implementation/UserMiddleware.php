<?php

namespace Bitrix\Tasks\Flow\Control\Middleware\Implementation;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\Collection;
use Bitrix\Main\UserTable;
use Bitrix\Tasks\AbstractCommand;
use Bitrix\Tasks\Flow\Control\Exception\MiddlewareException;
use Bitrix\Tasks\Flow\Control\Middleware\AbstractMiddleware;
use Bitrix\Tasks\Internals\Log\Logger;

class UserMiddleware extends AbstractMiddleware
{
	use CacheTrait;

	private static array $cache = [];

	/**
	 * @throws MiddlewareException
	 */
	public function handle(AbstractCommand $request)
	{
		$userIds = $request->getUserIdList();
		Collection::normalizeArrayValuesByInt($userIds, false);

		try
		{
			$this->load(...$userIds);
		}
		catch (SystemException $e)
		{
			Logger::logThrowable($e);
			throw new MiddlewareException("Error");
		}

		foreach ($userIds as $userId)
		{
			if (false === $this->has($userId))
			{
				throw new MiddlewareException("User {$userId} doesn't exists");
			}
		}

		return parent::handle($request);
	}

	/**
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	private function load(int ...$userIds): void
	{
		$notLoaded = $this->getNotLoaded(...$userIds);
		if (empty($notLoaded))
		{
			return;
		}

		$users = UserTable::query()
			->setSelect(['ID'])
			->whereIn('ID', $notLoaded)
			->exec()
			->fetchCollection();

		$this->store(...$users->getIdList());
	}
}