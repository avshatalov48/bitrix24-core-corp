import { MessagesModelState } from '../../../../model/messages/src/types/messages';
import { FilesModelState } from '../../../../model/files/src/types';
import { DialoguesModelState } from '../../../../model/dialogues/src/types';
import { UsersModelState } from '../../../../model/users/src/types';

type MessageContextMenuButton = {
	id: string,
	testId: string,
	type: 'button',
	text: string,
	iconName: string,
	iconFallbackUrl: string,
	/** @deprecated after API 54 use iconName and iconFallbackUrl instead */
	iconSvg?: string,
	style?: {
		fontColor?: string,
		iconColor?: string
	},
};

declare type MessageContextMenuSeparator = {
	type: 'separator',
};

interface IMessageMenuMessage {
	messageModel: MessagesModelState;
	fileModel?: FilesModelState;
	dialogModel: DialoguesModelState;
	userModel: UsersModelState;
	isPinned: boolean;
	isUserSubscribed: boolean;

	isPossibleReact(): boolean;

	isPossibleReply(): boolean;

	isPossibleCopy(): boolean;

	isPossibleCopyLink(): boolean;

	isPossiblePin(): boolean;

	isPossibleUnpin(): boolean;

	isPossibleForward(): boolean;

	isPossibleCreate(): boolean;

	isPossibleSaveToLibrary(): boolean;

	isPossibleShowProfile(): boolean;

	isPossibleCallFeedback(): boolean;

	isPossibleMultiselect(): boolean;

	isPossibleEdit(): boolean;

	isPossibleDelete(): boolean;

	isPossibleSubscribe(): boolean;

	isPossibleUnsubscribe(): boolean;

	isPossibleResend(): boolean;

	isDialogCopilot(): boolean;

	isAdmin(): boolean;
}

interface IMessageMenuView {
	reactionList: string[];
	actionList: Array<Object>;

	addReaction(reaction: string): this;

	addSeparator(): this;

	addAction(action: Object): this;
}

type MessageMenuControllerCreateParams = {
	serviceLocator: DialogLocator,
	getDialog: () => DialoguesModelState,
}

export { MessageContextMenuButton }
