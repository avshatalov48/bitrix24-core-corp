<?php

declare(strict_types=1);

namespace Bitrix\Intranet\Internal\Repository\User\Profile;

use Bitrix\Intranet\Component\UserProfile\ProfilePost;
use Bitrix\Intranet\Exception\UpdateFailedException;
use Bitrix\Intranet\Exception\WrongIdException;
use Bitrix\Intranet\Internal\Entity\User\Profile\Post;
use Bitrix\Intranet\Internal\Repository\Mapper\User\Profile\PostMapper;

class PostRepository
{
	public function __construct(
		private PostMapper $mapper,
	)
	{
	}

	public static function createByDefault(): PostRepository
	{
		return new PostRepository(
			new PostMapper(),
		);
	}

	public function getUserProfilePostByUserId(int $userId): ?Post
	{
		$post = new ProfilePost([
			'profileId' => $userId,
		]);

		$postId = $post->getPostId();

		return $this->mapper->createPostFromPostData(
			$post->getPostData($postId),
		);
	}

	/**
	 * @throws WrongIdException
	 * @throws UpdateFailedException
	 */
	public function updateUserProfilePost(int $userId, Post $post): void
	{
		if ($userId < 1)
		{
			throw new WrongIdException();
		}

		$profilePost = new ProfilePost([
			'profileId' => $userId,
			// use \Bitrix\Intranet\User\Access\Rule\UpdateRule and \Bitrix\Intranet\User\Access\Rule\ViewRule before Command
			'permissions' => ['view' => true, 'edit' => true],
		]);

		if (empty($post->text))
		{
			$result = $profilePost->deleteProfileBlogPostAction();
		}
		else
		{
			$result = $profilePost->sendProfileBlogPostFormAction([
				'text' => $post->text,
				'additionalData' => [
					'UF_BLOG_POST_FILE' => $post->fileIds,
				]
			]);
		}

		if (!$result)
		{
			throw new UpdateFailedException();
		}
	}
}
