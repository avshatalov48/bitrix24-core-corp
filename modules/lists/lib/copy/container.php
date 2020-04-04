<?php
namespace Bitrix\Lists\Copy;

use Bitrix\Main\Copy\Container as BaseContainer;

class Container extends BaseContainer
{
	private $iblockTypeId;
	private $socnetGroupId;

	private $targetIblockTypeId;
	private $targetSocnetGroupId;

	/**
	 * Writes a iblock type id to copy iblocks.
	 *
	 * @param string $iblockTypeId
	 */
	public function setIblockTypeId($iblockTypeId)
	{
		$this->iblockTypeId = (string) $iblockTypeId;
	}

	/**
	 * Returns a  iblock type id to copy iblocks.
	 *
	 * @return string
	 */
	public function getIblockTypeId()
	{
		return $this->iblockTypeId;
	}

	/**
	 * Writes a group id to copy iblocks.
	 *
	 * @param integer $socnetGroupId Group id.
	 */
	public function setSocnetGroupId($socnetGroupId)
	{
		$this->socnetGroupId = (int) $socnetGroupId;
	}

	/**
	 * Returns a group id to copy iblocks.
	 *
	 * @return integer
	 */
	public function getSocnetGroupId()
	{
		return $this->socnetGroupId;
	}

	/**
	 * Writes a target iblock type id to copy iblocks.
	 *
	 * @param string $targetIblockTypeId
	 */
	public function setTargetIblockTypeId($targetIblockTypeId)
	{
		$this->targetIblockTypeId = (string) $targetIblockTypeId;
	}

	/**
	 * Returns a target iblock type id to copy iblocks.
	 *
	 * @return string
	 */
	public function getTargetIblockTypeId()
	{
		return $this->targetIblockTypeId;
	}

	/**
	 * Writes a target group id to copy iblocks.
	 *
	 * @param integer $targetSocnetGroupId Group id.
	 */
	public function setTargetSocnetGroupId($targetSocnetGroupId)
	{
		$this->targetSocnetGroupId = (int) $targetSocnetGroupId;
	}

	/**
	 * Returns a target group id to copy iblocks.
	 *
	 * @return integer
	 */
	public function getTargetSocnetGroupId()
	{
		return $this->targetSocnetGroupId;
	}
}