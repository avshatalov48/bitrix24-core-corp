import { Reflection, Text, Type, Uri } from 'main.core';
import { MenuItem } from 'main.popup';

import 'sidepanel';

let instance = null;

class ListViewTypes
{
	static KANBAN = 'KANBAN';
	static LIST = 'LIST';
}

declare type UrlTemplatesSettings = {
	defaultRootUrlTemplates: UrlTemplates,
	customRootUrlTemplates: CustomRootUrlTemplates,
};

declare type UrlTemplates = Object<string, string>;

declare type CustomRootUrlTemplates = Object<number, UrlTemplates>;

/**
 * @memberOf BX.Crm
 */
class Router
{
	defaultRootUrlTemplates: UrlTemplates = {};
	customRootUrlTemplates: CustomRootUrlTemplates = {};
	currentViews: Object<number, string> = {};

	static get Instance(): Router
	{
		if ((window.top !== window) && Reflection.getClass('top.BX.Crm.Router'))
		{
			return window.top.BX.Crm.Router.Instance;
		}

		if (instance === null)
		{
			instance = new Router();
		}

		return instance;
	}

	/**
	 * @public
	 * @param params
	 * @return {BX.Crm.Router}
	 */
	setUrlTemplates(params: UrlTemplatesSettings): Router
	{
		if (Type.isPlainObject(params.defaultRootUrlTemplates))
		{
			this.defaultRootUrlTemplates = params.defaultRootUrlTemplates;
		}

		if (Type.isPlainObject(params.customRootUrlTemplates))
		{
			this.customRootUrlTemplates = params.customRootUrlTemplates;
		}

		return this;
	}

	setCurrentListView(entityTypeId: number, view: string): Router
	{
		this.currentViews[entityTypeId] = view;

		return this;
	}

	getCurrentListView(entityTypeId: number): string
	{
		return this.currentViews[entityTypeId] || ListViewTypes.LIST;
	}

	static openSlider(url: string | Uri, options: ?Object = null): Promise<?BX.SidePanel.Slider>
	{
		const preparedUrl = String(url);
		if (!Type.isStringFilled(preparedUrl))
		{
			return Promise.resolve();
		}

		let preparedOptions = Type.isPlainObject(options) ? options : {};
		preparedOptions = { cacheable: false, allowChangeHistory: true, events: {}, ...preparedOptions };

		return new Promise((resolve) => {
			preparedOptions.events.onClose = (event) => resolve(event.getSlider());

			BX.SidePanel.Instance.open(preparedUrl, preparedOptions);
		});
	}

	openTypeDetail(typeId: number, options: ?{}, queryParams: ?{}): ?Promise<?BX.SidePanel.Slider>
	{
		const preparedOptions = Type.isPlainObject(options) ? options : {};

		preparedOptions.width = 876;
		preparedOptions.allowChangeHistory = false;
		preparedOptions.cacheable = false;

		const uri = this.getTypeDetailUrl(typeId);
		if (uri)
		{
			if (Type.isPlainObject(queryParams))
			{
				uri.setQueryParams(queryParams);
			}

			return Router.openSlider(uri.toString(), preparedOptions);
		}

		return null;
	}

	openAutomatedSolutionDetail(automatedSolutionId: number = 0, options: {} = {}): ?Promise<?BX.SidePanel.Slider>
	{
		const preparedOptions = Type.isPlainObject(options) ? options : {};

		preparedOptions.width = 876;
		preparedOptions.allowChangeHistory = false;
		preparedOptions.cacheable = false;

		const uri = this.getAutomatedSolutionDetailUrl(automatedSolutionId);
		if (uri)
		{
			return Router.openSlider(uri, preparedOptions);
		}

		return null;
	}

	/**
	 * @protected
	 * @param component
	 * @param entityTypeId
	 * @return {string|null}
	 */
	getTemplate(component: string, entityTypeId: number = 0): ?string
	{
		if ((entityTypeId > 0) && Object.hasOwn(this.customRootUrlTemplates, entityTypeId))
		{
			return this.customRootUrlTemplates[entityTypeId][component] ?? null;
		}

		return this.defaultRootUrlTemplates[component] ?? null;
	}

	getTypeDetailUrl(entityTypeId: number = 0): ?Uri
	{
		const template = this.getTemplate('bitrix:crm.type.detail', entityTypeId);
		if (template)
		{
			return new Uri(template.replace('#entityTypeId#', entityTypeId));
		}

		return null;
	}

	getTypeListUrl(): ?Uri
	{
		const template = this.getTemplate('bitrix:crm.type.list');
		if (template)
		{
			return new Uri(template);
		}

		return null;
	}

	openTypeHelpPage()
	{
		Router.openHelper(null, 13_315_798);
	}

	static openHelper(event: Event = null, code: number = null)
	{
		if (event && Type.isFunction(event.preventDefault))
		{
			event.preventDefault();
		}

		if (top.BX.Helper && code > 0)
		{
			top.BX.Helper.show(`redirect=detail&code=${code}`);
		}
	}

