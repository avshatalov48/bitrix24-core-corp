<?php

namespace Bitrix\Crm\Controller\Settings;

use Bitrix\Crm\Controller\Base;
use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Crm\Tour\Config;
use Bitrix\Main\Engine\ActionFilter\ContentType;
use Bitrix\Main\Engine\ActionFilter\Scope;
use Bitrix\Main\Error;

final class Tour extends Base
{
	protected function getDefaultPreFilters(): array
	{
		$filters = parent::getDefaultPreFilters();

		$filters[] = new Scope(Scope::AJAX);
		$filters[] = new ContentType([ContentType::JSON]); // its pain to work with empty arrays, nulls and booleans otherwise

		return $filters;
	}

	/**
	* 'crm.settings.tour.updateOption' method handler.
	*
	* @param string $category	User option category
	* @param string $name		User option name
	* @param array $options		Additional options
	*
	* @return bool[]|null
	*/
	public function updateOptionAction(string $category, string $name, array $options): ?array
	{
		if (empty($category))
		{
			$this->addError(
				new Error(
					'user option category must be specified',
					ErrorCode::INVALID_ARG_VALUE
				)
			);

			return null;
		}

		if (empty($name))
		{
			$this->addError(
				new Error(
					'user option name must be specified',
					ErrorCode::INVALID_ARG_VALUE
				)
			);

			return null;
		}

		Config::setPersonalValue(
			$category,
			$name,
			Config::CODE_CLOSED,
			'Y'
		);

		$isNumberOfViewsExceeded = true;

		if (isset($options['isMultipleViewsAllowed']) && $options['isMultipleViewsAllowed'] === true)
		{
			$numberOfViewsLimit = (int)($options['numberOfViewsLimit'] ?? 1);

			Config::setPersonalValue(
				$category,
				$name,
				Config::CODE_LIMIT,
				$numberOfViewsLimit
			);

			$numberOfViewsRaw = Config::getPersonalValue(
				$category,
				$name,
				Config::CODE_NUMBER_OF_VIEWS
			);
			$numberOfViews = (int)($numberOfViewsRaw ?? 0);
			$newNumberOfViews = $numberOfViews + 1;

			Config::setPersonalValue(
				$category,
				$name,
				Config::CODE_NUMBER_OF_VIEWS,
				$newNumberOfViews
			);

			$isNumberOfViewsExceeded = $newNumberOfViews > $numberOfViewsLimit;
		}

		if (is_array($options['additionalTourIdsForDisable'] ?? null))
		{
			foreach ($options['additionalTourIdsForDisable'] as $additionalId)
			{
				Config::setPersonalValue(
					$category,
					$additionalId,
					Config::CODE_CLOSED,
					'Y'
				);
			}
		}

		return [
			'isNumberOfViewsExceeded' => $isNumberOfViewsExceeded,
		];
	}
}
