import { IBaseRecentService } from '../base/type';
import {JNListWidgetSectionItem} from "../../../../../../../../../../../mobile/dev/janative/api";
import {RecentWidgetItem} from "../../../../../messenger/view/recent/types/recent-item";

export interface IQuickRecentService extends IBaseRecentService
{
	renderList(): Promise<void>;
	save(sections:Array<JNListWidgetSectionItem>, items:Array<RecentWidgetItem|RecentItem>): void;
}
