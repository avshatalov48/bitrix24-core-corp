import { ajax as Ajax, Dom, Event, Reflection, Text, Type, Loc } from "main.core";
import { EventEmitter } from "main.core.events";
import { BaseButton, Button, ButtonIcon } from "ui.buttons";
import { Router } from "crm.router";
import { Menu } from "main.popup";
import {Guide} from "ui.tour";

import 'ui.hint';

import './style.css';

const namespace = Reflection.namespace('BX.Crm');

let instance = null;

class ToolbarEvents
{
	static TYPE_UPDATED = 'TypeUpdated';
	static CATEGORIES_UPDATED = 'CategoriesUpdated';
}

/**
 * @memberOf BX.Crm
 */
export default class ToolbarComponent extends EventEmitter
{
	constructor() {
		super();

		this.initHints();
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

	initHints()
	{
		BX.UI.Hint.init(BX('ui-toolbar-after-title-buttons'));
		BX.UI.Hint.popupParameters = {
			closeByEsc: true,
			autoHide: true,
			angle: { offset: 60 },
			offsetLeft: 40
		};
	}

	bindEvents()
	{
		const buttonNode = document.querySelector('[data-role="bx-crm-toolbar-categories-button"]');
		if (buttonNode)
		{
			const toolbar =	BX.UI.ToolbarManager.getDefaultToolbar();
			const button = toolbar.getButton(Dom.attr(buttonNode, 'data-btn-uniqid'));
			const entityTypeId = Number(buttonNode.dataset.entityTypeId);
			if (button && entityTypeId > 0)
			{
				this.subscribeCategoriesUpdatedEvent(() => {
					this.reloadCategoriesMenu(button, entityTypeId, buttonNode.dataset.categoryId);
				});
			}
		}

		this.#bindAutomationGuide();
	}

	#bindAutomationGuide()
	{
		const hash = document.location.hash;
		let guide;

		if (hash === '#robots')
		{
			const robotsBtn = document.querySelector('.crm-robot-btn');
			if (robotsBtn)
			{
				guide = new Guide({
					steps: [
						{
							target: robotsBtn,
							title: Loc.getMessage('CRM_TOOLBAR_COMPONENT_ROBOTS_GUIDE_TEXT_1'),
							text: ''
						}
					],
					onEvents: true,
				});
			}
		}
		else if (hash === '#scripts')
		{
			const scriptsBtn = document.querySelector('.intranet-binding-menu-btn');
			if (scriptsBtn)
			{
				guide = new Guide({
					steps: [
						{
							target: scriptsBtn,
							title: Loc.getMessage('CRM_TOOLBAR_COMPONENT_SCRIPTS_GUIDE_TEXT'),
							article: '13281632',
							text: ''
						}
					],
					onEvents: true,
				});
			}
		}

		if (guide)
		{
			guide.start();
			guide.getPopup().setAutoHide(true);
			guide.getPopup().setClosingByEsc(true);
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
				let link;
				if(entityTypeId === BX.CrmEntityType.enumeration.deal)
				{
					link = '/crm/deal/category/' + category.id + '/';
				}
				else
				{
					link = Router.Instance.getItemListUrlInCurrentView(entityTypeId, category.id);
					link = link.toString();
				}

				items.splice(startKey, 0, {
					id: 'toolbar-category-' + category.id,
					text: Text.encode(category.name),
					href: link ? link : null,
				});
				if (category.id > 0 && categoryId > 0 && Number(categoryId) === Number(category.id))
				{
					button.setText(category.name);
				}
				startKey++;
			});
			const options = menu.params;
			options.items = items;
			button.menuWindow = new Menu(options);
			Event.bind(button.getContainer(), 'click', button.menuWindow.show.bind(button.menuWindow));

			if (entityTypeId === BX.CrmEntityType.enumeration.deal)
			{
				this.reloadAddButtonMenu(categories);
			}
		}).catch((response) => {
			console.log('error trying reload categories', response.errors);
		});
	}

	reloadAddButtonMenu(categories: Array)
	{
		const addButtonNode = document.querySelector('.ui-btn-split.ui-btn-success');
		if (!addButtonNode)
		{
			return;
		}

		const addButtonId = addButtonNode.dataset.btnUniqid;
		const toolbar =	BX.UI.ToolbarManager.getDefaultToolbar();
		const button = toolbar.getButton(addButtonId, 'data-btn-uniqid');
		if (!button)
		{
			return;
		}

		let menu = button.menuWindow
		if (!menu)
		{
			return;
		}

		const menuItemsIds = menu
			.getMenuItems()
			.map(item => item.id)
			.filter(id => Type.isInteger(id));
		const categoryIds = categories.map(item => item.id);
		const idsToRemove = menuItemsIds.filter(id => !categoryIds.includes(id));
		const newCategories = categories.filter(item => !menuItemsIds.includes(item.id) && item.id > 0);

		// remove menu item(s)
		if (idsToRemove.length > 0)
		{
			idsToRemove.forEach(idToRemove => menu.removeMenuItem(idToRemove));
		}

		// add new item(s)
		if (newCategories.length > 0)
		{
			const targetItemId = menu
				.getMenuItems()
				.map(item => item.id)
				.filter(id => Type.isString(id))
				.at(1);

			newCategories.forEach(item => {
				menu.addMenuItem({
					id: item.id,
					text: item.name,
					onclick: function(event)
					{
						BX.SidePanel.Instance.open('/crm/deal/details/0/?category_id=' + item.id);
					}.bind(this)
				}, targetItemId);
			});
		}
	}

	getSettingsButton(): ?Button
	{
		const toolbar: ?BX.UI.Toolbar = BX.UI.ToolbarManager.getDefaultToolbar();
		if (!toolbar)
		{
			return null;
		}

		for (const [key: string, button: Button] of Object.entries(toolbar.getButtons()))
		{
			if (button.getIcon() === ButtonIcon.SETTING)
			{
				return button;
			}
		}

		return null;
	}
}

namespace.ToolbarComponent = ToolbarComponent;
