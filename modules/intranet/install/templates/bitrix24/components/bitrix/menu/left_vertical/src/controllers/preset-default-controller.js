import {Loc, Tag} from 'main.core';
import {EventEmitter} from 'main.core.events';
import {PopupManager} from 'main.popup';
import DefaultController from './default-controller';
import Options from "../options";
import Utils from "../utils";
import {CancelButton, CreateButton} from 'ui.buttons';

export default class PresetDefaultController extends DefaultController
{
	isReady: boolean = true;

	createPopup(mode): Popup
	{
		let button;
		const content = document.querySelector('#left-menu-preset-popup').cloneNode(true);
		return PopupManager.create(
			this.constructor.name.toString(), null, {
			overlay: true,
			contentColor : "white",
			contentNoPaddings : true,
			lightShadow: true,
			draggable: {restrict: true},
			closeByEsc: true,
			offsetTop: 1,
			offsetLeft: 20,
			cacheable: false,
			closeIcon: true,
			content: content,
			events: {
				onFirstShow: () => {
					[...content.querySelectorAll('.js-left-menu-preset-item')]
						.forEach((node) => {
							node.addEventListener('click', () => {
								[...content.querySelectorAll('.js-left-menu-preset-item')]
									.forEach((otherNode) => {
										otherNode.classList[otherNode === node ? 'add' : 'remove']('left-menu-popup-selected');
									})
								;
							});
						})
					;
				}
			},
			buttons: [
				(button = new CreateButton({
					text: Loc.getMessage('MENU_CONFIRM_BUTTON'),
					onclick: () => {
					if (button.isWaiting())
					{
						return;
					}
					button.setWaiting(true);

					let currentPreset = "";
					if (document.forms["left-menu-preset-form"])
					{
						[...document.forms["left-menu-preset-form"]
							.elements["presetType"]]
							.forEach((node) => {
								if (node.checked)
								{
									currentPreset = node.value;
								}
							})
						;
					}
					EventEmitter.emit(this, Options.eventName('onPresetIsSet'),
						{presetId: currentPreset, mode})
						.forEach((promise) => {
							promise
								.then((response) => {
									button.setWaiting(false);
									this.hide();
									if (response.data.hasOwnProperty("url"))
									{
										document.location.href = response.data.url;
									}
									else
									{
										document.location.reload();
									}
								})
								.catch(Utils.catchError)
							;
						})
					;
				}})),
				new CancelButton({
					text: Loc.getMessage('MENU_DELAY_BUTTON'),
					onclick: () => {
						EventEmitter.emit(this, Options.eventName('onPresetIsPostponed'), {mode});
						this.hide();
					} }),
			]
		});
	}
}