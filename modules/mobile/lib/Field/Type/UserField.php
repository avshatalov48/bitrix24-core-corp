<?php

namespace Bitrix\Mobile\Field\Type;

use Bitrix\Mobile\Field\BoundEntitiesContainer;

class UserField extends BaseField implements HasBoundEntities
{
	public const TYPE = 'employee';

	/**
	 * @inheritDoc
	 */
	public function getFormattedValue()
	{
		if (empty($this->value))
		{
			return null;
		}

		if ($this->isMultiple())
		{
			$userIds = [];

			foreach ((array)$this->getValue() as $value)
			{
				$userIds[] = (int)$value;
			}

			return $userIds;
		}

		return (int)$this->getValue();
	}

	public function getData(): array
	{
		$data = parent::getData();

		$users = BoundEntitiesContainer::getInstance()->getBoundEntities()['user'];
		$userIds = [];

		if (!empty($this->getValue()))
		{
			foreach ((array)$this->getValue() as $value)
			{
				$userIds[] = (int)$value;
			}
		}
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
		return array_merge($data, [
			'entityList' => $entityList,
			'provider' => [
				'context' => 'UF_USER_MOBILE',
			],
			'showSubtitle' => true,
		]);
	}

	public function getBoundEntities(): array
	{
		$value = $this->value;
		if (!$value)
		{
			return [];
		}

		if (!$this->isMultiple())
		{
			$value = [$value];
		}

		return [
			'user' => [
				'ids' => $value,
				'field' => $this,
			],
		];
	}
}
