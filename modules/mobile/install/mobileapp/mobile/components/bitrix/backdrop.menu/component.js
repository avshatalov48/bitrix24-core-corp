/**
* @bxjs_lang_path component.php
*/

(function()
{
	class BackdropMenuComponent
	{
		constructor(params)
		{
			this.id = params.id;
			this.config = params.config;
			this.componentId = params.componentId;
			this.rootWidget = params.rootWidget;

			if (params.top)
			{
				BX.listeners = {};
				BX.addCustomEvent("backdrop.menu:destroy", () => this.rootWidget.close());
				BX.addCustomEvent("backdrop.menu:showSubMenu", (params) => this.showSubMenu(params));
			}

			BX.onViewLoaded(() => this.init());
		}

		init()
		{

			this.rootWidget.setItems(this.config.items, this.config.sections);
			this.rootWidget.setListener((eventName, params) =>
			{
				if (eventName === 'onItemSelected')
				{
					this.itemSelected(params);
				}
				else if (eventName === 'onViewRemoved')
				{
					this.destroy();
				}
			});
		}

		itemSelected(params)
		{
			params = this.config.items.find(element => element.id === params.id);

			if (params.unselectable === true)
			{
			}
			else if (params.unclosable === true)
			{
				this.postEvent('selected', {id: params.id, sectionCode: params.sectionCode});
			}
			else
			{
				this.rootWidget.close(() => this.postEvent('selected', {id: params.id, sectionCode: params.sectionCode}));
			}
		}

		showSubMenu(params)
		{
			PageManager.openWidget("list",
			{
				title: params.title || '',
				onReady: widget =>
				{
					new BackdropMenuComponent({
						id: params.id,
						config: params.config,
						componentId: params.componentId,
						rootWidget: widget,
					});
				},
				onError: error => console.log(error),
			});
		}

		destroy()
		{
			this.postEvent('destroyed');
		}

		postEvent(name, params = {})
		{
			if (!this.componentId)
			{
				return false;
			}

			if (this.componentId === 'web')
			{
				BX.postWebEvent('backdrop.menu:events', {id: this.id, name, params});
			}
			else
			{
				BX.postComponentEvent('backdrop.menu:events', [{id: this.id, name, params}], this.componentId);
			}

			return true;
		}
	}

	this.BackdropMenuComponent = new BackdropMenuComponent({
		id: BX.componentParameters.get('ID', 0),
		config: BX.componentParameters.get('CONFIG', {items: [], sections: []}),
		componentId: BX.componentParameters.get('COMPONENT_ID', null),
		rootWidget: List,
		top: true
	});
})();