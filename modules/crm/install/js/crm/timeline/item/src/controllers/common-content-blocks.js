import { Type } from 'main.core';
import AddressBlock from '../components/content-blocks/address';
import { ClientMark } from '../components/content-blocks/client-mark';
import CommentContent from '../components/content-blocks/comment-content';
import DateBlock from '../components/content-blocks/date';
import DatePill from '../components/content-blocks/date-pill';
import EditableDate from '../components/content-blocks/editable-date';
import { EditableDescription } from '../components/content-blocks/editable-description';
import EditableText from '../components/content-blocks/editable-text';
import { FileList } from '../components/content-blocks/file-list';
import { InfoGroup } from '../components/content-blocks/info-group';
import ItemSelector from '../components/content-blocks/item-selector';
import LineOfTextBlocks from '../components/content-blocks/line-of-text-blocks';
import LinkBlock from '../components/content-blocks/link';
import LineOfTextBlocksButton from '../components/content-blocks/line-of-text-blocks-button';
import Money from '../components/content-blocks/money';
import { MoneyPill } from '../components/content-blocks/money-pill';
import { Note } from '../components/content-blocks/note';
import { PlayerAlert } from '../components/content-blocks/player-alert';
import { RestAppLayoutBlocks } from '../components/content-blocks/rest-app-layout-blocks';
import { SmsMessage } from '../components/content-blocks/sms-message';
import TextBlock from '../components/content-blocks/text';
import { TimelineAudio } from '../components/content-blocks/timeline-audio';
import { CallScoringPill } from '../components/content-blocks/copilot/call-scoring-pill';
import { CallScoring } from '../components/content-blocks/copilot/call-scoring.js';
import DeadlineAndPingSelector from '../components/content-blocks/todo/deadline-and-ping-selector';
import PingSelector from '../components/content-blocks/todo/ping-selector';
import WithTitle from '../components/content-blocks/with-title';
import WorkflowEfficiency from '../components/content-blocks/workflow-efficiency';
import ConfigurableItem from '../configurable-item';
import { Base } from './base';

export class CommonContentBlocks extends Base
{
	getContentBlockComponents(Item: ConfigurableItem): Object
	{
		return {
			AddressBlock,
			TextBlock,
			LinkBlock,
			LineOfTextBlocksButton,
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
			RestAppLayoutBlocks,
			DatePill,
			Note,
			FileList,
			InfoGroup,
			MoneyPill,
			SmsMessage,
			CommentContent,
			ItemSelector,
			PingSelector,
			DeadlineAndPingSelector,
			WorkflowEfficiency,
			CallScoringPill,
			CallScoring,
		};
	}

	/**
	 * Process common events that aren't bound to specific item type
	 */
	onItemAction(item: ConfigurableItem, actionParams: ActionParams): void
	{
		const { action, actionType, actionData } = actionParams;
		if (actionType !== 'jsEvent')
		{
			return;
		}

		if (action === 'Item:OpenEntityDetailTab' && Type.isStringFilled(actionData?.tabId))
		{
			this.#openEntityDetailTab(actionData.tabId);
		}

		if (action === 'Note:StartEdit')
		{
			this.#editNote(item);
		}

		if (action === 'Note:FinishEdit')
		{
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
