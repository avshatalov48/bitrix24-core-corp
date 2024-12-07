import {TextInputInline, Checker, Selector, UserSelector} from 'ui.form-elements.view';
import {Loc, Event, Tag, Type} from 'main.core';
import { EventEmitter } from 'main.core.events';
import 'ui.icon-set.main';
import 'ui.icon-set.actions';
import {Section, Row, SeparatorRow} from 'ui.section';
import 'ui.forms';
import { SettingsSection, SettingsField, SettingsRow, BaseSettingsPage } from 'ui.form-elements.field';
import { AnalyticSettingsEvent } from '../analytic';

export class CommunicationPage extends BaseSettingsPage
{
	constructor()
	{
		super();
		this.titlePage = Loc.getMessage('INTRANET_SETTINGS_TITLE_PAGE_COMMUNICATION');
		this.descriptionPage = Loc.getMessage('INTRANET_SETTINGS_DESCRIPTION_PAGE_COMMUNICATION');
	}

	getType(): string
	{
		return 'communication';
	}

	appendSections(contentNode: HTMLElement)
	{
		let profileSection = this.#buildNewsFeedSection();
		profileSection.renderTo(contentNode);

		let chatSection = this.#buildChatSection();
		chatSection.renderTo(contentNode);

		if (this.hasValue('availableGeneralChannel'))
		{
			let channelSection = this.#buildChannelSection();
			channelSection.renderTo(contentNode);
		}

		let diskSection = this.#buildDiskSection();
		diskSection.renderTo(contentNode);
	}

