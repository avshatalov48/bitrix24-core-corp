import {Loc, Tag} from 'main.core';
import {EventEmitter} from 'main.core.events';
import {PopupManager} from 'main.popup';
import DefaultController from './default-controller';
import Options from "../options";
import {CancelButton, SaveButton} from 'ui.buttons';

export default class PresetCustomController extends DefaultController
{
	isReady: boolean = true;

	createPopup()
	{
		const form = Tag.render`
			<form id="customPresetForm" style="min-width: 350px;">
				<div style="margin: 15px 0 15px 9px;">
					<input type="radio" name="userScope" id="customPresetCurrentUser" value="currentUser">
					<label for="customPresetCurrentUser">${Loc.getMessage("MENU_CUSTOM_PRESET_CURRENT_USER")}</label>
				</div>
				<div style="margin: 0 0 38px 9px;">
					<input type="radio" name="userScope" id="customPresetNewUser" value="newUser" checked>
					<label for="customPresetNewUser">${Loc.getMessage("MENU_CUSTOM_PRESET_NEW_USER")}</label>
				</div>
				<hr style="background-color: #edeef0; border: none; color:  #edeef0; height: 1px;">
			</form>`
		;

		let button;
		return PopupManager.create(
			this.constructor.toString(), null, {
			overlay: true,
			contentColor : "white",
			contentNoPaddings : true,
			lightShadow: true,
			draggable: {restrict: true},
			closeByEsc: true,
			titleBar: Loc.getMessage("MENU_CUSTOM_PRESET_POPUP_TITLE"),
			offsetTop: 1,
			offsetLeft: 20,
			cacheable: false,
			closeIcon: true,
			content: form,
			buttons: [
				(button = new SaveButton({ onclick: () => {
					if (this.isReady === false)
					{
						return;
					}
					button.setWaiting(true);
					this.isReady = false;
					EventEmitter.emit(this, Options.eventName('onPresetIsSet'), form.elements["userScope"].value === 'newUser')
						.forEach((promise) => {
							promise
								.then(() => {
									button.setWaiting(false);
									this.hide();
									PopupManager.create("menu-custom-preset-success-popup", null, {
										closeIcon: true,
										contentColor : "white",
										titleBar: Loc.getMessage("MENU_CUSTOM_PRESET_POPUP_TITLE"),
										content: Loc.getMessage("MENU_CUSTOM_PRESET_SUCCESS")
									}).show();
								})
								.catch(() => {
									console.log('Error!!')
								});
						})
					;
				}})),
				new CancelButton({ onclick: () => { this.hide();} }),
			]
		});
	}
}