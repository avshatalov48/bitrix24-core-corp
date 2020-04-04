<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage intranet
 * @copyright 2001-2019 Bitrix
 */

namespace Bitrix\Crm\WebForm\Embed;

use Bitrix\Main;
use Bitrix\Crm\Entity\Identificator\ComplexCollection;

/**
 * Class Hash
 * @package Bitrix\Crm\WebForm\Embed
 */
class Sign
{
	const uriParameterName = 'b24form_user';
	const signSalt = 'site.form.hash';
	const signTime = '+14 day';
	const delimiterDataItem = '.';
	const delimiterDataList = '_';
	const delimiterData = ';';
	const delimiterSign = '-';

	/** @var ComplexCollection */
	protected $entities;

	/**
	 * Hash constructor.
	 */
	public function __construct()
	{
		$this->entities = new ComplexCollection();
	}

	/**
	 * Add entity.
	 *
	 * @param int $entityTypeId Entity type ID.
	 * @param int $entityId Entity Id.
	 * @return $this
	 */
	public function addEntity(int $entityTypeId, int $entityId): self
	{
		$this->entities->addIdentificator($entityTypeId, $entityId);
		return $this;
	}

	/**
	 * Set entities.
	 *
	 * @param ComplexCollection $entities Entities.
	 * @return $this
	 */
	public function setEntities(ComplexCollection $entities): self
	{
		$this->entities = $entities;
		return $this;
	}

	/**
	 * Get entities.
	 *
	 * @return ComplexCollection
	 */
	public function getEntities(): ComplexCollection
	{
		return $this->entities;
	}

	/**
	 * Unpack hash.
	 * @param string $hash Hash.
	 * @return bool
	 */
	public function unpack(string $hash): bool
	{
		if (!$hash)
		{
			return false;
		}

		try
		{
			$data = $this->getSigner()->unsign($hash, self::signSalt);
		}
		catch (Main\Security\Sign\BadSignatureException $exception)
		{
			return false;
		}

		$data = explode(self::delimiterData, $data);
		$entities = $data[0];
		if ($entities)
		{
			foreach (explode(self::delimiterDataList, $entities) as $item)
			{
				$item = explode(self::delimiterDataItem, $item);
				if ($item[0] && $item[1])
				{
					$this->entities->addIdentificator($item[0], $item[1]);
				}
			}
		}

		return true;
	}

	private function getSigner(): Main\Security\Sign\TimeSigner
	{
		return (new Main\Security\Sign\TimeSigner())->setSeparator(self::delimiterSign);
	}

	/**
	 * Pack hash.
	 *
	 * @return string
	 */
	public function pack(): string
	{
		$data = [];
		$data[] = implode(
			self::delimiterDataList,
			array_map(
				function ($item)
				{
					return $item['ENTITY_TYPE_ID'] . self::delimiterDataItem . $item['ENTITY_ID'];
				},
				$this->entities->toSimpleArray()
			)
		);
		$data = implode(self::delimiterData, $data);

		return $this->getSigner()->sign($data, self::signTime, self::signSalt);
	}
}
