import { CallParsingEvent as AICallParsingEventBuilder } from './builders/ai/call-parsing-event';
import { CreateEvent as CreateAutomatedSolutionEvent } from './builders/automation/automatedsolution/create-event';
import { DeleteEvent as DeleteAutomatedSolutionEvent } from './builders/automation/automatedsolution/delete-event';
import { EditEvent as EditAutomatedSolutionEvent } from './builders/automation/automatedsolution/edit-event';
import { CreateEvent as CreateTypeEvent } from './builders/automation/type/create-event';
import { DeleteEvent as DeleteTypeEvent } from './builders/automation/type/delete-event';
import { EditEvent as EditTypeEvent } from './builders/automation/type/edit-event';
import { CloseEvent as BlockCloseEvent } from './builders/block/close-event';
import { EnableEvent as BlockEnableEvent } from './builders/block/enable-event';
import { LinkEvent as BlockLinkEvent } from './builders/block/link-event';
import { AddEvent as EntityAddEventBuilder } from './builders/entity/add-event';
import { ChangeStageEvent as EntityChangeStageEventBuilder } from './builders/entity/change-stage-event';
import { CloseEvent as EntityCloseEventBuilder } from './builders/entity/close-event';
import { ConvertBatchEvent as EntityConvertBatchEventBuilder } from './builders/entity/convert-batch-event';
import { ConvertEvent as EntityConvertEventBuilder } from './builders/entity/convert-event';

import { ClickEvent as RepeatSaleBannerClickBuilder } from './builders/repeat-sale/banner/click-event';
import { CloseEvent as RepeatSaleBannerCloseBuilder } from './builders/repeat-sale/banner/close-event';
import { ViewEvent as RepeatSaleBannerViewBuilder } from './builders/repeat-sale/banner/view-event';
import { CancelEvent as RepeatSaleSegmentCancelBuilder } from './builders/repeat-sale/segment/cancel-event';
import { EditEvent as RepeatSaleSegmentEditBuilder } from './builders/repeat-sale/segment/edit-event';
import { ViewEvent as RepeatSaleSegmentViewBuilder } from './builders/repeat-sale/segment/view-event';

import { DeleteEvent as CommunicationDeleteEvent } from './builders/communication/delete-event';
import { FormEvent as CommunicationFormEvent } from './builders/communication/form-event';
import { SendEvent as CommunicationSendEvent } from './builders/communication/send-event';

import { Dictionary } from './dictionary';
import { getCrmMode } from './helpers';

import type {
	AICallParsingEvent,
	EntityAddEvent,
	EntityCloseEvent,
	EntityConvertBatchEvent,
	EntityConvertEvent,
	EntityChangeStageEvent,
	EventStatus,
	RepeatSaleBannerClickEvent,
	RepeatSaleBannerCloseEvent,
	RepeatSaleBannerViewEvent,
	RepeatSaleSegmentCancelEvent,
	RepeatSaleSegmentEditEvent,
	RepeatSaleSegmentViewEvent,
} from './types';

const Builder = Object.freeze({
	Entity: {
		AddEvent: EntityAddEventBuilder,
		ConvertEvent: EntityConvertEventBuilder,
		ConvertBatchEvent: EntityConvertBatchEventBuilder,
		CloseEvent: EntityCloseEventBuilder,
		ChangeStageEvent: EntityChangeStageEventBuilder
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
	Communication: {
		DeleteEvent: CommunicationDeleteEvent,
		FormEvent: CommunicationFormEvent,
		SendEvent: CommunicationSendEvent,
	},
	RepeatSale: {
		Banner: {
			ViewEvent: RepeatSaleBannerViewBuilder,
			ClickEvent: RepeatSaleBannerClickBuilder,
			CloseEvent: RepeatSaleBannerCloseBuilder,
		},
		Segment: {
			ViewEvent: RepeatSaleSegmentViewBuilder,
			CancelEvent: RepeatSaleSegmentCancelBuilder,
			EditEvent: RepeatSaleSegmentEditBuilder,
		},
	},
});

export {
	Builder,
	Dictionary,
	getCrmMode,
};

export type {
	AICallParsingEvent,
	EntityAddEvent,
	EntityCloseEvent,
	EntityConvertEvent,
	EntityConvertBatchEvent,
	EntityChangeStageEvent,
	EventStatus,
	RepeatSaleBannerViewEvent,
	RepeatSaleBannerClickEvent,
	RepeatSaleBannerCloseEvent,
	RepeatSaleSegmentViewEvent,
	RepeatSaleSegmentCancelEvent,
	RepeatSaleSegmentEditEvent,
};
