import { MessagesModelState } from '../../../model/types/messages';
import { UsersModelState } from '../../../model/types/users';
import { FilesModelState } from '../../../model/types/files';
import { ReactionsModelState } from '../../../model/types/messages/reactions';
import { TariffRestrictions } from '../../../model/types/dialogues';

export interface MessageRepositoryPage {
	messageList: Array<MessagesModelState>,
	additionalMessageList: Array<MessagesModelState>,
	userList: Array<UsersModelState>,
	fileList: Array<FilesModelState>,
	reactionList: Array<ReactionsModelState>,
	dialogFields?: TariffRestrictions
}

export interface MessageRepositoryContext extends MessageRepositoryPage {
	hasContextMessage: boolean,
}