	showFeatureSlider(event, item, sliderCode: string = 'limit_smart_process_automation')
	{
		Router.Instance.closeSettingsMenu(event, item);

		if (Reflection.getClass('BX.UI.InfoHelper.show'))
		{
			BX.UI.InfoHelper.show(sliderCode);
		}
	}

	/**
	 * For dynamic entities only.
	 * Does not support knowledge about whether kanban available or not.
	 *
	 * @param entityTypeId
	 * @param categoryId
	 */
	getItemListUrlInCurrentView(entityTypeId: number, categoryId: ?number = 0): ?Uri
	{
		const currentListView = this.getCurrentListView(entityTypeId);
		let template = null;
		if (currentListView === ListViewTypes.KANBAN)
		{
			template = this.getTemplate('bitrix:crm.kanban', entityTypeId);
		}
		else
		{
			template = this.getTemplate('bitrix:crm.item.list', entityTypeId);
		}

		if (template)
		{
			return new Uri(template.replace('#entityTypeId#', entityTypeId).replace('#categoryId#', categoryId));
		}

		return null;
	}

	/**
	 * For factory based entities only.
	 * Does not support knowledge about whether kanban available or not.
	 *
	 * @public
	 * @param entityTypeId
	 * @param categoryId
	 * @return {null|BX.Uri}
	 */
	getKanbanUrl(entityTypeId: number, categoryId: ?number = 0): ?Uri
	{
		const template = this.getTemplate('bitrix:crm.item.kanban', entityTypeId);
		if (template)
		{
			return new Uri(template.replace('#entityTypeId#', entityTypeId).replace('#categoryId#', categoryId));
		}

		return null;
	}

	/**
	 * For factory based entities only
	 *
	 * @public
	 * @param entityTypeId
	 * @param categoryId
	 * @return {null|BX.Uri}
	 */
	getItemListUrl(entityTypeId: number, categoryId: ?number = 0): ?Uri
	{
		const template = this.getTemplate('bitrix:crm.item.list', entityTypeId);
		if (template)
		{
			return new Uri(template.replace('#entityTypeId#', entityTypeId).replace('#categoryId#', categoryId));
		}

		return null;
	}

	openDocumentSlider(documentId: number): Promise<?BX.SidePanel.Slider>
	{
		return Router.openSlider(
			`/bitrix/components/bitrix/crm.document.view/slider.php?documentId=${documentId}`,
			{
				width: 1060,
				loader: '/bitrix/components/bitrix/crm.document.view/templates/.default/images/document_view.svg',
			},
		);
	}

	openSignDocumentSlider(documentId: number, memberHash: string): Promise<?BX.SidePanel.Slider>
	{
		// todo make a url template
		return Router.openSlider(
			`/bitrix/components/bitrix/crm.signdocument.view/slider.php?documentId=${documentId}&memberHash=${memberHash}`,
			{
				width: 1060,
			},
		);
	}

	openSignDocumentModifySlider(documentId: number): Promise<?BX.SidePanel.Slider>
	{
		return Router.openSlider(
			`/sign/doc/0/?docId=${documentId}&stepId=changePartner&noRedirect=Y`,
			{
				width: 1250,
			},
		);
	}

	openCalendarEventSlider(eventId: number, isSharing: boolean): Promise<?BX.SidePanel.Slider>
	{
		const sliderId = `crm-calendar-slider-${eventId}-${Math.floor(Math.random() * 1000)}`;

		return new (window.top.BX || window.BX).Calendar.SliderLoader(eventId, {
			sliderId,
			isSharing,
		}).show();
	}

	closeSettingsMenu(event, item)
	{
		if (item && Type.isFunction(item.getMenuWindow))
		{
			const window = item.getMenuWindow();
			if (window)
			{
				window.close();

				return;
			}
		}
		// eslint-disable-next-line unicorn/no-this-assignment
		const menu = this;
		if (menu && Type.isFunction(menu.close))
		{
			menu.close();
		}
	}

	closeToolbarSettingsMenuRecursively(event: PointerEvent, menuItem: MenuItem): void
	{
		let menuWindow = menuItem?.getMenuWindow();
		if (!menuWindow)
		{
			return;
		}

		while (menuWindow)
		{
			menuWindow.close();
			menuWindow = menuWindow.getParentMenuWindow();
		}
	}

	closeSliderOrRedirect(redirectTo: string | Uri, currentWindow: ?Window = null): void
	{
		const slider: ?BX.SidePanel.Slider = BX.SidePanel?.Instance?.getSliderByWindow(currentWindow ?? window);
		if (slider)
		{
			slider.close();

			return;
		}

		if (redirectTo instanceof Uri)
		{
			window.location.href = redirectTo.toString();
		}
		else
		{
			window.location.href = redirectTo;
		}
	}

	getAutomatedSolutionListUrl(): ?Uri
	{
		return new Uri('/automation/type/automated_solution/list/');
	}

	getAutomatedSolutionDetailUrl(id: number): ?Uri
	{
		let normalizedId = Text.toInteger(id);
		normalizedId = normalizedId > 0 ? normalizedId : 0;

		return new Uri(`/automation/type/automated_solution/details/${normalizedId}/`);
	}
}

export {
	Router,
};
