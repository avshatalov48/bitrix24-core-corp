import { Type, ajax as Ajax, Loc, Tag, Dom, Reflection } from 'main.core';
import { MemoryCache, type BaseCache } from 'main.core.cache';
import { EventEmitter, type BaseEvent } from 'main.core.events';
import { PopupWindowButton, PopupWindowButtonLink, Popup } from 'main.popup';
import { Uploader, UploaderEvent, type UploaderError, type UploaderFile } from 'ui.uploader.core';
import { Loader } from 'main.loader';

import { ThemePickerDialog } from './theme-picker-dialog';
import { blur } from './blur';

import { type ThemePicker } from 'intranet.theme-picker';

export class NewThemeDialog extends EventEmitter
{
	#themePicker: ThemePicker;
	#listDialog: ThemePickerDialog;
	#colorPicker: Object = null;
	#popup: Popup = null;
	#previewApplied: boolean = false;
	#origAppliedThemeId: string = null;
	#uploader: Uploader | null = null;
	#refs: BaseCache<HTMLElement> = new MemoryCache();
	#previewLoader: Loader | null = null;

	constructor(themePicker: ThemePicker, listDialog: ThemePickerDialog)
	{
		super();

		this.setEventNamespace('BX.Intranet.Bitrix24.ThemePicker.NewThemeDialog');

		this.#themePicker = themePicker;
		this.#listDialog = listDialog;
	}

	show(): void
	{
		this.#getPopup().setContent(this.#getContent());
		this.#getPopup().show();
		this.#themePicker.disableThemeListDialog();
	}

	destroy(): void
	{
		this.#resetResources();
		this.#getPopup().destroy();
		this.#popup = null;
		this.#refs = null;
	}

