import {TextInputInline, Checker, Selector, UserSelector} from 'ui.form-elements.view';
import {Loc, Event, Tag, Type} from 'main.core';
import { EventEmitter } from 'main.core.events';
import 'ui.icon-set.main';
import 'ui.icon-set.actions';
import { Section, Row } from 'ui.section';
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

		let diskSection = this.#buildDiskSection();
		diskSection.renderTo(contentNode);
	}

	#buildNewsFeedSection(): SettingsSection
	{
		let newsFeedSection = new Section({
			title: Loc.getMessage('INTRANET_SETTINGS_SECTION_TITLE_NEWS_FEED'),
			titleIconClasses: 'ui-icon-set --feed-bold',
		});

		let settingsSection = new SettingsSection({
			section: newsFeedSection,
			parent: this,
		});

		if (this.hasValue('allow_livefeed_toall'))
		{
			let allowPostFeedField = new Checker({
				inputName: 'allow_livefeed_toall',
				title: Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_POST_FEED'),
				hintOn: Loc.getMessage('INTRANET_SETTINGS_FIELD_HINT_POST_FEED_ON'),
				checked: this.getValue('allow_livefeed_toall') === 'Y',
				hideSeparator: true,
			});

			CommunicationPage.addToSectionHelper(allowPostFeedField, settingsSection);

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
			let userSelectorRow = new Row({
				content: userSelectorField.render(),
				isHidden: !allowPostFeedField.isChecked(),
				className: 'ui-section__subrow',
				separator: 'bottom',
			});
			CommunicationPage.addToSectionHelper(userSelectorField, settingsSection, userSelectorRow);

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
			let allowPostToAllField = new Checker({
				inputName: 'default_livefeed_toall',
				title: Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_PUBLISH_TO_ALL_DEFAULT'),
				hintOn: Loc.getMessage('INTRANET_SETTINGS_FIELD_HINT_PUBLISH_TO_ALL_DEFAULT_ON'),
				checked: this.getValue('default_livefeed_toall') === 'Y',
				// helpDesk: '1',
			});

			CommunicationPage.addToSectionHelper(allowPostToAllField, settingsSection);
		}

		if (this.hasValue('ratingTextLikeY'))
		{
			const likeBtnNameField = new TextInputInline({
				inputName: this.getValue('ratingTextLikeY')?.name,
				label: Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_LIKE_INPUT'),
				hintTitle: Loc.getMessage('INTRANET_SETTINGS_FIELD_HINT_TITLE_LIKE'),
				value: this.getValue('ratingTextLikeY')?.value,
				valueColor: this.hasValue('ratingTextLikeY'),
				hintDesc: Loc.getMessage('INTRANET_SETTINGS_FIELD_HINT_DESC_LIKE'),
			});
			CommunicationPage.addToSectionHelper(likeBtnNameField, settingsSection);
		}

		return settingsSection;
	}

	#buildChatSection(): Section
	{
		let chatSection = new Section({
			title: Loc.getMessage('INTRANET_SETTINGS_SECTION_TITLE_CHATS'),
			titleIconClasses: 'ui-icon-set --chats-1',
			isOpen: false
		});

		let settingsSection = new SettingsSection({
			section: chatSection,
			parent: this,
		});

		if (this.hasValue('general_chat_can_post'))
		{
			let canPostGeneralChatField = new Checker({
				inputName: 'allow_post_general_chat',
				title: Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_ALLOW_POST_GEN_CHAT'),
				hintOn: Loc.getMessage('INTRANET_SETTINGS_FIELD_HINT_ALLOW_POST_GEN_CHAT_ON'),
				checked: this.getValue('allow_post_general_chat') === 'Y',
				helpDesk: 'redirect=detail&code=18213254',
			});

			let settingsField = new SettingsField({
				fieldView: canPostGeneralChatField,
			});
			let settingsRow = new SettingsRow({
				parent: settingsSection,
				child: settingsField,
			});

			let canPostGeneralChatListField = new Selector({
				label: Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_ALLOW_POST_GEN_CHAT_LIST'),
				name: this.getValue('general_chat_can_post').name,
				items: this.getValue('general_chat_can_post').values,
				current: this.getValue('general_chat_can_post').current,
			});
			settingsField = new SettingsField({
				fieldView: canPostGeneralChatListField,
			});

			let canPostGeneralChatListRow = new Row({
				isHidden: !canPostGeneralChatField.isChecked(),
				className: 'ui-section__subrow',
			});
			let canPostGeneralChatListSettingsRow = new SettingsRow({
				row: canPostGeneralChatListRow,
				parent: settingsRow,
				child: settingsField,
			});

			let subRowForGeneralChatList = new Row({
				content: canPostGeneralChatListField.render(),
			});
			new SettingsRow({
				row: subRowForGeneralChatList,
				parent: canPostGeneralChatListSettingsRow,
				child: settingsField,
			});

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
			settingsField = new SettingsField({
				fieldView: managerSelectorField,
			});
			let managerSelectorRow = new Row({
				content: managerSelectorField.render(),
				isHidden: this.getValue('general_chat_can_post').current !== 'MANAGER',
			});
			new SettingsRow({
				row: managerSelectorRow,
				parent: canPostGeneralChatListSettingsRow,
				child: settingsField,
			});

			EventEmitter.subscribe(
				canPostGeneralChatField.switcher,
				'toggled',
				() => {
					if (canPostGeneralChatField.isChecked())
					{
						canPostGeneralChatListRow.show();
					}
					else
					{
						canPostGeneralChatListRow.hide();
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
			let leaveMessageField = new Checker({
				inputName: 'general_chat_message_leave',
				title: Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_ALLOW_LEAVE_MESSAGE'),
				checked: this.getValue('general_chat_message_leave') === 'Y',
			});
			CommunicationPage.addToSectionHelper(leaveMessageField, settingsSection);
		}

		if (this.hasValue('general_chat_message_admin_rights'))
		{
			let adminMessageField = new Checker({
				inputName: 'general_chat_message_admin_rights',
				title: Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_ALLOW_ADMIN_MESSAGE'),
				checked: this.getValue('general_chat_message_admin_rights') === 'Y',
			});
			CommunicationPage.addToSectionHelper(adminMessageField, settingsSection);
		}

		if (this.hasValue('url_preview_enable'))
		{
			let allowUrlPreviewField = new Checker({
				inputName: 'url_preview_enable',
				title: Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_ALLOW_URL_PREVIEW'),
				hintOn: Loc.getMessage('INTRANET_SETTINGS_FIELD_HINT_URL_PREVIEW_ON'),
				checked: this.getValue('url_preview_enable') === 'Y',
			});
			CommunicationPage.addToSectionHelper(allowUrlPreviewField, settingsSection);
		}

		if (this.hasValue('create_overdue_chats'))
		{
			let overdueChatsField = new Checker({
				inputName: 'create_overdue_chats',
				title: Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_CREATE_OVERDUE_CHATS'),
				hintOn: Loc.getMessage('INTRANET_SETTINGS_FIELD_HINT_OVERDUE_CHATS_ON'),
				checked: this.getValue('create_overdue_chats') === 'Y',
				helpDesk: 'redirect=detail&code=18213270',
			});
			CommunicationPage.addToSectionHelper(overdueChatsField, settingsSection);
		}

		return settingsSection;
	}

	#buildDiskSection(): SettingsSection
	{
		let diskSection = new Section({
			title: Loc.getMessage('INTRANET_SETTINGS_SECTION_TITLE_DISK'),
			titleIconClasses: 'ui-icon-set --disk',
			isOpen: false,
		});

		let settingsSection = new SettingsSection({
			section: diskSection,
			parent: this,
		});

		if (this.hasValue('DISK_VIEWER_SERVICE'))
		{
			let fileViewerField = new Selector({
				label: Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_SELECT_FILE_VIEWER'),
				name: this.getValue('DISK_VIEWER_SERVICE').name,
				items: this.getValue('DISK_VIEWER_SERVICE').values,
				current: this.getValue('DISK_VIEWER_SERVICE').current,
			});
			CommunicationPage.addToSectionHelper(fileViewerField, settingsSection);
		}

		if (this.hasValue('DISK_LIMIT_PER_FILE'))
		{
			const messageNode = Tag.render`<span>${Loc.getMessage(
				'INTRANET_SETTINGS_FIELD_HELP_MESSAGE'
			)}</span>`;
			let fileLimitField = new Selector({
				label: Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_MAX_FILE_LIMIT'),
				hintTitle: this.getValue('DISK_LIMIT_PER_FILE').hintTitle,
				name: this.getValue('DISK_LIMIT_PER_FILE').name,
				items: this.getValue('DISK_LIMIT_PER_FILE').values,
				hints: this.getValue('DISK_LIMIT_PER_FILE').hints,
				current: this.getValue('DISK_LIMIT_PER_FILE').current,
				isEnable: this.getValue('DISK_LIMIT_PER_FILE').is_enable,
				bannerCode: 'limit_max_entries_in_document_history',
				helpDesk: 'redirect=detail&code=18869612',
				helpMessageProvider: this.helpMessageProviderFactory(messageNode),
			});
			let fileLimitRow = new Row({
				separator: 'bottom',
				className: '--block',
			});
			if (!this.getValue('DISK_LIMIT_PER_FILE').is_enable)
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

		if (this.hasValue('disk_allow_edit_object_in_uf'))
		{
			let allowEditDocField = new Checker({
				inputName: 'disk_allow_edit_object_in_uf',
				title: Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_ALLOW_EDIT_DOC'),
				hintOn: Loc.getMessage('INTRANET_SETTINGS_FIELD_HINT_ALLOW_EDIT_DOC_ON'),
				checked: this.getValue('disk_allow_edit_object_in_uf') === 'Y',
			});
			CommunicationPage.addToSectionHelper(allowEditDocField, settingsSection);
		}

		if (this.hasValue('disk_allow_autoconnect_shared_objects'))
		{
			let connectDiskField = new Checker({
				inputName: 'disk_allow_autoconnect_shared_objects',
				title: Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_ALLOW_AUTO_CONNECT_DISK'),
				hintOn: Loc.getMessage('INTRANET_SETTINGS_FIELD_HINT_ALLOW_AUTO_CONNECT_DISK_ON'),
				checked: this.getValue('disk_allow_autoconnect_shared_objects') === 'Y',
				helpDesk: 'redirect=detail&code=18213280',
			});
			CommunicationPage.addToSectionHelper(connectDiskField, settingsSection);
		}

		if (this.hasValue('disk_allow_use_external_link'))
		{
			const messageNode = Tag.render`<span>${Loc.getMessage(
				'INTRANET_SETTINGS_FIELD_HELP_MESSAGE'
			)}</span>`;
			let publicLinkField = new Checker({
				inputName: 'disk_allow_use_external_link',
				title: Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_ALLOW_PUBLIC_LINK'),
				hintOn: Loc.getMessage('INTRANET_SETTINGS_FIELD_HINT_ALLOW_PUBLIC_LINK_ON'),
				checked: this.getValue('disk_allow_use_external_link').value === 'Y',
				isEnable: this.getValue('disk_allow_use_external_link').is_enable,
				bannerCode: 'limit_admin_share_link',
				helpDesk: 'redirect=detail&code=5390599',
				helpMessageProvider: this.helpMessageProviderFactory(messageNode),
			});
			if (!this.getValue('disk_allow_use_external_link').is_enable)
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
				inputName: 'disk_object_lock_enabled',
				title: Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_ALLOW_BLOCK_DOC'),
				hintOn: Loc.getMessage('INTRANET_SETTINGS_FIELD_HINT_ALLOW_BLOCK_DOC_ON'),
				checked: this.getValue('disk_object_lock_enabled').value === 'Y',
				isEnable: this.getValue('disk_object_lock_enabled').is_enable,
				bannerCode: 'limit_document_lock',
				helpMessageProvider: this.helpMessageProviderFactory(messageNode),
				helpDesk: 'redirect=detail&code=2301293',
			});
			if (!this.getValue('disk_object_lock_enabled').is_enable)
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
				inputName: 'disk_allow_use_extended_fulltext',
				title: Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_ALLOW_SEARCH_DOC'),
				hintOn: Loc.getMessage('INTRANET_SETTINGS_FIELD_HINT_ALLOW_SEARCH_DOC_ON'),
				checked: this.getValue('disk_allow_use_extended_fulltext').value === 'Y',
				isEnable: this.getValue('disk_allow_use_extended_fulltext').is_enable,
				bannerCode: 'limit_in_text_search',
				helpDesk: 'redirect=detail&code=18213348',
				helpMessageProvider: this.helpMessageProviderFactory(messageNode),
			});
			if (!this.getValue('disk_allow_use_extended_fulltext').is_enable)
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
