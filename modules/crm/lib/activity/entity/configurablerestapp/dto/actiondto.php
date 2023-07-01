<?php

namespace Bitrix\Crm\Activity\Entity\ConfigurableRestApp\Dto;

use Bitrix\Crm\Activity\Entity\ConfigurableRestApp\Dto;

final class ActionDto extends \Bitrix\Crm\Dto\Dto
{
	public const TYPE_REDIRECT = 'redirect';
	public const TYPE_REST_EVENT = 'restEvent';
	public const TYPE_OPEN_REST_APP = 'openRestApp';

	public const ANIMATION_TYPE_LOADER = 'loader';
	public const ANIMATION_TYPE_DISABLE = 'disable';

	public ?string $type = null;
	public ?string $uri = null; // for `redirect` action
	public ?string $id = null; // for `restEvent` action
	public ?array $actionParams = null; // for `restEvent` and `openRestApp` actions
	public ?string $animationType = null; // for `restEvent` action

	public function getCastByPropertyName(string $propertyName): ?\Bitrix\Crm\Dto\Caster
	{
		switch ($propertyName)
		{
			case 'actionParams':
				return new \Bitrix\Crm\Dto\Caster\CollectionCaster(new \Bitrix\Crm\Dto\Caster\StringCaster());
		}

		return null;
	}

	protected function getValidators(array $fields): array
	{
		$validators = [];
		switch ($fields['type'] ?? null)
		{
			case self::TYPE_REDIRECT:
				$validators[] = new \Bitrix\Crm\Dto\Validator\RequiredField($this, 'uri');
				break;
			case self::TYPE_OPEN_REST_APP:
				$validators[] = new \Bitrix\Crm\Dto\Validator\ScalarCollectionField($this,'actionParams', 20);
				break;
			case self::TYPE_REST_EVENT:
				$validators[] = new \Bitrix\Crm\Dto\Validator\RequiredField($this, 'id');
				$validators[] = new \Bitrix\Crm\Dto\Validator\ScalarCollectionField($this, 'actionParams', 20);
				$validators[] = new \Bitrix\Crm\Dto\Validator\EnumField($this, 'animationType', [
					Dto\ActionDto::ANIMATION_TYPE_DISABLE,
					Dto\ActionDto::ANIMATION_TYPE_LOADER,
				]);
				break;
			default:
				$validators[] = new \Bitrix\Crm\Dto\Validator\RequiredField($this, 'type');
				$validators[] = new \Bitrix\Crm\Dto\Validator\EnumField($this, 'type', [
					self::TYPE_REDIRECT,
					self::TYPE_OPEN_REST_APP,
					self::TYPE_REST_EVENT,
				]);
		}

		return $validators;
	}
}
