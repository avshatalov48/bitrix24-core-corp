import {DialoguesMessengerModel, DialoguesModelActions, DialoguesModelMutation} from "../../model/dialogues/src/types";
import {ApplicationModelActions, ApplicationModelMutation} from "../../model/application/src/types";
import {FilesModelActions, FilesModelMutation} from "../../model/files/src/types";
import {MessagesMessengerModel, MessagesModelActions, MessagesModelMutation} from "../../model/messages/src/types/messages";
import {RecentMessengerModel, RecentModelActions, RecentModelMutation} from "../../model/recent/src/types";
import {RecentMessengerModelV2, RecentModelV2Actions, RecentModelV2Mutation} from "../../../messenger-v2/model/recent/src/types";
import {UsersModel, UsersModelActions, UsersModelMutation} from "../../model/users/src/types";
import {DraftModelActions, DraftModelMutation} from "../../model/draft/src/types";
import {ReactionsModelActions, ReactionsModelMutation} from "../../model/messages/src/reactions/types";
import {SidebarModelActions, SidebarModelMutation} from "../../model/sidebar/src/types";
import {
	RecentSearchModel,
	RecentSearchModelActions,
	RecentSearchModelMutation
} from "../../model/recent/src/search/types";
import {QueueModelActions, QueueModelMutation} from "../../model/queue/src/types";
import {PinModelActions, PinModelMutation} from "../../model/messages/src/pin/types";
import {CommentMessengerModel, CommentModelActions, CommentModelMutation} from "../../model/comment/src/types";
import {SidebarFilesModelActions, SidebarFilesModelMutation} from "../../model/sidebar/src/files/types";
import {SidebarLinksModelActions, SidebarLinksModelMutation} from "../../model/sidebar/src/links/types";
import { CollabModelActions, CollabModelMutation } from "../../model/dialogues/src/collab/types";
import { CopilotModelActions, CopilotModelMutation } from "../../model/dialogues/src/copilot/types";
import {CounterModelMutation, CounterModelActions, CounterMessengerModel} from "../../model/counter/src/types";
import { VoteModelActions, VoteModelMutation } from "../../model/messages/src/vote/types";
import {
	AnchorModelActions,
	AnchorModelMutation,
	AnchorMessengerModel
} from "../../model/anchor/src/types";
import { TranscriptModelMutation, TranscriptModelActions } from "../../model/files/src/transcript/types";

export type MessengerStoreActions =
	FilesModelActions
	| ApplicationModelActions
	| DialoguesModelActions
	| MessagesModelActions
	| RecentModelActions
	| RecentModelV2Actions
	| UsersModelActions
	| DraftModelActions
	| ReactionsModelActions
	| SidebarModelActions
	| RecentSearchModelActions
	| QueueModelActions
	| PinModelActions
	| CommentModelActions
	| SidebarFilesModelActions
	| SidebarLinksModelActions
	| CollabModelActions
	| CounterModelActions
	| CopilotModelActions
	| VoteModelActions
	| AnchorModelActions
	| TranscriptModelActions

export type MessengerStoreMutation =
	ApplicationModelMutation
	| DialoguesModelMutation
	| FilesModelMutation
	| MessagesModelMutation
	| RecentModelMutation
	| RecentModelV2Mutation
	| UsersModelMutation
	| DraftModelMutation
	| ReactionsModelMutation
	| SidebarModelMutation
	| RecentSearchModelMutation
	| QueueModelMutation
	| PinModelMutation
	| CommentModelMutation
	| SidebarFilesModelMutation
	| SidebarLinksModelMutation
	| CollabModelMutation
	| CounterModelMutation
	| CopilotModelMutation
	| VoteModelMutation
	| AnchorModelMutation
	| TranscriptModelMutation

type MessengerCoreStore = {
	dispatch(actionName: MessengerStoreActions, params?: any) : Promise<any>,
	getters: any,
	state: { // use it only for testing!!!
		messagesModel: ReturnType<MessagesMessengerModel['state']>,
		commentModel: ReturnType<CommentMessengerModel['state']>,
		dialoguesModel: ReturnType<DialoguesMessengerModel['state']>,
		recentModel: ReturnType<RecentMessengerModel['state']|RecentMessengerModelV2['state']>
			& { searchModel: ReturnType<RecentSearchModel['state']> }
		,
		usersModel: ReturnType<UsersModel['state']>,
		anchorModel: ReturnType<AnchorMessengerModel['state']>,
		counterModel: ReturnType<CounterMessengerModel['state']>,
	}
}

export class MessengerCoreStoreManager
{
	on(mutationName: MessengerStoreMutation, handler: Function): MessengerCoreStoreManager
	once(mutationName: MessengerStoreMutation, handler: Function): MessengerCoreStoreManager
	off(mutationName: MessengerStoreMutation, handler: Function): MessengerCoreStoreManager
	get isMultiContextMode(): boolean
	get store(): MessengerCoreStore

}
