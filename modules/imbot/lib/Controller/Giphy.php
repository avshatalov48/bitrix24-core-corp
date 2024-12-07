<?php

namespace Bitrix\ImBot\Controller;

use Bitrix\ImBot\Controller\Filter\CheckGiphyAvailable;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Request;

class Giphy extends Controller
{
	private const DEFAULT_LIMIT = 25;

	private \Bitrix\ImBot\Sender\Giphy $sender;

	public function __construct(Request $request = null)
	{
		parent::__construct($request);
		$this->sender = new \Bitrix\ImBot\Sender\Giphy();
	}

	protected function getDefaultPreFilters()
	{
		return array_merge(
			parent::getDefaultPreFilters(),
			[
				new CheckGiphyAvailable(),
			]
		);
	}

	/**
	 * @restMethod imbot.Giphy.list
	 */
	public function listAction(array $filter = [], int $limit = 25, int $offset = 0): ?array
	{
		$search = $filter['search'] ?? null;
		$limit = $this->getLimit($limit);

		if ($search === null || $search === '')
		{
			$this->addError(new Error(Loc::getMessage('IMBOT_GIPHY_EMPTY_SEARCH_ERROR'), 'EMPTY_SEARCH_ERROR'));

			return null;
		}

		$result = $this->sender->search($search, $limit, $offset);

		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		return $this->formatResult($result->getData());
	}

	/**
	 * @restMethod imbot.Giphy.listPopular
	 */
	public function listPopularAction(): ?array
	{
		$result = $this->sender->getPopular();

		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		return $this->formatResult($result->getData());
	}

	private function getLimit(int $limit): int
	{
		return ($limit > 0 && $limit <= 50) ? $limit : self::DEFAULT_LIMIT;
	}

	private function formatResult(array $rawResult): array
	{
		$result = [];

		foreach ($rawResult as $row)
		{
			if (isset($row['preview'], $row['original']) && is_string($row['preview']) && is_string($row['original']))
			{
				$result[] = [
					'preview' => $row['preview'],
					'original' => $row['original'],
				];
			}
		}

		return $result;
	}
}