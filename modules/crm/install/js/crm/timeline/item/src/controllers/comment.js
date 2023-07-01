import { Type } from 'main.core';
import { MessageBox, MessageBoxButtons } from 'ui.dialogs.messagebox';
import { Base } from './base';
import ConfigurableItem from '../configurable-item';

export class Comment extends Base
{
	getDeleteActionMethod(): string
	{
		return 'crm.timeline.comment.delete';
	}

	getDeleteActionCfg(recordId: Number, ownerTypeId: Number, ownerId: Number): Object
	{
		return {
			data: {
				id: recordId,
				ownerTypeId: ownerTypeId,
				ownerId: ownerId,
			}
		};
	}

	onItemAction(item: ConfigurableItem, actionParams: ActionParams): void
	{
		const { action, actionType, actionData, animationCallbacks } = actionParams;
		if (actionType !== 'jsEvent')
		{
			return;
		}

		if (action === 'Comment:Edit')
		{
			this.#showEditor(item);
		}

		if (action === 'Comment:Delete' && actionData)
		{
			this.#onCommentDelete(actionData, animationCallbacks);
		}

		if (action === 'Comment:StartEdit')
		{
			item.highlightContentBlockById('commentContent', true);
		}

		if (action === 'Comment:FinishEdit')
		{
			item.highlightContentBlockById('commentContent', false);
		}
	}

	#showEditor(item: ConfigurableItem): void
	{
		const commentBlock = item.getLayoutContentBlockById('commentContent');
		if (commentBlock)
		{
			commentBlock.startEditing();
		}
		else
		{
			throw new Error('Vue component "CommentContent" was not found');
		}
	}

	#onCommentDelete(actionData: Object, animationCallbacks: ?Object): void
	{
		if (!this.#isValidParams(actionData))
		{
			return;
		}

		const confirmationText = Type.isStringFilled(actionData.confirmationText) ? actionData.confirmationText : '';
		if (confirmationText)
		{
			MessageBox.show({
				message: confirmationText,
				modal: true,
				buttons: MessageBoxButtons.YES_NO,
				onYes: () => {
					return this.runDeleteAction(actionData.commentId, actionData.ownerTypeId, actionData.ownerId, animationCallbacks);
				},
				onNo: (messageBox) => {
					messageBox.close();
				},
			});
		}
		else
		{
			this.runDeleteAction(actionData.commentId, actionData.ownerTypeId, actionData.ownerId);
		}
	}

	#isValidParams(params: Object): boolean
	{
		return Type.isNumber(params.commentId)
			&& Type.isNumber(params.ownerId)
			&& Type.isNumber(params.ownerTypeId);
	}

	static isItemSupported(item: ConfigurableItem): boolean
	{
		return (item.getType() === 'Comment');
	}
}
