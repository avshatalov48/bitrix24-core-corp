import {MessengerModel, PayloadData} from "../../../../messenger/model/base";
import {DialogId} from "../../../../messenger/types/common";
import {RecentModelState} from "../../../../messenger/model/recent/src/types";

export type RecentModelCollection = {
	collection: Record<DialogId, RecentModelState>,
	chatIdCollection: Set<string>,
	copilotIdCollection: Set<string>,
	channelIdCollection: Set<string>,
	collabIdCollection: Set<string>,
	taskIdCollection: Set<string>,
}

export type RecentMessengerModelV2 = MessengerModel<RecentModelCollection>;

export type RecentModelV2Actions =
	'recentModel/set'
	| 'recentModel/setChat'
	| 'recentModel/setCopilot'
	| 'recentModel/setChannel'
	| 'recentModel/setCollab'
	| 'recentModel/setFirstPageByTab'
	| 'recentModel/setByRecentConfigTabs'
	| 'recentModel/setByNavigationTabs'
	| 'recentModel/setGroupCollection'
	| 'recentModel/hideByRecentConfigTabs'
	| 'recentModel/hideByNavigationTabs'
	| 'recentModel/delete'
	| 'recentModel/deleteOpenChannel'
	| 'recentModel/update'
	| 'recentModel/like'

export type RecentModelV2Mutation =
	'recentModel/setChatIdCollection'
	| 'recentModel/setCopilotIdCollection'
	| 'recentModel/setChannelIdCollection'
	| 'recentModel/setCollabIdCollection'
	| 'recentModel/storeIdCollection'
	| 'recentModel/deleteFromChatIdCollection'
	| 'recentModel/deleteFromCopilotIdCollection'
	| 'recentModel/deleteFromChannelIdCollection'
	| 'recentModel/deleteFromCollabIdCollection'
	| 'recentModel/deleteFromTaskIdCollection'
	| 'recentModel/add'
	| 'recentModel/update'
	| 'recentModel/delete'
;

export type RecentV2SetIdCollectionActions =
	'setChat'
	| 'setCollab'
	| 'setCopilot'
	| 'setChannel'
	;
export type RecentV2StoreIdCollectionActions = 'setFirstPageByTab';

export interface RecentV2SetIdCollectionData extends PayloadData
{
	itemIds: Array<string>;
}

export interface RecentV2StoreIdCollectionData extends PayloadData
{
	tab: string;
	itemIds: Array<string>;
}

export type RecentV2UpdateActions =
	'set'
	| 'update'
	| 'setFromPush'
	| 'setFromSync'
	| 'like'
	;
export interface RecentV2UpdateData extends PayloadData
{
	recentItemList: Array<{ fields: Partial<RecentModelState> }>;
}

export interface RecentV2DeleteData extends PayloadData
{
	id: string;
}
