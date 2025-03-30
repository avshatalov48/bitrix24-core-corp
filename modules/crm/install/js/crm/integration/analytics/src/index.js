import { CallParsingEvent as AICallParsingEventBuilder } from './builders/ai/call-parsing-event';
import { CreateEvent as CreateAutomatedSolutionEvent } from './builders/automation/automatedsolution/create-event';
import { DeleteEvent as DeleteAutomatedSolutionEvent } from './builders/automation/automatedsolution/delete-event';
import { EditEvent as EditAutomatedSolutionEvent } from './builders/automation/automatedsolution/edit-event';
import { CreateEvent as CreateTypeEvent } from './builders/automation/type/create-event';
import { DeleteEvent as DeleteTypeEvent } from './builders/automation/type/delete-event';
import { EditEvent as EditTypeEvent } from './builders/automation/type/edit-event';
import { AddEvent as EntityAddEventBuilder } from './builders/entity/add-event';
import { CloseEvent as EntityCloseEventBuilder } from './builders/entity/close-event';
import { ConvertBatchEvent as EntityConvertBatchEventBuilder } from './builders/entity/convert-batch-event';
import { ConvertEvent as EntityConvertEventBuilder } from './builders/entity/convert-event';
import { CloseEvent as BlockCloseEvent } from './builders/block/close-event';
import { EnableEvent as BlockEnableEvent } from './builders/block/enable-event';
import { LinkEvent as BlockLinkEvent } from './builders/block/link-event';
import { Dictionary } from './dictionary';
import { getCrmMode } from './helpers';
import type {
	AICallParsingEvent,
	EntityAddEvent,
	EntityCloseEvent,
	EntityConvertBatchEvent,
	EntityConvertEvent,
	EventStatus,
} from './types';

const Builder = Object.freeze({
	Entity: {
		AddEvent: EntityAddEventBuilder,
		ConvertEvent: EntityConvertEventBuilder,
		ConvertBatchEvent: EntityConvertBatchEventBuilder,
		CloseEvent: EntityCloseEventBuilder,
	},
	AI: {
		CallParsingEvent: AICallParsingEventBuilder,
	},
	Automation: {
		AutomatedSolution: {
			CreateEvent: CreateAutomatedSolutionEvent,
			EditEvent: EditAutomatedSolutionEvent,
			DeleteEvent: DeleteAutomatedSolutionEvent,
		},
		Type: {
			CreateEvent: CreateTypeEvent,
			EditEvent: EditTypeEvent,
			DeleteEvent: DeleteTypeEvent,
		},
	},
	Block: {
		CloseEvent: BlockCloseEvent,
		EnableEvent: BlockEnableEvent,
		LinkEvent: BlockLinkEvent,
	},
});

export {
	Builder,
	Dictionary,
	getCrmMode
};

export type {
	AICallParsingEvent,
	EntityAddEvent,
	EntityCloseEvent,
	EntityConvertEvent,
	EntityConvertBatchEvent,
	EventStatus,
};
