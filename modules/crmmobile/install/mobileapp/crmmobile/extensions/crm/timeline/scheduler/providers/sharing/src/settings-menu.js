/**
 * @module crm/timeline/scheduler/providers/sharing/settings-menu
 */
jn.define('crm/timeline/scheduler/providers/sharing/settings-menu', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Type } = require('type');
	const { Type: CrmType } = require('crm/type');
	const { ContextMenu } = require('layout/ui/context-menu');
	const { CommunicationSelector } = require('crm/communication/communication-selector');
	const { SendersSelector } = require('crm/timeline/ui/senders-selector');

	const SENDER_TYPE_PHONE = 'PHONE';
	const SENDER_TYPE_EMAIL = 'EMAIL';

	class SettingsMenu
	{
		constructor(props)
		{
			this.props = props;
			this.parentLayout = props.layout;
			this.sendersSelector = null;

			this.fromId = this.props.currentSender.fromId;
			this.currentSender = this.props.currentSender;

			this.toId = this.props.currentCommunication.id;
			this.toValue = this.props.currentCommunication.value;
			this.toName = this.props.currentCommunication.name;

			this.onContactSelect = this.onContactSelect.bind(this);
			this.onChangeSender = this.onChangeSender.bind(this);
			this.onDisabledSenderClick = this.onDisabledSenderClick.bind(this);
			this.onChangeSenderFrom = this.onChangeSenderFrom.bind(this);

			this.menu = new ContextMenu(this.getMenuConfig());
		}

		getMenuConfig()
		{
			return {
				actions: this.getItems(),
				params: {
					title: Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_SHARING_SETTINGS'),
					showCancelButton: false,
				},
			};
		}

		get layout()
		{
			return this.menu.layoutWidget;
		}

		get entity()
		{
			return this.props.entity;
		}

		getItems()
		{
			const items = [
				{
					id: 'sharing_receiver',
					title: Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_SHARING_RECEIVER'),
					subtitle: `${this.toName} (${this.toValue})`,
					onClickCallback: () => {
						this.openContactSelector();

						return Promise.resolve({ closeMenu: false });
					},
				},
			];

			if (this.props.areCommunicationChannelsAvailable)
			{
				items.push({
					id: 'sharing_sender',
					title: Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_SHARING_SENDER'),
					subtitle: this.getCommunicationChannelSubTitle(this.currentSender),
					onClickCallback: () => {
						this.openSenderSelector();

						return Promise.resolve({ closeMenu: false });
					},
				});
			}

			return items;
		}

		getCommunicationChannelSubTitle(sender)
		{
			const fromList = BX.prop.getArray(sender, 'fromList', []);
			const from = fromList.find((item) => item.id === this.fromId);

			return Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_SHARING_CHANNEL_FROM_' + sender.typeId, {
				'#CHANNEL#': sender.shortName,
				'#SENDER#': from.name,
			});
		}

		show()
		{
			void this.menu.show(this.parentLayout);
		}

		close()
		{
			this.menu.close();
		}

		update({ sender, fromId, communication })
		{
			let isNeedToRerender = false;

			if (sender && Type.isString(sender.shortName))
			{
				isNeedToRerender = true;
				this.currentSender = sender;
			}

			if (Type.isString(fromId) || Type.isNumber(fromId))
			{
				isNeedToRerender = true;
				this.fromId = fromId;
			}

			if (Type.isObject(communication) && Type.isString(communication.name))
			{
				isNeedToRerender = true;
				this.toName = communication.name;
				this.toId = communication.id;
				this.toValue = communication.value;
			}

			if (isNeedToRerender)
			{
				this.menu.rerender(this.getMenuConfig());
			}
		}

		openContactSelector()
		{
			const layout = this.layout;
			const ownerInfo = {
				ownerId: this.entity.id,
				ownerTypeName: CrmType.resolveNameById(this.entity.typeId),
			};

			CommunicationSelector.show({
				layout,
				communications: this.getCommunications(),
				ownerInfo,
				typeId: this.entity.typeId,
				selectedId: this.toId,
				onSelectCallback: this.onContactSelect,
			});
		}

		getCommunications()
		{
			return this.currentSender.contacts.map((contact) => {
				contact.caption = contact.name;

				const communication = {
					id: contact.id,
					type: contact.valueType,
					typeLabel: contact.valueTypeLabel,
					value: contact.value,
					valueFormatted: contact.value,
				};

				if (this.currentSender.typeId === SENDER_TYPE_EMAIL)
				{
					contact.emails = [communication];
				}

				if (this.currentSender.typeId === SENDER_TYPE_PHONE)
				{
					contact.phones = [communication];
				}

				return contact;
			});
		}

		onContactSelect(contact)
		{
			const communication = {
				id: contact.phone.id || contact.email.id,
				value: contact.phone.value || contact.email.value,
				name: contact.title,
			};

			this.update({ communication });
			this.props.onContactSelect({ communication });
		}

		openSenderSelector()
		{
			if (!this.sendersSelector)
			{
				this.sendersSelector = this.createSendersSelector(this.props.senders);
			}

			this.sendersSelector.show(this.layout);
		}

		onChangeSender({ sender, fromId })
		{
			const communication = sender.contacts.find((contact) => contact.id === this.toId) ?? sender.contacts[0];

			this.update({ sender, fromId, communication });
			this.props.onChangeSender({ sender, fromId, communication });
		}

		async onDisabledSenderClick({ sender: disabledSender, layoutWidget })
		{
			if (disabledSender.typeId === SENDER_TYPE_EMAIL)
			{
				const senders = await this.props.connectMailbox(layoutWidget);

				const sender = senders.find((it) => it.id === disabledSender.id);
				const fromId = sender.fromList[0].id;

				this.onChangeSender({ sender, fromId });

				this.sendersSelector.close();
				this.sendersSelector = this.createSendersSelector(senders);
			}
		}

		onChangeSenderFrom({ fromId })
		{
			this.update({ fromId });
			this.props.onChangeSenderFrom({ fromId });
		}

		createSendersSelector(senders)
		{
			return new SendersSelector({
				senders,
				contactCenterUrl: this.props.contactCenterUrl,
				currentFromId: this.fromId,
				currentSender: this.currentSender,
				onChangeSenderCallback: this.onChangeSender,
				onDisabledSenderClickCallback: this.onDisabledSenderClick,
				onChangeFromCallback: this.onChangeSenderFrom,
				smsAndMailSenders: true,
			});
		}
	}

	module.exports = { SettingsMenu };
});
