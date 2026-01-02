<?php

declare(strict_types=1);

namespace Bitrix\Intranet\Public\Provider\User;

use Bitrix\Intranet\Component\UserProfile\ProfilePost;
use Bitrix\Intranet\Internal\Entity\User\Profile\Post;
use Bitrix\Intranet\Internal\Repository\User\Profile\PostRepository;

class UserProfilePostProvider
{
	public function __construct(
		private PostRepository $postRepository,
	)
	{}

	public static function createByDefault(): UserProfilePostProvider
	{
		return new UserProfilePostProvider(
			PostRepository::createByDefault(),
		);
	}

	public function getByUserId(int $userId): ?Post
	{
		return $this->postRepository->getUserProfilePostByUserId($userId);
	}

	public function getByPostData(int $userId, string $text, array $fileIds = []): Post
	{
		$post = new ProfilePost([
			'profileId' => $userId,
		]);

		$postId = $post->getPostId();

		return new Post(
			id: is_int($postId) && $postId > 0 ? $postId : 0,
			text: $text,
			fileIds: $fileIds,
		);
	}
}
