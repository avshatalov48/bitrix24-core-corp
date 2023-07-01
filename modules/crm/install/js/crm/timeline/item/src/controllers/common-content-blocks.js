import { Base } from './base';
import ConfigurableItem from '../configurable-item';
import { Type } from "main.core";

import TextBlock from '../components/content-blocks/text';
import LinkBlock from '../components/content-blocks/link';
import DateBlock from '../components/content-blocks/date';
import WithTitle from '../components/content-blocks/with-title';
import LineOfTextBlocks from '../components/content-blocks/line-of-text-blocks';
import { TimelineAudio } from '../components/content-blocks/timeline-audio';
import { ClientMark } from '../components/content-blocks/client-mark';
import Money from '../components/content-blocks/money';
import EditableText from '../components/content-blocks/editable-text';
import { EditableDescription } from '../components/content-blocks/editable-description';
import EditableDate from '../components/content-blocks/editable-date';
import { PlayerAlert } from '../components/content-blocks/player-alert';
import { Note } from '../components/content-blocks/note';
import DatePill from '../components/content-blocks/date-pill';
import { MoneyPill } from '../components/content-blocks/money-pill';
import { InfoGroup } from '../components/content-blocks/info-group';
import { FileList } from '../components/content-blocks/file-list';
import { SmsMessage } from '../components/content-blocks/sms-message';
import CommentContent from '../components/content-blocks/comment-content';

export class CommonContentBlocks extends Base
{
	getContentBlockComponents(Item: ConfigurableItem): Object
	{
		return {
			TextBlock,
			LinkBlock,
			DateBlock,
			WithTitle,
			LineOfTextBlocks,
			TimelineAudio,
			ClientMark,
			Money,
			EditableText,
			EditableDescription,
			EditableDate,
			PlayerAlert,
			DatePill,
			Note,
			FileList,
			InfoGroup,
			MoneyPill,
			SmsMessage,
			CommentContent,
		};
	}

	/**
	 * Process common events that aren't bound to specific item type
	 */
	onItemAction(item: ConfigurableItem, actionParams: ActionParams): void
	{
		const {action, actionType, actionData} = actionParams;
		if (actionType !== 'jsEvent')
		{
			return;
		}

		if (action === 'Item:OpenEntityDetailTab' && Type.isStringFilled(actionData?.tabId))
		{
			this.#openEntityDetailTab(actionData.tabId);
		}

		if(action === 'Note:StartEdit') {
			this.#editNote(item);
		}

		if(action === 'Note:FinishEdit') {
			this.#cancelEditNote(item);
		}
	}

	#openEntityDetailTab(tabId: string): void
	{
		// the event is handled by compatible code, it's a pain to use EventEmitter in this case
		// eslint-disable-next-line bitrix-rules/no-bx
		BX.onCustomEvent(window, 'OpenEntityDetailTab', [tabId]);
	}

	#editNote(item: ConfigurableItem) {
		item.getLayoutContentBlockById('note')?.setEditMode(true);
		item.highlightContentBlockById('note', true);
	}

	#cancelEditNote(item: ConfigurableItem) {
		item.getLayoutContentBlockById('note')?.setEditMode(false);
		item.highlightContentBlockById('note', false);
	}

	static isItemSupported(item: ConfigurableItem): boolean
	{
		return true; // common blocks can be used anywhere
	}
}
