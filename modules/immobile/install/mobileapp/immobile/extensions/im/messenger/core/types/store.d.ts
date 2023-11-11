import {DialoguesModelActions, DialoguesModelMutation} from "../../model/types/dialogues";
import {ApplicationModelActions, ApplicationModelMutation} from "../../model/types/application";
import {FilesModelActions, FilesModelMutation} from "../../model/types/files";
import {MessagesModelActions, MessagesModelMutation} from "../../model/types/messages";
import {RecentModelActions, RecentModelMutation} from "../../model/types/recent";
import {UsersModelActions, UsersModelMutation} from "../../model/types/users";
import {DraftModelActions, DraftModelMutation} from "../../model/types/draft";
import {ReactionsModelActions, ReactionsModelMutation} from "../../model/types/messages/reactions";
import {SidebarModelActions} from "../../model/types/sidebar";
import {RecentSearchModelActions, RecentSearchModelMutation} from "../../model/types/recent/search";


type MessengerStoreActions =
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

type MessengerStoreMutation =
	ApplicationModelMutation
	| DialoguesModelMutation
	| FilesModelMutation
	| MessagesModelMutation
	| RecentModelMutation
	| UsersModelMutation
	| DraftModelMutation
	| ReactionsModelMutation
	| SidebarModelActions
	| RecentSearchModelMutation

type MessengerCoreStore = {
	dispatch(actionName: MessengerStoreActions, params?: any) : Promise<any>,
	getters: any
}

export class MessengerCoreStoreManager
{
	on(mutationName: MessengerStoreMutation, handler: Function): MessengerCoreStoreManager
	once(mutationName: MessengerStoreMutation, handler: Function): MessengerCoreStoreManager
	off(mutationName: MessengerStoreMutation, handler: Function): MessengerCoreStoreManager
	get isMultiContextMode(): boolean
	get store(): MessengerCoreStore

}

