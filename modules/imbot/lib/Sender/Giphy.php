<?php

namespace Bitrix\ImBot\Sender;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

class Giphy extends Base
{
	private ?string $lang = null;

	public function __construct(?string $lang = null)
	{
		parent::__construct();

		if (!empty($lang))
		{
			$this->lang = $lang;
		}
	}

	public function search(string $search, int $limit = 25, int $offset = 0): Result
	{
		return $this->performRequest(
			'botcontroller.Giphy.list',
			[
				'filter' => ['search' => $search],
				'limit' => $limit,
				'offset' => $offset,
				'lang' => $this->getLang(),
			]
		);
	}

	public function getPopular(): Result
	{
		return $this->performRequest('botcontroller.Giphy.listPopular');
	}

	private function getLang(): string
	{
		return $this->lang ?? LANGUAGE_ID ?? Loc::getCurrentLang();
	}
}