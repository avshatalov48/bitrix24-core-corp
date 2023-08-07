import { Dom } from 'main.core';
import type { MenuItem } from 'main.popup';

export const uncheckMenuItem = (menuItem: MenuItem): void => {
	Dom.addClass(menuItem.getContainer(), 'menu-popup-no-icon');
	Dom.removeClass(menuItem.getContainer(), 'disk-user-field-item-checked');

	menuItem.getMenuWindow().getPopupWindow().adjustPosition({ forceBindPosition: true });
};