<?php

namespace Bitrix\Disk\Uf;

use Bitrix\Main\Web\Uri;

final class BlogPostCommentConnector extends StubConnector
{
	private $canRead = null;

	/**
	 * @param $userId
	 * @return bool
	 */
	public function canRead($userId)
	{
		if(isset($this->canRead))
		{
			return $this->canRead;
		}

		$connector = BlogPostConnector::createFromBlogPostCommentConnector($this);
		$this->canRead = $connector->canRead($userId);

		return $this->canRead;
	}

	/**
	 * @param $userId
	 * @return bool
	 */
	public function canUpdate($userId)
	{
		if(isset($this->canRead))
		{
			return $this->canRead;
		}

		return $this->canRead($userId);
	}

	/**
	 * @inheritdoc
	 */
	public function canConfidenceReadInOperableEntity()
	{
		return true;
	}

	/**
	 * @inheritdoc
	 */
	public function canConfidenceUpdateInOperableEntity()
	{
		return true;
	}

	public function getDataToShow()
	{
		return $this->getDataToShowByUser($this->getUser()->getId());
	}

	public function getDataToShowByUser(int $userId)
	{
		$connector = BlogPostConnector::createFromBlogPostCommentConnector($this);
		$dataToShow = $connector->getDataToShow();
		$detailUrl = new Uri($dataToShow['DETAIL_URL'] . "#com{$this->entityId}");
		$detailUrl->addParams(['commentId' => $this->entityId]);

		$dataToShow['DETAIL_URL'] = $detailUrl->getUri();

		return $dataToShow;
	}

	/**
	 * @inheritdoc
	 */
	public function addComment($authorId, array $data)
	{
		$connector = BlogPostConnector::createFromBlogPostCommentConnector($this);
		$connector->addComment($authorId, $data);
	}

	public static function clearCacheByObjectId($id)
	{
		$attachedObjects = \Bitrix\Disk\AttachedObject::getModelList(array(
				'filter' => array(
					'=ENTITY_TYPE' => self::className(),
					'=OBJECT_ID' => $id,
				))
		);

		foreach($attachedObjects as $attachedObject)
		{
			BXClearCache(true, "/blog/comment/".intval($attachedObject->getEntityId() / 100)."/".$attachedObject->getEntityId()."/");
		}
	}
}
