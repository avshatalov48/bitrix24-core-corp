<?php

namespace Bitrix\Crm\AgentUtil\ProcessEntitiesStorage;

enum OptionEnum: string
{
	case ENTITY_TYPE_IDS = 'process_entity_type_ids_#AGENT#';
	case LAST_ENTITY_ID = 'process_last_entity_id_#AGENT#';
	case LIMIT = 'process_entity_ids_limit_#AGENT#';

	public function getOptionName(string $agent): string
	{
		return str_replace('#AGENT#', $agent, $this->value);
	}
}