	getUploader(): Uploader
	{
		this.#uploader ??= new Uploader({
			multiple: false,
			acceptOnlyImages: true,
			allowReplaceSingle: true,
			imagePreviewWidth: 1920,
			imagePreviewHeight: 1080,
			// imagePreviewWidth: 4096,
			// imagePreviewHeight: 2160,
			imagePreviewMimeType: 'image/webp',
			imagePreviewMimeTypeMode: 'force',
			imagePreviewQuality: 0.85,
			imageMinWidth: 300,
			imageMinHeight: 200,
			imageMaxWidth: 10000,
			imageMaxHeight: 10000,
			imageMaxFileSize: 20 * 1024 * 1024,
			events: {
				[UploaderEvent.FILE_LOAD_START]: () => {
					this.#hidePreview();
					this.#previewLoader = new Loader({ size: 40 });
					this.#previewLoader.show(this.#getControl('field-file-preview'));
				},
				[UploaderEvent.FILE_ERROR]: (event: BaseEvent<{ error: UploaderError }>) => {
					const error: UploaderError = event.getData().error;
					this.#showError(`${error.getMessage()}<br>${error.getDescription()}`);
					this.#previewLoader?.destroy();
					this.#previewLoader = null;
				},
				[UploaderEvent.FILE_COMPLETE]: async (event: BaseEvent<{ file: UploaderFile }>) => {
					this.#hideError();
					const file: UploaderFile = event.getData().file;
					const preview = file.getClientPreview();

					// console.time('blur');
					const { file: bgImageBlurred, color } = await blur(preview, 7);
					// console.timeEnd('blur');

					const bgImageBlurredUrl = URL.createObjectURL(bgImageBlurred);

					file.setCustomData('bgImageBlurred', bgImageBlurred);
					file.setCustomData('bgImageBlurredUrl', bgImageBlurredUrl);

					this.#previewLoader?.destroy();
					this.#previewLoader = null;

					this.#setBgColor(color);
					this.#setTextColor(this.#chooseTextColor(color));
					this.#showPreview(this.#getBgImageUrl());
					this.#applyThemePreview();
				},
			},
		});

		return this.#uploader;
	}

	#resetResources(): void
	{
		if (this.#getBgImageBlurredUrl() !== null)
		{
			URL.revokeObjectURL(this.#getBgImageBlurredUrl());
		}

		this.#uploader?.destroy();
		this.#uploader = null;

		if (this.#previewApplied)
		{
			this.#listDialog.applyTheme(this.#origAppliedThemeId);
		}

		this.#removeThemePreview();

		this.#themePicker.enableThemeListDialog();
	}

	#getBgImage(): UploaderFile | null
	{
		return this.getUploader().getFiles()[0] || null;
	}

	#getBgImageUrl(): string | null
	{
		return this.#getBgImage()?.getPreviewUrl() || null;
	}

	#getBgImageBlurred(): File | null
	{
		return this.#getBgImage()?.getCustomData('bgImageBlurred') || null;
	}

	#getBgImageBlurredUrl(): string | null
	{
		return this.#getBgImage()?.getCustomData('bgImageBlurredUrl') || null;
	}

	#getBgColor(): string
	{
		const color = this.#getControl('field-bg-color').value;

		return this.#validateBgColor(color) ? color : null;
	}

	#getTextColor(): string
	{
		return this.#getControl('field-text-color').value;
	}

	#getControl(name: string): HTMLElement
	{
		return this.#getPopup().getContentContainer().querySelector(`.theme-dialog-${name}`);
	}

	#getControls(name: string): NodeList
	{
		return this.#getPopup().getContentContainer().querySelectorAll(`.theme-dialog-${name}`);
	}

	#getPopup(): Popup
	{
		if (this.#popup)
		{
			return this.#popup;
		}

		this.#popup = new Popup({
			width: 500,
			height: 560,
			fixed: true,
			titleBar: Loc.getMessage('BITRIX24_THEME_CREATE_YOUR_OWN_THEME'),
			closeByEsc: true,
			bindOnResize: false,
			closeIcon: true,
			draggable: true,
			cacheable: false,
			events: {
				onClose: () => {
					this.#resetResources();
				},
				onDestroy: () => {
					this.emit('onDestroy');
				},
			},
			buttons: [
				new PopupWindowButton({
					id: 'theme-dialog-create-button',
					text: Loc.getMessage('BITRIX24_THEME_DIALOG_CREATE_BUTTON'),
					className: 'popup-window-button-accept',
					events: {
						click: this.#handleCreateButtonClick.bind(this),
					},
				}),
				new PopupWindowButtonLink({
					text: Loc.getMessage('BITRIX24_THEME_DIALOG_CANCEL_BUTTON'),
					className: 'popup-window-button-link theme-dialog-button-link',
					events: {
						click: () => {
							this.#popup.close();
						},
					},
				}),
			],
		});

		return this.#popup;
	}

	#getColorPicker(): ?Object
	{
		if (this.#colorPicker)
		{
			return this.#colorPicker;
		}

		const ColorPicker = Reflection.getClass('BX.ColorPicker');

		if (ColorPicker)
		{
			this.#colorPicker = new ColorPicker({
				onColorSelected: this.#handleBgColorSelect.bind(this),
				popupOptions: {
					fixed: true,
				},
			});
		}

		return this.#colorPicker;
	}

	#handleCreateButtonClick(): void
	{
		const error = this.#validateForm();
		if (error !== null)
		{
			this.#showError(error);

			return;
		}

		const createButton = this.#getPopup().getButton('theme-dialog-create-button');
		if (Dom.hasClass(createButton.getContainer(), 'popup-window-button-wait'))
		{
			// double click protection
			return;
		}

		const data = new FormData();
		data.append('bgImage', this.#getBgImage() ? this.#getBgImage().getClientPreview() : '');
		data.append('bgImageBlurred', this.#getBgImageBlurred() || '');
		data.append('bgColor', this.#getBgColor() || '');
		data.append('textColor', this.#getTextColor() || '');
		data.append('action', 'create');
		data.append('sessid', Loc.getMessage('bitrix_sessid'));
		data.append('siteId', this.#themePicker.getSiteId());
		data.append('templateId', this.#themePicker.getTemplateId());

		let postSize = 0;
		for (const [key, value] of data.entries())
		{
			postSize += key.length;
			postSize += value instanceof Blob ? value.size : value.length;
		}

		if (postSize > this.#themePicker.getMaxUploadSize())
		{
			const limit = this.#themePicker.getMaxUploadSize() / 1024 / 1024;

			this.#showError(Loc.getMessage(
				'BITRIX24_THEME_FILE_SIZE_EXCEEDED',
				{ '#LIMIT#': `${limit.toFixed(0)}Mb` },
			));

			return;
		}

		const form = document.forms['theme-new-theme-form'];

		createButton.addClassName('popup-window-button-wait');
		Dom.addClass(form, 'theme-dialog-form-disabled');

		Ajax({
			url: this.#themePicker.getAjaxHandlerPath(),
			method: 'POST',
			dataType: 'json',
			preparePost: false,
			data,
			onsuccess: (response) => {
				if (response && response.success && response.theme)
				{
					this.#listDialog.addItem(response.theme);
					this.#listDialog.preloadTheme(response.theme.id, () => {
						createButton.removeClassName('popup-window-button-wait');
						Dom.removeClass(form, 'theme-dialog-form-disabled');

						this.#removeThemePreview();
						this.#getPopup().close();
					});
				}
				else
				{
					createButton.removeClassName('popup-window-button-wait');
					Dom.removeClass(form, 'theme-dialog-form-disabled');
					this.#showError(response.error || Loc.getMessage('BITRIX24_THEME_UNKNOWN_ERROR'));
				}
			},
			onfailure: () => {
				createButton.removeClassName('popup-window-button-wait');
				Dom.removeClass(form, 'theme-dialog-form-disabled');
				this.#showError(Loc.getMessage('BITRIX24_THEME_UNKNOWN_ERROR'));
			},
		});
	}

	#getContent(): HTMLElement
	{
		return Tag.render`
			<form name="theme-new-theme-form" method="post" onsubmit="${(event) => event.preventDefault()}">
				<div class="theme-dialog-form-alert">
					<div class="theme-dialog-form-alert-content"></div>
					<div
						class="theme-dialog-form-alert-remove"
						onclick="${this.#hideError.bind(this)}"
					></div>
				</div>
				<div class="theme-dialog-form">
					${this.#createField(Loc.getMessage('BITRIX24_THEME_THEME_BG_IMAGE'), this.#getBgImageField())}
					${this.#createField(Loc.getMessage('BITRIX24_THEME_THEME_BG_COLOR'), this.#getBgColorField())}
					${this.#createField(Loc.getMessage('BITRIX24_THEME_THEME_TEXT_COLOR'), this.#getTextColorField())}
				</div>
			</form>
		`;
	}

	#showError(error: string): void
	{
		Dom.addClass(this.#getControl('form-alert'), 'theme-dialog-form-alert-show');
		this.#getControl('form-alert-content').innerHTML = error;
	}

	#hideError(): void
	{
		Dom.removeClass(this.#getControl('form-alert'), 'theme-dialog-form-alert-show');
	}

	#createField(title: string, field: HTMLElement): HTMLElement
	{
		return Tag.render`
			<div class="theme-dialog-field">
				<div class="theme-dialog-field-label">${title}</div>
				<div class="theme-dialog-field-value">${field}</div>
			</div>
		`;
	}

	#getBgColorField(): HTMLElement
	{
		return Tag.render`
			<div class="theme-dialog-field-textbox-wrapper" onclick="${this.#handleBgColorClick.bind(this)}">
				<div class="theme-dialog-field-textbox-color"></div>
				<input
					type="text"
					placeholder=""
					name="bgColor"
					maxlength="7"
					class="theme-dialog-field-textbox theme-dialog-field-bg-color"
					oninput="${this.#handleBgColorChange.bind(this)}"
					autocomplete="off"
				>
				<div
					class="theme-dialog-field-textbox-remove"
					onclick="${this.#handleBgColorClear.bind(this)}"
				></div>
			</div>
		`;
	}

	#getBgImageField(): HTMLElement
	{
		return this.#refs.remember('image-field', () => {
			const imageField = Tag.render`
				<div class="theme-dialog-field-file">
					<div
						class="theme-dialog-field-button"
						ondragenter="${this.#handleBgImageEnter.bind(this)}"
						ondragleave="${this.#handleBgImageLeave.bind(this)}"
						ondragover="${this.#handleBgImageOver.bind(this)}"
					>
						<div class="theme-dialog-field-file-preview"></div>
						<div class="theme-dialog-field-file-text">
							<span class="theme-dialog-field-file-add">
								${Loc.getMessage('BITRIX24_THEME_UPLOAD_BG_IMAGE')}
							</span>
							<span class="theme-dialog-field-file-add-info">
								${Loc.getMessage('BITRIX24_THEME_DRAG_BG_IMAGE')}
							</span>
						</div>
					</div>
				</div>
			`;

			this.getUploader().assignBrowse(imageField);
			this.getUploader().assignDropzone(imageField);

			return imageField;
		});
	}

	#validateForm(): ?string
	{
		const bgImage = this.#getBgImage();
		const bgColor = this.#getControl('field-bg-color').value;

		if (Type.isStringFilled(bgColor) && !this.#validateBgColor(bgColor))
		{
			return Loc.getMessage('BITRIX24_THEME_WRONG_BG_COLOR');
		}

		if (!bgImage && !Type.isStringFilled(bgColor))
		{
			return Loc.getMessage('BITRIX24_THEME_EMPTY_FORM_DATA');
		}

		if (bgImage && bgImage.isFailed())
		{
			return `${bgImage.getError().getMessage()}<br>${bgImage.getError().getDescription()}`;
		}

		return null;
	}

	#validateBgColor(color: string): boolean
	{
		return Type.isStringFilled(color) && color.match(/^#([\dA-Fa-f]{6})$/);
	}

	#showPreview(previewUrl: string): void
	{
		const preview = this.#getControl('field-file-preview');
		Dom.style(preview, 'background-image', `url("${encodeURI(previewUrl)}")`);
	}

	#hidePreview(): void
	{
		const preview = this.#getControl('field-file-preview');
		Dom.style(preview, 'background-image', null);
	}

	#handleBgImageEnter(event: DragEvent): void
	{
		Dom.addClass(this.#getControl('field-button'), 'theme-dialog-field-button-hover');
		event.stopPropagation();
		event.preventDefault();
	}

	#handleBgImageLeave(event: DragEvent): void
	{
		Dom.removeClass(this.#getControl('field-button'), 'theme-dialog-field-button-hover');

		event.stopPropagation();
		event.preventDefault();
	}

	#handleBgImageOver(event: DragEvent): void
	{
		event.stopPropagation();
		event.preventDefault();
	}

	#handleBgColorClick(): void
	{
		this.#getColorPicker().open({
			bindElement: this.#getControl('field-bg-color'),
		});
	}

	#handleBgColorChange(): void
	{
		if (this.#getBgColor())
		{
			this.#hideError();
		}

		this.#applyThemePreview();
	}

	#handleBgColorClear(event: MouseEvent): void
	{
		this.#getColorPicker().close();

		Dom.removeClass(this.#getControl('field-bg-color'), 'theme-dialog-field-textbox-not-empty');
		this.#getControl('field-bg-color').value = '';
		Dom.style(this.#getControl('field-textbox-color'), 'backgroundColor', null);

		this.#applyThemePreview();

		event.stopPropagation();
	}

	#handleBgColorSelect(color: string): void
	{
		this.#setBgColor(color);
		this.#hideError();
		this.#applyThemePreview();
	}

	#setBgColor(color: string): void
	{
		this.#getControl('field-bg-color').value = color;
		Dom.addClass(this.#getControl('field-bg-color'), 'theme-dialog-field-textbox-not-empty');
		Dom.style(this.#getControl('field-textbox-color'), 'backgroundColor', color);
	}

	#setTextColor(color: 'light' | 'dark'): void
	{
		const switchers = this.#getControls('field-button-switcher-item');

		[].forEach.call(switchers, (switcher) => {
			if (switcher.dataset.textColor === color)
			{
				Dom.addClass(switcher, 'theme-dialog-field-button-switcher-item-pressed');
			}
			else
			{
				Dom.removeClass(switcher, 'theme-dialog-field-button-switcher-item-pressed');
			}
		});

		this.#getControl('field-text-color').value = color;
	}

	#getTextColorField(): HTMLElement
	{
		return Tag.render`
			<div class="theme-dialog-field-button-switcher">
				<div
					class="theme-dialog-field-button-switcher-item theme-dialog-field-button-switcher-item-left theme-dialog-field-button-switcher-item-pressed"
					data-text-color="light"
					onclick="${this.#handleSwitcherClick.bind(this)}"
				>
					${Loc.getMessage('BITRIX24_THEME_THEME_LIGHT_COLOR')}
				</div>
				<div
					class="theme-dialog-field-button-switcher-item theme-dialog-field-button-switcher-item-right"
					data-text-color="dark"
					onclick="${this.#handleSwitcherClick.bind(this)}"
				>
					${Loc.getMessage('BITRIX24_THEME_THEME_DARK_COLOR')}
				</div>
				<input
					type="hidden"
					name="textColor"
					value="light"
					class="theme-dialog-field-text-color"
				>
			</div>
		`;
	}

	#handleSwitcherClick(event: MouseEvent): void
	{
		const color = event.target.dataset.textColor;
		this.#setTextColor(color);
		this.#applyThemePreview();
	}

	#applyThemePreview(): void
	{
		if (this.#getBgImage() === null && this.#getBgColor() === null)
		{
			if (this.#previewApplied)
			{
				this.#listDialog.applyTheme(this.#origAppliedThemeId);
			}

			return;
		}

		const baseThemes = this.#themePicker.getBaseThemes();
		const baseThemeId = this.#getTextColor();
		if (!baseThemes[baseThemeId] || !Type.isArray(baseThemes[baseThemeId].css))
		{
			return;
		}

		let style = ':root { ';

		if (this.#getBgImageUrl() !== null)
		{
			style += '--air-theme-bg-size: cover;';
			style += '--air-theme-bg-repeat: no-repeat;';
			style += '--air-theme-bg-position: 0 0;';
			style += '--air-theme-bg-attachment: fixed;';
			style += `--air-theme-bg-image: url("${encodeURI(this.#getBgImageUrl())}");`;

			if (this.#getBgImageBlurredUrl() !== null)
			{
				style += `--air-theme-bg-image-blurred: url("${encodeURI(this.#getBgImageBlurredUrl())}");`;
			}
		}

		if (this.#getBgColor())
		{
			style += `--air-theme-bg-color: ${this.#getBgColor()}; `;
		}

		style += ' }';

		if (!this.#previewApplied)
		{
			this.#origAppliedThemeId = this.#themePicker.getAppliedThemeId();
		}

		this.#listDialog.removeThemeAssets(this.#themePicker.getAppliedThemeId());

		this.#listDialog.applyThemeAssets({
			id: this.#getPreviewThemeId(),
			css: baseThemes[baseThemeId].css,
			style,
		});

		this.#themePicker.setAppliedThemeId(this.#getPreviewThemeId());

		this.#previewApplied = true;
	}

	#removeThemePreview(): void
	{
		this.#listDialog.removeThemeAssets(this.#getPreviewThemeId());
		this.#previewApplied = false;
	}

	#getPreviewThemeId(): string
	{
		return `${this.#getTextColor()}:custom_live_preview`;
	}

	#chooseTextColor(color: string): 'light' | 'dark'
	{
		let hexColor = color.replace(/^#/, '');
		if (hexColor.length === 3)
		{
			hexColor = [...hexColor].map((c) => c + c).join('');
		}

		const r = parseInt(hexColor.slice(0, 2), 16);
		const g = parseInt(hexColor.slice(2, 4), 16);
		const b = parseInt(hexColor.slice(4, 6), 16);

		const brightness = (r * 299 + g * 587 + b * 114) / 1000;

		return brightness > 186 ? 'dark' : 'light';
	}
}
