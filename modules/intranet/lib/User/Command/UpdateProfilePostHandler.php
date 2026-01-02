<?php

declare(strict_types=1);

namespace Bitrix\Intranet\User\Command;

use Bitrix\Intranet\Exception\UpdateFailedException;
use Bitrix\Intranet\Exception\WrongIdException;
use Bitrix\Intranet\Internal\Repository\User\Profile\PostRepository;

class UpdateProfilePostHandler
{
	public function __construct(
		private readonly PostRepository $postRepository,
	)
	{
	}

	/**
	 * @throws WrongIdException
	 * @throws UpdateFailedException
	 */
	public function __invoke(UpdateProfilePostCommand $command): void
	{
		$post = $command->profilePost;
		$userId = $command->userId;

		$this->postRepository->updateUserProfilePost($userId, $post);
	}
}
