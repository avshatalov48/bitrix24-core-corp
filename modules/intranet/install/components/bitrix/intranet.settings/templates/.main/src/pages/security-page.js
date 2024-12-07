import { Loc, Tag, Event, Dom, Type} from 'main.core';
import 'ui.forms';
import 'ui.icon-set.actions';
import 'ui.icon-set.main';
import { EventEmitter } from 'main.core.events';
import { Section, Row } from 'ui.section';
import { Checker, Selector, SingleChecker, UserSelector, TextInput} from 'ui.form-elements.view';
import { Switcher, SwitcherSize } from 'ui.switcher';
import { Popup } from 'main.popup';
import { SettingsSection, SettingsField, SettingsRow, BaseSettingsPage } from 'ui.form-elements.field';
import {TagSelector} from "ui.entity-selector";
import { AnalyticSettingsEvent } from '../analytic';

export class SecurityPage extends BaseSettingsPage
{
	#otpChecker: ?Checker;
	#otpSelector: ?Selector;
	#otpPopup: ?Popup;

	constructor()
	{
		super();
		this.titlePage = Loc.getMessage('INTRANET_SETTINGS_TITLE_PAGE_SECURITY');
		this.descriptionPage = Loc.getMessage('INTRANET_SETTINGS_DESCRIPTION_PAGE_SECURITY');
	}

	getType(): string
	{
		return 'security';
	}

	appendSections(contentNode: HTMLElement): void
	{
		const isBitrix24 = this.hasValue('IS_BITRIX_24') && this.getValue('IS_BITRIX_24');
		if (this.hasValue('SECURITY_OTP_ENABLED') && this.getValue('SECURITY_OTP_ENABLED'))
		{
			this.#buildOTPSection()?.renderTo(contentNode);
		}

		// if (isBitrix24)
		// {
		// 	this.#buildPasswordRecoverySection().renderTo(contentNode);
		// }
		this.#buildDevicesHistorySection()?.renderTo(contentNode);
		this.#buildEventLogSection()?.renderTo(contentNode);
		this.#buildMobileAppSection()?.renderTo(contentNode);
		if (isBitrix24)
		{
			this.#buildAccessIPSection()?.renderTo(contentNode);
			this.#buildBlackListSection()?.renderTo(contentNode);
		}
	}

