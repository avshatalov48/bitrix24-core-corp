<?php

namespace Bitrix\AI\Payload;

use Bitrix\AI\Engine\IEngine;
use Bitrix\AI\Facade\Bitrix24;
use Bitrix\AI\Prompt\Item;
use Bitrix\AI\Prompt\Manager;
use Bitrix\AI\Prompt\Role;

class Prompt extends Text implements IPayload
{
	private const DEFAULT_USAGE_COST_DEMO_PLAN = 1;

	private const SPEC_CODES = [
		'set_ai_session_name' => 'set_ai_session_name',
		'summarize_transcript' => 'summarize_transcript',
		'extract_form_fields' => 'extract_form_fields',
	];

	private string $promptCategory = '';

	private ?Item $promptItem;

	public function __construct(
		protected string $payload
	)
	{
		parent::__construct($payload);
		$this->promptItem = Manager::getByCode($payload);
	}

	/**
	 * @inheritDoc
	 */
	public function setEngine(IEngine $engine): static
	{
		$this->engine = $engine;
		if ($this->promptItem)
		{
			$this->engine->setParameters(
				$this->promptItem->getSettings()
			);
		}

		return $this;
	}

	/**
	 * return prompt code
	 *
	 * @return string
	 */
	public function getPromptCode(): string
	{
		return $this->promptItem?->getCode() ?? '';
	}

	/**
	 * return information on the availability of system category
	 *
	 * @return bool
	 */
	public function hasSystemCategory(): bool
	{
		return $this->promptItem?->hasSystemCategory() ?? false;
	}

	/**
	 * @inheritDoc
	 */
	public function getData(): string
	{
		$prompt = $this->promptItem ? $this->promptItem->getPrompt() : $this->payload;

		$markers = $this->getMarkers();
		if ($markers && $this->hasHiddenTokens())
		{
			$markersWithHiddenTokens = [];
			foreach ($markers as $key => $marker)
			{
				$markersWithHiddenTokens[$key] = $this->hideTokens($marker);
			}
			$markers = $markersWithHiddenTokens;
		}

		return (new Formatter($prompt, $this->engine))->format($markers);
	}

	/**
	 * @inheritDoc
	 */
	public function pack(): string
	{
		return json_encode([
			'data' => $this->payload,
			'markers' => $this->markers,
			'role' => $this->role?->getCode(),
			'promptCategory' => $this->promptCategory,
			static::PROPERTY_CUSTOM_COST => $this->customCost
		]);
	}

	/**
	 * @inheritDoc
	 */
	public static function unpack(string $packedData): ?static
	{
		$unpackedData = json_decode($packedData, true);

		$data = $unpackedData['data'] ?? '';
		$markers = $unpackedData['markers'] ?? [];
		$role = $unpackedData['role'] ?? null;
		$promptCategory = $unpackedData['promptCategory'] ?? '';

		$payload = (new static($data))
			->setMarkers($markers)
			->setRole(Role::get($role))
			->setPromptCategory($promptCategory)
		;

		static::setCustomCost($payload, $unpackedData);

		return $payload;
	}

	/**
	 * @inheritDoc
	 */
	public function getCost(): int
	{
		if (!is_null($this->customCost))
		{
			return $this->customCost;
		}

		$promptCode = $this->promptItem?->getCode();

		if (!empty(self::SPEC_CODES[$promptCode]) && Bitrix24::isDemoLicense())
		{
			return self::DEFAULT_USAGE_COST_DEMO_PLAN;
		}

		return self::DEFAULT_USAGE_COST;
	}

	/**
	 * Set prompt category.
	 *
	 * @param string $promptCategory
	 * @return $this
	 */
	public function getPromptCategory():string
	{
		return $this->promptCategory;
	}

	/**
	 * Set prompt category.
	 *
	 * @param string $promptCategory
	 * @return $this
	 */
	public function setPromptCategory(string $promptCategory):Prompt
	{
		$this->promptCategory = $promptCategory;
		return $this;
	}

	/**
	 * @inheritDoc
	 */
	public function shouldUseCache():bool
	{
		$useCache = in_array($this->promptCategory,($this->promptItem?->getCacheCategory() ?? []));
		$markers = $this->getMarkers();
		$messages = $this->engine?->getMessages() ?? [];
		$this->useCache = false;
		if(!$useCache){
			return false;
		}
		if(count($messages)>1){
			return false;
		}
		if(
			$markers['original_message']
			&& trim($markers['original_message']) != ''
			&& !in_array($this->promptCategory,['readonly_livefeed']))
		{
			return false;
		}
		if($markers['user_message'] && trim($markers['user_message']) != '')
		{
			return false;
		}
		$this->useCache = true;
		return $this->useCache;
	}
}
