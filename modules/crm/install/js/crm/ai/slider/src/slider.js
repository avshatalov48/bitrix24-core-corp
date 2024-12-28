import { Dom, Loc, Type } from 'main.core';
import { Button } from 'ui.buttons';
import { SidePanel } from 'ui.sidepanel';
import { Layout } from 'ui.sidepanel.layout';

import './style.css';

export class Slider
{
	DEFAULT_OPTIONS = {
		title: Loc.getMessage('CRM_COMMON_COPILOT'),
		allowChangeTitle: false,
		allowChangeHistory: false,
		cacheable: false,
		toolbar: this.getDefaultToolbarButtons,
		buttons: [],
		width: 795,
		extensions: [],
		events: {},
		label: {},
		design: {},
		sliderContentClass: null,
	};

	isOpen = false;

	#openedLayout: ?Layout = null;

	get openedLayout(): ?Layout {
		return this.#openedLayout;
	}

	constructor(options): void
	{
		this.initOptions(options);
	}

	initOptions(options): void
	{
		this.title = (Type.isString(options.title)) ? options.title : this.DEFAULT_OPTIONS.title;
		this.sliderTitle = (Type.isString(options.sliderTitle)) ? options.sliderTitle : this.DEFAULT_OPTIONS.title;
		this.sliderContentClass = (
			Type.isStringFilled(options.sliderContentClass)
				? options.sliderContentClass
				: this.DEFAULT_OPTIONS.sliderContentClass
		);
		this.toolbar = (Type.isFunction(options.toolbar)) ? options.toolbar : this.DEFAULT_OPTIONS.toolbar;
		this.buttons = Type.isFunction(options.buttons) ? options.buttons : this.DEFAULT_OPTIONS.buttons;
		this.cacheable = (Type.isBoolean(options.cacheable)) ? options.cacheable : this.DEFAULT_OPTIONS.cacheable;
		this.width = Type.isInteger(options.width) ? options.width : this.DEFAULT_OPTIONS.width;
		this.label = Type.isPlainObject(options.label) ? options.label : this.DEFAULT_OPTIONS.label;
		this.extensions = Type.isArray(options.extensions) ? options.extensions : this.DEFAULT_OPTIONS.extensions;
		this.events = Type.isPlainObject(options.events) ? options.events : this.DEFAULT_OPTIONS.events;
		this.design = Type.isPlainObject(options.design) ? options.design : this.DEFAULT_OPTIONS.design;

		// Need to buttons to always be transparent-white when enable DependOnTheme in Button
		this.enableLightThemeIntoSlider = Type.isBoolean(options.enableLightThemeIntoSlider)
			? options.enableLightThemeIntoSlider
			: true
		;

		this.allowChangeTitle = (Type.isBoolean(options.allowChangeTitle))
			? options.allowChangeTitle
			: this.DEFAULT_OPTIONS.allowChangeTitle
		;
		this.allowChangeHistory = (Type.isBoolean(options.allowChangeHistory))
			? options.allowChangeHistory
			: this.DEFAULT_OPTIONS.allowChangeHistory
		;

		this.setContent(options.content);
		this.url = Type.isString(options.url) ? options.url : this.getDefaultUrl();
	}

	setContent(content): void
	{
		if (!Type.isFunction(content))
		{
			this.content = () => {
				return content;
			};

			return;
		}

		this.content = content;
	}

	async open(): Boolean
	{
		this.#openedLayout = await this.makeLayout();
		this.isOpen = SidePanel.Instance.open(this.url, await this.getSliderOptions(this.#openedLayout));

		return this.isOpen;
	}

	close(): void
	{
		if (!this.isOpen)
		{
			return;
		}
		this.#openedLayout = null;
		SidePanel.Instance.close();
	}

	destroy(): void
	{
		SidePanel.Instance.destroy(this.url);
	}

	getInstance(): SidePanel
	{
		return SidePanel.Instance;
	}

	getDefaultUrl(): String
	{
		return 'crm.copilot-wrapper';
	}

	async makeLayout(): Promise<Layout>
	{
		return Layout.createLayout({
			title: this.sliderTitle,
			toolbar: this.toolbar,
			content: this.content,
			buttons: this.buttons,
			design: { section: false, ...this.design },
			extensions: ['crm.ai.slider', ...this.extensions],
		});
	}

	async getSliderOptions(layout: Layout): Object
	{
		return {
			contentClassName: this.getSliderContentClassName(),
			title: this.title,
			allowChangeTitle: this.allowChangeTitle,
			width: this.width,
			cacheable: this.cacheable,
			allowChangeHistory: this.allowChangeHistory,
			label: this.label,
			contentCallback: (slider) => {
				return layout.render();
			},
			events: this.events,
		};
	}

	getSliderContentClassName(): String
	{
		let className = 'crm-copilot-wrapper';

		if (this.enableLightThemeIntoSlider)
		{
			className += ' bitrix24-light-theme';
		}

		if (this.sliderContentClass)
		{
			className += ` ${this.sliderContentClass}`;
		}

		return className;
	}

	getDefaultToolbarButtons(): Array
	{
		return Slider.makeDefaultToolbarButtons();
	}

	static makeDefaultToolbarButtons(): Array
	{
		const helpdeskCode = '18799442';
		const helpButton = new Button({
			text: Loc.getMessage('CRM_COPILOT_WRAPPER_HELP_BUTTON_TITLE'),
			size: Button.Size.MEDIUM,
			color: Button.Color.LIGHT_BORDER,
			dependOnTheme: true,
			onclick: () => {
				if (top.BX.Helper)
				{
					top.BX.Helper.show(`redirect=detail&code=${helpdeskCode}`);
				}
			},
		});

		return [helpButton];
	}

	footerDisplay(show: boolean): void
	{
		if (!this.#openedLayout)
		{
			return;
		}

		if (this.#openedLayout.getFooterContainer())
		{
			Dom.style(this.#openedLayout.getFooterContainer(), 'display', show ? 'block' : 'none');
		}

		const footerAnchor = this.#openedLayout.getContainer()?.getElementsByClassName('ui-sidepanel-layout-footer-anchor')[0];
		if (footerAnchor)
		{
			Dom.style(footerAnchor, 'display', show ? 'block' : 'none');
		}
	}
}
