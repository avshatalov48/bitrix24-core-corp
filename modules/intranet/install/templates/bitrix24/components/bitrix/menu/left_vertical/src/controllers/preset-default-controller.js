import {Loc, Tag} from 'main.core';
import {EventEmitter} from 'main.core.events';
import {PopupManager} from 'main.popup';
import DefaultController from './default-controller';
import Options from "../options";
import Utils from "../utils";
import {CancelButton, CreateButton} from 'ui.buttons';
import {MessageBox, MessageBoxButtons} from "ui.dialogs.messagebox";

export default class PresetDefaultController extends DefaultController
{
	isReady: boolean = true;
	#unavailableToolPopup: ?MessageBox;
	#mode: string;

	createPopup(mode): Popup
	{
		let button;
		this.#mode = mode;
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
					const currentPreset = this.getSelectedPreset();

					if (!Options.isAdmin && Options.availablePresetTools && Options.availablePresetTools[currentPreset] === false)
					{
						button.setWaiting(false);
						this.showUnavailableToolPopup();

						return;
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

	getMode(): string
	{
		return this.#mode;
	}

	getSelectedPreset()
	{
		let currentPreset = '';
		if (document.forms['left-menu-preset-form'])
		{
			[...document.forms['left-menu-preset-form']
				.elements['presetType']]
				.forEach((node) => {
					if (node.checked)
					{
						currentPreset = node.value;
					}
				})
			;
		}

		return currentPreset;
	}

	showUnavailableToolPopup(): void
	{
		if (!(this.#unavailableToolPopup instanceof MessageBox))
		{
			this.#unavailableToolPopup = MessageBox.create({
				message: Loc.getMessage('MENU_UNAVAILABLE_TOOL_POPUP_DESCRIPTION'),
				buttons: MessageBoxButtons.OK,
			});
		}

		this.#unavailableToolPopup.show();
	}
}