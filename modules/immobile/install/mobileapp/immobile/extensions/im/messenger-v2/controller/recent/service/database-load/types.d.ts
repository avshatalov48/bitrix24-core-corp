import { IBaseRecentService } from '../base/type';

export interface IDatabaseLoadService extends IBaseRecentService
{
	setLastItem(lastItem: object | null): void;
	loadNextPage(): Promise<LoadedPageCursor>
	loadFirstPage(): Promise<void>
}

declare type LoadedPageCursor = {
	hasMore: boolean,
	lastItem: object,
}
