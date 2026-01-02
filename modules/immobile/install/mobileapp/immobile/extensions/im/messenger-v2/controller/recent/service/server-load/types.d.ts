import { IBaseRecentService } from '../base/type';
import {RefreshModeType} from "../../../../application/lib/refresher/types";

export interface IServerLoadService extends IBaseRecentService
{
	handleInitResult(mode: RefreshModeType, initResult: any): Promise<void>;
	getInitRequestMethod(mode: RefreshModeType): string | null;
	loadNextPage(): Promise<LoadNextPageResult>;
	setLastItem(lastItem: object | null): void;
}

export type RecentListRestOptions = {
	skipOpenlines: boolean,
	onlyCopilot: boolean,
	lastActivityDate: string,
}
export type LoadNextPageResult = {
	lastItem: object,
	hasMore: boolean,
};
