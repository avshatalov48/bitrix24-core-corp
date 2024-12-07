import type { BaseEvent } from 'main.core.events';
import type { RichTextAreaWidgetOptions } from 'ui.rich-text-area';
import type { TileWidgetOptions } from 'ui.uploader.tile-widget';

export type DiskRichTextAreaOptions = {
	canCreateDocuments?: boolean,
	disableLocalEdit?: boolean,
	allowDocumentFieldName?: string,
	photoTemplate?: 'grid' | 'gallery' | null,
	photoTemplateFieldName?: string,
	photoTemplateMode?: 'auto' | 'manual',
	tileWidgetOptions?: TileWidgetOptions,
	insertIntoText?: boolean,
	richTextOptions: RichTextAreaWidgetOptions,
	events?: { [eventName: string]: (event: BaseEvent) => void },
};
