import {DialoguesMessengerModel, DialoguesModelActions, DialoguesModelMutation} from "../../model/types/dialogues";
import {ApplicationModelActions, ApplicationModelMutation} from "../../model/types/application";
import {FilesModelActions, FilesModelMutation} from "../../model/types/files";
import {MessagesMessengerModel, MessagesModelActions, MessagesModelMutation} from "../../model/types/messages";
import {RecentMessengerModel, RecentModelActions, RecentModelMutation} from "../../model/types/recent";
import {UsersMessengerModel, UsersModelActions, UsersModelMutation} from "../../model/types/users";
import {DraftModelActions, DraftModelMutation} from "../../model/types/draft";
import {ReactionsModelActions, ReactionsModelMutation} from "../../model/types/messages/reactions";
import {SidebarModelActions, SidebarModelMutation} from "../../model/types/sidebar";
import {
	RecentSearchMessengerModel,
	RecentSearchModelActions,
	RecentSearchModelMutation
} from "../../model/types/recent/search";
import {QueueModelActions, QueueModelMutation} from "../../model/types/queue";
import {PinModelActions, PinModelMutation} from "../../model/types/messages/pin";
import {CommentMessengerModel, CommentModelActions, CommentModelMutation} from "../../model/types/comment";
import {SidebarFilesModelActions, SidebarFilesModelMutation} from "../../model/types/sidebar/files";
import {SidebarLinksModelActions, SidebarLinksModelMutation} from "../../model/types/sidebar/links";
import { CollabModelActions, CollabModelMutation } from "../../model/types/collab";


export type MessengerStoreActions =
	FilesModelActions
	| ApplicationModelActions
	| DialoguesModelActions
	| MessagesModelActions
	| RecentModelActions
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

export type MessengerStoreMutation =
	ApplicationModelMutation
	| DialoguesModelMutation
	| FilesModelMutation
	| MessagesModelMutation
	| RecentModelMutation
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

type MessengerCoreStore = {
	dispatch(actionName: MessengerStoreActions, params?: any) : Promise<any>,
	getters: any
	state: { // use it only for testing!!!
		messagesModel: ReturnType<MessagesMessengerModel['state']>,
		commentModel: ReturnType<CommentMessengerModel['state']>,
		dialoguesModel: ReturnType<DialoguesMessengerModel['state']>,
		recentModel: ReturnType<RecentMessengerModel['state']>
			& { searchModel: ReturnType<RecentSearchMessengerModel['state']> }
		,
		usersModel: ReturnType<UsersMessengerModel['state']>
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

