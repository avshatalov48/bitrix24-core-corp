<?php


namespace Bitrix\Crm\Kanban;


use Bitrix\Crm\Activity\ToDo\CalendarSettings\CalendarSettingsProvider;
use Bitrix\Crm\Activity\ToDo\ColorSettings\ColorSettingsProvider;
use Bitrix\Crm\Kanban;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Web\Uri;

class Desktop extends Kanban
{
	/**
	 * @param array $status
	 * @return bool
	 */
	protected function isDropZone(array $status = []): bool
	{
		if ($this->viewMode === ViewMode::MODE_DEADLINES)
		{
			return false;
		}

		return parent::isDropZone($status);
	}

	protected function getPathToImport(): string
	{
		if (!empty($this->params['PATH_TO_IMPORT']))
		{
			$uriImport = new Uri($this->params['PATH_TO_IMPORT']);
			$importUriParams = [
				'from' => 'kanban',
			];
			if ($this->entity->getCategoryId() > 0)
			{
				$importUriParams['category_id'] = $this->entity->getCategoryId();
			}
			$uriImport->addParams($importUriParams);
			return $uriImport->getUri();
		}

		return '';
	}

	protected function prepareComponentParams(array &$params): void
	{
		parent::prepareComponentParams($params);
		$params['PATH_TO_IMPORT'] = $this->getPathToImport();
	}

	public function getItemsConfig(array $params = []): array
	{
		if ($this->getApiVersion() <= 1)
		{
			return [];
		}

		$result = [
			'fields' => $this->getFieldsConfig(),
			'users' => $this->getUsersData($params['userIds'] ?? null),
		];

		if ($params['fullConfig'] ?? true)
		{
			$entity = $this->getEntity();

			$pingSettingsInfo = $entity->prepareMultipleItemsPingSettings(
				$entity->getTypeId(),
				$params['categoryId'] ?? null,
			);

			$fieldsList = $entity->getDisplayedFieldsList();

			$result = array_merge(
				$result,
				[
					'pingSettings' => $pingSettingsInfo[$params['categoryId']] ?? null,
					'calendarSettings' => (new CalendarSettingsProvider())->fetchForJsComponent(),
					'colorSettings' => (new ColorSettingsProvider())->fetchForJsComponent(),
					'showLastActivityTime' => isset($fieldsList['LAST_ACTIVITY_BY_TIME']),
					'showLastActivityUserAvatar' => isset($fieldsList['LAST_ACTIVITY_BY_USER_AVATAR']),
				],
			);
		}

		return $result;
	}

	private function getFieldsConfig(): array
	{
		$fields = [];
		$displayedFields = $this->getDisplayedFieldsList();
		$inlineFieldTypes = $this->getInlineFieldTypes();

		$fieldCodes = array_keys($this->additionalSelect);
		foreach ($fieldCodes as $code)
		{
			$displayedField = $displayedFields[$code];
			$fields[] = [
				'code' => $code,
				'title' => $this->sanitizeString($displayedField->getTitle()),
				'type' => $displayedField->getType(),
				'valueDelimiter' => in_array($displayedField->getType(), $inlineFieldTypes, true) ? ', ' : '<br>',
				'icon' => $displayedField->getDisplayParam('icon'),
				'html' => $displayedField->wasRenderedAsHtml(),
				'isMultiple' => $displayedField->isMultiple(),
			];
		}

		return $fields;
	}

	private function getUsersData(?array $ids): array
	{
		if (empty($ids))
		{
			return [];
		}

		$users = [];
		$items = Container::getInstance()->getUserBroker()->getBunchByIds($ids);

		$prefix = $this->getEntity()->getGridId() ?? '';

		foreach ($items as $item)
		{
			$userId = $item['ID'];
			$title = $item['FORMATTED_NAME'] ?? null;
			$link = $item['SHOW_URL'] ?? null;
			$picture = $item['PHOTO_URL'] ?? null;

			$users[] = [
				'id' => $userId,
				'title' => htmlspecialcharsbx($title),
				'link' => htmlspecialcharsbx($link),
				'picture' => htmlspecialcharsbx($picture),
				'balloon' => \CCrmViewHelper::PrepareUserBaloonHtml([
					'PREFIX' => $prefix,
					'USER_ID' => $userId,
					'USER_NAME' => $title,
					'USER_PROFILE_URL' => $link,
					'ENCODE_USER_NAME' => true,
				]),
			];
		}

		return $users;
	}
}
