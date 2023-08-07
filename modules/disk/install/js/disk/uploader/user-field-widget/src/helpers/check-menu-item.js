import { Dom } from 'main.core';
import type { MenuItem } from 'main.popup';

export const checkMenuItem = (menuItem: MenuItem): void => {
	Dom.addClass(menuItem.getContainer(), 'disk-user-field-item-checked');
	Dom.removeClass(menuItem.getContainer(), 'menu-popup-no-icon');

	menuItem.getMenuWindow().getPopupWindow().adjustPosition({ forceBindPosition: true });
};