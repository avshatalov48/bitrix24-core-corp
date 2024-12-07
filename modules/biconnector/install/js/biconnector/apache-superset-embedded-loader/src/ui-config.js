import type { FilterUiOption } from './filter-ui-config';

export type UiConfig = {
	hideTitle: boolean,
	hideTab: boolean,
	hideChartControls: boolean,
	filterOption: FilterUiOption,
	filters: Object,
	urlParams: Object,
}
