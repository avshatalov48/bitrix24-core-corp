export interface BaseSearchConfig
{
	id: string;
	context: string;
	clearUnavailableItems: boolean;
	preselectedItems: Array<any>;
	entities: Array<BaseSearchEntity>
	getConfig(): ajaxConfig;
	getSearchRequestEndpoint(): string;
	getLoadLatestResultEndpoint(): string;
	getSaveItemEndpoint(): string;
}

export interface BaseSearchEntity
{
	id: string;
	dynamicLoad: boolean;
	dynamicSearch: boolean;
	sort?: number;
}