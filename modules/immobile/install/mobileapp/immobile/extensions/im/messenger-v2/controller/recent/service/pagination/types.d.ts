import { IBaseRecentService } from '../base/type';
import { RecentItemData } from '../../../../../messenger/controller/recent/copilot/types/recent';

export interface IPaginationService extends IBaseRecentService
{
	setFirstPageData(source: string, { hasMore: boolean, nextPage: number, lastItem: RecentItemData }): void;
	getLastItem(source: string): RecentItemData | null;
}
