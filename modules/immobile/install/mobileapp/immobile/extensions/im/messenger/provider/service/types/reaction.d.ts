import {UsersModelState} from "../../../model/types/users";
import {ReactionType} from "../../../model/types/messages/reactions";
import {ReactionViewerUser} from "../../../controller/reaction-viewer/types/reaction-viewer";

type ReactionServiceLoadData = {
	users: Array<UsersModelState>,
	reactions: Array<{
		id: number,
		messageId: number,
		userId: number,
		dateCreate: string,
		reaction: Uppercase<ReactionType>
	}>,
}

type ReactionServiceGetData = {
	reactionViewerUsers: ReactionViewerUser[],
	hasNextPage: boolean,
}