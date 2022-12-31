import {Loc, Tag, Type} from 'main.core';
import {Layout} from 'ui.sidepanel.layout';
import {EmbedOptions} from './types';
import {DataProvider} from "./data_provider";
import {Widget} from './tab/widget';
import {Openlines} from './tab/openlines';
import * as Form from './tab/form/index';
import {openFeedbackForm} from "./util";

import 'ui.design-tokens';
import 'ui.fonts.opensans';
import './embed.css';

const DEFAULT_WIDGETS_COUNT = 10;

export class Embed
{
	#options: Object;

	#publink: Form.Publink;
	#inline: Form.Inline;
	#auto: Form.Auto;
	#click: Form.Click;

	#widget: Widget;
	#openlines: Openlines;

	constructor(formId: number, options: EmbedOptions = {})
	{
		this.#options = options;

		const dataProvider = new DataProvider();

		this.#publink = new Form.Publink(formId, dataProvider);
		this.#inline = new Form.Inline(formId, dataProvider);
		this.#click = new Form.Click(formId, dataProvider);
		this.#auto = new Form.Auto(formId, dataProvider);

		const widgetsCount = options.widgetsCount ? options.widgetsCount : DEFAULT_WIDGETS_COUNT;
		this.#widget = new Widget(formId, {widgetsCount: widgetsCount});
		this.#openlines = new Openlines(formId, {widgetsCount: widgetsCount});
	}

	static openSlider(formId: number,  options: EmbedOptions = {activeMenuItemId: 'link'})
	{
		const instance = (new Embed(formId, {...options}));
		BX.SidePanel.Instance.open("crm.webform:embed:" + formId, {
			width: 1046,
			cacheable: false,
			...options.sliderOptions,
			contentCallback: () => instance.#render(options),
			events: {
				onCloseComplete: (event) => {
					if (Type.isFunction(options.onCloseComplete))
					{
						options.onCloseComplete();
					}
				},
				onLoad: (event) => {
					// BX.UI.Switcher.initByClassName();
					instance.#loadTab(options.activeMenuItemId);
				}
			},
		});
	}

	/**
	 * @deprecated open => openSlider
	 * @see Embed.openSlider
	 */
	static open(formId: number, options: EmbedOptions = {widgetsCount: DEFAULT_WIDGETS_COUNT, activeMenuItemId: 'inline'})
	{
		Embed.openSlider(formId, options);
	}

	#render(options: EmbedOptions): Promise
	{
		return Layout.createContent({
			extensions: ['crm.form.embed', 'ui.sidepanel-content', 'ui.forms', 'landing.ui.field.color', 'ui.switcher'],
			title: BX.Loc.getMessage('EMBED_SLIDER_MAIN_TITLE'),
			design: {
				section: false,
			},
			toolbar ({Button}): Array
			{
				return [
					new Button({
						// icon: Button.Icon.SETTING,
						color: Button.Color.LIGHT_BORDER,
						text: BX.Loc.getMessage('EMBED_SLIDER_TOOLBAR_BTN_FEEDBACK'),
						onclick: openFeedbackForm,
					}),
				];
			},
			content: () => {
				return Tag.render`
					<div class="crm-form-embed-slider-wrapper">
						<div data-menu-item-id="widgets">${this.#widget.render()}</div>
						<div data-menu-item-id="openlines">${this.#openlines.render()}</div>
						<div data-menu-item-id="inline">${this.#inline.render()}</div>
						<div data-menu-item-id="click">${this.#click.render()}</div>
						<div data-menu-item-id="auto">${this.#auto.render()}</div>
						<div data-menu-item-id="link">${this.#publink.render()}</div>
					</div>
				`;
			},
			menu: {
				items: [
					{
						label: Loc.getMessage('EMBED_SLIDER_PUBLIC_LINK_MENU'),
						id: 'link',
						onclick: () => this.#loadTab('link'),
						active: options.activeMenuItemId === 'link',
					},
					{
						label: Loc.getMessage('EMBED_SLIDER_WIDGET_MENU'),
						id: 'widgets',
						onclick: () => this.#loadTab('widgets'),
						active: options.activeMenuItemId === 'widgets',
					},
					{
						label: Loc.getMessage('EMBED_SLIDER_OPENLINES_MENU'),
						id: 'openlines',
						onclick: () => this.#loadTab('openlines'),
						active: options.activeMenuItemId === 'openlines',
					},
					{
						label: Loc.getMessage('EMBED_SLIDER_INLINE_MENU'),
						id: 'inline',
						onclick: () => this.#loadTab('inline'),
						active: options.activeMenuItemId === 'inline',
					},
					{
						label: Loc.getMessage('EMBED_SLIDER_CLICK_MENU'),
						id: 'click',
						onclick: () => this.#loadTab('click'),
						active: options.activeMenuItemId === 'click',
					},
					{
						label: Loc.getMessage('EMBED_SLIDER_AUTO_MENU'),
						id: 'auto',
						onclick: () => this.#loadTab('auto'),
						active: options.activeMenuItemId === 'auto',
					},
				]
			},
			buttons: ({closeButton}) => {
				return [closeButton];
			},
		});
	}

	#loadTab(tabName: string)
	{
		switch (tabName)
		{
			case 'widgets':
				this.#widget.load();
				break;
			case 'openlines':
				this.#openlines.load();
				break;
			case 'inline':
				this.#inline.load();
				break;
			case 'click':
				this.#click.load();
				break;
			case 'auto':
				this.#auto.load();
				break;
			case 'link':
			default:
				this.#publink.load();
				break;
		}
	}
}