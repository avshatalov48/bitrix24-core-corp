import { BaseEvent, EventEmitter } from 'main.core.events';
import { BitrixVue, VueCreateAppResult } from 'ui.vue3';
import { Api } from 'humanresources.hcmlink.api';
import { Dom, Loc, Type } from 'main.core';
import { HumanresourcesHcmlinkMapper } from './app';
import { EventList } from './types';
import { Layout } from 'ui.sidepanel.layout';

type MapperOptions = {
	companyId: number,
	userIds: ?Set,
	mode: 'direct' | 'reverse',
};

/**
 * An entry point of data-mapper
 */
export class Mapper
{
	#container: HTMLElement;
	layout: Layout;
	#application: VueCreateAppResult | null = null;
	api: Api;
	options: MapperOptions;
	footerDisplayPointer: function;

	static MODE_DIRECT = 'direct';
	static MODE_REVERSE = 'reverse';

	constructor(options: MapperOptions)
	{
		this.api = new Api();
		this.options = options;
		this.footerDisplayPointer = this.footerDisplay.bind(this); // for correct sub/unsub

		if (Type.isNil(this.options.userIds))
		{
			this.options.userIds = new Set();
		}
	}

	static openSlider(
		options: MapperOptions,
		sliderOptions: { onCloseHandler: () => void },
	): void
	{
		let closure = null;
		BX.SidePanel.Instance.open('humanresources:mapper', {
			width: 800,
			loader: 'default-loader',
			cacheable: false,
			contentCallback: () => {
				return top.BX.Runtime.loadExtension('humanresources.hcmlink.data-mapper').then((exports) => {
					closure = new exports.Mapper(options);

					return closure.getLayout();
				});
			},
			events: {
				onClose: () => {
					sliderOptions?.onCloseHandler();
					closure.unmount();
				},
				onLoad: () => {
					// Here we need to get rid of title to replace the entire toolbar with our own markup
					// Why we just don't pass the title at all? If we don't pass it, then toolbar will not render too
					Dom.remove(closure.layout.getContainer().querySelector('.ui-sidepanel-layout-title'));
					// Add a class to differentiate this layout from other layouts
					Dom.addClass(closure.layout.getContainer().querySelector('.ui-sidepanel-layout-header'), 'hr-hcmlink-sync__toolbar');
				},
			},
		});
	}

	renderTo(container: HTMLElement)
	{
		Dom.append(this.render(), container);
	}

	render(): HTMLElement
	{
		this.#container = document.createElement('div');
		if (this.#application === null)
		{
			this.#application = BitrixVue.createApp(HumanresourcesHcmlinkMapper, {
				companyId: this.options.companyId,
				mode: this.options.mode,
				userIdCollection: [...this.options.userIds],
				toolbarContainer: '.hr-hcmlink-sync__toolbar .ui-sidepanel-layout-toolbar',
				api: this.api,
			});
			EventEmitter.subscribe(EventList.HR_DATA_MAPPER_FOOTER_DISPLAY, this.footerDisplayPointer);
			Dom.style(this.#container, 'height', '100%');
			this.component = this.#application.mount(this.#container);
		}

		return this.#container;
	}

	unmount(): void
	{
		EventEmitter.unsubscribe(EventList.HR_DATA_MAPPER_FOOTER_DISPLAY, this.footerDisplayPointer);
		this.#application.unmount();
	}

	async getLayout(): Promise<any>
	{
		const getContentLayout = function(): HTMLElement {
			return this.render();
		}.bind(this);

		const saveAction = async function(): Promise<any> {
			const collection = Object.values(this.component.getUserMappingSet());

			return this.api.saveMapping({
				collection,
				companyId: this.options.companyId,
			});
		}.bind(this);

		const prepareNextUsers = async function(): Promise<any> {
			this.component.prepareNextUsers();
		}.bind(this);

		this.layout = await Layout.createLayout({
			extensions: [
				'humanresources.hcmlink.data-mapper',
				'ui.entity-selector',
				'ui.icon-set.actions',
				'ui.select',
				'popup',
			],
			title: Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_SLIDER_TITLE'),
			toolbar()
			{
				// We need to pass at least empty array for ui-sidepanel-layout-toolbar to appear
				return [];
			},
			content(): HTMLElement
			{
				return getContentLayout();
			},
			buttons({ cancelButton, SaveButton }): Array
			{
				return [
					new SaveButton({
						text: Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_BUTTON_SAVE_AND_CONTINUE'),
						async onclick() {
							const result = await saveAction();
							if (result)
							{
								await prepareNextUsers();
							}
						},
						round: true,
					}),
					cancelButton,
				];
			},
		});

		return this.layout.render();
	}

	footerDisplay(showEvent: BaseEvent): void
	{
		if (!this.layout)
		{
			return;
		}

		if (this.layout.getFooterContainer())
		{
			Dom.style(this.layout.getFooterContainer(), 'display', showEvent.data ? 'block' : 'none');
		}

		const footerAnchor = this.layout.getContainer()?.getElementsByClassName('ui-sidepanel-layout-footer-anchor')[0];
		if (footerAnchor)
		{
			Dom.style(footerAnchor, 'display', showEvent.data ? 'block' : 'none');
		}
	}
}
