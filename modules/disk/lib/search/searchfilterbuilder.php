<?php
declare(strict_types=1);

namespace Bitrix\Disk\Search;

use Bitrix\Disk\Configuration;
use Bitrix\Disk\Search\Reindex\ExtendedIndex;
use Bitrix\Disk\Search\Reindex\HeadIndex;
use Bitrix\Main\Search\Content;

/**
 * Class SearchFilterBuilder
 *
 * Encapsulates the logic for building search filters to search for files and folders in the ORM.
 */
final class SearchFilterBuilder
{
	/**
	 * Constructor.
	 *
	 * @param string|null $refencePathToIndex The reference path to the index (e.g., 'FILE'). Default is null and means the root of the file table.
	 */
	public function __construct(
		protected ?string $refencePathToIndex = null
	)
	{
	}

	/**
	 * Builds the filter array based on the search term.
	 *
	 * @param string $search The search term input.
	 * @return array The constructed filter array for ORM queries.
	 */
	public function buildFilter(string $search): array
	{
		$filter = [];

		$search = trim($search);
		if (empty($search))
		{
			return $filter;
		}

		$fulltextContent = FullTextBuilder::create()
			->addText($search)
			->getSearchValue()
		;

		$filter['%=NAME'] = str_replace('%', '', $search) . '%';

		$refencePathToIndex = $this->refencePathToIndex ?: '';
		if ($refencePathToIndex)
		{
			$refencePathToIndex .= '.';
		}

		if ($fulltextContent && Content::canUseFulltextSearch($fulltextContent))
		{
			if (
				Configuration::allowUseExtendedFullText() &&
				ExtendedIndex::isReady()
			)
			{
				$filter["*{$refencePathToIndex}EXTENDED_INDEX.SEARCH_INDEX"] = $fulltextContent;
				unset($filter['%=NAME']);
			}
			elseif (HeadIndex::isReady())
			{
				$filter["*{$refencePathToIndex}HEAD_INDEX.SEARCH_INDEX"] = $fulltextContent;
				unset($filter['%=NAME']);
			}
		}

		return $filter;
	}
}