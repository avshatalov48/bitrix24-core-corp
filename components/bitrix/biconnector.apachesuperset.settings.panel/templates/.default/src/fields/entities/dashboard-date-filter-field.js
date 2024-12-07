import { Loc } from 'main.core';
import { DateFilterField } from './date-filter-field';

export class DashboardDateFilterField extends DateFilterField
{
	getHintText(): string
	{
		return Loc.getMessage('BICONNECTOR_SUPERSET_SETTINGS_DASHBOARD_RANGE_FIELD_HINT');
	}
}