	#buildOTPSection(): ?SettingsSection
	{
		if (!this.hasValue('sectionOtp'))
		{
			return ;
		}
		const otpSection = new Section(this.getValue('sectionOtp'));

		const section = new SettingsSection({
			section: otpSection,
			parent: this,
		});

		const descriptionRow = new Row({
			content: this.#getOTPDescription().getContainer(),
		});
		new SettingsRow({
			row: descriptionRow,
			parent: section,
		});

		if (this.hasValue('SECURITY_OTP') && this.hasValue('SEND_OTP_PUSH'))
		{
			const securityOtpCheckerRow = new Row({
				content: this.#getOTPChecker().render(),
				separator: this.#getOTPChecker().isChecked() ? '' : 'bottom',
				className: this.#getOTPChecker().isChecked() ? '' : '--block',
			});
			new SettingsRow({
				row: securityOtpCheckerRow,
				parent: section,
			});

			const securityOtpPeriodSelectorRow = new Row({
				content: this.#getOTPPeriodSelector().render(),
				isHidden: !this.#getOTPChecker().isChecked(),
			});
			new SettingsRow({
				row: securityOtpPeriodSelectorRow,
				parent: section,
			});

			const switcherWrapper = Tag.render`
				<div class="settings-switcher-wrapper">
					<div class="settings-security-message-switcher"/>
				</div>
			`;
			new SingleChecker({
				switcher: new Switcher({
					node: switcherWrapper.querySelector('.settings-security-message-switcher'),
					inputName: 'SEND_OTP_PUSH',
					checked: this.getValue('SEND_OTP_PUSH'),
					size: SwitcherSize.small,
				}),
			});
			const securityOtpMessageChatCheckerRow = new Row({
				content: switcherWrapper,
				isHidden: !this.#getOTPChecker().isChecked(),
			});
			switcherWrapper.append(Tag.render`<span class="settings-switcher-title">${
				Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_OTP_SWITCHING_MESSAGE_CHAT')
			}</span>`);
			new SettingsRow({
				row: securityOtpMessageChatCheckerRow,
				parent: section,
			});

			EventEmitter.subscribe(
				this.#getOTPChecker().switcher,
				'toggled',
				() => {
					if (this.getValue('SECURITY_IS_USER_OTP_ACTIVE') !== true && this.#getOTPChecker().isChecked())
					{
						this.#getOTPPopup().show();
						this.#getOTPChecker().cancel();
						this.#getOTPChecker().switcher.check(false);

						return;
					}

					if (this.hasValue('SECURITY_OTP_ENABLED') && this.getValue('SECURITY_OTP_ENABLED'))
					{
						this.getAnalytic()?.addEventToggle2fa(this.#getOTPChecker().isChecked());
					}

					if (this.#getOTPChecker().isChecked())
					{
						Dom.removeClass(securityOtpCheckerRow.render(), '--bottom-separator --block');
						securityOtpPeriodSelectorRow.show();
						securityOtpMessageChatCheckerRow.show();
					}
					else
					{
						Dom.addClass(securityOtpCheckerRow.render(), '--bottom-separator --block');
						securityOtpPeriodSelectorRow.hide();
						securityOtpMessageChatCheckerRow.hide();
					}
				},
			);
		}

		return section;
	}

	#getOTPChecker(): Checker
	{
		if (this.#otpChecker instanceof Checker)
		{
			return this.#otpChecker;
		}

		if (this.hasValue('fieldSecurityOtp'))
		{
			this.#otpChecker = new Checker({
				inputName: this.getValue('fieldSecurityOtp').inputName,
				checked: this.getValue('fieldSecurityOtp').checked,
				title: this.getValue('fieldSecurityOtp').title,
				isEnable: this.getValue('fieldSecurityOtp').isEnable,
				hideSeparator: true,
				alignCenter: true,
				noMarginBottom: true,
			});
		}

		this.#otpChecker.renderLockElement = () => {
			return null;
		};

		return this.#otpChecker;
	}

	#getOTPPopup(): Popup
	{
		if (this.#otpPopup instanceof Popup)
		{
			return this.#otpPopup;
		}

		const popupDescription = Tag.render`
			<div class="intranet-settings__security_popup_info">
				${Loc.getMessage('INTRANET_SETTINGS_POPUP_OTP_ENABLE')}
			</div>	
		`;

		const popupButton = new BX.UI.Button({
			text: Loc.getMessage('INTRANET_SETTINGS_POPUP_OTP_ENABLE_BUTTON'),
			color: BX.UI.Button.Color.PRIMARY,
			events: {
				click: () => {
					this.#getOTPPopup().close();
					BX.SidePanel.Instance.open(this.getValue('SECURITY_OTP_PATH'));
				},
			},
		});

		const popupContent = Tag.render`
			<div class="intranet-settings__security_popup_container">
				${popupDescription}
				<div class="ui-btn-container ui-btn-container-center">
					${popupButton.getContainer()}
				</div>			
			</div>
		`;

		this.#otpPopup = new Popup({
			bindElement: this.#otpChecker.getInputNode(),
			content: popupContent,
			autoHide: true,
			width: 337,
			angle: {
				offset: 200 - 15,
			},
			offsetLeft: this.#otpChecker.getInputNode().offsetWidth - 200 + 15,
			closeByEsc: true,
			borderRadius: 18,
		});

		return this.#otpPopup;
	}

	#getOTPPeriodSelector(): Selector
	{
		if (this.#otpSelector instanceof Selector)
		{
			return this.#otpSelector;
		}

		this.#otpSelector = new Selector({
			label: Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_OTP_SWITCHING_PERIOD'),
			name: 'SECURITY_OTP_DAYS',
			items: this.getValue('SECURITY_OTP_DAYS').ITEMS,
			current: this.getValue('SECURITY_OTP_DAYS').CURRENT,
		});

