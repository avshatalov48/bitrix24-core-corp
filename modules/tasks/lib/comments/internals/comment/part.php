<?php
namespace Bitrix\Tasks\Comments\Internals\Comment;

/**
 * Class CommentPart
 *
 * @package Bitrix\Tasks\Comments\Internals\Comment
 */
class Part
{
	private $name = '';
	private $text = '';
	private $data = [];

	/**
	 * CommentPart constructor.
	 *
	 * @param string $name
	 * @param string $text
	 * @param array $data
	 */
	public function __construct(string $name, string $text, array $data)
	{
		$this->setName($name);
		$this->setText($text);
		$this->setData($data);
	}

	#region get/set

	/**
	 * Returns part name.
	 *
	 * @return string
	 */
	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * Sets part name.
	 *
	 * @param string $name
	 */
	public function setName(string $name): void
	{
		$this->name = $name;
	}

	/**
	 * Returns part text.
	 *
	 * @return string
	 */
	public function getText(): string
	{
		return $this->text;
	}

	/**
	 * Sets part text.
	 *
	 * @param string $text
	 */
	public function setText(string $text): void
	{
		$this->text = $text;
	}

	/**
	 * Returns part data.
	 *
	 * @return array
	 */
	public function getData(): array
	{
		return $this->data;
	}

	/**
	 * Sets part data.
	 *
	 * @param array $data
	 */
	public function setData(array $data): void
	{
		$this->data = $data;
	}

	/**
	 * Appends part data.
	 *
	 * @param array $dataItem
	 */
	public function appendData(array $dataItem): void
	{
		$this->data[] = $dataItem;
	}

	#endregion
}