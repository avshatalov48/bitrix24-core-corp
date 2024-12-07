import { BaseEvent } from 'main.core.events';

export type FieldSelectEventData = { selectedFieldNames: Array<string> };
export type FieldSelectEvent = BaseEvent<FieldSelectEventData>;
