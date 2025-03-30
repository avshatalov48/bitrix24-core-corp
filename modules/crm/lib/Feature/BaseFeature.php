<?php

namespace Bitrix\Crm\Feature;

use Bitrix\Crm\Feature\Category\BaseCategory;
use Bitrix\Crm\Feature\Category\Common;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Config\Option;

abstract class BaseFeature
{
	/**
	 * Feature name in russian. Used to display feature switcher
	 *
	 * @return string
	 */
	abstract public function getName(): string;

	/**
	 * Is this feature currently enabled
	 *
	 * @return bool
	 */
	public function isEnabled(): bool
	{
		// unstrict comparison is used deliberately:
		return Option::get('crm', $this->getOptionName()) == $this->getEnabledValue();
	}

	/**
	 * Feature category. Used to group features by category
	 *
	 * @return BaseCategory
	 */
	public function getCategory(): BaseCategory
	{
		return Common::getInstance();
	}

	/**
	 * Feature sort index. Used to set correct features order in list
	 *
	 * @return int
	 */
	public function getSort(): int
	{
		return 100;
	}

	/**
	 * Enable this feature
	 *
	 * @return void
	 */
	public function enable(): void
	{
		$this->setOption($this->getOptionName(), $this->getEnabledValue());
		$this->logEnabled();
	}

	/**
	 * Disable this feature
	 *
	 * @return void
	 */
	public function disable(): void
	{
		$this->setOption($this->getOptionName(), $this->getDisabledValue());
		$this->logDisabled();
	}

	/**
	 * Allow to enable or to disable this feature via a secret link
	 *
	 * @return bool
	 */
	public function allowSwitchBySecretLink(): bool
	{
		return true;
	}

	/**
	 * Feature unique id
	 *
	 * @return string
	 */
	public function getId(): string
	{
		$classParts = explode('\\', static::class);

		return end($classParts);
	}

	/**
	 * Option name to store feature state (enabled or disabled)
	 *
	 * @return string
	 */
	protected function getOptionName(): string
	{
		return 'Feature_' . $this->getId();
	}

	/**
	 * Option value which mean feature is enabled
	 * Can be for example 'Y', true, 1 etc
	 *
	 * @return mixed
	 */
	protected function getEnabledValue(): mixed
	{
		return 'Y';
	}

	/**
	 * Option value which mean feature is disabled
	 * Can be for example 'N', false, 0 etc
	 * If equals to null, option will be removed from database when feature disabled
	 *
	 * @return mixed
	 */
	protected function getDisabledValue(): mixed
	{
		return null;
	}

	private function setOption(string $optionName, mixed $optionValue): void
	{
		if (is_null($optionValue))
		{
			Option::delete('crm', ['name' => $optionName]);
		}
		else
		{
			Option::set('crm', $optionName, $optionValue);
		}
	}

	protected function logEnabled(): void
	{
		$this->log($this->getId(). ' enabled');
	}

	protected function logDisabled(): void
	{
		$this->log($this->getId(). ' disabled');
	}

	private function log(string $message, string $level = \Psr\Log\LogLevel::INFO): void
	{
		$logger = (new \Bitrix\Crm\Service\Logger\DbLogger(
			'Features',
			(int)Option::get('crm', 'features_logger_ttl', 24*90))
		)->setLevel(Option::get('crm', 'features_logger_level', \Psr\Log\LogLevel::INFO));

		$context = [
			'userId' => Container::getInstance()->getContext()->getUserId(),
		];

		$logger->log($level, $message, $context);
	}
}
