<?php

namespace Bitrix\Booking\Component\Booking;

use Bitrix\Booking\Internals\Container;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Filter\Theme;

class Filter
{
	private const FIELD_CREATED_BY = 'CREATED_BY';
	private const FIELD_CONTACT = 'CONTACT';
	private const FIELD_COMPANY = 'COMPANY';
	private const FIELD_RESOURCE = 'RESOURCE';
	private const FIELD_CONFIRMED = 'CONFIRMED';
	private const FIELD_DELAYED = 'DELAYED';

	private const PRESET_UNCONFIRMED = 'booking-filter-preset-unconfirmed';
	private const PRESET_DELAYED = 'booking-filter-preset-delayed';
	private const PRESET_CREATED_BY_ME = 'booking-filter-preset-created-by-me';

	public static function getId(): string
	{
		return 'BOOKING_FILTER_ID';
	}

	public function getOptions(): array
	{
		return [
			'FILTER_ID' => self::getId(),
			'FILTER' => $this->getFields(),
			'FILTER_PRESETS' => $this->getPresets(),
			'ENABLE_LABEL' => true,
			'ENABLE_LIVE_SEARCH' => true,
			'DISABLE_SEARCH' => true,
			'THEME' => Theme::MUTED,
		];
	}

	public function getFields(): array
	{
		$provider = Container::getProviderManager()::getCurrentProvider();

		$fields = [
			self::FIELD_CREATED_BY => $this->getCreatedByField(),
			self::FIELD_CONTACT => $this->getContactField(),
			self::FIELD_COMPANY => $this->getCompanyField(),
			self::FIELD_RESOURCE => $this->getResourceField(),
			self::FIELD_CONFIRMED => $this->getConfirmedField(),
			self::FIELD_DELAYED => $this->getDelayedField(),
		];

		if ($provider?->getModuleId() !== 'crm')
		{
			unset($fields[self::FIELD_CONTACT], $fields[self::FIELD_COMPANY]);
		}

		return $fields;
	}

	private function getCreatedByField(): array
	{
		return [
			'id' => self::FIELD_CREATED_BY,
			'name' => Loc::getMessage('BOOKING_FILTER_FIELD_CREATED_BY'),
			'type' => 'entity_selector',
			'params' => [
				'multiple' => 'Y',
				'dialogOptions' => [
					'context' => 'BOOKING',
					'entities' => [
						[
							'id' => 'user',
							'options' => [
								'inviteEmployeeLink' => false,
							],
						],
					],
				],
			],
			'default' => true,
		];
	}

	private function getContactField(): array
	{
		return [
			'id' => self::FIELD_CONTACT,
			'name' => Loc::getMessage('BOOKING_FILTER_FIELD_CONTACT'),
			'type' => 'entity_selector',
			'params' => [
				'multiple' => 'Y',
				'dialogOptions' => [
					'context' => 'BOOKING',
					'entities' => [
						[
							'id' => 'contact',
							'dynamicLoad' => true,
							'dynamicSearch' => true,
						],
					],
				],
			],
			'default' => true,
		];
	}

	private function getCompanyField(): array
	{
		return [
			'id' => self::FIELD_COMPANY,
			'name' => Loc::getMessage('BOOKING_FILTER_FIELD_COMPANY'),
			'type' => 'entity_selector',
			'params' => [
				'multiple' => 'Y',
				'dialogOptions' => [
					'context' => 'BOOKING',
					'entities' => [
						[
							'id' => 'company',
							'dynamicLoad' => true,
							'dynamicSearch' => true,
						],
					],
				],
			],
			'default' => true,
		];
	}

	private function getResourceField(): array
	{
		return [
			'id' => self::FIELD_RESOURCE,
			'name' => Loc::getMessage('BOOKING_FILTER_FIELD_RESOURCE'),
			'type' => 'entity_selector',
			'params' => [
				'multiple' => 'Y',
				'dialogOptions' => [
					'context' => 'BOOKING_FILTER',
					'showAvatars' => false,
					'entities' => [
						[
							'id' => 'resource',
							'dynamicLoad' => true,
							'dynamicSearch' => true,
						],
					],
				],
			],
			'default' => true,
		];
	}

	private function getConfirmedField(): array
	{
		return [
			'id' => self::FIELD_CONFIRMED,
			'name' => Loc::getMessage('BOOKING_FILTER_FIELD_CONFIRMED'),
			'type' => 'checkbox',
			'default' => false,
		];
	}

	private function getDelayedField(): array
	{
		return [
			'id' => self::FIELD_DELAYED,
			'name' => Loc::getMessage('BOOKING_FILTER_FIELD_DELAYED'),
			'type' => 'checkbox',
			'default' => false,
		];
	}

	public function getPresets(): array
	{
		return [
			self::PRESET_UNCONFIRMED => [
				'id' => self::PRESET_UNCONFIRMED,
				'name' => Loc::getMessage('BOOKING_FILTER_PRESET_UNCONFIRMED'),
				'default' => false,
				'fields' => [
					self::FIELD_CONFIRMED => 'N',
				],
			],
			self::PRESET_DELAYED => [
				'id' => self::PRESET_DELAYED,
				'name' => Loc::getMessage('BOOKING_FILTER_PRESET_DELAYED'),
				'default' => false,
				'fields' => [
					self::FIELD_DELAYED => 'Y',
				],
			],
			self::PRESET_CREATED_BY_ME => [
				'id' => self::PRESET_CREATED_BY_ME,
				'name' => Loc::getMessage('BOOKING_FILTER_PRESET_CREATED_BY_ME'),
				'default' => false,
				'fields' => [
					self::FIELD_CREATED_BY => CurrentUser::get()?->getId(),
					self::FIELD_CREATED_BY . '_label' => CurrentUser::get()?->getFormattedName(),
				],
			],
		];
	}
}
