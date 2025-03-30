<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Integration\Ui\EntityEditor;

use Bitrix\Booking\Entity\Resource\Resource;
use Bitrix\Booking\Provider\Params\GridParams;
use Bitrix\Booking\Provider\ResourceTypeProvider;
use Bitrix\UI\EntityEditor\BaseProvider;
use Bitrix\Main\Engine\CurrentUser;
use DateTimeImmutable;

class ResourceProvider extends BaseProvider
{
	private ResourceTypeProvider $resourceTypeProvider;

	public function __construct(
		private readonly ?Resource $resource,
	)
	{
		$this->resourceTypeProvider = new ResourceTypeProvider();
	}

	public function getGUID(): string
	{
		return 'BOOKING_RESOURCE';
	}

	public function getEntityId(): ?int
	{
		if ($this->resource)
		{
			return $this->resource->getId();
		}

		return null;
	}

	public function getEntityTypeName(): string
	{
		return 'booking_resource';
	}

	public function getEntityFields(): array
	{
		$exists = isset($this->resource);

		return [
			[
				'name' => 'ID',
				'title' => 'ID',
				'editable' => false,
				'type' => 'text',
			],
			[
				'name' => 'NAME',
				'title' => 'NAME',
				'editable' => true,
				'type' => 'text',
			],
			[
				'name' => 'TYPE_ID',
				'title' => 'TYPE_ID',
				'type' => 'list',
				'data' => [
					'items' => array_map(
						function($type)
						{
							return ['VALUE' => $type['id'], 'NAME' => $type['name']];
						},
						$this->getResourceTypes()
					),
				],
				'required' => true,
			],
			[
				'name' => 'DESCRIPTION',
				'title' => 'DESCRIPTION',
				'editable' => true,
				'type' => 'textarea',
			],
			[
				'name' => 'CREATED_AT',
				'title' => 'CREATED_AT',
				'editable' => false,
				'type' => $exists ? 'datetime' : 'hidden',
			],
			[
				'name' => 'UPDATED_AT',
				'title' => 'UPDATED_AT',
				'editable' => false,
				'type' => $exists ? 'datetime' : 'hidden',
			],
		];
	}

	public function getEntityConfig(): array
	{
		$config = [
			[
				'type' => 'column',
				'name' => 'default_column',
				'elements' => [
					[
						'name' => 'main',
						'title' => 'О ресурсе',
						'type' => 'section',
						'elements' => [
							['name' => 'ID'],
							['name' => 'NAME'],
							['name' => 'TYPE_ID'],
							['name' => 'DESCRIPTION'],
						],
					]
				]
			]
		];

		if (isset($this->resource)) {
			$config[] = [
				'type' => 'section',
				'name' => 'additional',
				'title' => 'Системная информация',
				'editable' => false,
				'elements' => [
					['name' => 'CREATED_AT'],
					['name' => 'CREATED_BY'],
					['name' => 'UPDATED_AT'],
				],
				'data' => [
					'enableToggling' => false,
				],
			];
		}

		return $config;
	}

	public function getEntityData(): array
	{
		if (!isset($this->resource)) {
			return ['CREATED_BY' => CurrentUser::get()->getId()];
		}

		return [
			'ID' => $this->resource->getId(),
			'NAME' => $this->resource->getName(),
			'TYPE_ID' => $this->resource->getType()->getId(),
			'DESCRIPTION' => $this->resource->getDescription(),
			'CREATED_AT' => $this->prepareDateTime($this->resource->getCreatedAt()),
			'UPDATED_AT' => $this->prepareDateTime($this->resource->getUpdatedAt()),
			'CREATED_BY' => $this->resource->getCreatedBy(),
		];
	}

	private function prepareDateTime(int $timestamp): string
	{
		return (new DateTimeImmutable('@' . $timestamp))
			->format('d-m-Y H:i:s');
	}

	private function getResourceTypes(): array
	{
		$typesCollection = $this->resourceTypeProvider->getList(
			new GridParams(
				limit: 100,
			),
			userId: (int)CurrentUser::get()->getId(),
		);

		return $typesCollection->toArray();
	}
}
