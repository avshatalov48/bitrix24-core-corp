<?php

namespace Bitrix\SalesCenter\Fields;

use Bitrix\Crm\WebForm\Embed\Sign;
use Bitrix\Main\Web\Uri;
use Bitrix\SalesCenter\Model\Page;

class Manager
{
	protected $entities = [];
	protected $data = [];
	protected $ids = [];

	public function __construct()
	{
		$this
			->addEntity(new Entity\Deal())
			->addEntity(new Entity\Company())
			->addEntity(new Entity\Contact())
			->addEntity(new Entity\CurrentUser())
			->addEntity(new Entity\Assigned());
	}

	public function addEntity(Entity $entity): Manager
	{
		$this->entities[$entity->getCode()] = $entity;

		return $this;
	}

	public function setIds(array $ids): Manager
	{
		$this->ids = $ids;

		return $this;
	}

	public function getValue(string $param, int $id = null)
	{
		$chain = explode('.', $param);
		if(count($chain) <= 1)
		{
			return null;
		}
		$entityName = $chain[0];
		$entity = $this->getEntityByCode($entityName);
		if($entity)
		{
			if($id === null)
			{
				$id = $this->ids[$entityName];
			}
			if(!$id && $entity instanceof Entity\CurrentUser)
			{
				$id = $entity->getCurrentUserId();
			}
			if(!$id)
			{
				return null;
			}
			$data = $this->getEntityData($entity, $id);
			array_shift($chain);
			if(count($chain) === 1)
			{
				return $data[$chain[0]];
			}
			else
			{
				$id = (int) $data[$chain[0].'_ID'];
				if(!$id)
				{
					$id = 0;
				}
				return $this->getValue(implode('.', $chain), $id);
			}
		}
		else
		{
			return null;
		}
	}

	public function getEntityData(Entity $entity, int $id): ?array
	{
		if($id > 0)
		{
			if(!isset($this->data[$entity->getCode()][$id]))
			{
				$this->data[$entity->getCode()][$id] = $entity->loadData($id);
			}

			return $this->data[$entity->getCode()][$id];
		}

		return null;
	}

	/**
	 * @return Entity[]
	 */
	public function getEntities(): array
	{
		return $this->entities;
	}

	public function getEntityByCode(string $code): ?Entity
	{
		return $this->entities[$code];
	}

	public function getFieldsMap(): array
	{
		$result = [];

		foreach($this->getEntities() as $entity)
		{
			if($entity instanceof Entity\Assigned)
			{
				continue;
			}
			$result[] = [
				'name' => $entity->getCode(),
				'title' => $entity->getName(),
				'items' => $this->getEntityFields($entity, [(new Field($entity->getCode()))->setEntity($entity)])
			];
		}

		return $result;
	}

	public function getEntityFields(Entity $entity, array $previous = []): array
	{
		$result = [];

		$fields = $entity->getFields();
		foreach($fields as $field)
		{
			$fieldEntity = $field->getEntity();
			$chain = $fullName = '';
			foreach($previous as $item)
			{
				$fullName .= $item->getTitle().'.';
				$chain .= $item->getName().'.';
			}
			$fullName .= $field->getTitle();
			$chain .= $field->getName();
			if($fieldEntity && $fieldEntity->getCode() !== $entity->getCode())
			{
				array_push($previous, $field);
				$result[] = [
					'name' => $field->getName(),
					'title' => $field->getTitle(),
					'fullName' => $fullName,
					'chain' => $chain,
					'items' => $this->getEntityFields($fieldEntity, $previous),
				];
				array_pop($previous);
			}
			else
			{
				$result[] = [
					'name' => $field->getName(),
					'title' => $field->getTitle(),
					'fullName' => $fullName,
					'chain' => $chain,
				];
			}
		}

		return $result;
	}

	public function getUrlWithParameters(Page $page, array $additionalParams = []): string
	{
		$urlParameters = [];
		$parameters = $page->getParams();
		if (!empty($this->ids) && !empty($parameters))
		{
			foreach($parameters as $parameter)
			{
				$value = $this->getValue($parameter['FIELD']);
				if ($value)
				{
					$urlParameters[mb_strtolower($parameter['FIELD'])] = $value;
				}
			}
		}

		// add properties for webform link to handle it on openlines side
		if (
			isset($additionalParams['USER_CODE'], $additionalParams['EVENT_POSTFIX'])
			&& $page->isWebform()
			&& class_exists('\Bitrix\Crm\WebForm\Embed\Sign')
		)
		{
			$sign = new Sign();
			$sign->setProperty('eventNamePostfix', $additionalParams['EVENT_POSTFIX']);
			$sign->setProperty('openlinesCode', $additionalParams['USER_CODE']);
			foreach ($this->ids as $entityName => $entityId)
			{
				$entityType = \CCrmOwnerType::ResolveID($entityName);
				$entityId = (int)$entityId;
				if ($entityId > 0 && $entityType > 0)
				{
					$sign->addEntity($entityType, $entityId);
				}
			}
			$urlParameters[$sign::uriDataParameterName] = $sign->pack();
		}

		$uri = new Uri($page->getUrl());
		$uri->addParams($urlParameters);

		return $uri->getLocator();
	}
}
