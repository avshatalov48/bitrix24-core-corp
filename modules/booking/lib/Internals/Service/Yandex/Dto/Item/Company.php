<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service\Yandex\Dto\Item;

use Bitrix\Booking\Internals\Exception\Yandex\YandexException;
use Bitrix\Booking\Internals\Service\Yandex\Dto\Collection\ResourceCollection;
use Bitrix\Booking\Internals\Service\Yandex\Dto\Collection\ServiceCollection;
use Bitrix\Booking\Internals\Service\Yandex\Dto\Item;
use Bitrix\Main\Result;
use Bitrix\Booking\Internals\Container;

class Company extends Item
{
	public const DEFAULT_COMPANY_ID = 'default_company';

	private string|null $id = null;
	private string|null $name = 'no-value';
	private string $address = 'no-value';
	private string|null $permalink = null;
	private string|null $timezone = null;
	private array $rubrics = ['no-value'];

	public function getId(): string|null
	{
		return $this->id;
	}

	public function setId(string|null $id): self
	{
		$this->id = $id;

		return $this;
	}

	public function getName(): string|null
	{
		return $this->name;
	}

	public function getAddress(): string
	{
		return $this->address;
	}

	public function getPermalink(): ?string
	{
		return $this->permalink;
	}

	public function setPermalink(?string $permalink): self
	{
		$this->permalink = $permalink;

		return $this;
	}

	public function getTimezone(): string|null
	{
		return $this->timezone;
	}

	public function setTimezone(string|null $timezone): self
	{
		$this->timezone = $timezone;

		return $this;
	}

	public function getServices(): ServiceCollection
	{
		try
		{
			return Container::getYandexServiceProvider()->getServices($this->getId());
		}
		catch (YandexException)
		{
			return new ServiceCollection();
		}
	}

	public function getResources(): ResourceCollection
	{
		try
		{
			return Container::getYandexResourceProvider()->getResources($this->getId());
		}
		catch (YandexException)
		{
			return new ResourceCollection();
		}
	}

	public function getRubrics(): array
	{
		return $this->rubrics;
	}

	public function validate(): Result
	{
		return (new \Bitrix\Booking\Internals\Service\Yandex\Dto\Validator\Company())->validate($this);
	}

	protected function __toArray(): array
	{
		return [
			'id' => $this->id,
			'name' => $this->name,
			'permalink' => $this->permalink,
			'address' => $this->address,
			'services' => $this->getServices()->toArray(),
			'resources' => $this->getResources()->toArray(),
			'rubrics' => $this->rubrics,
		];
	}
}
