<?php
namespace Bitrix\Sign\Access;

use Bitrix\Main\Localization\Loc;
use Bitrix\Sign\Access\Permission\PermissionDictionary;
use Bitrix\Sign\Access\Permission\SignPermissionDictionary;
use Bitrix\Sign\Service\Container;
use ReflectionClass;

class SectionDictionary
{
	const B2B = 1;
	const B2E = 2;
	const ACCESS = 3;

	/**
	 * @return array[]
	 */
	public static function getMap(): array
	{
		$map = [
			self::B2B => [
				PermissionDictionary::SIGN_CRM_CONTACT_READ,
				PermissionDictionary::SIGN_CRM_SMART_DOCUMENT_READ,
				PermissionDictionary::SIGN_CRM_SMART_DOCUMENT_ADD,
				PermissionDictionary::SIGN_CRM_SMART_DOCUMENT_WRITE,
				PermissionDictionary::SIGN_CRM_SMART_DOCUMENT_DELETE,
				SignPermissionDictionary::SIGN_MY_SAFE,
				SignPermissionDictionary::SIGN_MY_SAFE_DOCUMENTS,
				SignPermissionDictionary::SIGN_TEMPLATES,
			],
			self::B2E => [
				PermissionDictionary::SIGN_CRM_SMART_B2E_DOC_READ,
				PermissionDictionary::SIGN_CRM_SMART_B2E_DOC_ADD,
				PermissionDictionary::SIGN_CRM_SMART_B2E_DOC_WRITE,
				PermissionDictionary::SIGN_CRM_SMART_B2E_DOC_DELETE,
				SignPermissionDictionary::SIGN_B2E_PROFILE_FIELDS_READ,
				SignPermissionDictionary::SIGN_B2E_PROFILE_FIELDS_ADD,
				SignPermissionDictionary::SIGN_B2E_PROFILE_FIELDS_EDIT,
				SignPermissionDictionary::SIGN_B2E_PROFILE_FIELDS_DELETE,
				SignPermissionDictionary::SIGN_B2E_MY_SAFE,
				SignPermissionDictionary::SIGN_B2E_MY_SAFE_DOCUMENTS,
				SignPermissionDictionary::SIGN_B2E_TEMPLATES,
			],
			self::ACCESS => [
				SignPermissionDictionary::SIGN_ACCESS_RIGHTS,
			],
		];

		if (!\Bitrix\Sign\Config\Storage::instance()->isB2eAvailable())
		{
			unset($map[self::B2E]);
		}

		return $map;
	}

	protected static function getClassName(): string
	{
		return __CLASS__;
	}

	/**
	 * Getting a list of the permission settings
	 * @return array
	 */
	public static function getList(): array
	{
		$class = new ReflectionClass(__CLASS__);
		return array_flip($class->getConstants());
	}

	/**
	 * This method returning Localized title of the sections in Permission settings
	 * @param int $value
	 * @return string
	 */
	public static function getTitle(int $value): string
	{
		$sectionsList = self::getList();

		if (!array_key_exists($value, $sectionsList))
		{
			return '';
		}
		$title = $sectionsList[$value];

		return Loc::getMessage("SIGN_CONFIG_SECTIONS_".$title) ?? '';
	}
}
