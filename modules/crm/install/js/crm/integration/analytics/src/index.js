import { CallParsingEvent as AICallParsingEventBuilder } from './builders/ai/call-parsing-event';
import { AddEvent as EntityAddEventBuilder } from './builders/entity/add-event';
import { ConvertBatchEvent as EntityConvertBatchEventBuilder } from './builders/entity/convert-batch-event';
import { ConvertEvent as EntityConvertEventBuilder } from './builders/entity/convert-event';
import { Dictionary } from './dictionary';
import type {
	AICallParsingEvent,
	EntityAddEvent,
	EntityConvertBatchEvent,
	EntityConvertEvent,
	EventStatus,
} from './types';

const Builder = Object.freeze({
	Entity: {
		AddEvent: EntityAddEventBuilder,
		ConvertEvent: EntityConvertEventBuilder,
		ConvertBatchEvent: EntityConvertBatchEventBuilder,
	},
	AI: {
		CallParsingEvent: AICallParsingEventBuilder,
	},
});

export {
	Builder,
	Dictionary,
};

export type { EntityAddEvent, EntityConvertEvent, EntityConvertBatchEvent, AICallParsingEvent, EventStatus };
