import { DialogId } from '../../../../types/common';
import { MessengerModel, PayloadData } from '../../../base';

export type RecentSearchModelState = {
	id: string | number,
	dateMessage: Date,
}

export type RecentSearchModelActions =
	'recentModel/searchModel/set'
	| 'recentModel/searchModel/clear'

export type RecentSearchModelMutation =
	'recentModel/searchModel/set'
	| 'recentModel/searchModel/clear'

export type RecentSearchSetActions = 'set';
export interface RecentSearchSetData extends PayloadData
{
	item: RecentSearchModelState;
}

export type RecentSearchClearActions = 'clear'
export interface RecentSearchClearData extends PayloadData
{
	// void object
}

export type RecentSearchModelCollection = {
	collection: Record<DialogId, RecentSearchModelState>
}

export type RecentSearchModel = MessengerModel<RecentSearchModelCollection>;
