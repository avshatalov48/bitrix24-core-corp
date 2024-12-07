<?php

namespace Bitrix\Sign\Contract;

use Bitrix\Sign\Item\Connector\RequisiteFieldCollection;
use Bitrix\Sign\Item\Connector\FetchRequisiteModifier;

interface RequisiteConnector
{
	public function fetchRequisite(?FetchRequisiteModifier $fetchModifier = null): RequisiteFieldCollection;
}