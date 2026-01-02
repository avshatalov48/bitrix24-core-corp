import { Type, ajax as Ajax, Loc, Tag, Dom, Reflection, Uri } from 'main.core';
import { EventEmitter, BaseEvent } from 'main.core.events';
import { PopupWindowButton, PopupWindowButtonLink } from 'main.popup';
import { Loader } from 'main.loader';

import { CheckboxButton } from './checkbox-button';
import { NewThemeDialog } from './new-theme-dialog';

import { blur } from './blur';

import { type ThemePicker } from 'intranet.theme-picker';

export class ThemePickerDialog
{
	#themePicker: ThemePicker;
	#newThemeDialog: NewThemeDialog = null;
	#checkboxButton: CheckboxButton = null;

	constructor(themePicker: ThemePicker)
	{
		this.#themePicker = themePicker;
	}

	show(): void
	{
		this.#loadThemes();
	}

	close(): void
	{
		this.#newThemeDialog?.destroy();
		this.#newThemeDialog = null;

		if (!this.#themePicker.needReturnValue())
		{
			this.applyTheme(this.#themePicker.getThemeId());
		}

		this.#themePicker.setThemes([]);
	}

	async makeCustomThemeBlurred(theme, options = {}): Promise<void>
	{
		if (!Type.isStringFilled(theme.bgImageUrlToBlur))
		{
			return Promise.resolve();
		}

		const lsKey = 'custom_themes_errors';
		const localStorage = Reflection.getClass('BX.localStorage');
		if (localStorage && localStorage.get(lsKey)?.includes(theme.id))
		{
			console.log('Skipping blurred image for theme', theme.id);

			return Promise.resolve();
		}

		const saveError = (timeout = 3600) => {
			if (localStorage)
			{
				const errorCustomThemes: string = localStorage.get(lsKey) || '';
				localStorage.set(lsKey, `${errorCustomThemes}|${theme.id}`, timeout);
			}
		};

		let bgImageBlurred = null;
		let color = null;
		try
		{
			const { image } = await this.#loadImage(theme.bgImageUrlToBlur);
			const result = await blur(image, 7);

			bgImageBlurred = result.file;
			color = result.color;
		}
		catch (error)
		{
			saveError();

			return Promise.reject(error);
		}

		const { ignoreErrors = false, applyTheme = false } = options;

		const data = new FormData();
		data.append('action', 'save-blurred-image');
		data.append('themeId', theme.id);
		data.append('bgImageBlurred', bgImageBlurred);
		data.append('bgColor', color);
		data.append('ignoreErrors', ignoreErrors);

		return this.#ajax(data)
			.then((response) => {
				if (response && response.success && response.theme)
				{
					this.#themePicker.updateTheme(response.theme.id, { bgImageUrlToBlur: null });
					this.#themePicker.updateTheme(response.theme.id, response.theme);
					this.preloadTheme(response.theme.id, () => {
						if (applyTheme)
						{
							this.removeThemeAssets(this.#themePicker.getAppliedThemeId());
							this.applyThemeAssets(response.theme);
						}
					});
				}
				else
				{
					console.log('Error while saving blurred image for theme', theme.id);
					saveError();
				}
			})
			.catch((error) => {
				console.error(error);
				saveError();
			})
		;
	}

	#loadImage(src: string): Promise<{ width: number, height: number, image: HTMLImageElement }>
	{
		return new Promise((resolve, reject) => {
			const image: HTMLImageElement = document.createElement('img');
			let url = src;
			if (url.startsWith('http') && !url.startsWith(window.location.origin))
			{
				url = '/bitrix/tools/landing/proxy.php';
				url += `?sessid=${Loc.getMessage('bitrix_sessid')}&url=${encodeURIComponent(this.#urnEncode(src))}`;
			}

			image.src = url;
			image.onerror = (error) => {
				reject(error);
			};

			image.onload = () => {
				resolve({
					width: image.naturalWidth,
					height: image.naturalHeight,
					image,
				});
			};
		});
	}

	#urnEncode(str: string): string
	{
		let result = '';
		const parts = str.split(/(:\/\/|:\d+\/|\/|\?|=|&)/);

		for (const [i, part] of parts.entries())
		{
			result += (i % 2 === 1) ? part : encodeURIComponent(part);
		}

		return result;
	}

	#ajax(data): Promise
	{
		const formData = Type.isFormData(data) ? data : new FormData();
		if (Type.isPlainObject(data))
		{
			for (const [key, value] of Object.entries(data))
			{
				formData.append(key, value);
			}
		}

		formData.append('sessid', Loc.getMessage('bitrix_sessid'));
		formData.append('templateId', this.#themePicker.getTemplateId());
		formData.append('siteId', this.#themePicker.getSiteId());
		formData.append('entityType', this.#themePicker.getEntityType());
		formData.append('entityId', this.#themePicker.getEntityId());

		return new Promise((resolve, reject) => {
			Ajax({
				method: 'POST',
				dataType: 'json',
				preparePost: false,
				url: this.#themePicker.getAjaxHandlerPath(),
				data: formData,
				onsuccess: resolve,
				onfailure: reject,
			});
		});
	}

	#loadThemes(): void
	{
		this.#ajax({ action: 'getlist' })
			.then((data) => {
				if (!data || !data.success || !Type.isArray(data.themes) || data.themes.length === 0)
				{
					this.#showFatalError();

					return;
				}

				this.#hideLoader();
				this.#themePicker.setThemes(data.themes);
				this.#themePicker.setBaseThemes(data.baseThemes);
				this.#setButtons();
				this.#renderLayout();
			})
			.catch(() => {
				this.#showFatalError();
			})
		;
	}

	#setButtons(): void
	{
		const buttons = [];

		if (this.#themePicker.isCurrentUserAdmin() && this.#themePicker.getEntityType() === 'USER')
		{
			buttons.push(this.#getCheckboxButton());
		}

		buttons.push(this.#getSaveButton(), this.#getCancelButton(), this.#getCreateButton());

		this.#themePicker.getDialogPopup().setButtons(buttons);
	}

	#getCheckboxButton(): CheckboxButton
	{
		if (!this.#checkboxButton)
		{
			this.#checkboxButton = new CheckboxButton(this.#themePicker);
		}

		return this.#checkboxButton;
	}

	#showFatalError(): void
	{
		this.#hideLoader();
		this.#themePicker.getDialogPopup().setContent(Loc.getMessage('BITRIX24_THEME_UNKNOWN_ERROR'));
		this.#themePicker.getDialogPopup().setButtons([this.#getCancelButton()]);
	}

	#hideLoader(): void
	{
		this.#themePicker.hideLoader();
	}

	#getSaveButton(): PopupWindowButton
	{
		return new PopupWindowButton({
			id: 'save-button',
			text: Loc.getMessage('BITRIX24_THEME_DIALOG_SAVE_BUTTON'),
			className: 'popup-window-button-accept',
			events: {
				click: () => {
					this.#handleSaveButtonClick();
				},
			},
		});
	}

	#handleSaveButtonClick(): void
	{
		if (this.#themePicker.needReturnValue())
		{
			const eventData = {
				theme: this.#themePicker.getTheme(this.#themePicker.getReturnValue()),
			};

			EventEmitter.emit(
				'Intranet.ThemePicker:onSave',
				new BaseEvent({ data: [eventData], compatData: [eventData] }),
			);
		}
		else if (
			this.#themePicker.getThemeId() !== this.#themePicker.getAppliedThemeId()
			|| this.#isCheckboxChecked()
		)
		{
			this.#saveTheme(this.#themePicker.getAppliedThemeId());
		}

		this.#themePicker.closeDialog();
	}

	#isCheckboxChecked(): boolean
	{
		return this.#checkboxButton ? this.#checkboxButton.isChecked() : false;
	}

	#getCancelButton(): PopupWindowButtonLink
	{
		return new PopupWindowButtonLink({
			id: 'cancel-button',
			text: Loc.getMessage('BITRIX24_THEME_DIALOG_CANCEL_BUTTON'),
			className: 'popup-window-button-link theme-dialog-button-link',
			events: {
				click: () => {
					this.#themePicker.closeDialog();
				},
			},
		});
	}

	#getCreateButton(): PopupWindowButtonLink
	{
		return new PopupWindowButtonLink({
			id: 'create-button',
			text: Loc.getMessage('BITRIX24_THEME_DIALOG_NEW_THEME'),
			className: 'popup-window-button-link theme-dialog-button-link theme-dialog-new-theme-btn',
			events: {
				click: () => {
					this.getNewThemeDialog().show();
				},
			},
		});
	}

	#renderLayout(): void
	{
		const container = Tag.render`
			<div class="theme-dialog-content"></div>
		`;

		this.#themePicker.getThemes().forEach((theme) => {
			Dom.append(this.createItem(theme), container);
		});

		this.#themePicker.getDialogPopup().setContent(
			Tag.render`
				<div class="theme-dialog-container">${container}</div>
			`,
		);
	}

	addItem(theme): void
	{
		const themes = this.#themePicker.getThemes();
		themes.unshift(theme);
		this.#themePicker.setThemes(themes);
		const newItem = this.createItem(theme);
		Dom.prepend(newItem, this.getContentContainer());
		this.#selectItem(newItem);
	}

	createItem(theme): HTMLElement
	{
		let className = 'theme-dialog-item';

		if (theme.video)
		{
			className += ' theme-dialog-item-video';
		}

		if (this.#themePicker.getAppliedThemeId() === theme.id)
		{
			className += ' theme-dialog-item-selected';
		}

		const div = Tag.render`
			<div
				class="${className}"
				data-theme-id="${theme.id}"
				onclick="${this.handleItemClick.bind(this)}"
			>
				<div class="theme-dialog-item-title">
					<span class="theme-dialog-item-title-text">${theme.title}</span>
					${theme.removable ? Tag.render`
						<div
							class="theme-dialog-item-remove"
							data-theme-id="${theme.id}"
							title="${Loc.getMessage('BITRIX24_THEME_REMOVE_THEME')}"
							onclick="${this.handleRemoveBtnClick.bind(this)}"
						></div>
					` : ''}
				</div>
				${theme.default === true ? this.#createDefaultLabel() : ''}
				${theme.new === true ? this.#createNewLabel() : ''}
			</div>
		`;

		if (Type.isStringFilled(theme.previewImage))
		{
			const img = Tag.render`
				<img 
					src="${encodeURI(theme.previewImage)}" 
					alt="${theme.title}" 
					class="theme-dialog-item-preview-img" 
					loading="lazy"
				>
			`;

			Dom.append(img, div);
		}

		if (Type.isStringFilled(theme.previewColor))
		{
			Dom.style(div, 'background-color', theme.previewColor);
		}

		return div;
	}

	getNewThemeDialog(): NewThemeDialog
	{
		if (!this.#newThemeDialog)
		{
			this.#newThemeDialog = new NewThemeDialog(this.#themePicker, this);
			this.#newThemeDialog.subscribe('onDestroy', () => {
				this.#newThemeDialog = null;
			});
		}

		return this.#newThemeDialog;
	}

	handleItemClick(event): void
	{
		this.#selectItem(this.getItemNode(event));
	}

	handleRemoveBtnClick(event): void
	{
		const item = this.getItemNode(event);
		if (!item)
		{
			return;
		}

		const themeId = item.dataset.themeId;
		const theme = this.#themePicker.getTheme(themeId);
		if (theme && theme.default)
		{
			const defaultThemeItem = this.getContentContainer().querySelector('[data-theme-id="default"]');
			if (defaultThemeItem)
			{
				Dom.append(this.#createDefaultLabel(), defaultThemeItem);
			}
		}

		this.#themePicker.removeTheme(themeId);
		Dom.remove(item);

		if (this.#themePicker.getAppliedThemeId() === themeId)
		{
			const firstItem = this.getContentContainer().children[0];
			this.#selectItem(firstItem);

			if (this.#themePicker.getThemeId() === themeId && firstItem && firstItem.dataset.themeId)
			{
				this.#saveTheme(firstItem.dataset.themeId);
			}
		}
		else if (this.#themePicker.getThemeId() === themeId)
		{
			this.#saveTheme(this.#themePicker.getAppliedThemeId());
		}

		void this.#ajax({ action: 'remove', themeId });
		event.stopPropagation();
	}

	#saveTheme(themeId): void
	{
		const theme = this.#themePicker.getTheme(themeId);
		const eventData = { theme, themeId, setDefaultTheme: this.#isCheckboxChecked() };

		EventEmitter.emit(
			'Intranet.ThemePicker:onSaveTheme',
			new BaseEvent({ data: eventData, compatData: [eventData] }),
		);

		const data = new FormData();
		data.append('action', 'save');
		data.append('themeId', themeId);
		data.append('setDefaultTheme', this.#isCheckboxChecked());

		void this.#ajax(data);

		this.#themePicker.setThemeId(themeId);
	}

	getItemNode(event): ?HTMLElement
	{
		if (!event || !event.target)
		{
			return null;
		}

		const item = event.target.closest('.theme-dialog-item');

		return Type.isDomNode(item) ? item : null;
	}

	getContentContainer(): HTMLElement
	{
		return this.#themePicker.getDialogPopup().getContentContainer().querySelector('.theme-dialog-content');
	}

	#createDefaultLabel(): HTMLElement
	{
		return Tag.render`
			<div class="theme-dialog-item-default">
				${Loc.getMessage('BITRIX24_THEME_DEFAULT_THEME')}
			</div>
		`;
	}

	#createNewLabel(): HTMLElement
	{
		return Tag.render`
			<div class="theme-dialog-item-new">
				${Loc.getMessage('BITRIX24_THEME_NEW_THEME')}
			</div>
		`;
	}

	#selectItem(item): void
	{
		if (!Type.isDomNode(item) || !Dom.hasClass(item, 'theme-dialog-item'))
		{
			return;
		}

		const themeId = item.dataset.themeId;

		this.getContentContainer()
			.querySelectorAll('div.theme-dialog-item[data-theme-id]')
			.forEach((currentItem) => Dom.removeClass(currentItem, 'theme-dialog-item-selected'))
		;

		Dom.addClass(item, 'theme-dialog-item-selected');

		if (this.#themePicker.needReturnValue())
		{
			this.#themePicker.setReturnValue(themeId);
		}
		else
		{
			const loader = new Loader({ size: 100 });
			loader.show(item);

			this.preloadTheme(themeId, async () => {
				const theme = this.#themePicker.getTheme(themeId);
				if (Type.isStringFilled(theme.bgImageUrlToBlur))
				{
					try
					{
						await this.makeCustomThemeBlurred(theme);
					}
					catch (ex)
					{
						console.error(ex);
					}
				}

				if (Dom.hasClass(item, 'theme-dialog-item-selected')) // by this time user could select another theme
				{
					loader.hide();
					this.applyTheme(themeId);
				}
			});
		}
	}

	applyTheme(themeId): void
	{
		if (!Type.isStringFilled(themeId) || themeId === this.#themePicker.getAppliedThemeId())
		{
			return;
		}

		const theme = this.#themePicker.getTheme(themeId);
		if (!theme)
		{
			return;
		}

		const eventData = { id: themeId, theme };
		EventEmitter.emit(
			'BX.Intranet.Bitrix24:ThemePicker:onThemeApply',
			new BaseEvent({ data: eventData, compatData: [eventData] }),
		);
		this.applyThemeAssets(theme);
		this.removeThemeAssets(this.#themePicker.getAppliedThemeId());
		this.#themePicker.setAppliedThemeId(themeId);

		this.appliedTheme = theme;

		EventEmitter.emit('BX.Intranet.Bitrix24:ThemePicker:onThemeAfterApply', { id: themeId, theme });
	}

	applyThemeAssets(assets): void
	{
		if (!assets || !Type.isArray(assets.css) || !Type.isStringFilled(assets.id))
		{
			return;
		}

		const head = document.head;
		const themeId = assets.id;

		assets.css.forEach((file) => {
			const link = Tag.render`
				<link type="text/css" rel="stylesheet" href="${file}" data-theme-id="${themeId}">
			`;
			head.appendChild(link);
		});

		if (Type.isStringFilled(assets.style))
		{
			const style = Tag.render`
				<style type="text/css" data-theme-id="${themeId}">
					${assets.style}
				</style>
			`;
			head.appendChild(style);
		}

		if (assets.video && Type.isPlainObject(assets.video.sources))
		{
			const sources = [];
			for (const type in assets.video.sources)
			{
				sources.push(Tag.render`<source type="video/${type}" src="${assets.video.sources[type]}">`);
			}

			const video = Tag.render`
				<div class="theme-video-container" data-theme-id="${themeId}">
					<video
						class="theme-video"
						poster="${assets.video.poster}"
						autoplay
						loop
						muted
						playsinline
						data-theme-id="${themeId}"
					>
						${sources}
					</video>
				</div>
			`;

			document.body.insertBefore(video, document.body.firstElementChild);
		}

		const appliedBaseThemeId = this.#themePicker.getAppliedThemeId().split(':')[0];
		const baseThemeId = themeId.split(':')[0];

		const contexts = {
			light: '--ui-context-edge-dark',
			dark: '--ui-context-edge-light',
			default: '--ui-context-edge-light',
		};

		if (appliedBaseThemeId !== baseThemeId)
		{
			Dom.removeClass(document.body, [`bitrix24-${appliedBaseThemeId}-theme`, contexts[appliedBaseThemeId]]);
			Dom.addClass(document.body, [`bitrix24-${baseThemeId}-theme`, contexts[baseThemeId]]);
		}
	}

	removeThemeAssets(themeId): void
	{
		const styles = document.head.querySelectorAll(`[data-theme-id="${themeId}"]`);
		for (const style of styles)
		{
			Dom.remove(style);
		}

		Dom.remove(document.querySelector(`body > [data-theme-id="${themeId}"]`));
	}

	preloadTheme(themeId, callback: Function): void
	{
		const fn = Type.isFunction(callback) ? callback : () => {};
		const theme = this.#themePicker.getTheme(themeId);

		if (!theme)
		{
			fn();

			return;
		}

		let asyncCount = 2; // preloadImages & preloadCss
		const onload = () => {
			asyncCount--;
			if (asyncCount === 0)
			{
				fn();
			}
		};

		this.preloadImages(theme.prefetchImages, onload);
		this.#preloadCss(theme.css, onload);
	}

	preloadImages(images, callback): void
	{
		const fn = Type.isFunction(callback) ? callback : () => {};

		if (!Type.isArrayFilled(images))
		{
			fn();

			return;
		}

		let loaded = 0;

		images.forEach((imageSrc) => {
			const image = new Image();
			image.src = encodeURI(imageSrc);
			image.onload = image.onerror = () => {
				loaded++;
				if (loaded === images.length)
				{
					fn();
				}
			};
		});
	}

	#preloadCss(css, fn): void
	{
		if (!Type.isArray(css))
		{
			if (Type.isFunction(fn))
			{
				fn();
			}

			return;
		}

		const iframe = Tag.render`<iframe src="javascript:void(0)" style="display: none"></iframe>`;
		document.body.appendChild(iframe);

		const iframeDoc = iframe.contentWindow.document;
		if (!iframeDoc.body)
		{
			// null in IE
			iframeDoc.write('<body></body>');
		}

		// to avoid a conflict between a theme's preload image and a <body>'s image from preload css files
		iframeDoc.body.style.cssText = 'background: #fff !important';

		BX.load(
			css,
			() => {
				Dom.remove(iframe);
				if (Type.isFunction(fn))
				{
					fn();
				}
			},
			iframeDoc,
		);
	}
}
