import { Type, Event, Dom, Runtime, Loc, Reflection } from 'main.core';
import { BaseCache, MemoryCache } from 'main.core.cache';
import { EventEmitter } from 'main.core.events';
import { Popup } from 'main.popup';
import { ThemePickerDialog } from 'intranet.theme-picker.dialog';
import { Loader } from 'main.loader';
import { SidePanel, type SliderManager, type Slider } from 'main.sidepanel';

export type ThemePickerOptions = {
	themeId?: string,
	templateId?: string,
	theme?: Record<string, any>,
	siteId?: string,
	entityType?: string,
	entityId?: string | number,
	maxUploadSize?: number,
	ajaxHandlerPath?: string,
	isAdmin?: boolean,
	allowSetDefaultTheme?: boolean,
	isVideo?: boolean,
	link?: HTMLElement,
	behaviour?: string,
};

export type BaseTheme = {
	id: string,
	css: string[],
};

export type Theme = {
	id: string,
	css: string[],
	prefetchImages?: string[],
	previewImage?: string,
	previewColor?: string,
	width?: number,
	height?: number,
	resizable?: boolean,
	removable: boolean,
	new: boolean,
	custom: boolean,
	style?: string,
	sort?: number,
	default?: boolean,
	bgImageUrlToBlur?: string,
}

type ThemePickerInternalOptions = {
	themeId?: string,
	templateId?: string,
	appliedThemeId?: string,
	appliedTheme?: Record<string, any> | null,
	siteId?: string,
	entityType?: string,
	entityId?: string | number,
	maxUploadSize?: number,
	ajaxHandlerPath?: string | null,
	isAdmin?: boolean,
	allowSetDefaultTheme?: boolean,
	isVideo?: boolean,
	isBodyClassRemoved?: boolean,
	removedContextClassname?: string,
	behaviour?: string,
	returnValue?: string,
};

export class ThemePicker
{
	#cache: BaseCache<any> = new MemoryCache();
	#dialog: ThemePickerDialog | null = null;

