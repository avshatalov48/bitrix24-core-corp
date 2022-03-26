<?php

namespace Bitrix\Crm\Timeline\HistoryDataModel;

use Bitrix\Crm\Service\Container;
use Bitrix\Main\Context;
use Bitrix\Main\IO\Path;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;
use Bitrix\Sale\Cashbox\CheckManager;

/**
 * Class Presenter
 *
 * This class is used for generating history data model in timeline controllers.
 * Implements the pattern 'Bridge'
 *
 * Abstraction - Presenter
 * Implementation - @see \Bitrix\Crm\Timeline\HistoryDataModel\EntityImplementation
 *
 * This base class is used as 'null-object', therefore it is not abstract
 */
class Presenter
{
	public const CREATED_TIME_FORMAT = 'Y-m-d H:i:s';

	/** @var EntityImplementation */
	protected $entityImplementation;

	/**
	 * Presenter constructor.
	 *
	 * @param EntityImplementation $entityImplementation - entity specific object that is used for customizing data model
	 * for a specified entity type
	 */
	public function __construct(EntityImplementation $entityImplementation)
	{
		$this->entityImplementation = $entityImplementation;
	}

	/**
	 * Prepare HistoryDataModel for specific event type and entity type
	 *
	 * @param array $data
	 * @param array|null $options = [
	 *     'ENABLE_USER_INFO' => false, // prepare detailed author info (link, image, name). Disabled by default
	 * ];
	 *
	 * @return array
	 */
	public function prepareHistoryDataModel(array $data, array $options = null): array
	{
		$data = $this->prepareDataBySettings($data);

		$data = $this->prepareDataByOptions($data, $options);

		if(isset($data['CREATED']) && $data['CREATED'] instanceof DateTime)
		{
			$data['CREATED_SERVER'] = $data['CREATED']->format(static::CREATED_TIME_FORMAT);
		}

		$entityId = (int)($data['ASSOCIATED_ENTITY_ID'] ?? null);
		if ($entityId > 0)
		{
			$data['ASSOCIATED_ENTITY'] = $this->entityImplementation->getEntityInfo($entityId);
		}

		return $data;
	}

	protected function prepareDataBySettings(array $data): array
	{
		$settings = (array)($data['SETTINGS'] ?? []);

		$data['TITLE'] = $this->getHistoryTitle($settings['FIELD'] ?? null);
		$data['START_NAME'] = $settings['START_NAME'] ?? $settings['START'] ?? '';
		$data['FINISH_NAME'] = $settings['FINISH_NAME'] ?? $settings['FINISH'] ?? '';

		$data = $this->prepareDataBySettingsForSpecificEvent($data, $settings);

		unset($data['SETTINGS']);

		return $data;
	}

	protected function getHistoryTitle(string $fieldName = null): string
	{
		return '';
	}

	/**
	 * Modify data according for the specific event.
	 * For example, add specific legend for creation event
	 *
	 * @param array $data
	 * @param array $settings
	 *
	 * @return array
	 */
	protected function prepareDataBySettingsForSpecificEvent(array $data, array $settings): array
	{
		return $data;
	}

	protected function prepareDataByOptions(array $data, ?array $options): array
	{
		if (!is_array($options))
		{
			$options = [];
		}

		if (isset($options['ENABLE_USER_INFO']) && $options['ENABLE_USER_INFO'] === true)
		{
			$data['AUTHOR'] = $this->prepareAuthorInfo((int)($data['AUTHOR_ID'] ?? null));
		}

		return $data;
}

	protected function prepareAuthorInfo(int $authorId): ?array
	{
		if ($authorId <= 0)
		{
			return null;
		}

		$user = Container::getInstance()->getUserBroker()->getById($authorId);
		if (empty($user))
		{
			return null;
		}

		return [
			'FORMATTED_NAME' => $user['FORMATTED_NAME'] ?? null,
			'SHOW_URL' => $user['SHOW_URL'] ?? null,
			'IMAGE_URL' => $user['PHOTO_URL'] ?? null,
		];
	}
}
