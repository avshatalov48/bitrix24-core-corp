import { defineStore } from 'ui.vue3.pinia';
import { nextTick } from "ui.vue3";

export const marketInstallState = defineStore('market-install', {
	state: () => ({
		appInfo: [],
		installStep: 1,
		popupInstallButton: null,
		licenseError: '',
		installLoader: null,

		installResult: {},
		redirectPriority: false,

		openAppAfterInstall: 15,
		timer: null,
		installError: false,

		slider: {
			install: 'install',
			support: 'support',
			scope: 'scope',
		},

		popupNodes: {},
		versionSlider: {
			container: false,
			slideBox: false,
			items: false,
			navBox: false,
			navArItems: false,
			column: 1,
			count: 0,
			currentItem: 0,
		},
	}),
	getters: {
		double() {
			return this.counter * 2;
		},
	},
	actions: {
		cleanLicenseError() {
			this.licenseError = '';
		},
		setPopupNode(appCode, popup) {
			this.popupNodes[appCode] = popup;
		},
		setAppInfo(appInfo) {
			this.appInfo = appInfo;
		},
		isRestOnlyApp() {
			return this.appInfo.OPEN_API === 'Y';
		},
		isSubscriptionApp() {
			return this.appInfo.BY_SUBSCRIPTION === 'Y';
		},
		isHiddenBuy() {
			return this.appInfo.HIDDEN_BUY === 'Y';
		},

		showInstallPopup(isUpdate = false) {
			if (!this.popupNodes[this.appInfo.CODE]) {
				return;
			}

			(new BX.Main.Popup({
				content: this.popupNodes[this.appInfo.CODE],
				overlay: true,
				closeIcon: true,
				autoHide: true,
				closeByEsc: true,
				width: 469,
				borderRadius: 12,
				padding: 0,
				className: 'market-popup__window',
				buttons: [
					(this.popupInstallButton = new BX.UI.Button({
						text : isUpdate ? BX.message('MARKET_DETAIL_ACTION_JS_REFRESH') : BX.message('MARKET_DETAIL_ACTION_JS_INSTALL'),
						color: BX.UI.Button.Color.SUCCESS,
						size: BX.UI.Button.Size.SMALL,
						events: {
							click: () => {
								if (!this.checkLicense()) {
									return;
								}

								this.goToInstallStep2();
							}
						}
					})),
				],
			})).show();

			if (isUpdate && this.popupNodes[this.appInfo.CODE]) {
				const target = this.popupNodes[this.appInfo.CODE].querySelector('[data-role="market-popup__versions-slider"]');
				if (target) {
					this.sliderInit({
						target: target,
					});
				}
			}
		},
		checkLicense() {
			if (!this.popupNodes[this.appInfo.CODE]) {
				return false;
			}

			const tosLicense = this.popupNodes[this.appInfo.CODE].querySelector('[data-role="market-tos-license"]');
			const license = this.popupNodes[this.appInfo.CODE].querySelector('[data-role="market-install-license"]');
			const confidentiality = this.popupNodes[this.appInfo.CODE].querySelector('[data-role="market-install-confidentiality"]');

			if (tosLicense && !tosLicense.checked) {
				this.licenseError = BX.message('MARKET_INSTALL_TOS_ERROR');
				return false;
			}

			if (
				(license && !license.checked) ||
				(confidentiality && !confidentiality.checked)
			) {
				this.licenseError = BX.message('MARKET_INSTALL_LICENSE_ERROR');
				return false;
			}

			return this.installStep === 1;
		},
		goToInstallStep2() {
			this.installStep = 2;
			this.popupInstallButton.button.parentElement.classList.add('--hidden')

			nextTick(() => {
				if (!this.installLoader) {
					this.installLoader = new BX.Loader({
						target: this.popupNodes[this.appInfo.CODE].querySelector('.market-install-loader'),
						size: 50,
					});
				}

				if (!this.installLoader.isShown()) {
					this.installLoader.show();
				}
			});

			let params = this.appInfo.INSTALL_INFO;
			params.IFRAME = location.href.indexOf("IFRAME=Y") > 0;

			this.queryInstall(params);
		},
		queryInstall(params) {
			let queryParam = {
				code: params.CODE
			};

			if (!!params.VERSION) {
				queryParam.version = params.VERSION;
			}

			const analytics = queryParam;

			if (!!params.CHECK_HASH) {
				queryParam.checkHash = params.CHECK_HASH;
				queryParam.installHash = params.INSTALL_HASH;
			}

			BX.ajax.runAction(
				'market.Application.install',
				{
					data: queryParam,
					analyticsLabel: analytics,
				}
			).then((response) => this.installFinish(response))
		},

		installFinish(response) {
			const result = !!response.data ? response.data : response;
			this.installResult = result;

			if (!!result.error) {
				if (!!result.helperCode && result.helperCode !== '') {
					top.BX.UI.InfoHelper.show(result.helperCode);
				} else {
					this.installError = true
					this.showError(result.error + (!!result.errorDescription ? '<br />' + result.errorDescription : ''));
					setTimeout(() => this.reloadSlider(), 12000);
				}
			} else if (!!result.redirect && this.redirectPriority === true) {
				top.location.href = result.redirect;
			} else {
				if (!!result.installed) {
					let eventResult = {};
					top.BX.onCustomEvent(top, 'Rest:AppLayout:ApplicationInstall', [true, eventResult], false);
				}

				if (this.isConfigurationAppInstall()) {
					this.reloadSlider();
					return;
				}

				this.installStep = 3;

				if (!this.isRestOnlyApp()) {
					this.timer = setInterval(() => {
						this.openAppAfterInstall--;
						if (this.openAppAfterInstall === 0) {
							this.openApplication();
						}
					}, 1000)
				}
			}
		},
		showError: function (message) {
			BX.UI.Notification.Center.notify(
				{
					content: message,
					autoHideDelay: 12000,
				}
			);
		},
		openApplication: function () {
			clearTimeout(this.timer);

			if (!!this.installResult.open) {
				top.BX.rest.AppLayout.openApplication(this.installResult.id, {});
			}

			const current = BX.SidePanel.Instance.getTopSlider();
			const previous = BX.SidePanel.Instance.getPreviousSlider(current);
			if (previous) {
				previous.reload();
			}

			current.reload();

			if (this.isConfigurationAppInstall()) {
				BX.SidePanel.Instance.open(this.installResult.openSlider);
			}
		},
		isConfigurationAppInstall: function () {
			return !!this.installResult.openSlider;
		},
		reloadSlider: function () {
			BX.SidePanel.Instance.getTopSlider().reload();
		},

		openSliderWithContent: function (contentCode, width) {
			BX.SidePanel.Instance.open("market-detail-" + contentCode, {
				contentCallback: function () {
					const outerContainer = document.createElement('div');
					outerContainer.classList.add('market-detail-slider-content-container');
					const innerContainer = document.createElement('div');
					innerContainer.classList.add('market-detail-slider-content-inner');
					innerContainer.innerHTML = BX('market-slider-block-' + contentCode).innerHTML;
					outerContainer.appendChild(innerContainer);

					return outerContainer;
				},
				width: width || 650,
			});
		},

		sliderInit: function(options) {
			this.versionSlider.container = options.target;
			this.versionSlider.slideBox = options.target.querySelector(".market-popup__versions-content");
			this.versionSlider.items = options.target.querySelectorAll(".market-popup__versions-content_item");

			this.versionSlider.navBox = options.target.querySelector(".market-popup__versions-nav");
			this.versionSlider.navArItems = options.target.querySelectorAll(".market-popup__versions-nav_item");

			this.versionSlider.count = this.versionSlider.items.length;
			this.versionSlider.currentItem = this.versionSlider.items.length - 1;
			this.versionSlider.column = options.column || 1;

			this.create();
		},
		create: function () {
			if (!BX.type.isDomNode(this.versionSlider.container) || !BX.type.isDomNode(this.versionSlider.slideBox)) {
				return;
			}

			this.versionSlider.slideBox.style.width = (100 * this.versionSlider.items.length / this.versionSlider.column) + "%";
			this.versionSlider.slideBox.style.left = -(100 * (this.versionSlider.items.length - 1) / this.versionSlider.column) + "%";

			let navArItemsWidth = this.getNavBoxShift(this.versionSlider.navArItems.length);
			this.versionSlider.navBox.style.width = navArItemsWidth + "px";
			this.versionSlider.navBox.style.left = 'calc(50% - ' +  navArItemsWidth + 'px)';
		},
		goTo: function(itemTarget) {
			this.versionSlider.slideBox.style.left = (itemTarget * (-100 / this.versionSlider.column)) +  '%';
			this.versionSlider.navBox.style.left = 'calc(50% - ' +  this.getNavBoxShift(itemTarget) + 'px)';
			this.versionSlider.currentItem = itemTarget;
		},
		getNavBoxShift: function (itemTarget) {
			let navBoxShift = 0;
			for (let i = 0; i < itemTarget; i++) {
				navBoxShift += (this.versionSlider.navArItems[i].offsetWidth + 6);
			}

			return navBoxShift;
		},
		prevVersion: function () {
			let prevItem = this.versionSlider.currentItem - 1;
			if (prevItem < 0) {
				prevItem = this.versionSlider.count - 1
			}

			this.goTo(prevItem)
		},
		nextVersion: function () {
			let nextItem = this.versionSlider.currentItem + 1;
			if (nextItem >= this.versionSlider.count) {
				nextItem = 0
			}

			this.goTo(nextItem)
		},
	},
});