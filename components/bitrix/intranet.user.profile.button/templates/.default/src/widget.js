import {Uri, Type, Tag, Cache, Loc, Browser, Text, Dom, ajax, Event} from 'main.core';
import {EventEmitter} from 'main.core.events';
import { AvatarRoundGuest } from 'ui.avatar';
import {PopupComponentsMaker} from 'ui.popupcomponentsmaker';
import {StressLevel} from './stress-level';
import ThemePicker from './theme-picker';
import {Menu} from 'main.popup';
import 'main.qrcode';
import Options from "./options";
import Ustat from "./ustat";
import UserLoginHistory from './user-login-history';
import { SignDocument } from './sign-document';
import {QrAuthorization} from "ui.qrauthorization";
import {Otp} from "./otp";
import {DesktopApi} from 'im.v2.lib.desktop-api';

const widgetMarker = Symbol('user.widget');
export default class Widget extends EventEmitter
{
	#container: Element;
	#popup: ?PopupComponentsMaker;
	#profile = null;
	#features = {};
	#cache = new Cache.MemoryCache();
	#desktopDownloadLinks: {
		windows: string,
		macos: string,
		linuxDeb: string,
		linuxRpm: string,
	};
	#networkProfileUrl;

	constructor(container, {
		profile: {ID, FULL_NAME, PHOTO, MASK, STATUS, STATUS_CODE, URL, WORK_POSITION},
		component: {componentName, signedParameters},
		features,
		desktopDownloadLinks,
		networkProfileUrl,
	})
	{
		super();
		this.setEventNamespace(Options.eventNameSpace);
		this.#setEventHandlers();
		this.#container = container;
		this.#profile = {ID, FULL_NAME, PHOTO, MASK, STATUS, STATUS_CODE, URL, WORK_POSITION};
		this.#features = features;
		if (!Type.isStringFilled(this.#features.browser))
		{
			this.#features.browser =
				(Browser.isLinux() ? 'Linux'
				: (Browser.isWin() ? 'Windows' : 'MacOs')
				)
			;
		}
		this.#desktopDownloadLinks = desktopDownloadLinks;
		this.#networkProfileUrl = networkProfileUrl;

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
		this.emit('init');
		let signDocument = Type.isNull(this.#getSignDocument())
			? null
			: {
				html: this.#getSignDocument(),
			}
		;
		let content = [
			this.#getProfileContainer(),
			(this.#getAdminPanelContainer() ? {
				html: this.#getAdminPanelContainer(),
				backgroundColor: '#fafafa'
			} : null),
			signDocument,
			[
				{
					html: this.#getThemeContainer(),
					marginBottom: 24,
					overflow: true,
					minHeight: '63px',
				},
				{
					html: this.#getMaskContainer(),
					backgroundColor: '#fafafa',
				}
			],
			this.#getCompanyPulse(!!this.#getStressLevel()) ?
			[
				{
					html: this.#getCompanyPulse(!!this.#getStressLevel()),
					overflow: true,
					marginBottom: 24,
					flex: this.#getStressLevel() ? 0.5 : 1,
					minHeight: this.#getStressLevel() ? '115px' : '56px',
				},
				this.#getStressLevel(),
			] : null,
			this.#getOTPContainer(this.#getDeskTopContainer() === null) && this.#getDeskTopContainer() ?
			[
				{
					flex: 0.5,
					html: this.#getQrContainer(0.7),
					minHeight: '190px',
				},
				[
					{
						html: this.#getDeskTopContainer(),
						displayBlock: true
					},
					this.#getOTPContainer(false)
				]
			] : this.#getDeskTopContainer() || this.#getOTPContainer(true) ? [
					{
						html: this.#getQrContainer(2),
						flex: 2
					},
					this.#getDeskTopContainer() ?? this.#getOTPContainer(true)
			] : this.#getQrContainer(0),
			this.#getLoginHistoryContainer(),
			{
				html: this.#getBindings(),
				backgroundColor: '#fafafa'
			},
			(this.#getb24NetPanelContainer() ? {
				html: this.#getb24NetPanelContainer(),
				marginBottom: 24,
				backgroundColor: '#fafafa'
			} : null),
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
				displayBlock: item['displayBlock'] || null,
				minHeight: item['minHeight'] || null,
				secondary: item['secondary'] || false,
			};
		}

		this.#popup = new PopupComponentsMaker({
			target: this.#container,
			content: content.map(prepareFunc),
			width: 400,
			offsetTop: -14,
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

			let avatar;
			let avatarNode;

			if (this.#profile.STATUS_CODE === 'collaber')
			{
				avatar = new AvatarRoundGuest({
					size: 36,
					userpicPath: encodeURI(this.#profile.PHOTO),
					baseColor: '#19cc45',
				});
				avatarNode = avatar.getContainer();
			}
			else
			{
				avatarNode = Tag.render`
					<span class="system-auth-form__profile-avatar--image"
						${this.#profile.PHOTO ? `
							style="background-size: cover; background-image: url('${encodeURI(this.#profile.PHOTO)}')"` : ''}>
					</span>
				`;
			}

			const nameNode = Tag.render`
				<div class="system-auth-form__profile-name">${this.#profile.FULL_NAME}</div>
			`;
			EventEmitter.subscribe(
				EventEmitter.GLOBAL_TARGET,
				'BX.Intranet.UserProfile:Avatar:changed',
				({data: [{url, userId}]}) => {
					if (this.#profile.ID > 0 && userId && this.#profile.ID.toString() === userId.toString())
					{
						this.#profile.PHOTO = url;
						avatar.setUserPic(url);
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
			if (
				this.#profile.STATUS
				&& (this.#profile.STATUS !== 'collaber' || workPosition === '')
				&& Loc.hasMessage('INTRANET_USER_PROFILE_' + this.#profile.STATUS)
			)
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

	#getPopupContainer(): HTMLElement
	{
		return this.#cache.remember('popup-container', () => {
			return this.getPopup().getPopup().getPopupContainer();
		});
	}

	#setEventHandlers()
	{
		const autoHideHandler = (event) => {
			console.log(event)
			if (event.data.popup)
			{
				setTimeout(() => {
					Event.bind(this.#getPopupContainer(), 'click', () => {
						event.data.popup.close();
					});
				}, 100);
			}
		}

		this.subscribe('init', () => {
			EventEmitter.subscribe(EventEmitter.GLOBAL_TARGET, Options.eventNameSpace + ':onOpen', this.hide);
			this.subscribe('bindings:open', autoHideHandler);
			EventEmitter.subscribe(EventEmitter.GLOBAL_TARGET, Options.eventNameSpace + ':showOtpMenu', autoHideHandler);
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
				<a class="system-auth-form__item system-auth-form__scope --center --padding-sm --clickable" href="${this.#networkProfileUrl}">
					<div class="system-auth-form__item-logo">
						<div class="system-auth-form__item-logo--image --network"></div>
					</div>
					<div class="system-auth-form__item-container --center">
						<div class="system-auth-form__item-title --light">${Loc.getMessage('AUTH_PROFILE_B24NET_MSGVER_1')}</div>
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

	#getThemeContainer(): Promise
	{
		return this.#cache.remember('themePicker', () => {
			return ThemePicker.getPromise();
		});
	}

	#getMaskContainer(): Element
	{
		return this.#cache.remember('Mask', () => {
			return Tag.render`
				<div class="system-auth-form__item system-auth-form__scope --padding-sm">
					<div class="system-auth-form__item-logo">
						<div class="system-auth-form__item-logo--image --mask"></div>
					</div>
					<div class="system-auth-form__item-container">
						<div class="system-auth-form__item-title">
							<span>${Loc.getMessage('INTRANET_USER_PROFILE_MASKS')}</span>
							<span style="cursor: default" class="system-auth-form__icon-help"></span>
						</div>
						<div class="system-auth-form__item-content --center --center-force">
							<div class="ui-qr-popupcomponentmaker__btn --disabled">${Loc.getMessage('INTRANET_USER_PROFILE_INSTALL')}</div>
						</div>
					</div>
					<div class="system-auth-form__item-new --soon">
						<div class="system-auth-form__item-new--title">${Loc.getMessage('INTRANET_USER_PROFILE_SOON')}</div>
					</div>
				</div>
			`;
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
				return new Promise((resolve) => {
					ajax.runComponentAction('bitrix:intranet.user.profile.button', 'getUserStatComponent', {
						mode: 'class'
					}).then((response) => {
						BX.Runtime.html(null, response.data.html).then(() => {
							resolve(Ustat.getPromise({
								userId: this.#profile.ID,
								isNarrow,
								data: this.#features['pulseData'] ?? null
							}));
						});
					});
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

	#getSignDocument(): Promise<HTMLElement> | null
	{
		if (this.#features['signDocument']['available'] !== 'Y')
		{
			return null;
		}

		const isLocked = this.#features['signDocument']['locked'] === 'Y';

		return this.#cache.remember('getSignDocument', (): Promise<HTMLElement> => {
			EventEmitter.subscribe(
				SignDocument,
				SignDocument.events.onDocumentCreateBtnClick,
				() => this.hide(),
			);

			return SignDocument.getPromise(isLocked);
		});
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
			return new Promise((resolve, reject) => {
				BX.loadExt(['ui.qrauthorization', 'qrcode']).then(() => {
					const onclick = () => {
						this.hide();
						(new QrAuthorization({
							title: Loc.getMessage('INTRANET_USER_PROFILE_QRCODE_TITLE2'),
							content: Loc.getMessage('INTRANET_USER_PROFILE_QRCODE_BODY2'),
							intent: 'profile'
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
					<div class="system-auth-form__item system-auth-form__scope" style="padding: 10px 14px">
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
					<div class="system-auth-form__item system-auth-form__scope --padding-qr-xl">
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
					<div class="system-auth-form__item system-auth-form__scope --padding-mid-qr">
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

					return resolve(node);
				}).catch(reject);
			});
		});
	}

	#getDeskTopContainer(): ?Element
	{
		return this.#cache.remember('getDeskTopContainer', () => {
			let isInstalled = this.#features['appInstalled']['APP_MAC_INSTALLED'] === 'Y';
			let cssPostfix = '--apple';
			let title = Loc.getMessage('INTRANET_USER_PROFILE_DESKTOP_APPLE');
			let linkToDistributive = this.#desktopDownloadLinks.macos;
			const typesInstallersForLinux = {
				'DEB': {
					text: Loc.getMessage('INTRANET_USER_PROFILE_DOWNLOAD_LINUX_DEB'),
					href: this.#desktopDownloadLinks.linuxDeb,
				},
				'RPM': {
					text: Loc.getMessage('INTRANET_USER_PROFILE_DOWNLOAD_LINUX_RPM'),
					href: this.#desktopDownloadLinks.linuxRpm,
				},
			};

			if (this.#features.browser === 'Windows')
			{
				isInstalled = this.#features['appInstalled']['APP_WINDOWS_INSTALLED'] === 'Y';
				cssPostfix = '--windows';
				title = Loc.getMessage('INTRANET_USER_PROFILE_DESKTOP_WINDOWS');
				linkToDistributive = this.#desktopDownloadLinks.windows;
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

			if (this.#features['otp'].IS_ENABLED !== 'Y')
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

	#getOTPContainer(single): ?Promise
	{
		if (this.#features.otp.IS_ENABLED !== 'Y')
		{
			return null;
		}

		return this.#cache.remember('getOTPContainer', () => {
			return (new Otp(single, this.#features.otp)).getContainer();
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
							this.emit('bindings:open');
						},
					}
				})));
				this.__bindingsMenu.toggle();
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
			const onclickLogout = () => {
				if (DesktopApi.isDesktop())
				{
					DesktopApi.logout();
				}
				else
				{
					const backUrl = new Uri(window.location.pathname);
					backUrl.removeQueryParam(['logout', 'login', 'back_url_pub', 'user_lang']);
					const newUrl = new Uri('/auth/?logout=yes');
					newUrl.setQueryParam('sessid', BX.bitrix_sessid());
					newUrl.setQueryParam('backurl', encodeURIComponent(backUrl.toString()));
					document.location.href = newUrl;
				}
			};

			//TODO
			return Tag.render`
				<div class="system-auth-form__item system-auth-form__scope --padding-sm">
					<div class="system-auth-form__item-logo">
						<div class="system-auth-form__item-logo--image --logout"></div>
					</div>
					<div class="system-auth-form__item-container --center">
						<div class="system-auth-form__item-title --light">${Loc.getMessage('AUTH_LOGOUT')}</div>
					</div>
					<a onclick="${onclickLogout}" class="system-auth-form__item-link-all"></a>
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
	}

	static instance = null;

	static getInstance():? this
	{
		return this.instance;
	}
}
