import { Type } from 'main.core';
import { VueUploaderWidget } from 'ui.uploader.vue';

import { UserFieldWidgetComponent } from './user-field-widget-component';

import type { UploaderOptions } from 'ui.uploader.core';
import type { UserFieldWidgetOptions } from './user-field-widget-options';

/**
 * @memberof BX.Disk.Uploader
 */
export default class UserFieldWidget extends VueUploaderWidget
{
	constructor(uploaderOptions: UploaderOptions, options: UserFieldWidgetOptions)
	{
		const widgetOptions: UserFieldWidgetOptions = Type.isPlainObject(options) ? { ...options } : {};
		super(UserFieldWidget.prepareUploaderOptions(uploaderOptions), widgetOptions);
	}

	defineComponent(): ?Function
	{
		return UserFieldWidgetComponent;
	}

	static prepareUploaderOptions(uploaderOptions: UploaderOptions): UploaderOptions
	{
		return {
			...UserFieldWidget.getDefaultUploaderOptions(),
			...(Type.isPlainObject(uploaderOptions) ? uploaderOptions : {}),
		};
	}

	static getDefaultUploaderOptions(): UploaderOptions
	{
		return {
			controller: 'disk.uf.integration.diskUploaderController',
			multiple: true,
			maxFileSize: null,
		};
	}
}
