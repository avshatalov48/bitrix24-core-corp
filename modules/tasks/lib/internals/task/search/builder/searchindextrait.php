<?php

namespace Bitrix\Tasks\Internals\Task\Search\Builder;

use Bitrix\Main\Loader;
use Bitrix\Main\Text\Emoji;
use Bitrix\Tasks\UI;

trait SearchIndexTrait
{
	private function makeUnique(): static
	{
		$fields = explode(' ', $this->index);
		$fields = array_unique($fields);
		$this->index = implode(' ', $fields);

		return $this;
	}

	private function convertSpecialCharacters(): static
	{
		$this->index = UI::convertBBCodeToHtmlSimple($this->index);
		if (Loader::includeModule('search'))
		{
			$this->index = \CSearch::killTags($this->index);
		}

		$this->index = mb_strtoupper(trim(str_replace(["\r", "\n", "\t"], ' ', $this->index)));

		return $this;
	}

	private function encodeEmoji(): static
	{
		$this->index = Emoji::encode($this->index);

		return $this;
	}

	private function moveCharacters(): static
	{
		$this->index = str_rot13($this->index);

		return $this;
	}
}