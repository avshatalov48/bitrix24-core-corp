import {Dom, Event, Loc, Runtime, Tag} from 'main.core';
import { SettingsField, BaseSettingsElement } from "ui.form-elements.field";
import { TextInput } from "ui.form-elements.view";

import { BaseEvent, EventEmitter } from 'main.core.events';
import { StackWidget } from 'ui.uploader.stack-widget';
import { SiteThemePickerOptions } from './site-theme-picker-field';
import {Loader} from "main.loader";

export type SiteLogoOptions = {
	id: string,
	src: string,
	width: number,
	height: number
};

export type SiteLogoFieldType = {
	parent: BaseSettingsElement,
	siteLogoLabel: ?string,
	siteLogoOptions: ?SiteLogoOptions,
	canUserEditLogo: boolean
};


class HiddenInput extends TextInput
{
	constructor(params)
	{
		super({
			inputName: 'logo',
			isEnable: params.isEnable,
			defaultValue: 'default',
			bannerCode: 'limit_admin_logo',
			helpDeskCode: 123,
			helpMessageProvider: () => {}
		});
		this.getInputNode().type = 'hidden';
		this.getInputNode().disabled = true;
	}

	renderContentField(): HTMLElement
	{
		return this.getInputNode();
	}
}

export class SiteLogoField extends SettingsField
{
	#content: HTMLElement;
	#uploader: StackWidget;
	#siteLogo: ?SiteLogoOptions;

	#hiddenContainer: HTMLElement;
	#hiddenRemoveInput: HTMLElement;

	#loader: Loader;

	constructor(params: SiteLogoFieldType)
	{
		params.fieldView = new HiddenInput({
			isEnable: params.canUserEditLogo,
		});
		super(params);
		this.#siteLogo = params.siteLogoOptions;
		this.siteLogoLabel = params.siteLogoLabel;

		this.setEventNamespace('BX.Intranet.Settings');
	}

	initUploader({TileWidget, StackWidget, StackWidgetSize}): this
	{
		const defaultOptions = {
			maxFileCount: 1,
			acceptOnlyImages: true,
			multiple: false,
			acceptedFileTypes: ['image/png'],
			events: {
				'onError': function(event) {
					console.error('File Uploader onError', event.getData().error);
				},
				'File:onError': this.onFileError.bind(this),
				'File:onAdd': this.onLogoAdd.bind(this),
				'File:onRemove': this.onLogoRemove.bind(this),
				'onBeforeFilesAdd': this.getFieldView().isEnable() ? () => {
				} : (event: BaseEvent) => {
					this.getFieldView().showBanner();
					event.preventDefault();
				}
			},

			allowReplaceSingle: true,
			hiddenFieldName: 'logo_file',
			hiddenFieldsContainer: this.#getFileContainer(),
			assignAsFile: true,

			// imageMaxWidth: 444,
			// imageMaxHeight: 110,

			// imageMaxFileSize?: number,
			// imageMinFileSize?: number,

			imageResizeWidth: 444,
			imageResizeHeight: 110,
			imageResizeMode: 'contain',
			imageResizeMimeType: 'image/png',

			imagePreviewWidth: 444,
			imagePreviewHeight: 110,
			imagePreviewResizeMode: 'contain',

			// serverOptions: ServerOptions,
			// filters?: Array<{ type: FilterType, filter: Filter | Function | string, options: { [key: string]: any } }>,
			files: this.#siteLogo ? [
				[1, {
					serverFileId: this.#siteLogo.id,
					serverId: this.#siteLogo.id,
					type: 'image/png',

					width: this.#siteLogo.width,
					height: this.#siteLogo.height,
					treatImageAsFile: true,
					downloadUrl: this.#siteLogo.src,

					serverPreviewUrl: this.#siteLogo.src,
					serverPreviewWidth: this.#siteLogo.width,
					serverPreviewHeight: this.#siteLogo.height,

					src: this.#siteLogo.src,
					preload: true
				}]
			] : null,
		};
		this.#uploader = new StackWidget(defaultOptions, {size: StackWidgetSize.LARGE });
		return this;
	}

	onFileError(event): void
	{
		console.error('File Error', event.getData().error);
		EventEmitter.subscribeOnce(
			EventEmitter.GLOBAL_TARGET,
			this.getEventNamespace() + ':onAfterShowPage',
			this.removeFailedLogo.bind(this),
		);

		const tabField = this.getParentElement();

		if (tabField)
		{
			EventEmitter.subscribeOnce(
				tabField.getFieldView(),
				'onActive',
				this.removeFailedLogo.bind(this),
			);
		}
	}

	removeFailedLogo(): void
	{
		const logo = this.#uploader.getUploader().getFiles()[0];

		if (logo && logo.isLoadFailed())
		{
			this.#uploader.getUploader().removeFiles();
		}
	}

	onLogoAdd(event: BaseEvent)
	{
		EventEmitter.emit(
			EventEmitter.GLOBAL_TARGET,
			this.getEventNamespace() + ':Portal:Change',
			new BaseEvent({ data: { logo: { src: event.getData().file.getClientPreviewUrl() } } })
		);

		this.getFieldView().getInputNode().disabled = false;
		this.getFieldView().getInputNode().value = 'add';
		this.getFieldView().getInputNode().form.dispatchEvent(new window.Event('change'));
	}

	onLogoRemove(event: BaseEvent)
	{
		EventEmitter.emit(
			EventEmitter.GLOBAL_TARGET,
			this.getEventNamespace() + ':Portal:Change',
			new BaseEvent({ data: { logo: null } })
		);
		this.getFieldView().getInputNode().disabled = false;
		this.getFieldView().getInputNode().value = 'remove';
		this.getFieldView().getInputNode().form.dispatchEvent(new window.Event('change'));
	}

	getName(): string
	{
		return 'logo';
	}

	cancel(): void
	{
	}

	#getFileContainer(): HTMLElement
	{
		if (!this.#hiddenContainer)
		{
			this.#hiddenContainer = document.createElement('div');
		}
		return this.#hiddenContainer;
	}

	render(): HTMLElement
	{
		if (this.#content)
		{
			return this.#content;
		}

		this.#content = Tag.render`<div></div>`;
		this.#showLoader();

		Runtime
			.loadExtension('ui.uploader.stack-widget')
			.then((exports) => {
				this.initUploader(exports);
				this.#renderAfterLoad();
				this.#removeLoader();
			});

		return this.#content;
	}

	#renderAfterLoad(): void
	{
		const uploaderContent = Tag.render`<div></div>`;
		const content = Tag.render`<div>
				<div class="ui-section__field-label">${this.siteLogoLabel ?? Loc.getMessage('INTRANET_SETTINGS_SECTION_TAB_TITLE_WIDGET_LOGO_TITLE1')}</div>
				${uploaderContent}
				<div class="ui-section__field-label">${Loc.getMessage('INTRANET_SETTINGS_SECTION_TAB_TITLE_WIDGET_LOGO_TITLE2')}</div>
				${this.getFieldView().getInputNode()}
				${this.#getFileContainer()}
				${this.getFieldView().renderErrors()}
			</div>`
		;
		this.#uploader.renderTo(uploaderContent);
		Dom.replace(this.#content, content);
	}

	#showLoader()
	{
		this.#loader = new Loader({
			target: this.#content,
			color: 'rgba(82, 92, 105, 0.9)',
			mode: 'inline'
		});
		this
			.#loader
			.show()
			.then(() => {
				console.log('The loader is shown')
			})
		;
	}

	#removeLoader()
	{
		if (this.#loader)
		{
			this.#loader.destroy();
			this.#loader = null;
		}
	}
}
