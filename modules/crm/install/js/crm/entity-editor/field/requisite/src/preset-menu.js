import {MenuManager} from "main.popup";
import {EventEmitter} from "main.core.events";
import {MessageBox} from 'ui.dialogs.messagebox';
import {Loc, Text} from "main.core";

export class PresetMenu extends EventEmitter
{
	constructor(id, presetList)
	{
		super();
		this.setEventNamespace('BX.Crm.RequisitePresetMenu');
		this._isShown = false;
		this.menuId = id;
		this.presetList = presetList;
	}

	toggle(bindElement)
	{
		if (this._isShown)
		{
			this.close();
		}
		else if (bindElement)
		{
			this.show(bindElement);
		}
	}

	show(bindElement)
	{
		if (this._isShown)
		{
			return;
		}

		if (!this.presetList || !this.presetList.length)
		{
			MessageBox.alert(Loc.getMessage('REQUISITE_LIST_EMPTY_PRESET_LIST'), Loc.getMessage('REQUISITE_LIST_ITEM_ERROR_CAPTION'));
			return;
		}
		let menu = [];
		for (let item of this.presetList)
		{
			menu.push(
				{
					text: Text.encode(item.name),
					value: item.value,
					onclick: this.onSelect.bind(this, item)
				}
			);
		}

		MenuManager.show(
			this.menuId,
			bindElement,
			menu,
			{
				angle: false,
				cacheable: false,
				events:
					{
						onPopupShow: () =>
						{
							this._isShown = true;
						},
						onPopupClose: () =>
						{
							this._isShown = false;
						}
					}
			}
		);
	}

	close()
	{
		if (!this._isShown)
		{
			return;
		}

		let menu = MenuManager.getMenuById(this.menuId);
		if (menu)
		{
			menu.popupWindow.close();
		}
	}

	isShown()
	{
		return this._isShown;
	}

	onSelect(item)
	{
		this.close();
		this.emit('onSelect', item);
	}
}