	constructor(options: ThemePickerOptions)
	{
		this.#cache.set('options', {
			themeId: options.themeId,
			templateId: options.templateId,
			appliedThemeId: options.themeId,
			appliedTheme: Type.isPlainObject(options.theme) ? options.theme : null,
			siteId: options.siteId,
			entityType: options.entityType,
			entityId: options.entityId,
			maxUploadSize: Type.isNumber(options.maxUploadSize) ? options.maxUploadSize : 5 * 1024 * 1024,
			ajaxHandlerPath: Type.isStringFilled(options.ajaxHandlerPath) ? options.ajaxHandlerPath : null,
			isAdmin: options.isAdmin === true,
			allowSetDefaultTheme: options.allowSetDefaultTheme === true,
			isVideo: options.isVideo === true,
			behaviour: Type.isStringFilled(options.behaviour) ? options.behaviour : 'apply',
			returnValue: options.behaviour === 'return' ? options.themeId : null,
			isBodyClassRemoved: false,
		});

		if (Type.isStringFilled(options?.theme.bgImageUrlToBlur))
		{
			this.#trySaveBlurredImage(options.theme);
		}

		if (this.#getOptions().isVideo)
		{
			this.#setVideoEventHandlers();
		}

		const iframeMode = window !== window.top;
		if (iframeMode)
		{
			EventEmitter.subscribe('SidePanel.Slider:onLoad', this.#handleSliderLoad.bind(this));
			EventEmitter.subscribe('BX.Intranet.Bitrix24:ThemePicker:onThemeApply', this.#handleThemeApply.bind(this));
			EventEmitter.subscribe('BX.Intranet.Bitrix24:ThemePicker:onThemeAfterApply', this.#handleThemeAfterApply.bind(this));
		}

		this.#setPrintEventHandlers();
	}

	showDialog(scrollToTop: boolean = true): void
	{
		if (scrollToTop)
		{
			window.scrollTo({
				top: 0,
				left: 0,
			});
		}

		const dialogPopup = this.getDialogPopup();
		if (dialogPopup.isShown())
		{
			return;
		}

		dialogPopup.show();
		this.#getLoader().show(dialogPopup.getContentContainer());
		this.#loadThemeDialog();
	}

	closeDialog(): void
	{
		this.#dialog?.close();
		this.#dialog = null;

		this.getDialogPopup().close();
	}

	enableThemeListDialog(): void
	{
		Dom.removeClass(
			this.getDialogPopup().popupContainer,
			'theme-dialog-popup-window-container-disabled',
		);
	}

	disableThemeListDialog()
	{
		Dom.addClass(
			this.getDialogPopup().popupContainer,
			'theme-dialog-popup-window-container-disabled',
		);
	}

	getDialogPopup(): Popup
	{
		return this.#cache.remember('dialogPopup', () => {
			return new Popup({
				width: 800,
				height: 500,
				titleBar: Loc.getMessage('BITRIX24_THEME_DIALOG_TITLE'),
				fixed: true,
				closeByEsc: true,
				cacheable: false,
				bindOnResize: false,
				closeIcon: true,
				draggable: true,
				events: {
					onAfterClose: () => {
						this.closeDialog();
					},
					onDestroy: () => {
						this.#cache.delete('dialogPopup');
					},
				},
			});
		});
	}

	hideLoader(): void
	{
		this.#getLoader().hide();
	}

	getTemplateId(): ?string
	{
		return this.#getOptions().templateId;
	}

	getSiteId(): ?string
	{
		return this.#getOptions().siteId;
	}

	getEntityType(): ?string
	{
		return this.#getOptions().entityType;
	}

	getEntityId(): string | number | null
	{
		return this.#getOptions().entityId;
	}

	getAjaxHandlerPath(): ?string
	{
		return this.#getOptions().ajaxHandlerPath;
	}

	getVideoElement(): HTMLVideoElement
	{
		return document.querySelector('.theme-video');
	}

	getMaxUploadSize(): number
	{
		return this.#getOptions().maxUploadSize;
	}

	isCurrentUserAdmin(): boolean
	{
		return this.#getOptions().isAdmin;
	}

	canSetDefaultTheme(): boolean
	{
		return this.#getOptions().allowSetDefaultTheme;
	}

	getThemeId(): string
	{
		return this.#getOptions().themeId;
	}

	setThemeId(themeId: string): void
	{
		const options = this.#getOptions();
		options.themeId = themeId;
	}

	getAppliedThemeId(): string
	{
		return this.#getOptions().appliedThemeId;
	}

	setAppliedThemeId(themeId: string): void
	{
		const options = this.#getOptions();
		options.appliedThemeId = themeId;
	}

	getAppliedTheme(): ?Record<string, any>
	{
		return this.#getOptions().appliedTheme;
	}

	setThemes(themes: Theme[]): void
	{
		if (Type.isArray(themes))
		{
			this.#cache.set('themes', themes);
		}
	}

	getThemes(): Theme[]
	{
		return this.#cache.get('themes', []);
	}

	setBaseThemes(themes: Object<string, BaseTheme>): void
	{
		if (Type.isPlainObject(themes))
		{
			this.#cache.set('baseTheme', themes);
		}
	}

	getBaseThemes(): Object<string, BaseTheme>
	{
		return this.#cache.get('baseTheme', {});
	}

	getTheme(themeId: string): Theme | null
	{
		const themes = this.getThemes();
		for (const theme of themes)
		{
			if (theme.id === themeId)
			{
				return theme;
			}
		}

		return null;
	}

	removeTheme(themeId: string): void
	{
		this.setThemes(this.getThemes().filter((theme) => theme.id !== themeId));
	}

	updateTheme(themeId: string, theme: Theme): void
	{
		const themes = this.getThemes();
		const themeIndex = themes.findIndex((t) => t.id === themeId);

		if (themeIndex === -1 || !Type.isPlainObject(theme))
		{
			return;
		}

		for (const [key, value] of Object.entries(theme))
		{
			themes[themeIndex][key] = value;
		}
	}

	setReturnValue(themeId: string): void
	{
		const options = this.#getOptions();
		options.returnValue = themeId;
	}

	getReturnValue(): ?string
	{
		return this.#getOptions().returnValue;
	}

	needReturnValue(): boolean
	{
		return this.#getOptions().behaviour === 'return';
	}

	shouldPlayVideo(): boolean
	{
		const iframeMode = window !== window.top;

		if (iframeMode)
		{
			return SidePanel.Instance.getSliderByWindow(window) === SidePanel.Instance.getTopSlider();
		}

		return !SidePanel.Instance.isOpen();
	}

	playVideo(): void
	{
		const video = this.getVideoElement();

		if (video)
		{
			video.play().catch(() => {});
		}
	}

	pauseVideo(): void
	{
		const video = this.getVideoElement();

		if (video)
		{
			video.pause();
		}
	}

	handleWindowFocus(): void
	{
		if (this.shouldPlayVideo())
		{
			this.playVideo();
		}
	}

	handleWindowBlur(): void
	{
		this.pauseVideo();
	}

	handleSliderOpen(): void
	{
		this.handleWindowBlur();
	}

	handleSliderClose(): void
	{
		this.handleWindowFocus();
	}

	handleVisibilityChange(): void
	{
		const video = this.getVideoElement();

		if (video)
		{
			if (document.visibilityState === 'hidden')
			{
				this.handleWindowBlur();
			}
			else
			{
				this.handleWindowFocus();
			}
		}
	}

	handleBeforePrint(event): void
	{
		window.scroll(0, 0);

		if (Dom.hasClass(document.body, 'bitrix24-light-theme'))
		{
			Dom.removeClass(document.body, 'bitrix24-light-theme');
			this.#getOptions().isBodyClassRemoved = true;
		}

		const contextsToRemove = [
			'--ui-context-edge-dark',
			'--ui-context-edge-light',
			'--ui-context-content-dark',
		];

		contextsToRemove.forEach((contextToRemove) => {
			if (Dom.hasClass(document.body, contextToRemove))
			{
				Dom.removeClass(document.body, contextToRemove);
				this.#getOptions().removedContextClassname = contextToRemove;
			}
		});

		if (this.#getOptions().removedContextClassname)
		{
			Dom.addClass(document.body, '--ui-context-content-light');
		}
	}

	handleAfterPrint(): void
	{
		if (this.#getOptions().isBodyClassRemoved)
		{
			Dom.addClass(document.body, 'bitrix24-light-theme');
			this.#getOptions().isBodyClassRemoved = false;
		}

		if (this.#getOptions().removedContextClassname)
		{
			Dom.removeClass(document.body, 'ui-context-content-light');
			Dom.addClass(document.body, this.#getOptions().removedContextClassname);
			this.#getOptions().removedContextClassname = null;
		}
	}

	handleMediaPrint(mql): void
	{
		if (mql.matches)
		{
			this.handleBeforePrint();
		}
		else
		{
			this.handleAfterPrint();
		}
	}

	#getCurrentSlider(): Slider | null
	{
		const sliderManager: SliderManager = Reflection.getClass('top.BX.SidePanel.Instance');
		if (sliderManager === null)
		{
			return null;
		}

		return sliderManager.getSliderByWindow(window);
	}

	#handleSliderLoad(): void
	{
		this.#applySliderTheme();
	}

	#handleThemeApply(): void
	{
		this.#resetSliderTheme();
	}

	#handleThemeAfterApply(): void
	{
		this.#applySliderTheme();
	}

	#applySliderTheme(): void
	{
		const currentSlider = this.#getCurrentSlider();
		if (currentSlider === null)
		{
			return;
		}

		const background = Dom.style(document.body, '--air-theme-background');
		if (Type.isStringFilled(background))
		{
			Dom.style(currentSlider.getContentContainer(), 'background', background);
			Dom.style(document.body, '--air-theme-background', 'transparent');
		}
		else
		{
			Dom.style(currentSlider.getContentContainer(), 'background', null);
		}
	}

	#resetSliderTheme(): void
	{
		const currentSlider = this.#getCurrentSlider();
		if (currentSlider === null)
		{
			return;
		}

		Dom.style(document.body, '--air-theme-background', null);
		Dom.style(currentSlider.getContentContainer(), 'background', null);
	}

	getVideoContainer(): HTMLElement
	{
		return document.querySelector('.theme-video-container');
	}

	#getOptions(): ThemePickerInternalOptions
	{
		return this.#cache.get('options', {});
	}

	#setVideoEventHandlers(): void
	{
		Event.bind(window, 'focus', this.handleWindowFocus.bind(this));
		Event.bind(window, 'blur', this.handleWindowBlur.bind(this));

		EventEmitter.subscribe('OnIframeFocus', this.handleWindowFocus.bind(this));
		EventEmitter.subscribe('SidePanel.Slider:onOpenComplete', this.handleSliderOpen.bind(this));
		EventEmitter.subscribe('SidePanel.Slider:onCloseComplete', this.handleSliderClose.bind(this));

		const eventHandler = this.handleVisibilityChange.bind(this);
		Event.bind(window, 'load', eventHandler);
		Event.bind(document, 'visibilitychange', eventHandler);
	}

	#setPrintEventHandlers(): void
	{
		if ('onbeforeprint' in window)
		{
			Event.bind(window, 'beforeprint', this.handleBeforePrint.bind(this));
			Event.bind(window, 'afterprint', this.handleAfterPrint.bind(this));
		}
		else if (window.matchMedia)
		{
			window.matchMedia('print').addListener(this.handleMediaPrint.bind(this));
		}
	}

	#loadThemeDialog(): void
	{
		Runtime.loadExtension('intranet.theme-picker.dialog').then((exports) => {
			const { ThemePickerDialog: Dialog } = exports;
			this.#dialog ??= new Dialog(this);
			this.#dialog.show();
		}).catch((error) => {
			console.error(error);
		});
	}

	#getLoader(): Loader
	{
		return this.#cache.remember('loader', () => {
			return new Loader({
				size: 120,
			});
		});
	}

	#trySaveBlurredImage(theme: Theme): void
	{
		const saveBlurredImage = async () => {
			try
			{
				const { ThemePickerDialog: Dialog } = await Runtime.loadExtension('intranet.theme-picker.dialog');
				const dialog = new Dialog(this);
				await dialog.makeCustomThemeBlurred(theme, { ignoreErrors: true, applyTheme: true });
				await new Promise(() => {}); // Keep the lock exclusive between browser tabs
			}
			catch (ex)
			{
				console.error(ex);
			}
		};

		if (window.navigator.locks)
		{
			window.navigator.locks.request('save-blurred-image', saveBlurredImage);
		}
		else
		{
			void saveBlurredImage();
		}
	}
}
