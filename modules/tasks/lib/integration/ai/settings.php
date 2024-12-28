<?php

namespace Bitrix\Tasks\Integration\AI;

use Bitrix\AI\Context;
use Bitrix\AI\Engine;
use Bitrix\AI\Tuning;
use Bitrix\Main\Entity;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

class Settings
{
	protected const TEXT_CATEGORY = 'text';
	protected const IMAGE_CATEGORY = 'image';
	protected const TUNING_CODE_IMAGE = 'tasks_allow_image_generate';
	protected const TUNING_CODE_TEXT = 'tasks_allow_text_generate';
	protected const TUNING_CODE_IMAGE_COMMENT = 'tasks_comment_allow_image_generate';
	protected const TUNING_CODE_TEXT_COMMENT = 'tasks_comment_allow_text_generate';

	public static function isTextAvailable(): bool
	{
		if (!self::checkEngineAvailable(self::TEXT_CATEGORY))
		{
			return false;
		}

		$item = (new Tuning\Manager())->getItem(self::TUNING_CODE_TEXT);

		return $item ? $item->getValue() : true;
	}

	public static function isTextCommentAvailable(): bool
	{
		if (!self::checkEngineAvailable(self::TEXT_CATEGORY))
		{
			return false;
		}

		$item = (new Tuning\Manager())->getItem(self::TUNING_CODE_TEXT_COMMENT);

		return $item ? $item->getValue() : true;
	}

	public static function isImageAvailable(): bool
	{
		if (!self::checkEngineAvailable(self::IMAGE_CATEGORY))
		{
			return false;
		}

		$item = (new Tuning\Manager())->getItem(self::TUNING_CODE_IMAGE);

		return $item ? $item->getValue() : true;
	}

	public static function isImageCommentAvailable(): bool
	{
		if (!self::checkEngineAvailable(self::IMAGE_CATEGORY))
		{
			return false;
		}

		$item = (new Tuning\Manager())->getItem(self::TUNING_CODE_IMAGE_COMMENT);

		return $item ? $item->getValue() : true;
	}

	protected static function checkEngineAvailable(string $type): bool
	{
		if (!Loader::includeModule('ai'))
		{
			return false;
		}

		$engine = Engine::getByCategory($type, Context::getFake());
		if (!$engine)
		{
			return false;
		}

		return true;
	}

	public static function onTuningLoad(): Entity\EventResult
	{
		$result = new Entity\EventResult();

		$items = [];
		$groups = [];

		if (Engine::getByCategory(self::TEXT_CATEGORY, Context::getFake()))
		{
			$items[self::TUNING_CODE_TEXT] = [
				'group' => Tuning\Defaults::GROUP_TEXT,
				'header' => Loc::getMessage('TASKS_AI_SETTINGS_ALLOW_TEXT_COPILOT_DESC'),
				'title' => Loc::getMessage('TASKS_AI_SETTINGS_COPILOT_TITLE'),
				'type' => Tuning\Type::BOOLEAN,
				'default' => true,
				'sort' => 200,
			];

			$items[self::TUNING_CODE_TEXT_COMMENT] = [
				'group' => Tuning\Defaults::GROUP_TEXT,
				'header' => Loc::getMessage('TASKS_AI_SETTINGS_ALLOW_TEXT_COMMENT_COPILOT_DESC'),
				'title' => Loc::getMessage('TASKS_AI_SETTINGS_COPILOT_COMMENT_TITLE'),
				'type' => Tuning\Type::BOOLEAN,
				'default' => true,
				'sort' => 210,
			];
		}

		if (Engine::getByCategory(self::IMAGE_CATEGORY, Context::getFake()))
		{
			$items[self::TUNING_CODE_IMAGE] = [
				'group' => Tuning\Defaults::GROUP_IMAGE,
				'header' => Loc::getMessage('TASKS_AI_SETTINGS_ALLOW_IMAGE_COPILOT_DESC'),
				'title' => Loc::getMessage('TASKS_AI_SETTINGS_COPILOT_TITLE'),
				'type' => Tuning\Type::BOOLEAN,
				'default' => true,
				'sort' => 200,
			];

			$items[self::TUNING_CODE_IMAGE_COMMENT] = [
				'group' => Tuning\Defaults::GROUP_IMAGE,
				'header' => Loc::getMessage('TASKS_AI_SETTINGS_ALLOW_IMAGE_COMMENT_COPILOT_DESC'),
				'title' => Loc::getMessage('TASKS_AI_SETTINGS_COPILOT_COMMENT_TITLE'),
				'type' => Tuning\Type::BOOLEAN,
				'default' => true,
				'sort' => 210,
			];
		}

		$result->modifyFields([
			'items' => $items,
			'groups' => $groups,
		]);

		return $result;
	}
}