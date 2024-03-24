import { defineStore } from 'ui.vue3.pinia';
import {EventEmitter} from "main.core.events";

export const marketUninstallState = defineStore('market-uninstall', {
	state: () => ({
		uninstallConfirmShown: false,
		appCode: '',
		uninstallNodes: {},
		popupDelete: null,
		refreshUri: '',
		action: '',
	}),
	actions: {
		setNodesInfo(appCode, node) {
			this.uninstallNodes[appCode] = node;
		},
		setDeleteActionInfo(action) {
			this.action = action;
		},
		deleteAction: function (appCode, refreshUri) {
			this.refreshUri = refreshUri ?? '';

			if (this.uninstallConfirmShown) {
				return;
			}

			if (!!appCode && appCode.length > 0) {
				this.appCode = appCode;
			}

			if (this.appCode.length <= 0) {
				return;
			}

			this.uninstallConfirmShown = true;

			this.showUninstallConfirmPopup();
		},
		showUninstallConfirmPopup: function () {
			this.popupDelete = new BX.PopupWindow(
				'market_delete_confirm_popup_' + this.appCode,
				null,
				{
					content: this.uninstallNodes[this.appCode],
					closeByEsc: true,
					closeIcon: false,
					events: {
						onPopupClose: () => {
							this.uninstallConfirmShown = false;
							this.popupDelete.destroy();
						}
					},
					buttons: [
						new BX.PopupWindowButton(
							{
								text: BX.message('MARKET_APPLICATION_JS_DELETE'),
								className: 'popup-window-button-decline',
								events: {
									click: () => this.uninstallApp()
								}
							}
						),
						new BX.PopupWindowButtonLink(
							{
								text: BX.message('JS_CORE_WINDOW_CANCEL'),
								className: 'popup-window-button-link-cancel',
								events: {
									click: () => {
										this.uninstallConfirmShown = false;
										this.popupDelete.close();
										this.popupDelete.destroy();
										this.popupDelete = null;
									}
								}
							}
						)
					]
				}
			);
			this.popupDelete.show();
		},
		uninstallApp: function () {
			BX.ajax.runAction(
				'market.Application.uninstall',
				{
					data: {
						code: this.appCode,
						clean: (this.uninstallNodes[this.appCode].querySelector('[name="delete-data"]').checked) ? 'Y' : 'N',
					},
					analyticsLabel: {
						code: this.appCode,
						viewMode: this.refreshUri ? 'list' : 'detail',
					},
					method: 'POST',
				}
			).then(
				(response) => {
					let result = response.data;
					if (result.error) {
						this.popupDelete.setContent('<div class="market_delete_confirm"><div class="market_delete_confirm_text">' + result.error + '</div></div>');
						this.popupDelete.setButtons(
							[
								new BX.PopupWindowButtonLink(
									{
										text: BX.message('JS_CORE_WINDOW_CLOSE'),
										className: 'popup-window-button-link-cancel',
										events: {
											click: function()
											{
												this.popupWindow.close();
											}
										}
									}
								)
							]
						);
						this.popupDelete.adjustPosition();
					} else {
						if (this.action.length > 0) {
							try {
								eval(this.action);
							} catch (e) {}
						}

						this.popupDelete.close();
						this.uninstallConfirmShown = false;

						if (!!result.sliderUrl) {
							BX.SidePanel.Instance.open(result.sliderUrl);
						} else {
							if (this.refreshUri) {
								EventEmitter.emit(
									'market:refreshUri',
									{
										refreshUri: this.refreshUri,
										skeleton: 'list',
									}
								);
								return;
							}

							let current = BX.SidePanel.Instance.getTopSlider();
							if (current) {
								current.reload();
							} else {
								window.location.reload();
							}
						}
					}
				}
			);
		},
	},
});