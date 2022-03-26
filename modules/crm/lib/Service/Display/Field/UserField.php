<?php

namespace Bitrix\Crm\Service\Display\Field;


use Bitrix\Crm\Service\Display\Options;
use Bitrix\Crm\Service\Container;

class UserField extends BaseLinkedEntitiesField
{
	protected const TYPE = 'user';

	protected function getFormattedValueForKanban($fieldValue, int $itemId, Options $displayOptions)
	{
		$results = [];
		$prefix = ($displayOptions->getGridId() ?? '');

		$fieldValue = is_array($fieldValue) ? $fieldValue : [$fieldValue];
		foreach ($fieldValue as $elementId)
		{
			if (!$this->isMultiple())
			{
				return $this->getPreparedValue($elementId, $prefix);
			}

			$preparedValue = $this->getPreparedValue($elementId, $prefix);
			if ($preparedValue !== '')
			{
				$results[] = $preparedValue;
			}
		}

		return $results;
	}

	/**
	 * @param int|string $elementId
	 * @param string $prefix
	 * @return array|null|string
	 */
	protected function getPreparedValue($elementId, string $prefix)
	{
		$linkedEntitiesValues = $this->getLinkedEntitiesValues();
		$user = $linkedEntitiesValues[$elementId];

		if (is_array($user))
		{
			$this->setWasRenderedAsHtml(true);

			$displayParams = $this->getDisplayParams();
			$customUrlTemplate = ($displayParams['SHOW_URL_TEMPLATE'] ?? '');

			$showUrl = (
				$customUrlTemplate === ''
					? $user['SHOW_URL']
					: str_replace('#user_id#', $user['ID'], $customUrlTemplate)
			);

			if (isset($displayParams['AS_ARRAY']) && $displayParams['AS_ARRAY'])
			{
				return [
					'link' => htmlspecialcharsbx($showUrl),
					'title' => htmlspecialcharsbx($user['FORMATTED_NAME']),
					'picture' => htmlspecialcharsbx($user['PHOTO_URL']),
				];
			}
			return \CCrmViewHelper::PrepareUserBaloonHtml([
				'PREFIX' => $prefix,
				'USER_ID' => $user['ID'],
				'USER_NAME' => $user['FORMATTED_NAME'],
				'USER_PROFILE_URL' => $showUrl,
				'ENCODE_USER_NAME' => true,
			]);
		}

		return '';
	}

	public function loadLinkedEntities(array &$linkedEntitiesValues, array $linkedEntity): void
	{
		$linkedEntitiesId = $linkedEntity['ID'];
		$fieldType = $this->getType();
		$linkedEntitiesValues[$fieldType] = Container::getInstance()
			->getUserBroker()
			->getBunchByIds($linkedEntitiesId)
		;
	}

	protected function getFormattedValueForExport($fieldValue, int $itemId, Options $displayOptions): string
	{
		if (!$this->isMultiple())
		{
			return $this->getPreparedValueForExport($fieldValue);
		}

		$results = [];
		foreach ($fieldValue as $elementId)
		{
			$results[] = $this->getPreparedValueForExport($elementId);
		}

		return implode($displayOptions->getMultipleFieldsDelimiter(), $results);
	}

	/**
	 * @param int|string $elementId
	 * @return string
	 */
	protected function getPreparedValueForExport($elementId): string
	{
		$linkedEntitiesValues = $this->getLinkedEntitiesValues();
		return htmlspecialcharsbx($linkedEntitiesValues[$elementId]['FORMATTED_NAME']);
	}
}