		return this.#otpSelector;
	}

	#getOTPDescription(): BX.UI.Alert
	{
		return new BX.UI.Alert({
			text: this.#getOTPDescriptionText(),
			inline: true,
			size: BX.UI.Alert.Size.SMALL,
			color: BX.UI.Alert.Color.PRIMARY,
			animated: true,
		});
	}

	#getOTPDescriptionText(): string
	{
		return `
		${Loc.getMessage('INTRANET_SETTINGS_SECTION_SECURITY_DESCRIPTION_FIRST')}
		</br></br>
		<span class="settings-section-description-focus-text --security-info">
			${Loc.getMessage('INTRANET_SETTINGS_SECTION_SECURITY_DESCRIPTION_SECOND')}
			<a class="ui-section__link" onclick="top.BX.Helper.show('redirect=detail&code=17728602')">
				${Loc.getMessage('INTRANET_SETTINGS_CANCEL_MORE')}
			</a>
		</span>`;
	}

	#buildAccessIPSection(): ?SettingsSection
	{
		if (!this.hasValue('sectionAccessIp'))
		{
			return;
		}
		const accessIpSection = new Section(this.getValue('sectionAccessIp'));

		const section = new SettingsSection({
			section: accessIpSection,
			parent: this,
		});

		const descriptionRow = new Row({
			content: this.#getIpAccessDescription().getContainer(),
		});
		new SettingsRow({
			row: descriptionRow,
			parent: section,
		});

		let fieldsCount = 0;
		if (this.hasValue('IP_ACCESS_RIGHTS'))
		{
			for (const ipUsersList of this.getValue('IP_ACCESS_RIGHTS'))
			{
				fieldsCount++;
				new SettingsRow({
					parent: section,
					child: this.#getUserSelectorRow(ipUsersList),
				});
				new SettingsRow({
					parent: section,
					child: this.#getAccessIpRow(ipUsersList),
				});
			}
		}

		if (fieldsCount === 0)
		{
			fieldsCount++;
			new SettingsRow({
				parent: section,
				child: this.#getEmptyUserSelectorRow(fieldsCount),
			});
			new SettingsRow({
				parent: section,
				child: this.#getEmptyAccessIpRow(fieldsCount),
			});
		}

		const onclickAddField = () => {
			if (this.getValue('IP_ACCESS_RIGHTS_ENABLED'))
			{
				fieldsCount++;

				const emptyUserSelectorRow = new Row({
					content: this.#getEmptyUserSelectorRow(fieldsCount).render(),
				});
				Dom.insertBefore(emptyUserSelectorRow.render(), additionalUsersAccessIpButton.parentElement);

				const emptyAccessIpRow = new Row({
					content: this.#getEmptyAccessIpRow(fieldsCount).render(),
				});
				Dom.insertBefore(emptyAccessIpRow.render(), additionalUsersAccessIpButton.parentElement);
			}
			else
			{
				BX.UI.InfoHelper.show('limit_admin_ip');
			}
		};

		const additionalUsersAccessIpButton = Tag.render`
			<div class="ui-text-right">
				<a class="ui-section__link" href="javascript:void(0)" onclick="${onclickAddField}">
					${Loc.getMessage('INTRANET_SETTINGS_ADDITIONAL_USER_ACCESS_IP')}
				</a>
			</div>
		`;

		new SettingsRow({
			row: new Row({
				content: additionalUsersAccessIpButton,
			}),
			parent: section,
		});

		return section;
	}

	#getEmptyUserSelectorRow(fieldNumber): SettingsRow
	{
		const userSelector = new UserSelector({
			inputName: `SECURITY_IP_ACCESS_${fieldNumber}_USERS[]`,
			label: Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_SELECT_USER_ACCESS_IP'),
			enableDepartments: true,
			encodeValue: (value) => {
				if (!Type.isNil(value.id))
				{
					return value.id === 'all-users' ? 'AU' : value.type + value.id.toString().split(':')[0];
				}

				return null;
			},
			isEnable: this.getValue('IP_ACCESS_RIGHTS_ENABLED'),
			helpMessageProvider: this.helpMessageProviderFactory(Loc.getMessage('INTRANET_SETTINGS_FIELD_HELP_MESSAGE_PRO')),
		});

		return new SettingsField({
			fieldView: userSelector,
		});
	}

	#getEmptyAccessIpRow(fieldNumber): SettingsRow
	{
		const inputName = `SECURITY_IP_ACCESS_${fieldNumber}_IP`;
		const accessIp = new TextInput({
			inputName,
			label: this.getValue('IP_ACCESS_RIGHTS_ENABLED_LABEL') ?? Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_SELECT_ACCEPTED_IP'),
			isEnable: this.getValue('IP_ACCESS_RIGHTS_ENABLED'),
			helpMessageProvider: this.helpMessageProviderFactory(Loc.getMessage('INTRANET_SETTINGS_FIELD_HELP_MESSAGE_PRO')),
		});

		return new SettingsField({
			fieldView: accessIp,
		});
	}

	#getUserSelectorRow(ipUsersList): SettingsRow
	{
		const userSelector = new UserSelector({
			inputName: `SECURITY_IP_ACCESS_${ipUsersList.fieldNumber}_USERS[]`,
			label: this.getValue('IP_ACCESS_RIGHTS_ENABLED_LABEL') ?? Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_SELECT_USER_ACCESS_IP'),
			values: Object.values(ipUsersList.users),
			enableDepartments: true,
			encodeValue: (value) => {
				if (!Type.isNil(value.id))
				{
					return value.id === 'all-users' ? 'AU' : value.type + value.id.toString().split(':')[0];
				}

				return null;
			},
			decodeValue: (value: string) => {
				if (value === 'AU')
				{
					return {
						type: value,
						id: '',
					}
				}

				const arr = value.match(/^(U|DR|D)(\d+)/);

				if (!Type.isArray(arr))
				{
					return {
						type: null,
						id: null,
					};
				}

				return {
					type: arr[1],
					id: arr[2],
				}
			},
		});

		return new SettingsField({
			fieldView: userSelector,
		});
	}

	#getAccessIpRow(ipUsersList): SettingsRow
	{
		const inputName = `SECURITY_IP_ACCESS_${ipUsersList.fieldNumber}_IP`;
		const accessIp = new TextInput({
			inputName,
			label: Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_SELECT_ACCEPTED_IP'),
			value: ipUsersList.ip,
		});

		return new SettingsField({
			fieldView: accessIp,
		});
	}

	#getIpAccessDescription(): BX.UI.Alert
	{
		return new BX.UI.Alert({
			text: `
				${Loc.getMessage('INTRANET_SETTINGS_SECTION_SECURITY_DESCRIPTION_IP_ACCESS', {'#ARTICLE_CODE#': 'redirect=detail&code=17300230'})}
				<a class="ui-section__link" onclick="top.BX.Helper.show('redirect=detail&code=17300230')">
					${Loc.getMessage('INTRANET_SETTINGS_CANCEL_MORE')}
				</a>
			`,
			inline: true,
			size: BX.UI.Alert.Size.SMALL,
			color: BX.UI.Alert.Color.PRIMARY,
			animated: true,
		});
	}

	#buildPasswordRecoverySection(): ?SettingsSection
	{
		return new Section({
			title: Loc.getMessage('INTRANET_SETTINGS_SECTION_TITLE_PASSWORD_RECOVERY'),
			titleIconClasses: 'ui-icon-set',
			isOpen: false,
			canCollapse: false,
		});
	}

	#buildDevicesHistorySection(): ?SettingsSection
	{
		if (!this.hasValue('sectionHistory'))
		{
			return;
		}

		const devicesHistorySection = new Section(this.getValue('sectionHistory'));

		const settingsSection = new SettingsSection({
			section: devicesHistorySection,
			parent: this,
		});

		const devicesHistoryDescription = new BX.UI.Alert({
			text: `
				${Loc.getMessage('INTRANET_SETTINGS_SECTION_SECURITY_DESCRIPTION_DEVICE_HISTORY')}
				<a class="ui-section__link" onclick="top.BX.Helper.show('redirect=detail&code=16623484')">
					${Loc.getMessage('INTRANET_SETTINGS_CANCEL_MORE')}
				</a>
			`,
			inline: true,
			size: BX.UI.Alert.Size.SMALL,
			color: BX.UI.Alert.Color.PRIMARY,
			animated: true,
		})

		const descriptionRow = new Row({
			content: devicesHistoryDescription.getContainer(),
		});
		new SettingsRow({
			row: descriptionRow,
			parent: settingsSection,
		});

		if (this.hasValue('DEVICE_HISTORY_SETTINGS'))
		{
			const messageNode = Tag.render`<span>${Loc.getMessage(
				'INTRANET_SETTINGS_FIELD_HELP_MESSAGE_ENT', { '#TARIFF#': 'ent250' }
			)}</span>`;
			const cleanupDaysField = new Selector({
				label: this.getValue('DEVICE_HISTORY_SETTINGS').label,
				name: this.getValue('DEVICE_HISTORY_SETTINGS').name,
				items: this.getValue('DEVICE_HISTORY_SETTINGS').values,
				current: this.getValue('DEVICE_HISTORY_SETTINGS').current,
				isEnable: this.getValue('DEVICE_HISTORY_SETTINGS').isEnable,
				bannerCode: 'limit_office_login_history',
				helpMessageProvider: this.helpMessageProviderFactory(messageNode),
			});
			if (!this.getValue('DEVICE_HISTORY_SETTINGS').isEnable)
			{
				Event.bind(
					cleanupDaysField.getInputNode(),
					'click',
					() =>
					{
						this.getAnalytic()?.addEventOpenHint(this.getValue('DEVICE_HISTORY_SETTINGS').name);
					}
				);
				Event.bind(
					messageNode.querySelector('a'),
					'click',
					() => this.getAnalytic()?.addEventOpenTariffSelector(this.getValue('DEVICE_HISTORY_SETTINGS').name)
				);
			}

			SecurityPage.addToSectionHelper(cleanupDaysField, settingsSection);
		}

		const goToUserListButton = Tag.render`
			<div class="ui-text-right">
				<a class="ui-section__link" href="/company/" target="_blank">
					${Loc.getMessage('INTRANET_SETTINGS_GO_TO_USER_LIST_LINK')}
				</a>
			</div>
		`;

		new SettingsRow({
			row: new Row({
				content: goToUserListButton,
			}),
			parent: settingsSection,
		});

		return settingsSection;
	}

	#buildEventLogSection(): ?SettingsSection
	{
		if (!this.hasValue('sectionEventLog'))
		{
			return;
		}
		const eventLogSection = new Section(this.getValue('sectionEventLog'));

		const settingsSection = new SettingsSection({
			section: eventLogSection,
			parent: this,
		});

		const eventLogDescription = new BX.UI.Alert({
			text: `
				${Loc.getMessage('INTRANET_SETTINGS_SECTION_SECURITY_DESCRIPTION_EVENT_LOG')}
				<a class="ui-section__link" onclick="top.BX.Helper.show('redirect=detail&code=17296266')">
					${Loc.getMessage('INTRANET_SETTINGS_CANCEL_MORE')}
				</a>
			`,
			inline: true,
			size: BX.UI.Alert.Size.SMALL,
			color: BX.UI.Alert.Color.PRIMARY,
			animated: true,
		})

		const descriptionRow = new Row({
			content: eventLogDescription.getContainer(),
		});
		new SettingsRow({
			row: descriptionRow,
			parent: settingsSection,
		});

		const goToUserListButton = this.hasValue('EVENT_LOG')
			? Tag.render`
				<div class="ui-text-right">
					<a class="ui-section__link" href="${this.getValue('EVENT_LOG')}" target="_blank">
						${Loc.getMessage('INTRANET_SETTINGS_GO_TO_EVENT_LOG_LINK')}
					</a>
				</div>
			`
			: Tag.render`
				<div class="ui-text-right">
					<a class="ui-section__link" href="javascript:void(0)" onclick="BX.UI.InfoHelper.show('limit_office_login_log')">
						${Loc.getMessage('INTRANET_SETTINGS_GO_TO_EVENT_LOG_LINK')}
					</a>
				</div>
			`;

		new SettingsRow({
			row: new Row({
				content: goToUserListButton,
			}),
			parent: settingsSection,
		});

		return settingsSection;
	}

	#buildBlackListSection(): ?SettingsSection
	{
		if (!this.hasValue('sectionBlackList'))
		{
			return;
		}
		let params = this.getValue('sectionBlackList');
		params['singleLink'] = {
			href: '/settings/configs/mail_blacklist.php',
		};

		return new Section(params);
	}

	#buildMobileAppSection(): ?SettingsSection
	{
		if (!this.hasValue('sectionMobileApp'))
		{
			return;
		}

		const mobileAppSection = new Section(this.getValue('sectionMobileApp'));

		const settingsSection = new SettingsSection({
			section: mobileAppSection,
			parent: this,
		});

		if (this.hasValue('switcherDisableCopy'))
		{
			let disableCopyField = new Checker(this.getValue('switcherDisableCopy'));
			SecurityPage.addToSectionHelper(disableCopyField, settingsSection);
		}

		if (this.hasValue('switcherDisableScreenshot'))
		{
			let disableCopyScreenshotField = new Checker(this.getValue('switcherDisableScreenshot'));
			SecurityPage.addToSectionHelper(disableCopyScreenshotField, settingsSection);
		}

		return settingsSection;
	}
}
