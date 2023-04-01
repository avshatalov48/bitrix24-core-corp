<?php

namespace Bitrix\SalesCenter\Fields;

class Field
{
	protected $name;
	protected $title;
	protected $entity;

	public function __construct(string $name, array $params = [])
	{
		$this->name = $name;
		if (!empty($params['title']) && is_string($params['title']))
		{
			$this->title = $params['title'];
		}

		if (isset($params['entity']) && $params['entity'] instanceof Entity)
		{
			$this->setEntity($params['entity']);
		}
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getTitle(): string
	{
		if(!empty($this->title))
		{
			return $this->title;
		}
		elseif($this->getEntity())
		{
			return $this->getEntity()->getName();
		}

		return $this->name;
	}

	public function setTitle(string $title): Field
	{
		$this->title = $title;

		return $this;
	}

	public function getEntity(): ?Entity
	{
		return $this->entity;
	}

	public function setEntity(Entity $entity): Field
	{
		$this->entity = $entity;

		return $this;
	}
}
