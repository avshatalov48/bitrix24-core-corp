<?php declare(strict_types=1);

namespace Bitrix\AI\ThirdParty\Dto;

use Bitrix\Main\Type\DateTime;


/**
 * Third Party Engine DTO
 * Contains data for registration of a new third party engine
 */
class ThirdPartyEngineDto
{
	private string $name;
	private string $code;
	private string $category;
	private string $completionsUrl;
	private ?string $appCode;
	private ?array $settings;
	private ?int $engineId;

	public function setName(string $name): ThirdPartyEngineDto
	{
		$this->name = $name;

		return $this;
	}

	public function setCode(string $code): ThirdPartyEngineDto
	{
		$this->code = $code;

		return $this;
	}

	public function setCategory(string $category): ThirdPartyEngineDto
	{
		$this->category = $category;

		return $this;
	}

	public function setCompletionsUrl(string $completionsUrl): ThirdPartyEngineDto
	{
		$this->completionsUrl = $completionsUrl;

		return $this;
	}

	public function setAppCode(?string $appCode): ThirdPartyEngineDto
	{
		$this->appCode = $appCode;

		return $this;
	}

	public function setSettings(?array $settings): ThirdPartyEngineDto
	{
		$this->settings = $settings;

		return $this;
	}

	public function setEngineId(?int $engineId): ThirdPartyEngineDto
	{
		$this->engineId = $engineId;

		return $this;
	}

	public function getName(): string
	{
		return $this->name ?? '';
	}

	public function getCode(): string
	{
		return $this->code ?? '';
	}

	public function getCategory(): string
	{
		return $this->category ?? '';
	}

	public function getCompletionsUrl(): string
	{
		return $this->completionsUrl ?? '';
	}

	public function getAppCode(): ?string
	{
		return $this->appCode ?? null;
	}

	public function getSettings(): array
	{
		return $this->settings ?? [];
	}

	public function getExists(): bool
	{
		return isset($this->engineId) && $this->engineId;
	}

	public function getEngineId(): ?string
	{
		return isset($this->engineId) ? (string)$this->engineId : null;
	}

	public function getArray(DateTime $dateCreate = null): array
	{
		$fields = [
			'name' => $this->name,
			'code' => $this->code,
			'category' => $this->category,
			'completions_url' => $this->completionsUrl,
			'app_code' => $this->appCode,
			'settings' => $this->settings,
		];

		if ($dateCreate)
		{
			$fields['date_create'] = $dateCreate;
		}

		return $fields;
	}
}
