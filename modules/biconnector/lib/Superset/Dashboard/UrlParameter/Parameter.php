<?php

namespace Bitrix\BIConnector\Superset\Dashboard\UrlParameter;

use Bitrix\Main\Localization\Loc;

enum Parameter: string
{
	case CurrentUser = 'current_user';
	case WorkflowTemplateId = 'workflow_template_id';
	case TasksFlowsFlowId = 'tasks_flows_flow_id';

	/**
	 * @return string
	 */
	public function code(): string
	{
		return $this->value;
	}

	/**
	 * @return string
	 */
	public function title(): string
	{
		$code = strtoupper($this->value);

		return Loc::getMessage("BI_CONNECTOR_DASHBOARD_URL_PARAMETER_{$code}_TITLE") ?? $this->value;
	}

	/**
	 * @return string
	 */
	public function description(): string
	{
		$code = strtoupper($this->value);

		return Loc::getMessage("BI_CONNECTOR_DASHBOARD_URL_PARAMETER_{$code}_DESCRIPTION") ?? $this->value;
	}
}