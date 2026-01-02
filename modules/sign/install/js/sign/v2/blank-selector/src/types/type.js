import type { BaseEvent } from 'main.core.events';
import { DocumentInitiatedType } from 'sign.type';

export type BlankSelectorConfig = {
	events?: {
		[key: string]: (event: BaseEvent) => void
	},
	uploaderOptions: {
		acceptedFileTypes: [],
		maxFileSize: number,
		maxFileCount: number,
		imageMaxFileSize: number,
		maxTotalFileSize: number,
	},
	portalConfig: {
		isUnsecuredScheme: boolean,
		isDomainChanged: boolean,
		isEdoRegion: boolean,
	},
	type?: 'b2b' | 'b2e',
	region: string,
	regionDocumentTypes: [],
	canUploadNewBlank?: boolean,
	documentMode?: 'template' | 'document',
	isOpenedFromRobot?: boolean,
	isOpenedFromTemplateFolder?: boolean,
	initiatedByType?: DocumentInitiatedType,
};

export type BlankData = {
	id: number,
	title: string,
	previewUrl: string | null,
	userAvatarUrl: string | null,
	userName: string | null,
	dateCreate: string | null
};

export type ListItemProps = {
	title: string;
	modifier: string;
	description?: string;
};

export type BlankProps = {
	...ListItemProps;
	userAvatarUrl?: BlankData['userAvatarUrl'];
};

export type ToggleEvent = BaseEvent<{ selected: false } | {
	id: number,
	title: string,
	selected: true,
	extra: Object,
	previousSelectedBlankId: number | null,
}>;
