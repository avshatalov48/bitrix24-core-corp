<?php

namespace Bitrix\DocumentGenerator\Components\Support;

use Bitrix\DocumentGenerator\Document;
use Bitrix\DocumentGenerator\Template;

final class PlaceholderFilter
{
	public function __construct(private readonly string $titleChainSeparator)
	{
	}

	public function filter(array $placeholders, array $uiFilter): array
	{
		if (empty($uiFilter))
		{
			return $placeholders;
		}

		// calculate it once for better performance
		$placeholderChain = $this->getPlaceholderChain($uiFilter);

		foreach ($placeholders as $placeholder => $field)
		{
			if (!$this->isPlaceholderIncluded($placeholder, $field, $uiFilter, $placeholderChain))
			{
				unset($placeholders[$placeholder]);
			}
		}

		return $placeholders;
	}

	private function getPlaceholderChain(array $uiFilter): string
	{
		$placeholderChain = '';
		foreach ($uiFilter as $key => $value)
		{
			// is key like 'provider1' or 'provider2'
			if (!empty($value) && str_starts_with($key, 'provider') && $key !== 'provider')
			{
				$placeholderChain .= '.' . $value;
			}
		}

		if (!empty($placeholderChain))
		{
			$placeholderChain = Document::THIS_PLACEHOLDER . '.' . Template::MAIN_PROVIDER_PLACEHOLDER . $placeholderChain;
		}

		return $placeholderChain;
	}

	private function isPlaceholderIncluded(
		string $placeholder,
		array $field,
		array $uiFilter,
		string $placeholderChainFromFilter
	): bool
	{
		$placeholderChain = $field['VALUE'];
		if (!empty($placeholderChainFromFilter) && !str_starts_with($placeholderChain, $placeholderChainFromFilter))
		{
			return false;
		}

		// case insensitive
		if (!empty($uiFilter['placeholder']) && mb_stripos($placeholder, $uiFilter['placeholder']) === false)
		{
			return false;
		}

		if (!$this->isPlaceholderIncludedByTitleFilter($placeholder, $field, $uiFilter))
		{
			return false;
		}

		if (!$this->isPlaceholderIncludedByFulltextFilter($placeholder, $field, $uiFilter))
		{
			return false;
		}

		return true;
	}

	private function isPlaceholderIncludedByTitleFilter(string $placeholder, array $field, array $uiFilter): bool
	{
		if (empty($uiFilter['title']))
		{
			// no filtering by title
			return true;
		}

		$title = empty($field['TITLE']) ? $placeholder : $field['TITLE'];

		if (empty($title))
		{
			// empty title doesn't match any filter
			return false;
		}

		// is title contains filter value (case insensitive)
		return mb_stripos($title, $uiFilter['title']) !== false;
	}

	private function isPlaceholderIncludedByFulltextFilter(string $placeholder, array $field, array $uiFilter): bool
	{
		if (empty($uiFilter['FIND']))
		{
			// no fulltext search
			return true;
		}

		// case insensitive
		$isMatchByPlaceholder = mb_stripos($placeholder, $uiFilter['FIND']) !== false;
		if ($isMatchByPlaceholder)
		{
			return true;
		}

		// chain of all human-readable titles for this field, like Deal.Company.Requisites
		$titleChain = implode($this->titleChainSeparator, $field['GROUP']);
		if (empty($titleChain))
		{
			// empty title doesn't match any filter
			return false;
		}

		// is title contains filter value (case insensitive)
		return mb_stripos($titleChain, $uiFilter['FIND']) !== false;
	}
}
