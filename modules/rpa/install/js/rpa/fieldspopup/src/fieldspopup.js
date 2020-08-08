import {Tag, Loc, Type, Text} from "main.core";
import {PopupWindowManager, Popup, PopupWindowButton} from "main.popup";

export class FieldsPopup
{
	constructor(id: string, fields: Array, title: string)
	{
		this.selectedFields = new Set();
		this.fields = new Map();
		if(Type.isString(id))
		{
			this.title = title;
			this.id = id;
			if(Type.isArray(fields))
			{
				fields.forEach((field) =>
				{
					this.fields.set(field.name, field);
				});
				this.save();
			}
		}
	}

	getPopup(onSave: Function): Popup
	{
		let popup = PopupWindowManager.getPopupById(this.id);

		if(!popup)
		{
			popup = new BX.PopupWindow(this.id, null, {
				titleBar: this.title,
				zIndex: 200,
				className: "rpa-fields-popup",
				autoHide: false,
				closeByEsc: false,
				closeIcon: false,
				content : this.getContent(),
				width: 500,
				overlay: true,
				lightShadow: false,
				buttons: this.getButtons(),
				cacheable: false,
			});
		}

		if(Type.isFunction(onSave))
		{
			popup.setButtons(this.getButtons(onSave));
		}

		return popup;
	}

	getButtons(onSave: Function): Array
	{
		return [
			new PopupWindowButton({
				text : Loc.getMessage('RPA_POPUP_SAVE_BUTTON'),
				className : "ui-btn ui-btn-md ui-btn-primary",
				events : {
					click : () =>
					{
						this.save();
						if(Type.isFunction(onSave))
						{
							onSave(this.getSelectedFields());
						}
						this.getPopup().close();
					}
				}
			}),
			new PopupWindowButton({
				text : Loc.getMessage('RPA_POPUP_CANCEL_BUTTON'),
				className : "ui-btn ui-btn-md",
				events : {
					click : () =>
					{
						if(Type.isFunction(onSave))
						{
							onSave(false);
						}
						this.getPopup().close();
					}
				}
			})
		];
	}

	getContent(): Element
	{
		let content = '';
		this.fields.forEach((field) =>
		{
			content += this.renderField(field);
		});
		return Tag.render`<div class="rpa-fields-popup-wrapper">
							<div class="rpa-fields-popup-inner">${content}</div>
						</div>`;
	}

	renderField({title, name, checked}): string
	{
		let checkedString = (checked === true ? 'checked="checked"': '');

		return `
		<label class="ui-ctl ui-ctl-checkbox">
			<input data-role="field-checkbox" type="checkbox" class="ui-ctl-element" name="${Text.encode(name)}" value="y" ${checkedString}>
			<div class="ui-ctl-label-text">${Text.encode(title)}</div>
		</label>`;
	}

	show(): Promise
	{
		return new Promise((resolve) =>
		{
			this.getPopup(resolve).show();
		});
	}

	getSelectedFields(): Set
	{
		return this.selectedFields;
	}

	save()
	{
		this.selectedFields.clear();
		let container = this.getPopup().getContentContainer();
		if(container)
		{
			let inputs = Array.from(container.querySelectorAll('[data-role="field-checkbox"]'));
			inputs.forEach((input) =>
			{
				if(input.checked)
				{
					this.selectedFields.add(input.name);
				}
			})
		}
	}

	close()
	{
		this.getPopup().close();
	}
}