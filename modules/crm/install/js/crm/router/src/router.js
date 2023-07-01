import { Reflection, Type, Uri } from 'main.core';

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

	static openSlider(url, options): Promise<?BX.SidePanel.Slider>
	{
		if (!Type.isPlainObject(options))
		{
			options = {};
		}
		options = { ...{ cacheable: false, allowChangeHistory: true, events: {} }, ...options };
		return new Promise((resolve) =>
		{
			if (Type.isString(url) && url.length > 1)
			{
				options.events.onClose = function(event)
				{
					resolve(event.getSlider());
				};
				BX.SidePanel.Instance.open(url, options);
			}
			else
			{
				resolve();
			}
		});
	}

	openTypeDetail(typeId: number, options: ?{}): ?Promise<?BX.SidePanel.Slider>
	{
		if (!Type.isPlainObject(options))
		{
			options = {};
		}
		options.width = 702;
		const uri = this.getTypeDetailUrl(typeId);
		if (uri)
		{
			return Router.openSlider(uri.toString(), options);
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
		if ((entityTypeId > 0) && this.customRootUrlTemplates.hasOwnProperty(entityTypeId))
		{
			if (this.customRootUrlTemplates[entityTypeId].hasOwnProperty(component))
			{
				return this.customRootUrlTemplates[entityTypeId][component];
			}

			return null;
		}

		return (this.defaultRootUrlTemplates.hasOwnProperty(component) ? this.defaultRootUrlTemplates[component] : null);
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
		Router.openHelper(null, 13315798);
	}

	static openHelper(event: Event = null, code: number = null)
	{
		if (event && Type.isFunction(event.preventDefault))
		{
			event.preventDefault();
		}
		if (top.BX.Helper && code > 0)
		{
			top.BX.Helper.show('redirect=detail&code=' + code);
		}
	}

    showFeatureSlider(event, item)
    {
        Router.Instance.closeSettingsMenu(event, item);
        BX.UI.InfoHelper.show('limit_smart_process_automation');
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
        let template;
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
			'/bitrix/components/bitrix/crm.document.view/slider.php?documentId=' + documentId,
			{
				width: 1060,
				loader: '/bitrix/components/bitrix/crm.document.view/templates/.default/images/document_view.svg',
			}
		);
	}

	openSignDocumentSlider(documentId: number, memberHash: string): Promise<?BX.SidePanel.Slider>
	{
		// todo make a url template
		return Router.openSlider(
			'/bitrix/components/bitrix/crm.signdocument.view/slider.php?documentId=' + documentId
			+ '&memberHash=' + memberHash
			,
			{
				width: 1060,
			}
		);
	}

	openSignDocumentModifySlider(documentId: number): Promise<?BX.SidePanel.Slider>
	{
		return Router.openSlider(
			'/sign/doc/0/?docId=' + documentId + '&stepId=changePartner&noRedirect=Y'
		);
	}

	openCalendarEventSlider(eventId: number, isSharing: boolean): Promise<?BX.SidePanel.Slider>
	{
		const sliderId = 'crm-calendar-slider-' + eventId + '-' + Math.floor(Math.random() * 1000);

		return new (window.top.BX || window.BX).Calendar.SliderLoader(eventId, {
			sliderId: sliderId,
			isSharing: isSharing
		}).show();
	}

    closeSettingsMenu(event, item)
    {
        if(item && Type.isFunction(item.getMenuWindow))
        {
            const window = item.getMenuWindow();
            if(window)
            {
                window.close();
                return;
            }
        }
        const menu = this;
        if(menu && Type.isFunction(menu.close))
        {
            menu.close();
        }
    }
}

export {
	Router
};
