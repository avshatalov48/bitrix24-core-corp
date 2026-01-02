<?php

declare(strict_types=1);

namespace Bitrix\Intranet\User\Command;

use Bitrix\Intranet\Exception\UpdateFailedException;
use Bitrix\Intranet\Exception\WrongIdException;
use Bitrix\Intranet\Internal\Entity\User\Profile\Post;
use Bitrix\Intranet\Internal\Repository\User\Profile\PostRepository;
use Bitrix\Main\Command\AbstractCommand;
use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Exception;

class UpdateProfilePostCommand extends AbstractCommand
{
	public function __construct(
		public readonly int $userId,
		public readonly Post $profilePost,
	)
	{
	}

	protected function execute(): Result
	{
		$result = new Result();

		try
		{
			$postRepository = PostRepository::createByDefault();
			$handler = new UpdateProfilePostHandler($postRepository);
			$handler($this);
		}
		catch (WrongIdException)
		{
			$result->addError(new Error('Wrong user id'));
		}
		catch (UpdateFailedException)
		{
			$result->addError(new Error('Post update failed'));
		}
		catch (Exception $exception)
		{
			$result->addError(new Error($exception->getMessage()));
		}

		return $result;
	}

	public function toArray(): array
	{
		return [];
	}
}
