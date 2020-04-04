<?php
namespace Bitrix\Main\Copy;

use Bitrix\Main\Result;

trait CompositeImplementation
{
	/**
	 * @var Result
	 */
	private $result;

	/**
	 * @var Copyable[]
	 */
	private $entitiesToCopy = [];

	/**
	 * Adding entities to be copied later by the parent.
	 *
	 * @param Copyable $entity
	 */
	public function addEntityToCopy(Copyable $entity)
	{
		$this->entitiesToCopy[] = $entity;
	}

	/**
	 * Starts copying added entities.
	 *
	 * @param ContainerManager $containerManager
	 */
	public function startCopyEntities(ContainerManager $containerManager)
	{
		foreach ($this->entitiesToCopy as $entity)
		{
			$result = $entity->copy($containerManager);
			$this->result->setData($result->getData());
			if ($result->getErrors())
			{
				$this->result->addErrors($result->getErrors());
			}
		}
	}
}