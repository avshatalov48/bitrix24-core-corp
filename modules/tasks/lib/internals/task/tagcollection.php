<?php

namespace Bitrix\Tasks\Internals\Task;

class TagCollection extends EO_Label_Collection
{
	public function sort(array $ids): void
	{
		foreach ($ids as $id)
		{
			$item = $this->getByPrimary($id);
			if (!is_null($item))
			{
				$this->removeByPrimary($id);
				$this->add($item);
			}
		}
	}

	public function mergeByName(TagCollection $collection): self
	{
		$nameList = $this->getNameList();

		foreach ($collection as $item)
		{
			if (!in_array($item->getName(), $nameList, true))
			{
				$this->add($item);
			}
		}

		return $this;
	}
}