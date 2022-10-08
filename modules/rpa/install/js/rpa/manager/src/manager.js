import {Reflection, Type, Uri} from 'main.core';

import 'ui.design-tokens';
import 'ui.fonts.opensans';
import 'sidepanel';


let instance = null;

class Manager
{
	urlTemplates = {};

	static editors = {};

	static get Instance(): Manager
	{
		if (window.top !== window)
		{
			return window.top.BX.Rpa.Manager.Instance;
		}

		if(instance === null)
		{
			instance = new Manager();
		}

		return instance;
	}

	setUrlTemplates(urlTemplates: Object): Manager
	{
		if(Type.isPlainObject(urlTemplates))
		{
			this.urlTemplates = urlTemplates;
		}

		return this;
	}

	static addEditor(typeId: number, itemId: number, editor)
	{
		const editorClass = Reflection.getClass('BX.UI.EntityEditor');
		if(!editorClass)
		{
			return;
		}
		if(Type.isInteger(typeId) && Type.isInteger(itemId) && editor instanceof BX.UI.EntityEditor)
		{
			if(!Manager.editors[typeId])
			{
				Manager.editors[typeId] = {};
			}
			Manager.editors[typeId][itemId] = editor;
		}
	}

	/**
	 * @param typeId
	 * @param itemId
	 * @returns {null|BX.UI.EntityEditor}
	 */
	static getEditor(typeId: number, itemId: number): ?Object
	{
		if(Type.isInteger(typeId) && Type.isInteger(itemId) && Manager.editors[typeId] && Manager.editors[typeId][itemId])
		{
			return Manager.editors[typeId][itemId];
		}

		return null;
	}

	static openSlider(url, options): Promise<?BX.SidePanel.Slider>
	{
		if(!Type.isPlainObject(options))
		{
			options = {};
		}
		options = {...{cacheable: false, allowChangeHistory: true, events: {}}, ...options};
		return new Promise((resolve) =>
		{
			if(Type.isString(url) && url.length > 1)
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

	openTasks(typeId: number, itemId: number): Promise<{isCompleted: boolean}>
	{
		return new Promise((resolve) =>
		{
			Manager.openSlider(
				this.getTasksUrl(typeId, itemId).toString(),
				{width: 580, cacheable: false, allowChangeHistory: false}
			).then((slider) =>
			{
				let isCompleted = false;
				let item = null;
				if(slider.isLoaded())
				{
					isCompleted = slider.getData().get('isCompleted') || false;
					item = slider.getData().get('item') || null;
				}

				resolve({
					isCompleted,
					item,
				});
			});
		});
	}

	getTasksUrl(typeId: number, itemId: number): ?Uri
	{
		const template = this.urlTemplates['bitrix:rpa.task'];
		if(template)
		{
			return new Uri(template.replace('#typeId#', typeId).replace('#elementId#', itemId));
		}

		return null;
	}

	openKanban(typeId: number): boolean
	{
		const template = this.urlTemplates['bitrix:rpa.kanban'];
		if(template)
		{
			location.href = (new Uri(template.replace('#typeId#', typeId))).toString();

			return true;
		}

		return false;
	}

	openTypeDetail(typeId: number, options: ?{}): ?Promise<?BX.SidePanel.Slider>
	{
		if(!Type.isPlainObject(options))
		{
			options = {};
		}
		options.width = 702;
		const template = this.urlTemplates['bitrix:rpa.type.detail'];
		if(template)
		{
			return Manager.openSlider(template.replace('#id#', typeId), options);
		}

		return null;
	}

	getItemDetailUrl(typeId: number, itemId: number = 0): ?Uri
	{
		const template = this.urlTemplates['bitrix:rpa.item.detail'];
		if(template)
		{
			return new Uri(template.replace('#typeId#', typeId).replace('#id#', itemId));
		}

		return null;
	}

	openItemDetail(typeId: number, itemId: number = 0, options: ?{} = {}): ?Promise<?BX.SidePanel.Slider>
	{
		const uri = this.getItemDetailUrl(typeId, itemId);
		if(uri)
		{
			return Manager.openSlider(uri.toString(), options);
		}

		return null;
	}

	getStageListUrl(typeId: number): ?Uri
	{
		const template = this.urlTemplates['bitrix:rpa.stage.list'];
		if(template)
		{
			return new Uri(template.replace('#typeId#', typeId));
		}

		return null;
	}

	openStageList(typeId: number): ?Promise<?BX.SidePanel.Slider>
	{
		const url = this.getStageListUrl(typeId);
		if(url)
		{
			return Manager.openSlider(url.toString());
		}

		return null;
	}

	getFieldsListUrl(typeId: number): ?Uri
	{
		const template = this.urlTemplates['fieldsList'];
		if(template)
		{
			return new Uri(template.replace('#typeId#', typeId));
		}

		return null;
	}

	openFieldsList(typeId: number): ?Promise<?BX.SidePanel.Slider>
	{
		const url = this.getFieldsListUrl(typeId);
		if(url)
		{
			return Manager.openSlider(url.toString());
		}

		return null;
	}

	getFieldDetailUrl(typeId: number, fieldId: number): ?Uri
	{
		const template = this.urlTemplates['fieldDetail'];
		if(template)
		{
			return new Uri(template.replace('#typeId#', typeId).replace('#fieldId#', fieldId));
		}

		return null;
	}

	openFieldDetail(typeId: number, fieldId: number, options: {}): ?Promise<?BX.SidePanel.Slider>
	{
		const url = this.getFieldDetailUrl(typeId, fieldId);
		if(url)
		{
			return Manager.openSlider(url.toString(), options);
		}

		return null;
	}

	static calculateTextColor(baseColor)
	{
		var r, g, b;
		if ( baseColor.length > 7 )
		{
			var hexComponent = baseColor.split("(")[1].split(")")[0];
			hexComponent = hexComponent.split(",");
			r = parseInt(hexComponent[0]);
			g = parseInt(hexComponent[1]);
			b = parseInt(hexComponent[2]);
		}
		else
		{
			if(/^#([A-Fa-f0-9]{3}){1,2}$/.test(baseColor))
			{
				var c = baseColor.substring(1).split('');
				if(c.length === 3)
				{
					c= [c[0], c[0], c[1], c[1], c[2], c[2]];
				}
				c = '0x'+c.join('');
				r = ( c >> 16 ) & 255;
				g = ( c >> 8 ) & 255;
				b =  c & 255;
			}
		}

		var y = 0.21 * r + 0.72 * g + 0.07 * b;
		return ( y < 145 ) ? "#fff" : "#333";
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

	showFeatureSlider(event, item)
	{
		Manager.Instance.closeSettingsMenu(event, item);
		BX.UI.InfoHelper.show('limit_robotic_process_automation');
	}
}

export {
	Manager
};