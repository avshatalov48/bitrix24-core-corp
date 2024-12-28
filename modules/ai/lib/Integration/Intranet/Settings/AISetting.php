<?php

namespace Bitrix\AI\Integration\Intranet\Settings;

use Bitrix\AI;
use Bitrix\Intranet;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

class AISetting extends Intranet\Settings\AbstractSettings
{
	public const TYPE = 'ai';
	private const FIELD_ONSAVE_SUFFIX = '_onsave';

	public function validate(): ErrorCollection
	{
		$errors = new ErrorCollection();

		//todo: need Validate?

		return $errors;
	}

	public function save(): Result
	{
		// todo: validate
		$config = new AI\Tuning\Manager();
		foreach ($this->data as $code => $value)
		{
			$item = $config->getItem($code);
			if ($item)
			{
				$item->setValue($value);

				if (
					isset($this->data[$code . self::FIELD_ONSAVE_SUFFIX])
					&& $this->data[$code . self::FIELD_ONSAVE_SUFFIX] === 'Y'
				)
				{
					$item->getOnSave()?->activate();
				}
			}
		}
		$config->save();

		return new Result();
	}

	public function get(): Intranet\Settings\SettingsInterface
	{
		$manager = new AI\Tuning\Manager();
		$fields = $manager->getList(true);
		foreach ($manager->getList() as $group)
		{
			/**
			 * @var AI\Tuning\Group $group
			 */
			foreach ($group->getItems() as $item)
			{
				if (
					$item->isList()
					&& ($item->getAdditional()['isProviderSelector'] ?? false) === true
					&& ($item->getAdditional()['marketLink'] ?? true) !== false
				)
				{
					$link = $item->getAdditional()['marketLink']['link'] ?? null;
					$text = $item->getAdditional()['marketLink']['text'] ?? null;
					$icon = $item->getAdditional()['marketLink']['icon'] ?? null;

					$fields[$group->getCode()]['items'][$item->getCode()]['options']['market'] =
						self::getInternalItemsMarketOption($link, $text, $icon);
				}
			}
		}

		$this->data['fields'] = $fields;

		return $this;
	}

	/**
	 * Just a pilot format for special links in options. It may be modified
	 * @param string|null $link
	 * @param string|null $text
	 * @param string|null $icon
	 * @return array
	 */
	protected static function getInternalItemsMarketOption(
		?string $link = null,
		?string $text = null,
		?array $icon = null,
	): array
	{
		$defaults = self::getDefaultMarketLInk();

		$link = $link ?: $defaults['link'];
		$text = $text ?: $defaults['text'];
		$icon = [
			'code' => $icon['code'] ?? $defaults['icon']['code'],
			'set' => $icon['set'] ?? $defaults['icon']['set'],
		];

		return [
			'type' => 'link',
			'link' => $link,
			'isSlider' => true,
			'text' => $text,
			'icon' => $icon,
		];
	}

	protected static function getDefaultMarketLInk(): array
	{
		return [
			'link' => '/market/collection/ai_provider_partner_crm/',
			'text' => Loc::getMessage('AI_SETTINGS_INTERNAL_MARKET_LINK'),
			'icon' => [
				'code' => '--market-1',
				'set' => 'ui.icon-set.main',
			],
		];
	}

	public function find(string $query): array
	{
		$fields = [];
		$manager = new AI\Tuning\Manager();
		foreach($manager->getList() as $group)
		{
			/**
			 * @var AI\Tuning\Group $group
			 */
			foreach ($group->getItems() as $item)
			{
				$fields[$item->getCode()] = $item->getTitle();
			}
		}

		$searchEngine = Intranet\Settings\Search\SearchEngine::initWithDefaultFormatter($fields);

		return $searchEngine->find($query);
	}
}