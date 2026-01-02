import { IBaseRecentService } from '../base/type';

export interface ISearchService extends IBaseRecentService
{
	openSearch(): void;
}
