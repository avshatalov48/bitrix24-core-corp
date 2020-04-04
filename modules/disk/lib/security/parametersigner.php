<?php

namespace Bitrix\Disk\Security;


use Bitrix\Main\Security\Sign\Signer;

final class ParameterSigner
{
	const SALT_IMAGE_SIZE     = 'disk.image.size';
	const SALT_ARCHIVE        = 'disk.archive';
	const SALT_ENTITY_ARCHIVE = 'disk.entity.archive';

	/**
	 * Returns signature for image by width and height.
	 * It is used to prevent unnecessary requests to resize image.
	 *
	 * @param string $id Id of image.
	 * @param int $width Width (px).
	 * @param int $height Height (px).
	 * @return string
	 * @throws \Bitrix\Main\ArgumentTypeException
	 */
	public static function getImageSignature($id, $width, $height)
	{
		$sign = new Signer;
		return $sign->getSignature($id . '|' . (int)$width . 'x' . (int)$height , self::SALT_IMAGE_SIZE);
	}

	/**
	 * Validates signature for image.
	 * @see ParameterSigner::getImageSignature().
	 *
	 * @param string $signature Signature to check.
	 * @param string $id Id of image.
	 * @param int $width Width (px).
	 * @param int $height Height (px).
	 * @return bool
	 */
	public static function validateImageSignature($signature, $id, $width, $height)
	{
		$sign = new Signer;
		return $sign->validate($id . '|' . (int)$width . 'x' . (int)$height, $signature, self::SALT_IMAGE_SIZE);
	}

	/**
	 * Returns signature for archive by ids of files which will be archived.
	 * It is used to prevent unnecessary requests to make archive.
	 *
	 * @param array $ids List of id.
	 * @return string
	 * @throws \Bitrix\Main\ArgumentTypeException
	 */
	public static function getArchiveSignature(array $ids)
	{
		$sign = new Signer;
		return $sign->getSignature(self::prepareList($ids), self::SALT_ARCHIVE);
	}

	/**
	 * Returns signature for archive which are created by entity.
	 * It is used to prevent unnecessary requests to make archive.
	 *
	 * @param string $entity Entity name.
	 * @param string|int $entityId Entity id.
	 * @param string $fieldName Field name.
	 *
	 * @return string
	 */
	public static function getEntityArchiveSignature($entity, $entityId, $fieldName)
	{
		$sign = new Signer;

		return $sign->getSignature(implode('|', array($entity, $entityId, $fieldName)), self::SALT_ENTITY_ARCHIVE);
	}

	/**
	 * Validates signature for archive which are created by entity.
	 * @see ParameterSigner::getEntityArchiveSignature().
	 *
	 * @param string $signature Signature to check.
	 * @param string $entity Entity name.
	 * @param string|int $entityId Entity id.
	 * @param string $fieldName Field name.
	 *
	 * @return bool
	 */
	public static function validateEntityArchiveSignature($signature, $entity, $entityId, $fieldName)
	{
		$sign = new Signer;

		return $sign->validate(
			implode('|', array($entity, $entityId, $fieldName)),
			$signature,
			self::SALT_ENTITY_ARCHIVE
		);
	}

	/**
	 * Validates signature for archive.
	 * @see ParameterSigner::getImageSignature().

	 * @param string $signature Signature to check.
	 * @param array $ids List of id.
	 * @return bool
	 */
	public static function validateArchiveSignature($signature, array $ids)
	{
		$sign = new Signer;
		return $sign->validate(self::prepareList($ids), $signature, self::SALT_ARCHIVE);
	}

	private static function prepareList(array $list)
	{
		$list = array_values($list);
		sort($list);

		return implode(',', $list);
	}
}