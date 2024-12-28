<?php

namespace Bitrix\AI\Tuning;

/**
 * Settings field class
 */
class Item
{
	private const AVAILABLE_TYPES = [
		Type::BOOLEAN,
		Type::LIST,
	];

	private mixed $value;

	/**
	 * Values for list type (selector)
	 * @var array
	 */
	private array $options = [];

	private array $recommended = [];

	/**
	 * Field sort in group
	 * @var int|null
	 */
	private ?int $sort = null;

	/**
	 * On field save callback
	 * @var OnSave|null
	 */
	private ?OnSave $onSave = null;

	/**
	 * Additional options for different purposes.
	 * @var array
	 */
	private array $additional = [];

	private function __construct(
		private string $code,
		private string $title,
		private string $type,
		private ?string $header,
	) {}

	/**
	 * Creates tuning Item by raw data.
	 *
	 * @param string $code Unique tuning code.
	 * @param array $data Raw data.
	 * @return self|null
	 */
	public static function create(string $code, array $data): ?self
	{
		if (
			!is_string($data['title'] ?? null)
			|| !is_string($data['type'] ?? null)
			|| !in_array($data['type'], self::AVAILABLE_TYPES)
		)
		{
			return null;
		}

		$item = new self(
			$code,
			$data['title'],
			$data['type'],
			$data['header'] ?? null
		);

		if (!empty($data['sort']) && is_int($data['sort']))
		{
			$item->setSort($data['sort']);
		}

		if (
			isset($data['options'])
			&& is_array($data['options'])
		)
		{
			$item->setOptions($data['options']);
		}

		if (
			isset($data['onSave'])
			&& is_array($data['onSave'])
			&& is_callable($data['onSave']['callback'])
		)
		{
			$onSave = new OnSave($data['onSave']['callback']);
			if ($data['onSave']['switcher'])
			{
				$onSave->setSwitcher((string)$data['onSave']['switcher']);
			}
			$item->setOnSave($onSave);
		}

		if (
			isset($data['additional'])
			&& is_array($data['additional'])
		)
		{
			$item->setAdditional($data['additional']);
		}

		if (!empty($data['recommended']))
		{
			$item->setRecommended($data['recommended']);
		}

		return $item;
	}

	public function getCode(): string
	{
		return $this->code;
	}

	public function getTitle(): string
	{
		return $this->title;
	}

	public function getHeader(): ?string
	{
		return $this->header;
	}

	/**
	 * @return int|null
	 */
	public function getSort(): ?int
	{
		return $this->sort;
	}

	/**
	 * @param int $sort - value of sorting, less sort will be higher
	 */
	public function setSort(int $sort): void
	{
		$this->sort = $sort;
	}

	public function getOnSave(): ?OnSave
	{
		return $this->onSave;
	}

	public function setOnSave(OnSave $onSave): void
	{
		$this->onSave = $onSave;
	}

	public function onSave(): void
	{
		$this->getOnSave()?->call();
	}

	public function getAdditional(): array
	{
		return $this->additional;
	}

	public function setAdditional(?array $additional): void
	{
		$this->additional = $additional;
	}

	public function isBoolean(): bool
	{
		return $this->type === Type::BOOLEAN;
	}

	public function isList(): bool
	{
		return $this->type === Type::LIST;
	}

	public function setValue(mixed $value): void
	{
		switch ($this->type)
		{
			case Type::BOOLEAN:
				$this->value =
					$value === true
					|| mb_strtolower($value) === 'true'
					|| (string)$value === 'Y'
					|| (int)$value === 1
				;
				break;

			case Type::LIST:
				$this->value =
					isset($value, $this->options[$value])
						? $value
						: ($this->value ?? current(array_keys($this->options)))
				;
				break;
		}
	}

	/**
	 * Set options, if list element
	 * @param array $options - array of code => option
	 * @return void
	 */
	public function setOptions(array $options): void
	{
		if ($this->type === Type::LIST)
		{
			$this->options = $options;
		}
	}

	/**
	 * Return options of list element
	 * @return array of code => option
	 */
	public function getOptions(): array
	{
		if ($this->type === Type::LIST)
		{
			return $this->options;
		}

		return [];
	}

	/**
	 * Set preferred option for list item
	 * @param string|array $optionCodes `preferred` options
	 * @return void
	 */
	public function setRecommended(string|array $optionCodes): void
	{
		if ($this->type == Type::LIST)
		{
			foreach ((array)$optionCodes as $code)
			{
				if (isset($this->getOptions()[$code]))
				{
					$this->recommended[] = $code;
				}
			}
		}
	}

	/**
	 * Get preferred option for list item
	 * @return array of stings (code of options)
	 */
	public function getRecommended(): array
	{
		$recommended = [];
		if ($this->type === Type::LIST)
		{
			foreach ($this->recommended as $code)
			{
				if (isset($this->getOptions()[$code]))
				{
					$recommended[] = $code;
				}
			}
		}

		return $recommended;
	}

	public function getValue(): mixed
	{
		return $this->value;
	}

	/**
	 * Return scalar data of item
	 *
	 * @return array
	 */
	public function toArray(): array
	{
		$itemData = [
			'code' => $this->code,
			'title' => $this->title,
			'type' => $this->type,
			'header' => $this->header ?? '',
		];
		if ($this->type === Type::LIST)
		{
			$itemData['options'] = $this->getOptions();
			$itemData['recommended'] = $this->getRecommended();
		}
		if (isset($this->value))
		{
			$itemData['value'] = $this->value;
		}
		if ($this->getOnSave())
		{
			$itemData['onSave'] = $this->onSave->toArray();
		}
		if ($this->getAdditional())
		{
			$itemData['additional'] = [];
			foreach ($this->getAdditional() as $key => $value)
			{
				if (is_string($value) || is_numeric($value))
				{
					$itemData['additional'][$key] = $value;
				}
				else
				{
					$itemData['additional'][$key] = (bool)$value;
				}
			}
		}

		return $itemData;
	}
}
