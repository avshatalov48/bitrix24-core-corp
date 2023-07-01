<?php
namespace Bitrix\Crm\Automation\Trigger\Sign;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main;
use Bitrix\Sign;
use Bitrix\Crm;
use Bitrix\Crm\Automation;

Loc::loadMessages(__FILE__);

class InitiatorSignedTrigger extends Automation\Trigger\BaseTrigger
{
	public static function isEnabled()
	{
		return Main\Loader::includeModule('sign')
			&& Sign\Config\Storage::instance()->isAvailable()
		;
	}

	public static function isSupported($entityTypeId)
	{
		return ($entityTypeId === \CCrmOwnerType::Deal || $entityTypeId === \CCrmOwnerType::SmartDocument);
	}

	public static function executeBySmartDocumentId(
		int $smartDocumentId,
		array $inputData = null
	): Main\Result
	{
		$bindings = [
			[
				'OWNER_ID' => $smartDocumentId,
				'OWNER_TYPE_ID' => \CCrmOwnerType::SmartDocument,
			]
		];
		$itemId = new Crm\ItemIdentifier(
			\CCrmOwnerType::SmartDocument,
			$smartDocumentId
		);
		$itemId = (new Crm\Relation\RelationManager)->getParentElements($itemId)[0] ?? null;
		if (
			$itemId
			&& $itemId->getEntityId()
			&& $itemId->getEntityTypeId() === \CCrmOwnerType::Deal
		)
		{
			$bindings[] = [
				'OWNER_ID' => $itemId->getEntityId(),
				'OWNER_TYPE_ID' => $itemId->getEntityTypeId(),
			];
		}

		return static::execute($bindings, $inputData);
	}

	public static function getCode()
	{
		return 'SIGN_INITIATOR_SIGNING';
	}

	public static function getName()
	{
		return Loc::getMessage('CRM_AUTOMATION_TRIGGER_SIGN_INITIATOR_SIGNED_NAME_2');
	}

	public static function getGroup(): array
	{
		return ['paperwork'];
	}

	public static function getDescription(): string
	{
		return Loc::getMessage('CRM_AUTOMATION_TRIGGER_SIGN_INITIATOR_SIGNED_DESCRIPTION') ?? '';
	}

	public static function toArray()
	{
		$result = parent::toArray();
		if (
			static::isEnabled()
			&& Main\Loader::includeModule('bitrix24')
			&& !\Bitrix\Bitrix24\Feature::isFeatureEnabled('sign_automation')
		)
		{
			$result['LOCKED'] = [
				'INFO_CODE' => 'limit_crm_sign_automation',
			];
		}

		return $result;
	}
}