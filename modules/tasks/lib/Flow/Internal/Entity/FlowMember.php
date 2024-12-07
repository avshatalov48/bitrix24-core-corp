<?php

namespace Bitrix\Tasks\Flow\Internal\Entity;

use Bitrix\Tasks\Flow\Internal\EO_FlowMember;
use Bitrix\Tasks\Internals\InsertIgnoreTrait;
use Stringable;

class FlowMember extends EO_FlowMember implements Stringable
{
	use InsertIgnoreTrait;

	public function __toString(): string
	{
		return $this->getFlowId() . '_' . $this->getAccessCode() . '_' . $this->getRole();
	}

	public function getInsertValues(): string
	{
		return "({$this->getFlowId()}, '{$this->getAccessCode()}', {$this->getEntityId()}, '{$this->getEntityType()}', '{$this->getRole()}')";
	}

	public function getInsertFields(): array
	{
		return ['FLOW_ID', 'ACCESS_CODE', 'ENTITY_ID', 'ENTITY_TYPE', 'ROLE'];
	}
}