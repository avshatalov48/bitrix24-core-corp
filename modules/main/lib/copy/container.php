<?
namespace Bitrix\Main\Copy;

/**
 * Class works with data that is needed in the process of copying the entity.
 *
 * @package Bitrix\Main\Copy
 */
class Container
{
	protected $entityId;
	protected $copiedEntityId;

	public function __construct($entityId)
	{
		$this->entityId = (int) $entityId;
	}

	/**
	 * Returns the id of the parent entity that is being copied.
	 *
	 * @return integer
	 */
	public function getEntityId()
	{
		return $this->entityId;
	}

	/**
	 * Writes a copied entity id.
	 *
	 * @param integer $id A copied entity id.
	 */
	public function setCopiedEntityId($id)
	{
		$this->copiedEntityId = (int) $id;
	}

	/**
	 * Returns a copied entity id.
	 *
	 * @return integer|null
	 */
	public function getCopiedEntityId()
	{
		return $this->copiedEntityId;
	}
}