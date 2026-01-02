import { RecentModelState } from "../../../../model/recent/src/types";
import { FilesModelState } from "../../../../model/files/src/types";
import { UsersModelState } from "../../../../model/users/src/types";
import { MessagesModelState } from "../../../../model/messages/src/types/messages";

declare interface RecentList {
	items: Array<RecentModelState>,
	users: Array<UsersModelState>,
	messages: Array<MessagesModelState>,
	files: Array<FilesModelState>,
	draft: Array<RecentModelState>,
	hasMore: Boolean,
}
