import {Reflection, Event, ajax as Ajax, Text} from "main.core";
import {EventEmitter} from "main.core.events";
import {BaseButton, ButtonManager} from "ui.buttons";
import {Router} from "crm.router";
import {Menu} from "main.popup";

const namespace = Reflection.namespace('BX.Crm');

let instance = null;

class ToolbarEvents
{
	static TYPE_UPDATED = 'TypeUpdated';
	static CATEGORIES_UPDATED = 'CategoriesUpdated';
}

class ToolbarComponent extends EventEmitter
{
	constructor() {
		super();

		this.setEventNamespace('BX.Crm.ToolbarComponent');

		Event.ready(this.bindEvents.bind(this));
	}

	static get Instance(): ToolbarComponent
	{
		if ( (window.top !== window) && Reflection.getClass('top.BX.Crm.ToolbarComponent') )
		{
			return window.top.BX.Crm.ToolbarComponent.Instance;
		}

		if(instance === null)
		{
			instance = new ToolbarComponent();
		}

		return instance;
	}

	bindEvents()
	{
		const buttonNode = document.querySelector('[data-role="bx-crm-toolbar-categories-button"]');
		if (buttonNode)
		{
			const entityTypeId = Number(buttonNode.dataset.entityTypeId);
			const button = ButtonManager.createFromNode(buttonNode);
			if (button && entityTypeId > 0)
			{
				this.subscribeCategoriesUpdatedEvent(() => {
					this.reloadCategoriesMenu(button, entityTypeId, buttonNode.dataset.categoryId);
				});
			}
		}
	}

	emitTypeUpdatedEvent(data)
	{
		this.emit(ToolbarEvents.TYPE_UPDATED, data);
	}

	emitCategoriesUpdatedEvent(data)
	{
		this.emit(ToolbarEvents.CATEGORIES_UPDATED, data);
	}

	subscribeTypeUpdatedEvent(callback)
	{
		this.subscribe(ToolbarEvents.TYPE_UPDATED, callback);
	}

	subscribeCategoriesUpdatedEvent(callback)
	{
		this.subscribe(ToolbarEvents.CATEGORIES_UPDATED, callback);
	}

	reloadCategoriesMenu(button: BaseButton, entityTypeId: number, categoryId: ?number)
	{
		const menu = button.getMenuWindow();
		if (!menu)
		{
			return;
		}
		Ajax.runAction('crm.controller.category.list', {
			data: {
				entityTypeId
			}
		}).then((response) => {
			let startKey = 0;
			const items = [];
			const categories = response.data.categories;
			menu.menuItems.forEach((item) => {
				if (item.id.indexOf('toolbar-category-') !== 0)
				{
					items.push(item.options);
				}
				else if (item.id === 'toolbar-category-all')
				{
					items.push(item.options);
					startKey = 1;
				}
			});
			menu.destroy();
			Event.unbindAll(button.getContainer(), 'click');
			categories.forEach((category) => {
				const link = Router.Instance.getItemListUrlInCurrentView(entityTypeId, category.id);
				items.splice(startKey, 0, {
					id: 'toolbar-category-' + category.id,
					text: Text.encode(category.name),
					href: link ? link.toString() : null,
				});
				if (category.id > 0 && categoryId > 0 && Number(categoryId) === Number(category.id))
				{
					button.setText(Text.encode(category.name));
				}
				startKey++;
			});
			const options = menu.params;
			options.items = items;
			button.menuWindow = new Menu(options);
			Event.bind(button.getContainer(), 'click', button.menuWindow.show.bind(button.menuWindow));
		}).catch((response) => {
			console.log('error trying reload categories', response.errors);
		});
	}
}

namespace.ToolbarComponent = ToolbarComponent;