	#buildNewsFeedSection(): SettingsSection
	{
		if (!this.hasValue('sectionFeed'))
		{
			return ;
		}
		let newsFeedSection = new Section(this.getValue('sectionFeed'));

		let settingsSection = new SettingsSection({
			section: newsFeedSection,
			parent: this,
		});

		if (this.hasValue('allow_livefeed_toall'))
		{
			let allowPostFeedField = new Checker(this.getValue('allow_livefeed_toall'));
			allowPostFeedField.hideSeparator = true;

			let settingsField = new SettingsField({
				fieldView: allowPostFeedField,
			});
			const settingsRow = new SettingsRow({
				parent: settingsSection,
				child: settingsField,
			});

			let userSelectorField = new UserSelector({
				inputName: 'livefeed_toall_rights[]',
				label: Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_SELECT_USER_PUBLIC_MESS'),
				values: Object.values(this.getValue('arToAllRights')),
				enableDepartments: true,
				encodeValue: (value) => {
					if (!Type.isNil(value.id))
					{
						return value.id === 'all-users' ? 'AU' : value.type + value.id.toString().split(':')[0];
					}

					return null;
				},
				decodeValue: (value: string) => {
					if (value === 'UA')
					{
						return {
							type: 'AU',
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
			settingsField = new SettingsField({
				fieldView: userSelectorField,
			});

			const userSelectorRow = new Row({
				isHidden: !allowPostFeedField.isChecked(),
				className: 'ui-section__subrow',
			});

			new SettingsRow({
				row: userSelectorRow,
				parent: settingsRow,
				child: settingsField,
			});

			EventEmitter.subscribe(
				allowPostFeedField.switcher,
				'toggled',
				() => {
					if (allowPostFeedField.isChecked())
					{
						userSelectorRow.show();
					}
					else
					{
						userSelectorRow.hide();
					}
				},
			);
		}

		if (this.hasValue('default_livefeed_toall'))
		{
			let allowPostToAllField = new Checker(this.getValue('default_livefeed_toall'));

			CommunicationPage.addToSectionHelper(allowPostToAllField, settingsSection);
		}

		if (this.hasValue('ratingTextLikeY'))
		{
			const likeBtnNameField = new TextInputInline({
				inputName: this.getValue('ratingTextLikeY')?.name,
				label: this.getValue('ratingTextLikeY').label ?? Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_LIKE_INPUT'),
				hintTitle: Loc.getMessage('INTRANET_SETTINGS_FIELD_HINT_TITLE_LIKE'),
				value: this.getValue('ratingTextLikeY')?.current,
				valueColor: this.hasValue('ratingTextLikeY'),
				hintDesc: Loc.getMessage('INTRANET_SETTINGS_FIELD_HINT_DESC_LIKE'),
			});
			CommunicationPage.addToSectionHelper(likeBtnNameField, settingsSection);
		}

		return settingsSection;
	}

	#buildChatSection(): Section
	{
		if (!this.hasValue('sectionChats'))
		{
			return ;
		}

		let chatSection = new Section(this.getValue('sectionChats'));

		let settingsSection = new SettingsSection({
			section: chatSection,
			parent: this,
		});

		if (this.hasValue('general_chat_can_post'))
		{
			let canPostGeneralChatField = new Checker(this.getValue('allow_post_general_chat'));

			let settingsField = new SettingsField({
				fieldView: canPostGeneralChatField,
			});
			let settingsRow = new SettingsRow({
				parent: settingsSection,
				child: settingsField,
			});

			let canPostGeneralChatListField = new Selector(this.getValue('general_chat_can_post'));
			settingsField = new SettingsField({
				fieldView: canPostGeneralChatListField,
			});

			let canPostGeneralChatListRow = new Row({
				isHidden: !canPostGeneralChatField.isChecked(),
				className: 'ui-section__subrow --no-border',
			});

			CommunicationPage.addToSectionHelper(canPostGeneralChatListField, settingsRow, canPostGeneralChatListRow);

			let managerSelectorField = new UserSelector({
				inputName: 'imchat_toall_rights[]',
				label: Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_SELECT_USER_PUBLIC_MESS'),
				enableAll: false,
				values: Object.values(this.getValue('generalChatManagersList') ?? []),
				encodeValue: (value) => {
					if (!Type.isNil(value.id))
					{
						return value.id === 'all-users' ? 'AU' : 'U' + value.id;
					}

					return null;
				},
				decodeValue: (value) => {
					if (value === 'UA')
					{
						return {
							type: 'AU',
							id: '',
						}
					}

					const arr = value.match(/^(U)(\d+)/);

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

			let managerSelectorRow = new Row({
				content: managerSelectorField.render(),
				isHidden: this.getValue('general_chat_can_post').current !== 'MANAGER',
				className: 'ui-section__subrow --no-border',
			});

			CommunicationPage.addToSectionHelper(managerSelectorField, settingsRow, managerSelectorRow);

			const separatorRow = new SeparatorRow({
				isHidden: this.getValue('general_chat_can_post').current !== 'MANAGER',
			});
			new SettingsRow({
				row: separatorRow,
				parent: settingsRow,
			});

			EventEmitter.subscribe(
				canPostGeneralChatField.switcher,
				'toggled',
				() => {
					if (canPostGeneralChatField.isChecked())
					{
						canPostGeneralChatListRow.show();
						if (canPostGeneralChatListField.getInputNode().value === 'MANAGER')
						{
							managerSelectorRow.show();
						}
						separatorRow.show();
					}
					else
					{
						canPostGeneralChatListRow.hide();
						managerSelectorRow.hide();
						separatorRow.hide();
					}
				},
			);

			canPostGeneralChatListField.getInputNode()
				.addEventListener('change', (event) => {
					if (event.target.value === 'MANAGER')
					{
						managerSelectorRow.show();
					}
					else
					{
						managerSelectorRow.hide();
					}
				});
		}

		if (this.hasValue('general_chat_message_leave'))
		{
			let leaveMessageField = new Checker(this.getValue('general_chat_message_leave'));
			CommunicationPage.addToSectionHelper(leaveMessageField, settingsSection);
		}

		if (this.hasValue('general_chat_message_admin_rights'))
		{
			let adminMessageField = new Checker(this.getValue('general_chat_message_admin_rights'));
			CommunicationPage.addToSectionHelper(adminMessageField, settingsSection);
		}

		if (this.hasValue('url_preview_enable'))
		{
			let allowUrlPreviewField = new Checker(this.getValue('url_preview_enable'));
			CommunicationPage.addToSectionHelper(allowUrlPreviewField, settingsSection);
		}

		if (this.hasValue('create_overdue_chats'))
		{
			let overdueChatsField = new Checker(this.getValue('create_overdue_chats'));
			CommunicationPage.addToSectionHelper(overdueChatsField, settingsSection);
		}

		return settingsSection;
	}

	#buildChannelSection(): Section
	{
		if (!this.hasValue('sectionChannels'))
		{
			return ;
		}

		let chatSection = new Section(this.getValue('sectionChannels'));

		let settingsSection = new SettingsSection({
			section: chatSection,
			parent: this,
		});

		if (this.hasValue('general_channel_can_post'))
		{
			let canPostGeneralChannelField = new Checker(this.getValue('allow_post_general_channel'));

			let settingsField = new SettingsField({
				fieldView: canPostGeneralChannelField,
			});
			let settingsRow = new SettingsRow({
				parent: settingsSection,
				child: settingsField,
			});

			let canPostGeneralChannelListField = new Selector(this.getValue('general_channel_can_post'));
			settingsField = new SettingsField({
				fieldView: canPostGeneralChannelListField,
			});

			let canPostGeneralChannelListRow = new Row({
				isHidden: !canPostGeneralChannelField.isChecked(),
				className: 'ui-section__subrow --no-border',
			});

			CommunicationPage.addToSectionHelper(canPostGeneralChannelListField, settingsRow, canPostGeneralChannelListRow);

			let managerSelectorField = new UserSelector({
				inputName: 'imchannel_toall_rights[]',
				label: Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_SELECT_USER_PUBLIC_MESS_CHANNEL') ?? '',
				enableAll: false,
				values: Object.values(this.getValue('generalChannelManagersList') ?? []),
				encodeValue: (value) => {
					if (!Type.isNil(value.id))
					{
						return value.id === 'all-users' ? 'AU' : 'U' + value.id;
					}

					return null;
				},
				decodeValue: (value) => {
					if (value === 'UA')
					{
						return {
							type: 'AU',
							id: '',
						}
					}

					const arr = value.match(/^(U)(\d+)/);

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

			let managerSelectorRow = new Row({
				content: managerSelectorField.render(),
				isHidden: this.getValue('general_channel_can_post').current !== 'MANAGER',
				className: 'ui-section__subrow --no-border',
			});

			CommunicationPage.addToSectionHelper(managerSelectorField, settingsRow, managerSelectorRow);

			const separatorRow = new SeparatorRow({
				isHidden: this.getValue('general_channel_can_post').current !== 'MANAGER',
			});
			new SettingsRow({
				row: separatorRow,
				parent: settingsRow,
			});

			EventEmitter.subscribe(
				canPostGeneralChannelField.switcher,
				'toggled',
				() => {
					if (canPostGeneralChannelField.isChecked())
					{
						canPostGeneralChannelListRow.show();
						if (canPostGeneralChannelListField.getInputNode().value === 'MANAGER')
						{
							managerSelectorRow.show();
						}
						separatorRow.show();
					}
					else
					{
						canPostGeneralChannelListRow.hide();
						managerSelectorRow.hide();
						separatorRow.hide();
					}
				},
			);

			canPostGeneralChannelListField.getInputNode()
				.addEventListener('change', (event) => {
					if (event.target.value === 'MANAGER')
					{
						managerSelectorRow.show();
					}
					else
					{
						managerSelectorRow.hide();
					}
				});
		}

		return settingsSection;
	}

	#buildDiskSection(): SettingsSection
	{
		if (!this.hasValue('sectionDisk'))
		{
			return ;
		}

		let diskSection = new Section(this.getValue('sectionDisk'));

		let settingsSection = new SettingsSection({
			section: diskSection,
			parent: this,
		});

		if (this.hasValue('DISK_VIEWER_SERVICE'))
		{
			let fileViewerField = new Selector(this.getValue('DISK_VIEWER_SERVICE'));
			CommunicationPage.addToSectionHelper(fileViewerField, settingsSection);
		}

		if (this.hasValue('DISK_LIMIT_PER_FILE'))
		{
			const messageNode = Tag.render`<span>${Loc.getMessage(
				'INTRANET_SETTINGS_FIELD_HELP_MESSAGE'
			)}</span>`;
			let fileLimitField = new Selector({
				label: this.getValue('DISK_LIMIT_PER_FILE').label ?? Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_MAX_FILE_LIMIT'),
				hintTitle: this.getValue('DISK_LIMIT_PER_FILE').hintTitle,
				name: this.getValue('DISK_LIMIT_PER_FILE').name,
				items: this.getValue('DISK_LIMIT_PER_FILE').values,
				hints: this.getValue('DISK_LIMIT_PER_FILE').hints,
				current: this.getValue('DISK_LIMIT_PER_FILE').current,
				isEnable: this.getValue('DISK_LIMIT_PER_FILE').isEnable,
				bannerCode: 'limit_max_entries_in_document_history',
				helpDesk: 'redirect=detail&code=18869612',
				helpMessageProvider: this.helpMessageProviderFactory(messageNode),
			});

			let fileLimitRow = new Row({
				separator: 'bottom',
				className: '--block',
			});
			if (!this.getValue('DISK_LIMIT_PER_FILE').isEnable)
			{
				Event.bind(
					fileLimitField.getInputNode(),
					'click',
					() =>
					{
						this.getAnalytic()?.addEventOpenHint(this.getValue('DISK_LIMIT_PER_FILE').name);
					}
				);
				Event.bind(
					messageNode.querySelector('a'),
					'click',
					() => this.getAnalytic()?.addEventOpenTariffSelector(this.getValue('DISK_LIMIT_PER_FILE').name)
				);
			}

			CommunicationPage.addToSectionHelper(fileLimitField, settingsSection, fileLimitRow);
		}

		new SettingsRow({
			row: new SeparatorRow(),
			parent: settingsSection,
		});

		if (this.hasValue('disk_allow_edit_object_in_uf'))
		{
			let allowEditDocField = new Checker(this.getValue('disk_allow_edit_object_in_uf'));
			let allowEditDocRow = new Row({
				separator: 'top',
				className: '--block',
			});
			CommunicationPage.addToSectionHelper(allowEditDocField, settingsSection, allowEditDocRow);
		}

		if (this.hasValue('disk_allow_autoconnect_shared_objects'))
		{
			let connectDiskField = new Checker(this.getValue('disk_allow_autoconnect_shared_objects'));
			CommunicationPage.addToSectionHelper(connectDiskField, settingsSection);
		}

		if (this.hasValue('disk_allow_use_external_link'))
		{
			const messageNode = Tag.render`<span>${Loc.getMessage(
				'INTRANET_SETTINGS_FIELD_HELP_MESSAGE'
			)}</span>`;
			let publicLinkField = new Checker({
				inputName: this.getValue('disk_allow_use_external_link').inputName,
				title: this.getValue('disk_allow_use_external_link').label ?? Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_ALLOW_PUBLIC_LINK'),
				hintOn: this.getValue('disk_allow_use_external_link').hintOn,
				checked: this.getValue('disk_allow_use_external_link').checked,
				isEnable: this.getValue('disk_allow_use_external_link').isEnable,
				bannerCode: 'limit_admin_share_link',
				helpDesk: this.getValue('disk_allow_use_external_link').helpDesk,
				helpMessageProvider: this.helpMessageProviderFactory(messageNode),
			});
			if (!this.getValue('disk_allow_use_external_link').isEnable)
			{
				EventEmitter.subscribe(
					publicLinkField.switcher,
					'toggled',
					() =>
					{
						this.getAnalytic()?.addEventOpenHint('disk_allow_use_external_link');
					}
				);
				Event.bind(
					messageNode.querySelector('a'),
					'click',
					() => this.getAnalytic()?.addEventOpenTariffSelector('enable_pub_link')
				);
			}

			CommunicationPage.addToSectionHelper(publicLinkField, settingsSection);
		}

		if (this.hasValue('disk_object_lock_enabled'))
		{
			const messageNode = Tag.render`<span>${Loc.getMessage(
				'INTRANET_SETTINGS_FIELD_HELP_MESSAGE'
			)}</span>`;
			let enableBlockDocField = new Checker({
				inputName: this.getValue('disk_object_lock_enabled').inputName,
				title: this.getValue('disk_object_lock_enabled').label ?? Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_ALLOW_BLOCK_DOC'),
				hintOn: this.getValue('disk_object_lock_enabled').hintOn,
				checked: this.getValue('disk_object_lock_enabled').checked,
				isEnable: this.getValue('disk_object_lock_enabled').isEnable,
				bannerCode: 'limit_document_lock',
				helpMessageProvider: this.helpMessageProviderFactory(messageNode),
				helpDesk: this.getValue('disk_object_lock_enabled').helpDesk,
			});
			if (!this.getValue('disk_object_lock_enabled').isEnable)
			{
				EventEmitter.subscribe(
					enableBlockDocField.switcher,
					'toggled',
					() =>
					{
						this.getAnalytic()?.addEventOpenHint('disk_object_lock_enabled');
					}
				);
				Event.bind(
					messageNode.querySelector('a'),
					'click',
					() => this.getAnalytic()?.addEventOpenTariffSelector('disk_object_lock_enabled')
				);
			}

			CommunicationPage.addToSectionHelper(enableBlockDocField, settingsSection);
		}

		if (this.hasValue('disk_allow_use_extended_fulltext'))
		{
			const messageNode = Tag.render`<span>${Loc.getMessage(
				'INTRANET_SETTINGS_FIELD_HELP_MESSAGE_ENT',
				{ '#TARIFF#': 'ent250'},
			)}</span>`;
			let enableFindField = new Checker({
				inputName: this.getValue('disk_allow_use_extended_fulltext').inputName,
				title: this.getValue('disk_allow_use_extended_fulltext').label ?? Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_ALLOW_SEARCH_DOC'),
				hintOn: this.getValue('disk_allow_use_extended_fulltext').hintOn,
				checked: this.getValue('disk_allow_use_extended_fulltext').checked,
				isEnable: this.getValue('disk_allow_use_extended_fulltext').isEnable,
				bannerCode: 'limit_in_text_search',
				helpDesk: this.getValue('disk_allow_use_extended_fulltext').helpDesk,
				helpMessageProvider: this.helpMessageProviderFactory(messageNode),
			});
			if (!this.getValue('disk_allow_use_extended_fulltext').isEnable)
			{
				EventEmitter.subscribe(
					enableFindField.switcher,
					'toggled',
					() =>
					{
						this.getAnalytic()?.addEventOpenHint('disk_allow_use_extended_fulltext');
					}
				);
				Event.bind(
					messageNode.querySelector('a'),
					'click',
					() => this.getAnalytic()?.addEventOpenTariffSelector('disk_allow_use_extended_fulltext')
				);
			}

			CommunicationPage.addToSectionHelper(enableFindField, settingsSection);
		}

		return settingsSection;
	}
}
