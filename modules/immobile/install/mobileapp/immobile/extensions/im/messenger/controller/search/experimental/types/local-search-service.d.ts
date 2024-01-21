import {UsersModelState} from "../../../../model/types/users";
import {DialoguesModelState} from "../../../../model/types/dialogues";

type RecentLocalItem = {
	dialogId: string,
	dialog: DialoguesModelState,
	user?: UsersModelState,
	dateMessage: string,
}