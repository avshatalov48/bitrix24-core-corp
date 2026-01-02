<?php

namespace Bitrix\HumanResources\Type;

use Bitrix\HumanResources\Internals\Trait\ValuesTrait;

enum AccessCodeType: string
{
	case HrStructureNodeType = 'SN';
	case IntranetDepartmentType = 'D';
	case IntranetDepartmentRecursiveType = 'DR';
	case HrTeamType = 'SNT';
	case HrTeamRecursiveType = 'SNTR';
	case HrDepartmentType = 'SND';
	case HrDepartmentRecursiveType = 'SNDR';

	/**
	 * @return list<self>
	 */
	public static function getRecursiveTypes(): array
	{
		return [
			self::HrTeamRecursiveType,
			self::HrDepartmentRecursiveType,
			self::IntranetDepartmentRecursiveType,
		];
	}

	/**
	 * @return list<string>
	 */
	public static function getRecursiveTypesPrefixes(): array
	{
		return array_map(
			fn(AccessCodeType $type) => $type->value,
			self::getRecursiveTypes(),
		);
	}

	/**
	 * @return list<self>
	 */
	public static function getTeamTypes(): array
	{
		return [
			self::HrTeamRecursiveType,
			self::HrTeamType,
		];
	}

	/**
	 * @return list<string>
	 */
	public static function getTeamTypesPrefixes(): array
	{
		return array_map(
			fn(AccessCodeType $type) => $type->value,
			self::getTeamTypes(),
		);
	}

	/**
	 * @return list<self>
	 */
	public static function getIntranetDepartmentTypes(): array
	{
		return [
			self::IntranetDepartmentType,
			self::IntranetDepartmentRecursiveType,
		];
	}

	/**
	 * @return list<string>
	 */
	public static function getIntranetDepartmentTypesPrefixes(): array
	{
		return array_map(
			fn(AccessCodeType $type) => $type->value,
			self::getIntranetDepartmentTypes(),
		);
	}

	/**
	 * @return list<self>
	 */
	public static function getDepartmentTypes(): array
	{
		return [
			self::HrDepartmentRecursiveType,
			self::HrDepartmentType,
		];
	}

	public function buildAccessCode(int $nodeId): string
	{
		return $this->value . $nodeId;
	}

	/**
	 * @return list<string>
	 */
	public function buildAccessCodes(int ...$nodeIds): array
	{
		return array_map(
			fn($nodeId) => $this->buildAccessCode($nodeId),
			$nodeIds,
		);
	}

	/**
	 * Returns all structure access code types that are used together with nodeId.
	 * @return array
	 */
	public static function getAllStructureTypes(): array
	{
		return [
			AccessCodeType::HrStructureNodeType,
			AccessCodeType::HrTeamType,
			AccessCodeType::HrTeamRecursiveType,
			AccessCodeType::HrDepartmentType,
			AccessCodeType::HrDepartmentRecursiveType,
		];
	}

	/**
	 * @return array
	 */
	public static function getAllStructurePrefixes(): array
	{
		return array_map(
			fn(AccessCodeType $type) => $type->value,
			self::getAllStructureTypes(),
		);
	}

	use ValuesTrait;
}
