<?php

namespace Bitrix\AI\Tuning;

/**
 * Container for onSave callback function
 */
class OnSave
{
	public const TYPES = [
		'default' => 'default',
		'flag' => 'flag',
	];

	/**
	 * @var callable|null
	 */
	private mixed $callback;
	private string $type;
	private ?string $title = null;
	private bool $active = false;

	public function __construct(callable $callback)
	{
		$this->type = self::TYPES['default'];
		$this->callback = $callback;
	}

	public function setSwitcher(string $title): static
	{
		$this->type = self::TYPES['flag'];
		$this->title = $title;

		return $this;
	}

	/**
	 * Activate callback
	 * @return $this
	 */
	public function activate(): static
	{
		$this->active = true;

		return $this;
	}

	/**
	 * Deactivate callback, if can (default always on))
	 * @return void
	 */
	public function deactivate(): void
	{
		if ($this->type === self::TYPES['flag'])
		{
			$this->active = false;
		}
	}

	public function call(): static
	{
		if (
			$this->active
			&& is_callable($this->callback)
		)
		{
			call_user_func($this->callback);
		}

		return $this;
	}

	public function toArray(): array
	{
		$data = [
			'type' => $this->type,
			'callback' => $this->callback,
		];
		if (
			$this->type === self::TYPES['flag']
			&& $this->title
		)
		{
			$data['switcher'] = $this->title;
		}

		return $data;
	}
}
