<?php
namespace Bitrix\ImBot\Itr;

class Menu
{
	private $id = 0;
	private $text = '';
	private $items = Array();

	/**
	 * ItrMenu constructor.
	 * @param $id
	 */
	public function __construct($id)
	{
		$this->id = intval($id);
	}

	public function getId()
	{
		return $this->id;
	}

	public function getText()
	{
		return $this->text;
	}

	public function getItems()
	{
		return $this->items;
	}

	public function setText($text)
	{
		$this->text = trim($text);
	}

	public function addItem($id, $title, array $action)
	{
		$id = intval($id);
		if ($id <= 0 && !in_array($action['TYPE'], Array(Item::TYPE_VOID, Item::TYPE_TEXT)))
		{
			return false;
		}

		$title = trim($title);

		$this->items[$id] = Array(
			'ID' => $id,
			'TITLE' => $title,
			'ACTION' => $action
		);

		return true;
	}
}