import {Loc} from 'main.core';
import {Menu, MenuItem} from 'main.popup';
import {EventEmitter, BaseEvent} from 'main.core.events';
import Options from '../options';
import DefaultController from './default-controller';

export default class SettingsController extends DefaultController
{
	menuId = 'leftMenuSettingsPopup';

	createPopup()
	{
		const menu = new Menu({
			bindElement: this.container,
			items: this.getItems(),
			angle: true,
			offsetTop: 0,
			offsetLeft: 50,
			// cacheable: false,
		});
		return menu.getPopupWindow();
	}

	getItems(): Array
	{
		const menuItems = [];
		Array.from(...EventEmitter.emit(this,
			Options.eventName('onGettingSettingMenuItems'),
		)).forEach(({text, html, onclick, className}) => {
			if (!text && !html)
			{
				return;
			}
			menuItems.push(Object.assign(html ? {
				html: html
			} : {
				text : text}, {
				className: ["menu-popup-no-icon", className ?? ''].join(' '),
				onclick: (event, item) => {
					item.getMenuWindow().close();
					onclick(event, item);
				}
			}));
		});
		return menuItems;
	}
}