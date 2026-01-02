<?php

declare(strict_types=1);

namespace Bitrix\Intranet\Internal\Repository\Mapper\User\Profile;

use Bitrix\Intranet\Internal\Entity\User\Profile\Post;

class PostMapper
{
	public function createPostFromPostData(array $postData): ?Post
	{
		if (empty($postData['ID']))
		{
			return null;
		}

		$fileIds = $postData['UF']['UF_BLOG_POST_FILE']['VALUE'] ?? [];

		return new Post(
			id: (int)$postData['ID'],
			text: $postData['DETAIL_TEXT'] ?? '',
			fileIds: is_array($fileIds) ? $fileIds : [],
		);
	}
}
