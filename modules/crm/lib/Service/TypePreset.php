<?php

namespace Bitrix\Crm\Service;

use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\Web\Json;

class TypePreset implements \JsonSerializable
{
	protected $id;
	protected $isDisabled = false;
	protected $title;
	protected $category;
	protected $description;
	protected $icon;
	protected $data;

	/**
	 * TypePreset constructor.
	 * @param array $fields
	 * @param array $data
	 * @throws ArgumentNullException
	 */
	public function __construct(array $fields, array $data)
	{
		$this->setTitle($fields['title'])
			->setId($fields['id'])
			->setDisabled((bool)$fields['disabled'])
			->setCategory((string)$fields['category'])
			->setDescription((string)$fields['description'])
			->setIcon((string)$fields['icon'])
			->setData($data);
	}

	/**
	 * Creates new preset from json string.
	 *
	 * @param string $json
	 * @return TypePreset|null
	 */
	public static function createFromJson(string $json): ?TypePreset
	{
		try
		{
			$parsedData = Json::decode($json);
			if (isset($parsedData['fields'], $parsedData['data']))
			{
				return new TypePreset($parsedData['fields'], $parsedData['data']);
			}
		}
		finally
		{
			//do nothing
		}

		return null;
	}

	/**
	 * Prepares data for json.
	 *
	 * @return array
	 */
	public function jsonSerialize(): array
	{
		return [
			'fields' => [
				'id' => $this->getId(),
				'title' => $this->getTitle(),
				'category' => $this->getCategory(),
				'description' => $this->getDescription(),
				'icon' => $this->getIcon(),
			],
			'data' => $this->getData(),
		];
	}

	/**
	 * Return title of the preset.
	 *
	 * @return string
	 */
	public function getTitle(): string
	{
		return $this->title;
	}

	/**
	 * Set title of the preset.
	 *
	 * @param string $title
	 * @return $this
	 * @throws ArgumentNullException
	 */
	public function setTitle(string $title): self
	{
		if (empty($title))
		{
			throw new ArgumentNullException('title');
		}
		$this->title = $title;
		return $this;
	}

	/**
	 * Return category code of the preset.
	 *
	 * @return string
	 */
	public function getCategory(): string
	{
		return $this->category;
	}

	/**
	 * Set category code of the preset.
	 *
	 * @param string $category
	 * @return $this
	 */
	public function setCategory(string $category): self
	{
		$this->category = $category;
		return $this;
	}

	/**
	 * Get description of the preset.
	 *
	 * @return string|null
	 */
	public function getDescription(): ?string
	{
		return $this->description;
	}

	/**
	 * Set description of the preset.
	 *
	 * @param string $description
	 * @return $this
	 */
	public function setDescription(string $description): self
	{
		$this->description = $description;

		return $this;
	}

	/**
	 * Get path to the icon of the preset.
	 *
	 * @return string
	 */
	public function getIcon(): string
	{
		return $this->icon;
	}

	/**
	 * Set path to the icon of the preset.
	 *
	 * @param string $icon
	 * @return $this
	 */
	public function setIcon(string $icon): self
	{
		$this->icon = $icon;
		return $this;
	}

	/**
	 * Return data of the preset, containing settings for creating new dynamic type.
	 *
	 * @return array
	 */
	public function getData(): array
	{
		return $this->data;
	}

	/**
	 * Return data of the preset, containing settings for creating new dynamic type.
	 *
	 * @param array $data
	 * @return $this
	 */
	public function setData(array $data): self
	{
		$this->data = $data;
		return $this;
	}

	/**
	 * Return identifier of this preset.
	 *
	 * @return string
	 */
	public function getId(): string
	{
		return $this->id;
	}

	/**
	 * Set identifier of this preset
	 *
	 * @param string $id
	 * @return $this
	 * @throws ArgumentNullException
	 */
	public function setId(string $id): self
	{
		if (empty($id))
		{
			throw new ArgumentNullException('id');
		}
		$this->id = $id;
		return $this;
	}

	/**
	 * Return disabled status of this preset.
	 *
	 * @return bool
	 */
	public function isDisabled(): bool
	{
		return $this->isDisabled;
	}

	/**
	 * Set disabled status of this preset
	 *
	 * @param bool $isDisabled
	 * @return $this
	 */
	public function setDisabled(bool $isDisabled): TypePreset
	{
		$this->isDisabled = $isDisabled;
		return $this;
	}
}
