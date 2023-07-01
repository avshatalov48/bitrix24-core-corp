import { Uri, Type, Tag, Cache, Loc, Browser, Text, Dom, ajax } from 'main.core';
import {EventEmitter} from 'main.core.events';
import {PopupComponentsMaker} from 'ui.popupcomponentsmaker';
import {StressLevel} from './stress-level';
import ThemePicker from './theme-picker';
import {Menu} from 'main.popup';
import 'main.qrcode';
import Options from "./options";
import MaskEditor from "./mask-editor";
import Ustat from "./ustat";
import UserLoginHistory from './user-login-history';
import {QrAuthorization} from "ui.qrauthorization";
import { Loader } from 'main.loader';

const widgetMarker = Symbol('user.widget');
export default class Widget extends EventEmitter
{
	#container: Element;
	#popup: ?PopupComponentsMaker;
	#profile = null;
	#features = {};
	#cache = new Cache.MemoryCache();


	constructor(container, {
		profile: {ID, FULL_NAME, PHOTO, MASK, STATUS, URL, WORK_POSITION},
		component: {componentName, signedParameters},
		features
	})
	{
		super();
		this.setEventNamespace(Options.eventNameSpace);
		this.#container = container;
		this.#profile = {ID, FULL_NAME, PHOTO, MASK, STATUS, URL, WORK_POSITION};
		this.#features = features;
		if (!Type.isStringFilled(this.#features.browser))
		{
			this.#features.browser =
				(Browser.isLinux() ? 'Linux'
				: (Browser.isWin() ? 'Windows' : 'MacOs')
				)
			;
		}

		this.#cache.set('componentParams', {componentName, signedParameters});
		this.hide = this.hide.bind(this);
	}

	toggle()
	{
		if (this.getPopup().isShown())
		{
			this.hide();
		}
		else
		{
			this.show();
		}
	}

	hide()
	{
		this.getPopup().close();
	}

	show()
	{
		this.getPopup().show();
	}

	getPopup(): PopupComponentsMaker
	{
		if (this.#popup)
		{
			return this.#popup;
		}
		let content = [
			this.#getProfileContainer(),
			(this.#getb24NetPanelContainer() ? {
				html: this.#getb24NetPanelContainer(),
				backgroundColor: '#fafafa'
			} : null),
			(this.#getAdminPanelContainer() ? {
				html: this.#getAdminPanelContainer(),
				backgroundColor: '#fafafa'
			} : null),
			[
				{
					html: this.#getThemeContainer(),
					marginBottom: 24,
					overflow: true
				},
				{
					html: this.#getMaskContainer(),
					backgroundColor: '#fafafa'
				}
			],
			this.#getCompanyPulse(!!this.#getStressLevel()) ?
			[
				{
					html: this.#getCompanyPulse(!!this.#getStressLevel()),
					overflow: true,
					marginBottom: 24,
					flex: this.#getStressLevel() ? 0.5 : 1
				},
				this.#getStressLevel(),
			] : null,
			this.#getOTPContainer(this.#getDeskTopContainer() === null) && this.#getDeskTopContainer() ?
			[
				{
					flex: 0.5,
					html: this.#getQrContainer(0.7)
				},
				[
					{
						html: this.#getDeskTopContainer(),
						displayBlock: true
					},
					this.#getOTPContainer()
				]
			] : this.#getDeskTopContainer() || this.#getOTPContainer() ? [
					{
						html: this.#getQrContainer(2),
						flex: 2
					},
					this.#getDeskTopContainer() ?? this.#getOTPContainer()
			] : this.#getQrContainer(0),
			this.#getLoginHistoryContainer(),
			{
				html: this.#getBindings(),
				marginBottom: 24,
				backgroundColor: '#fafafa'
			},
			[
				{
					html: this.#getNotificationContainer() ?? null,
					backgroundColor: '#fafafa'
				},
				{
					html: this.#getLogoutContainer(),
					backgroundColor: '#fafafa'
				}
			]
		];

		const filterFunc = (data): ?Array => {
			const result = [];
			if (Type.isArray(data))
			{
				for (let i = 0; i < data.length; i++)
				{
					if (Type.isArray(data[i]))
					{
						const buff = filterFunc(data[i]);
						if (buff !== null)
						{
							if (Type.isArray(buff) && buff.length === 1)
							{
								result.push(buff[0]);
							}
							else
							{
								result.push(buff);
							}
						}
					}
					else if (data[i] !== null)
					{
						result.push(data[i]);
					}
				}
			}
			return result.length <= 0 ? null : (
				result.length === 1 ? result[0] : result
			)
		};
		content = filterFunc(content);

		const prepareFunc = (item, index, array) => {
			if (Type.isArray(item))
			{
				return {
					html:  item.map(prepareFunc)
				};
			}

			return {
				flex: item['flex'] || 0,
				html: item['html'] || item,
				backgroundColor: item['backgroundColor'] || null,
				disabled: item['disabled'] || null,
				overflow: item['overflow'] || null,
				marginBottom: item['marginBottom'] || null,
				displayBlock: item['displayBlock'] || null
			};
		}
		this.#popup = new PopupComponentsMaker({
			target: this.#container,
			content: content.map(prepareFunc),
			width: 400,
		});
		EventEmitter.subscribe('BX.Main.InterfaceButtons:onMenuShow', this.hide);
		EventEmitter.subscribe(Options.eventNameSpace + 'onNeedToHide', this.hide);
		return this.#popup;
	}

	#getProfileContainer(): Element
	{
		return this.#cache.remember('profile', () => {
			const onclick = (event) => {
				this.hide();
				return BX.SidePanel.Instance.open(this.#profile.URL);
			};
			const avatarNode = Tag.render`
				<span class="system-auth-form__profile-avatar--image"
					${this.#profile.PHOTO ? `
						style="background-size: cover; background-image: url('${encodeURI(this.#profile.PHOTO)}')"` : ''}>
				</span>
				`;
			const nameNode = Tag.render`
				<div class="system-auth-form__profile-name">${Text.encode(this.#profile.FULL_NAME)}</div>
			`;
			EventEmitter.subscribe(
				EventEmitter.GLOBAL_TARGET,
				'BX.Intranet.UserProfile:Avatar:changed',
				({data: [{url, userId}]}) => {
					if (this.#profile.ID > 0 && userId && this.#profile.ID.toString() === userId.toString())
					{
						this.#profile.PHOTO = url;
						avatarNode.style = Type.isStringFilled(url)
							? `background-size: cover; background-image: url('${encodeURI(this.#profile.PHOTO)}')`
							: ''
						;
					}
				})
			;
			EventEmitter.subscribe(
				EventEmitter.GLOBAL_TARGET,
				'BX.Intranet.UserProfile:Name:changed',
				({data: [{fullName}]}) => {
					this.#profile.FULL_NAME = fullName;
					nameNode.innerHTML = fullName;
					this.#container.querySelector('#user-name').innerHTML = fullName;
				})
			;
			let workPosition = (Type.isStringFilled(this.#profile.WORK_POSITION) ? Text.encode(this.#profile.WORK_POSITION) : '');
			if (this.#profile.STATUS && Loc.hasMessage('INTRANET_USER_PROFILE_' + this.#profile.STATUS))
			{
				workPosition = Loc.getMessage('INTRANET_USER_PROFILE_' + this.#profile.STATUS)
			}

			return Tag.render`
				<div class="system-auth-form__item system-auth-form__scope --clickable" onclick="${onclick}">
					<div class="system-auth-form__profile">
						<div class="system-auth-form__profile-avatar">
							${avatarNode}
						</div>
						<div class="system-auth-form__profile-content --margin--right">
							${nameNode}
							<div class="system-auth-form__profile-position">${workPosition}</div>
						</div>
						<div class="system-auth-form__profile-controls">
							<span class="ui-qr-popupcomponentmaker__btn --large --border" >
								${Loc.getMessage('INTRANET_USER_PROFILE_PROFILE')}
							</span>
					 		<!-- <span class="ui-qr-popupcomponentmaker__btn --large --success">any text</span> -->
						</div>
					</div>
				</div>
			`;
		});
	}

	#getb24NetPanelContainer()
	{
		return this.#cache.remember('b24netPanel', () => {
			if (this.#features['b24netPanel'] !== 'Y')
			{
				return null;
			}

			return Tag.render`
				<a class="system-auth-form__item system-auth-form__scope --center --padding-sm --clickable" href="https://www.bitrix24.net/">
					<div class="system-auth-form__item-logo">
						<div class="system-auth-form__item-logo--image --network"></div>
					</div>
					<div class="system-auth-form__item-container --center">
						<div class="system-auth-form__item-title --light">${Loc.getMessage('AUTH_PROFILE_B24NET')}</div>
					</div>
					<div class="system-auth-form__item-container --block">
						<div class="ui-qr-popupcomponentmaker__btn">${Loc.getMessage('INTRANET_USER_PROFILE_GOTO')}</div>
					</div>
				</a>
			`;
		});
	}

	#getAdminPanelContainer()
	{
		return this.#cache.remember('adminPanel', () => {
			if (this.#features['adminPanel'] !== 'Y')
			{
				return null;
			}

			return Tag.render`
				<a class="system-auth-form__item system-auth-form__scope --center --padding-sm --clickable" href="/bitrix/admin/">
					<div class="system-auth-form__item-logo">
						<div class="system-auth-form__item-logo--image --admin-panel"></div>
					</div>
					<div class="system-auth-form__item-container --center">
						<div class="system-auth-form__item-title --light">${Loc.getMessage('INTRANET_USER_PROFILE_ADMIN_PANEL')}</div>
					</div>
					<div class="system-auth-form__item-container --block">
						<div class="ui-qr-popupcomponentmaker__btn">${Loc.getMessage('INTRANET_USER_PROFILE_GOTO')}</div>
					</div>
				</a>
			`;
		});
	}

	#getThemeContainer(): Element
	{
		return this.#cache.remember('themePicker', () => {
			if (this.#features['themePicker'] === null)
			{
				return null;
			}
			const themePicker = new ThemePicker(this.#features['themePicker']);
			themePicker.subscribe('onOpen', this.hide);
			return themePicker.getPromise();
		});
	}

	#getMaskContainer(): Element
	{
		return this.#cache.remember('Mask', () => {
			const maskEditor = new MaskEditor(this.#profile.MASK);
			maskEditor.subscribe('onOpen', this.hide);
			maskEditor.subscribe('onChangePhoto', ({data}) => {
				this.#savePhoto(data)
			});
			this.emit('onChangePhoto')
			return maskEditor.getPromise();
		});
	}

	#getCompanyPulse(isNarrow): Element
	{
		return this.#cache.remember('getCompanyPulse', () => {
			if (this.#features.pulse === 'Y'
				&& this.#profile.ID > 0
				&& this.#profile.ID === Loc.getMessage('USER_ID')
			)
			{
				return Ustat.getPromise({
					userId: this.#profile.ID,
					isNarrow,
					data: this.#features['pulseData'] ?? null
				});
			}
			return null;
		});
	}

	#savePhoto(dataObj)
	{
		ajax.runComponentAction(
			this.#cache.get('componentParams').componentName,
			'loadPhoto',
			{
				signedParameters: this.#cache.get('componentParams').signedParameters,
				mode: 'ajax',
				data: dataObj
			}
		).then(function (response) {
			if (response.data)
			{
				(top || window).BX.onCustomEvent('BX.Intranet.UserProfile:Avatar:changed', [{
					url: response.data,
					userId: this.#profile.ID,
				}]);
			}
		}.bind(this), function (response) {
			console.log('response: ', response);
		}.bind(this));
	}

	#getStressLevel(): Promise
	{
		if (this.#features['stressLevel'] !== 'Y')
		{
			return null;
		}
		return this.#cache.remember('getStressLevel', () => {
			return StressLevel.getPromise({
				signedParameters: this.#cache.get('componentParams').signedParameters,
				componentName: this.#cache.get('componentParams').componentName,
				userId: this.#profile.ID,
				data: this.#features['stressLevelData'] ?? null
			});
		});
	}

	#getQrContainer(flex): Element
	{
		return this.#cache.remember('getQrContainer', () => {
			const isInstalled = this.#features['appInstalled']['APP_ANDROID_INSTALLED'] === 'Y'
				|| this.#features['appInstalled']['APP_IOS_INSTALLED'] === 'Y';
			const onclick = () => {
				this.hide();
				(new QrAuthorization({
					title: Loc.getMessage('INTRANET_USER_PROFILE_QRCODE_TITLE2'),
					content: Loc.getMessage('INTRANET_USER_PROFILE_QRCODE_BODY2'),
					helpLink: ''
				})).show();
			}
			const onclickHelp = (event: MouseEvent) => {
				top.BX.Helper.show('redirect=detail&code=14999860');
				this.hide();
				event.preventDefault();
				event.stopPropagation();
				return false;
			};
			let node;
			if (flex !== 2 && flex !== 0)
			{
				// for a small size
				node = Tag.render`
					<div class="system-auth-form__item system-auth-form__scope ${isInstalled ? '--active' : ''}  --clickable" onclick="${onclick}" style="padding: 10px 14px">
						<div class="system-auth-form__item-container --center --column --center">
							<div class="system-auth-form__item-title --center --margin-xl">${Loc.getMessage('INTRANET_USER_PROFILE_MOBILE_TITLE2_SMALL')}</div>
							<div class="system-auth-form__qr" style="margin-bottom: 12px">
							</div>
							<div class="ui-qr-popupcomponentmaker__btn --border" style="margin-top: auto" onclick="${onclick}">${Loc.getMessage('INTRANET_USER_PROFILE_MOBILE_SHOW_QR_SMALL')}</div>
						</div>
						<div class="system-auth-form__icon-help --absolute" onclick="${onclickHelp}" title="${Loc.getMessage('INTRANET_USER_PROFILE_MOBILE_HOW_DOES_IT_WORK')}"></div>
					</div>
				`;
			}
			else if (flex === 0)
			{
				//full size
				node = Tag.render`
					<div class="system-auth-form__item system-auth-form__scope ${isInstalled ? '--active' : ''} --padding-qr-xl">
						<div class="system-auth-form__item-container --column --flex --flex-start">
							<div class="system-auth-form__item-title --l">${Loc.getMessage('INTRANET_USER_PROFILE_MOBILE_TITLE2')}</div>
							<div class="system-auth-form__item-title --link-dotted" onclick="${onclickHelp}">
								${Loc.getMessage('INTRANET_USER_PROFILE_MOBILE_HOW_DOES_IT_WORK')}
							</div>
							<div class="ui-qr-popupcomponentmaker__btn --large --border" style="margin-top: auto" onclick="${onclick}">
								${Loc.getMessage('INTRANET_USER_PROFILE_MOBILE_SHOW_QR')}
							</div>
						</div>
						<div class="system-auth-form__item-container --qr">
							<div class="system-auth-form__qr --full-size"></div>
						</div>
					</div>
				`;
			}
			else
			{
				// for flex 2. It is kind of middle
				node = Tag.render`
					<div class="system-auth-form__item system-auth-form__scope ${isInstalled ? '--active' : ''} --padding-mid-qr  --clickable" onclick="${onclick}">
						<div class="system-auth-form__item-container --column --flex --flex-start">
							<div class="system-auth-form__item-title --block">
								${Loc.getMessage('INTRANET_USER_PROFILE_MOBILE_TITLE2_SMALL')}
								<span class="system-auth-form__icon-help --inline" onclick="${onclickHelp}" title="${Loc.getMessage('INTRANET_USER_PROFILE_MOBILE_HOW_DOES_IT_WORK')}"></span>
							</div>
							<div class="ui-qr-popupcomponentmaker__btn --border" style="margin-top: auto" onclick="${onclick}">${Loc.getMessage('INTRANET_USER_PROFILE_MOBILE_SHOW_QR')}</div>
						</div>
						<div class="system-auth-form__item-container --qr">
							<div class="system-auth-form__qr --size-2"></div>
						</div>
					</div>
				`;
			}
			return node;
		});
	}

	#getDeskTopContainer(): ?Element
	{
		return this.#cache.remember('getDeskTopContainer', () => {
			let isInstalled = this.#features['appInstalled']['APP_MAC_INSTALLED'] === 'Y';
			let cssPostfix = '--apple';
			let title = Loc.getMessage('INTRANET_USER_PROFILE_DESKTOP_APPLE');
			let linkToDistributive = 'https://dl.bitrix24.com/b24/bitrix24_desktop.dmg';
			const typesInstallersForLinux = {
				'DEB': {
					text: Loc.getMessage('INTRANET_USER_PROFILE_DOWNLOAD_LINUX_DEB'),
					href: 'https://dl.bitrix24.com/b24/bitrix24_desktop.deb',
				},
				'RPM': {
					text: Loc.getMessage('INTRANET_USER_PROFILE_DOWNLOAD_LINUX_RPM'),
					href: 'https://dl.bitrix24.com/b24/bitrix24_desktop.rpm',
				},
			};

			if (this.#features.browser === 'Windows')
			{
				isInstalled = this.#features['appInstalled']['APP_WINDOWS_INSTALLED'] === 'Y';
				cssPostfix = '--windows';
				title = Loc.getMessage('INTRANET_USER_PROFILE_DESKTOP_WINDOWS');
				linkToDistributive = 'https://dl.bitrix24.com/b24/bitrix24_desktop.exe'
			}

			let onclick = isInstalled ? (event) => {
				event.preventDefault();
				event.stopPropagation();
				return false;
			} : () => {
				this.hide();
				return true;
			};

			let menuLinux = null;
			const showMenuLinux = (event) => {
				event.preventDefault();
				menuLinux = (menuLinux || new Menu({
					className: 'system-auth-form__popup',
					bindElement: event.target,
					items: [
						{
							text: typesInstallersForLinux.DEB.text,
							href: typesInstallersForLinux.DEB.href,
							onclick: () => {
								menuLinux.close();
							}
						},
						{
							text: typesInstallersForLinux.RPM.text,
							href: typesInstallersForLinux.RPM.href,
							onclick: () => {
								menuLinux.close();
							}
						},

					],
					angle: true,
					offsetLeft: 10,
					events: {
						onShow: () => {
							this.getPopup().getPopup().setAutoHide(false);
						},
						onClose: () => {
							this.getPopup().getPopup().setAutoHide(true);
						}
					}
				}));
				menuLinux.toggle();
			}

			if (this.#features.browser === 'Linux')
			{
				isInstalled = this.#features['appInstalled']['APP_LINUX_INSTALLED'] === 'Y';
				cssPostfix = '--linux';
				title = Loc.getMessage('INTRANET_USER_PROFILE_DESKTOP_LINUX');
				linkToDistributive = '';

				onclick = isInstalled ? (event) => {
					event.preventDefault();
					event.stopPropagation();
					return false;
				} : showMenuLinux;
			}

			if (Type.isPlainObject(this.#features['otp']) === false)
			{
				let menuPopup = null;
				let menuItems = [{
					text: Loc.getMessage('INTRANET_USER_PROFILE_DOWNLOAD'),
					href: linkToDistributive,
					onclick: () => {
						menuPopup.close();
						this.hide();
					}
				}];

				if (this.#features.browser === 'Linux')
				{
					menuItems = [
						{
							text: typesInstallersForLinux.DEB.text,
							href: typesInstallersForLinux.DEB.href,
							onclick: () => {
								menuPopup.close();
							}
						},
						{
							text: typesInstallersForLinux.RPM.text,
							href: typesInstallersForLinux.RPM.href,
							onclick: () => {
								menuPopup.close();
							}
						},
					];
				}

				const popupClick = (event: MouseEvent) => {
					menuPopup = (menuPopup || (new Menu({
						className: 'system-auth-form__popup',
						bindElement: event.target,
						items: menuItems,
						angle: true,
						offsetLeft: 10,
						events: {
							onShow: () => {
								this.getPopup().getPopup().setAutoHide(false);
							},
							onClose: () => {
								this.getPopup().getPopup().setAutoHide(true);
							}
						}
					})));
					menuPopup.toggle();
				};
				return Tag.render`
					<div data-role="desktop-item" class="system-auth-form__item system-auth-form__scope --padding-sm-all ${isInstalled ? ' --active' : ''} --vertical --center">
						<div class="system-auth-form__item-logo --margin-bottom --center system-auth-form__item-container --flex">
							<div class="system-auth-form__item-logo--image ${cssPostfix}"></div>
						</div>
						${isInstalled ? Tag.render`<div class="system-auth-form__config --absolute" onclick="${popupClick}"></div>` : ''}
						<div class="system-auth-form__item-container --flex --center --display-flex">
							<div class="system-auth-form__item-title --light --center --s">${title}</div>
						</div>
						<div class="system-auth-form__item-content --flex --center --display-flex">
							<a class="ui-qr-popupcomponentmaker__btn" style="margin-top: auto" href="${linkToDistributive}" target="_blank" onclick="${onclick}">
								${isInstalled ? Loc.getMessage('INTRANET_USER_PROFILE_INSTALLED') : Loc.getMessage('INTRANET_USER_PROFILE_INSTALL')}
							</a>
						</div>
					</div>
				`;
			}

			const getLinkForHiddenState = () => {
				const link = Tag.render`
					<a href="${linkToDistributive}" class="system-auth-form__item-title --link-dotted">${Loc.getMessage('INTRANET_USER_PROFILE_DOWNLOAD')}</a>
				`;

				if (this.#features.browser === 'Linux')
				{
					link.addEventListener('click', showMenuLinux);
				}

				return link;
			};

			return Tag.render`
				<div class="system-auth-form__item system-auth-form__scope --padding-bottom-10 ${isInstalled ? ' --active' : ''}">
					<div class="system-auth-form__item-logo">
						<div class="system-auth-form__item-logo--image ${cssPostfix}"></div>
					</div>
					<div class="system-auth-form__item-container">
						<div class="system-auth-form__item-title ${isInstalled ? ' --without-margin' : '--min-height'}">${title}</div>
						${isInstalled ? getLinkForHiddenState() : ''}
						<div class="system-auth-form__item-content --center --center-force">
							<a class="ui-qr-popupcomponentmaker__btn" href="${linkToDistributive}" target="_blank" onclick="${onclick}">
								${isInstalled ? Loc.getMessage('INTRANET_USER_PROFILE_INSTALLED') : Loc.getMessage('INTRANET_USER_PROFILE_INSTALL')}
							</a>
						</div>
					</div>
				</div>
			`;
		});
	}

	#getOTPContainer(single): Element
	{
		if (Type.isPlainObject(this.#features['otp']) === false)
		{
			return null;
		}

		return this.#cache.remember('getOTPContainer', () => {
			const isInstalled = this.#features['otp']['IS_ACTIVE'] === 'Y' || this.#features['otp']['IS_ACTIVE'] === true;
			const onclick = () => {
				this.hide();
				if (String(this.#features['otp']['URL']).length > 0)
				{
					Uri.addParam(this.#features['otp']['URL'], {page: 'otpConnected'});
					BX.SidePanel.Instance.open(Uri.addParam(this.#features['otp']['URL'], {page: 'otpConnected'}), {width: 1100});
				}
				else
				{
					console.error('Otp page is not defined. Check the component params');
				}
			};
			const button = isInstalled ?
				Tag.render`<div class="ui-qr-popupcomponentmaker__btn" style="margin-top: auto" onclick="${onclick}">${Loc.getMessage('INTRANET_USER_PROFILE_TURNED_ON')}</div>`
				:
				Tag.render`<div class="ui-qr-popupcomponentmaker__btn" style="margin-top: auto" onclick="${onclick}">${Loc.getMessage('INTRANET_USER_PROFILE_TURN_ON')}</div>`;
			const onclickHelp = () => {
				top.BX.Helper.show('redirect=detail&code=6641271');
				this.hide();
			};
			if (single !== true)
			{
				return Tag.render`
					<div class="system-auth-form__item system-auth-form__scope --padding-bottom-10 ${isInstalled ? ' --active' : ''}">
						<div class="system-auth-form__item-logo">
							<div class="system-auth-form__item-logo--image --authentication"></div>
						</div>
						<div class="system-auth-form__item-container --flex --column --flex-start">
							<div class="system-auth-form__item-title --without-margin --block">
								${Loc.getMessage('INTRANET_USER_PROFILE_OTP_MESSAGE')}&nbsp;<span class="system-auth-form__icon-help --inline" onclick="${onclickHelp}"></span>
							</div>
							${isInstalled ?
								Tag.render`
									<div class="system-auth-form__item-title --link-dotted" onclick="${onclick}">${Loc.getMessage('INTRANET_USER_PROFILE_CONFIGURE')}</div>
								` : ''
							}
							<div class="system-auth-form__item-content --margin-top-auto --center --center-force">
								${button}
							</div>
						</div>
						${isInstalled ? '' : `
							<div class="system-auth-form__item-new">
								<div class="system-auth-form__item-new--title">${Loc.getMessage('INTRANET_USER_PROFILE_OTP_TITLE')}</div>
							</div>`
						}
					</div>
				`;
			}

			let menuPopup = null;
			const popupClick = (event: MouseEvent) => {
				menuPopup = (menuPopup || (new Menu({
					className: 'system-auth-form__popup',
					bindElement: event.target,
					items: [
						{
							text: Loc.getMessage('INTRANET_USER_PROFILE_CONFIGURE'),
							onclick: () => {
								menuPopup.close();
								onclick();
							}
						}
					],
					angle: true,
					offsetLeft: 10,
					events: {
						onShow: () => {
							this.getPopup().getPopup().setAutoHide(false);
						},
						onClose: () => {
							this.getPopup().getPopup().setAutoHide(true);
						}
					}
				})));
				menuPopup.toggle();
			};
			return Tag.render`
				<div class="system-auth-form__item system-auth-form__scope --padding-sm-all ${isInstalled ? ' --active' : ''} --vertical --center">
					<div class="system-auth-form__item-logo --margin-bottom --center system-auth-form__item-container --flex">
						<div class="system-auth-form__item-logo--image --authentication"></div>
					</div>
					<div class="system-auth-form__item-container --flex">
						<div class="system-auth-form__item-title --light --center --s">
							${Loc.getMessage('INTRANET_USER_PROFILE_OTP_MESSAGE')}
							<!-- <span class="system-auth-form__icon-help" onclick="${onclickHelp}"></span> -->
						</div>
					</div>
					${isInstalled ? Tag.render`<div class="system-auth-form__config --absolute" onclick="${popupClick}"></div>` : ''}
					<div class="system-auth-form__item-container --flex --column --space-around">
						<div class="system-auth-form__item-content --flex --display-flex">
							${button}
						</div>
					</div>
					${isInstalled ? '' : `
						<div class="system-auth-form__item-new system-auth-form__item-new-icon --ssl">
							<div class="system-auth-form__item-new--title">${Loc.getMessage('INTRANET_USER_PROFILE_OTP_TITLE')}</div>
						</div>`
					}
				</div>
			`;
		});
	}

	#getLoginHistoryContainer(): ?Element
	{
		if (this.#features.loginHistory.isHide)
		{
			return null;
		}

		return this.#cache.remember('getLoginHistoryContainer', () => {
			const history = new UserLoginHistory(this.#features.loginHistory, this);
			return {
				html: history.getContainer(),
				backgroundColor: '#fafafa',
			};
		});
	}

	#getBindings(): ?Element
	{
		if (!(Type.isPlainObject(this.#features['bindings'])
			&& Type.isStringFilled(this.#features['bindings']['text'])
			&& Type.isArray(this.#features['bindings']['items'])
			&& this.#features['bindings']['items'].length > 0
		))
		{
			return null;
		}

		return this.#cache.remember('getBindingsContainer', () => {
			const div = Tag.render`
				<div class="system-auth-form__item --hover system-auth-form__scope --center --padding-sm">
					<div class="system-auth-form__item-logo">
						<div class="system-auth-form__item-logo--image --binding"></div>
					</div>
					<div class="system-auth-form__item-container --center">
						<div class="system-auth-form__item-title --light">${Text.encode(this.#features['bindings']['text'])}</div>
					</div>
					<div data-role="arrow" class="system-auth-form__item-icon --arrow-right"></div>
				</div>
			`;
			div.addEventListener('click', () => {
				this.__bindingsMenu = (this.__bindingsMenu || (new Menu({
					className: 'system-auth-form__popup',
					bindElement: div.querySelector('[data-role="arrow"]'),
					items: this.#features['bindings']['items'],
					angle: true,
					cachable: false,
					offsetLeft: 10,
					events: {
						onShow: () => {
							this.getPopup().getPopup().setAutoHide(false);
						},
						onClose: () => {
							this.getPopup().getPopup().setAutoHide(true);
							if (this.__bindingsMenu.isNeedToHide !== false)
							{
								this.hide();
							}
						}
					}
				})));
				this.__bindingsMenu.isNeedToHide = false;
				this.__bindingsMenu.toggle();
				setTimeout(() => {
					this.__bindingsMenu.isNeedToHide = true;
				}, 0);
			});
			return div;
		});
	}

	#getNotificationContainer(): Element
	{
		if (this.#features['im'] !== 'Y')
		{
			return null;
		}
		return this.#cache.remember('getNotificationContainer', () => {
			const div = Tag.render`
				<div class="system-auth-form__item --hover system-auth-form__scope --padding-sm">
					<div class="system-auth-form__item-logo">
						<div class="system-auth-form__item-logo--image --notification"></div>
					</div>
					<div class="system-auth-form__item-container --center">
						<div class="system-auth-form__item-title --light">${Loc.getMessage('AUTH_NOTIFICATION')}</div>
					</div>
				</div>
			`;
			div.addEventListener('click', () => {
				this.hide();
				BXIM.openSettings({'onlyPanel':'notify'});
			});
			return div;
		});
	}

	#getLogoutContainer(): Element
	{
		return this.#cache.remember('getLogoutContainer', () => {
			const backUrl = new Uri(window.location.pathname);
			backUrl.removeQueryParam(['logout', 'login', 'back_url_pub', 'user_lang']);
			const newUrl =  new Uri('/auth/?logout=yes');
			newUrl.setQueryParam('sessid', BX.bitrix_sessid());
			newUrl.setQueryParam('backurl', encodeURIComponent(backUrl.toString()));
			//TODO
			return Tag.render`
				<div class="system-auth-form__item system-auth-form__scope --padding-sm">
					<div class="system-auth-form__item-logo">
						<div class="system-auth-form__item-logo--image --logout"></div>
					</div>
					<div class="system-auth-form__item-container --center">
						<div class="system-auth-form__item-title --light">${Loc.getMessage('AUTH_LOGOUT')}</div>
					</div>
					<a href="${newUrl.toString()}" class="system-auth-form__item-link-all"></a>
				</div>
			`;
		});
	}

	static init(node, options)
	{
		if (node[widgetMarker])
		{
			return;
		}
		const onclick = () => {
			if (!node['popupSymbol'])
			{
				node['popupSymbol'] = new this(node, options);
			}
			node['popupSymbol'].toggle();
			this.instance = node['popupSymbol'];
		};
		node[widgetMarker] = true;
		node.addEventListener('click', onclick);
		setTimeout(onclick, 100);
	}

	static instance = null;

	static getInstance():? this
	{
		return this.instance;
	}
}