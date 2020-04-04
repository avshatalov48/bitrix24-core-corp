<?
namespace Bitrix\Main\Copy;

/**
 * Class for working with containers to copy.
 *
 * @package Bitrix\Main\Copy
 */
class ContainerManager
{
	/**
	 * @var Container[]
	 */
	private $containers = [];

	/**
	 * Adds container to copy.
	 *
	 * @param Container $container
	 */
	public function addContainer(Container $container)
	{
		$this->containers[$container->getEntityId()] = $container;
	}

	/**
	 * Returns containers.
	 *
	 * @return Container[]
	 */
	public function getContainers()
	{
		return $this->containers;
	}

	/**
	 * Checks if there are containers to copy.
	 *
	 * @return bool
	 */
	public function isEmpty()
	{
		return empty($this->containers);
	}
}