import {Base} from './base';
import ConfigurableItem from '../configurable-item';
import { Type } from "main.core";

import TextBlock from '../components/content-blocks/text';
import LinkBlock from '../components/content-blocks/link';
import WithTitle from '../components/content-blocks/with-title';
import LineOfTextBlocks from '../components/content-blocks/line-of-text-blocks';
import {TimelineAudio} from '../components/content-blocks/timeline-audio';
import {ClientMark} from '../components/content-blocks/client-mark';
import Money from '../components/content-blocks/money';
import EditableText from '../components/content-blocks/editable-text';
import EditableDate from '../components/content-blocks/editable-date';
import {PlayerAlert} from '../components/content-blocks/player-alert';
import {Note} from '../components/layout/note';
import DatePill from '../components/content-blocks/date-pill';

export class CommonContentBlocks extends Base
{
	getContentBlockComponents(Item: ConfigurableItem): Object
	{
		return {
			TextBlock,
			LinkBlock,
			WithTitle,
			LineOfTextBlocks,
			TimelineAudio,
			ClientMark,
			Money,
			EditableText,
			EditableDate,
			PlayerAlert,
			DatePill,
			Note,
		};
	}

	/**
	 * Process common events that aren't bound to specific item type
	 */
	onItemAction(item: ConfigurableItem, action: String, actionData: ?Object)
	{
		if (action === 'Item:OpenEntityDetailTab' && Type.isStringFilled(actionData?.tabId))
		{
			this.#openEntityDetailTab(actionData.tabId);
		}
	}

	#openEntityDetailTab(tabId: string): void
	{
		// the event is handled by compatible code, it's a pain to use EventEmitter in this case
		// eslint-disable-next-line bitrix-rules/no-bx
		BX.onCustomEvent(window, 'OpenEntityDetailTab', [tabId]);
	}

	static isItemSupported(item: ConfigurableItem): boolean
	{
		return true; // common blocks can be used anywhere
	}
}

