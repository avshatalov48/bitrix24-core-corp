<?php
namespace Bitrix\Tasks\Comments\Internals;

use Bitrix\Tasks\Comments\Internals\Comment\Part;
use Bitrix\Tasks\Util\Collection;

/**
 * Class Comment
 *
 * @package Bitrix\Tasks\Comments\Internals
 */
class Comment
{
	public const TYPE_DEFAULT = 0;
	public const TYPE_ADD = 1;
	public const TYPE_UPDATE = 2;
	public const TYPE_STATUS = 3;
	public const TYPE_EXPIRED = 4;
	public const TYPE_EXPIRED_SOON = 5;
	public const TYPE_PING_STATUS = 6;

	private $authorId = 0;
	private $type = self::TYPE_DEFAULT;
	private $parts;

	/**
	 * Comment constructor.
	 *
	 * @param string $message
	 * @param int $authorId
	 * @param int $type
	 * @param array $data
	 */
	public function __construct(string $message, int $authorId, int $type = self::TYPE_DEFAULT, array $data = [])
	{
		$this->parts = new Collection();
		$this->addPart('main', $message, $data);
		$this->setType($type);
		$this->setAuthorId($authorId);
	}

	/**
	 * @param string $message
	 * @param int $authorId
	 * @param int $type
	 * @param array $data
	 * @return Comment
	 */
	public static function createFromData(
		string $message,
		int $authorId,
		int $type = self::TYPE_DEFAULT,
		array $data = []
	): Comment
	{
		$c = 0;
		$comment = new Comment($message, $authorId, $type);

		if (empty($data))
		{
			return $comment;
		}

		foreach ($data as $partData)
		{
			if (is_array($partData))
			{
				foreach ($partData as $part)
				{
					$comment->addPart($c++, '', $part);
				}
			}
		}

		return $comment;
	}

	/**
	 * @return string
	 */
	public function getText(): string
	{
		$text = '';

		foreach ($this->parts as $part)
		{
			/** @var Part $part */
			$text .= $part->getText();
		}

		return $text;
	}

	/**
	 * @return array
	 */
	public function getData(): array
	{
		$data = [];

		foreach ($this->parts as $part)
		{
			/** @var Part $part */
			$data[] = $part->getData();
		}

		return $data;
	}

	#region Part

	/**
	 * @param string $partName
	 * @param $text
	 * @param array $data
	 * @return Part
	 */
	public function addPart(string $partName, $text, array $data): Part
	{
		$part = new Part($partName, $text?:"", $data);
		$this->parts->offsetSet($partName, $part);

		return $part;
	}

	/**
	 * @param string $partName
	 * @return Part|null
	 */
	public function getPart(string $partName): ?Part
	{
		return $this->parts->offsetGet($partName);
	}

	/**
	 * @param string $partName
	 */
	public function deletePart(string $partName): void
	{
		$this->parts->offsetUnset($partName);
	}

	/**
	 * @param string $partName
	 * @return bool
	 */
	public function isPartExist(string $partName): bool
	{
		return $this->parts->offsetExists($partName);
	}

	/**
	 * @param string $partName
	 * @param string $text
	 */
	public function appendPartText(string $partName, string $text): void
	{
		if ($part = $this->getPart($partName))
		{
			$part->setText($part->getText().$text);
		}
		else
		{
			$this->addPart($partName, $text, []);
		}
	}

	/**
	 * @param string $partName
	 * @param array $data
	 */
	public function appendPartData(string $partName, array $data): void
	{
		if ($part = $this->getPart($partName))
		{
			$part->appendData($data);
		}
		else
		{
			$this->addPart($partName, '', [$data]);
		}
	}

	#endregion

	#region get/set

	/**
	 * @return int
	 */
	public function getType(): int
	{
		return $this->type;
	}

	/**
	 * @param int $type
	 */
	public function setType(int $type): void
	{
		$this->type = $type;
	}

	/**
	 * @return int
	 */
	public function getAuthorId(): int
	{
		return $this->authorId;
	}

	/**
	 * @param int $authorId
	 */
	public function setAuthorId(int $authorId): void
	{
		$this->authorId = $authorId;
	}

	#endregion
}