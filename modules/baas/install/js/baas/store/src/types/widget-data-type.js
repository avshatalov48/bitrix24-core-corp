import { ResponsePackageDataType } from './response-package-data-type';
import { Icon } from 'ui.icon-set.api.core';

export type WidgetDataHeaderType = {
	title: string,
	subtitle: string,
};
export type WidgetDataBodyType = {
	title: string,
	subtitle: string,
	subtitleDescription: ?string,
	roundContent: HTMLElement | Icon | {
		posterUrl: string,
		videos: {
			url: string,
			type: string,
		}[],
	},
	moreLabel: string,
	code: ?string,
};
export type WidgetDataType = {
	header: {
		icon: HTMLElement | Icon,
		top: WidgetDataHeaderType,
		info: WidgetDataBodyType
	},
	items?: ResponsePackageDataType[],
}
