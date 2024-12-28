<?php

namespace Bitrix\Sign\Config;

use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sign\Access\AccessController;
use Bitrix\Sign\Access\ActionDictionary;
use Bitrix\Sign\Service\Providers\LegalInfoProvider;
use Bitrix\UI\Form\EntityEditorConfigScope;
use Bitrix\UI\Form\EntityEditorConfiguration;

class LegalInfo
{
	public const USER_FIELD_ENTITY_ID = 'USER_LEGAL';
	public const EDITOR_CONFIG_CATEGORY_NAME = 'sign.legal.form.editor';
	public const EDITOR_CONFIG_CONFIG_ID = 'legal_info_form';

	public static function unsetConfigForProfileEntityEditor(): void
	{
		(new EntityEditorConfiguration(self::EDITOR_CONFIG_CATEGORY_NAME))
			->reset(self::EDITOR_CONFIG_CONFIG_ID, ['scope' => EntityEditorConfigScope::COMMON]);
	}

	public static function onProfileConfigAdditionalBlocks(Event $event): EventResult
	{
		$user = CurrentUser::get();
		$userId = (int)$user?->getId();
		$profileId = (int)$event->getParameter('profileId');
		$fields = (new LegalInfoProvider())->getUserFields();

		if (
			!Storage::instance()->isB2eAvailable()
			|| !User::instance()->canUserParticipateInSigning($profileId)
			|| !self::canView($userId)
			|| empty($fields)
		)
		{
			return new EventResult(EventResult::ERROR);
		}

		$componentParams = [
			'PROFILE_ID' => $profileId,
		];

		return new EventResult(
			EventResult::SUCCESS,
			[
				'additionalBlock' => [
					'TITLE' => Loc::getMessage('SIGN_USER_PROFILE_LEGAL_SECTION_CONTACT_TITLE'),
					'COMPONENT_NAME' => 'bitrix:sign.userprofile.legal.block',
					'COMPONENT_PARAMS' => $componentParams,
				],
			],
			'sign'
		);
	}

	public static function getElementsForEntityEditor($userFields): array
	{
		return array_map(
			static fn($property) => [
				'name' => $property['FIELD_NAME'],
				'optionFlags' => 0,
			],
			$userFields
		);
	}

	public static function canView(int $userId): bool
	{
		return (new AccessController($userId))->check(ActionDictionary::ACTION_B2E_PROFILE_FIELDS_READ);
	}

	public static function canEdit(int $userId): bool
	{
		return (new AccessController($userId))->check(ActionDictionary::ACTION_B2E_PROFILE_FIELDS_EDIT);
	}

	public static function canAdd(int $userId): bool
	{
		return (new AccessController($userId))->check(ActionDictionary::ACTION_B2E_PROFILE_FIELDS_ADD);
	}

	public static function canDelete(int $userId): bool
	{
		return (new AccessController($userId))->check(ActionDictionary::ACTION_B2E_PROFILE_FIELDS_DELETE);
	}
}
