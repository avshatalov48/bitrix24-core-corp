import { UsersModelState } from '../../../../model/users/src/types';
import { ReactionType } from '../../../../model/messages/src/reactions/types';
import { ReactionViewerUser } from '../../../../controller/reaction-viewer/types/reaction-viewer';

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
