<?php

namespace Bitrix\Crm\Service\Display\Field;

use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Display\Options;
use Bitrix\Crm\UI\EntitySelector;

class UserField extends BaseLinkedEntitiesField
{
	public const TYPE = 'user';

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
		if (!is_scalar($elementId))
		{
			return '';
		}

		$linkedEntitiesValues = $this->getLinkedEntitiesValues();
		$user = $linkedEntitiesValues[$elementId] ?? null;

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
					'link' => $this->sanitizeString((string)$showUrl),
					'title' => $this->sanitizeString((string)$user['FORMATTED_NAME']),
					'picture' => $this->sanitizeString((string)$user['PHOTO_URL']),
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

	protected function getFormattedValueForMobile($fieldValue, int $itemId, Options $displayOptions): array
	{
		if ($this->isMultiple())
		{
			$userIds = [];
			if (is_array($fieldValue))
			{
				foreach ($fieldValue as $value)
				{
					$userIds[] = (int)$value;
				}
			}
			return [
				'value' => $userIds,
				'config' => $this->getPreparedConfig($userIds),
			];
		}

		$userId = (int)$fieldValue;
		return [
			'value' => $userId,
			'config' => $this->getPreparedConfig([$userId]),
		];
	}

	/**
	 * @param array $userIds
	 * @return array[]
	 */
	protected function getPreparedConfig(array $userIds): array
	{
		$users = $this->getLinkedEntitiesValues();
		$entityList = [];
		foreach ($userIds as $userId)
		{
			if (!empty($users[$userId]))
			{
				$entityList[] = [
					'id' => $userId,
					'title' => $users[$userId]['FORMATTED_NAME'],
					'imageUrl' => $users[$userId]['PHOTO_URL'],
					'customData' => [
						'position' => $users[$userId]['WORK_POSITION'],
					],
				];
			}
		}
		return [
			'entityList' => $entityList,
			'provider' => [
				'context' => EntitySelector::CONTEXT,
			],
			'showSubtitle' => true,
		];
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

		return $this->sanitizeString((string)$linkedEntitiesValues[$elementId]['FORMATTED_NAME']);
	}
}
