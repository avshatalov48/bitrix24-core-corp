import { MessengerModel } from '../base';
import { DialogId } from '../../../types/common';

export type CollabEntity = {
	counter: number,
	url: string,
};

export type CollabEntitiesKeys = 'tasks' | 'files' | 'calendar'
export type CollabEntities = Record<CollabEntitiesKeys, CollabEntity>

declare type CollabItem = {
	guestCount: number,
	collabId: number,
	entities: CollabEntities,
}

declare type CollabModelState = {
	collection: Record<DialogId, CollabItem>,
}

export type CollabModel = MessengerModel<CollabModelState>

declare type CollabModelActions =
	'collabModel/set'
	| 'collabModel/seEntities'
	| 'collabModel/setGuestCount'

declare type CollabModelMutation =
	'CollabModel/set'
	| 'CollabModel/seEntities'
	| 'CollabModel/setGuestCount'

declare type CollabSetActions = 'set';
declare type CollabSetData = {
	dialogId: DialogId,
	collabId: number,
	entities: CollabEntities,
}

declare type CollabSetEntityCounterActions = 'seEntities';
declare type CollabSetEntityCounterData = {
	dialogId: DialogId,
	entity: string,
	counter: number,
}

declare type CollabSetGuestCountActions = 'setHasNextPage';
declare type CollabSetGuestCountData = {
	dialogId: DialogId,
	guestCount: number,
}
