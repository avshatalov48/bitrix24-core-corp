import {ajax, Loc} from 'main.core';
import Options from './options';

export default class Backend {

	static toggleMenu(collapse) {
		if (Loc.getMessage('USER_ID') <= 0)
		{
			return;
		}

		return ajax.runAction(`intranet.leftmenu.${collapse ? "collapseMenu" : "expandMenu"}`, {
			data: {},
			analyticsLabel: { leftmenu: {action: collapse ? "collapseMenu" : "expandMenu"} }
		});
	}

	static saveSelfItemMenu(itemData) {
		const action =  itemData.id > 0 ? "update" : "add";
		return ajax.runAction(`intranet.leftmenu.${action}SelfItem`, {
			data: {itemData: itemData },
			analyticsLabel: { leftmenu: {action: 'selfItemAddOrUpdate'} }
		});
	}

	static deleteSelfITem(id) {
		return ajax.runAction('intranet.leftmenu.deleteSelfItem', {
			data: {menuItemId: id},
			analyticsLabel: {leftmenu: {action: 'selfItemDelete'}}
		});
	}

	static addFavoritesItemMenu(itemData) {
		return ajax.runAction('intranet.leftmenu.addStandartItem', {
			data: { itemData: itemData },
			analyticsLabel: {leftmenu: {action: 'standardItemAdd'}}
		});
	}

	static deleteFavoritesItemMenu(itemData) {
		return ajax.runAction('intranet.leftmenu.deleteStandartItem', {
			data: { itemData: itemData },
			analyticsLabel: {leftmenu: {action: 'standardItemDelete'}}
		});
	}

	static updateFavoritesItemMenu(itemData) {
		return ajax.runAction('intranet.leftmenu.updateStandartItem', {
			data: {
				itemText: itemData.text,
				itemId: itemData.id,
			},
			analyticsLabel: {leftmenu: {action: 'standardItemUpdate'}}
		})
	}
	static addAdminSharedItemMenu(itemData) {
		return ajax.runAction('intranet.leftmenu.addItemToAll', {
			data: { itemInfo: itemData },
			analyticsLabel: {leftmenu: {action: 'adminItemAdd'}}
		});
	}

	static deleteAdminSharedItemMenu(id) {
		return ajax.runAction('intranet.leftmenu.deleteItemFromAll', {
			data: { menu_item_id: id},
			analyticsLabel: {leftmenu: {action: 'adminItemDelete'}}
		});
	}

	static saveItemsSort(menuItems, firstItemLink, analyticsLabel)
	{
		return ajax.runAction('intranet.leftmenu.saveItemsSort', {
			data: {
				items: menuItems,
				firstItemLink: firstItemLink,
				version: Options.version
			},
			analyticsLabel: {leftmenu: analyticsLabel}
		});
	}

	static setFirstPage(firstPageLink)
	{
		return ajax.runAction('intranet.leftmenu.setFirstPage', {
			data: {firstPageUrl: firstPageLink},
			analyticsLabel: {leftmenu: {action: 'mainPageIsSet'}}
		})
	}

	static setDefaultPreset()
	{
		return ajax.runAction('intranet.leftmenu.setDefaultMenu', {
			data: {},
			analyticsLabel: {leftmenu: {action: 'defaultMenuIsSet'}}
		});
	}

	static setCustomPreset(forNewUsersOnly, itemsSort, customItems, firstItemLink)
	{
		return ajax.runAction('intranet.leftmenu.saveCustomPreset', {
			data: {
				userApply: forNewUsersOnly === true ? 'newUser' : 'currentUser',
				itemsSort: itemsSort,
				customItems: customItems,
				firstItemLink: firstItemLink,
				version: Options.version
			},
			analyticsLabel: { leftmenu: {action: 'customPresetIsSet'} }
		});
	}

	static deleteCustomItem(id)
	{
		return ajax.runAction('intranet.leftmenu.deleteCustomItemFromAll', {
			data: {menu_item_id: id},
			analyticsLabel: {leftmenu: {action: 'customItemDelete'}}
		});
	}

	static setSystemPreset(mode, presetId)
	{
		return ajax.runAction('intranet.leftmenu.setPreset', {
			data: {
				preset: presetId,
				mode: mode === 'global' ? 'global' : 'personal'
			},
			analyticsLabel: {
				leftmenu: {
					action: 'systemPresetIsSet',
					presetId: presetId,
					mode: mode,
					analyticsFirst: mode === 'global' ? 'y' : 'n'
				}
			}
		});
	}

	static postponeSystemPreset(mode)
	{
		return ajax.runAction('intranet.leftmenu.delaySetPreset', {
			data: {},
			analyticsLabel: {
				leftmenu: {
					action: 'systemPresetIsPostponed',
					mode: mode,
					analyticsFirst: mode === 'global' ? 'y' : 'n'
				}
			}
		});
	}

	static clearCache()
	{
		return ajax.runAction('intranet.leftmenu.clearCache', {
			data: {},
			analyticsLabel: { leftmenu: {action: 'clearCache'} }
		});
	}

	static expandGroup(id)
	{
		if (Loc.getMessage('USER_ID') <= 0)
		{
			return;
		}

		return ajax.runAction('intranet.leftmenu.expandMenuGroup', {
			data: {id},
			analyticsLabel: { leftmenu: {action: 'expandMenuGroup' }}
		});
	}

	static collapseGroup(id)
	{
		if (Loc.getMessage('USER_ID') <= 0)
		{
			return;
		}

		return ajax.runAction('intranet.leftmenu.collapseMenuGroup', {
			data: {id},
			analyticsLabel: { leftmenu: {action: 'collapseMenuGroup' }}
		});
	}
}