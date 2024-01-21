/**
 * @module crm/timeline/scheduler/providers/sharing/settings-menu
 */
jn.define('crm/timeline/scheduler/providers/sharing/settings-menu', (require, exports, module) => {

	const { Loc } = require('loc');
	const { Type } = require('type');
	const { Type: CrmType } = require('crm/type');
	const { CommunicationSelector } = require('crm/communication/communication-selector');
	const { SendersSelector } = require('crm/timeline/ui/senders-selector');

	class SettingsMenu
	{
		constructor(props)
		{
			this.props = props;
			this.parentLayout = props.layout;
			this.sendersSelector = null;

			this.fromPhoneId = this.props.currentSender.fromPhoneId;
			this.currentSender = this.props.currentSender;
			this.toPhoneId = this.props.currentCommunication.toPhoneId;
			this.communicationName = this.props.currentCommunication.caption;
			this.toPhoneValue = this.props.currentCommunication.toPhoneValue;

			this.onContactPhoneSelect = this.onContactPhoneSelect.bind(this);
			this.onChangeSender = this.onChangeSender.bind(this);
			this.onChangeSenderPhone = this.onChangeSenderPhone.bind(this);

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
					subtitle: this.communicationName + ' (' + this.toPhoneValue + ')',
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
			let fromPhone = '';
			const fromList = BX.prop.getArray(sender, 'fromList', []);
			fromList.map((item) => {
				if (item.id && item.name && item.id === this.fromPhoneId)
				{
					fromPhone = item.name;
				}
			});

			const translatedPhrase = Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_SHARING_FROM_NUMBER');

			return `${sender.shortName} ${translatedPhrase} ${fromPhone}`;
		}

		show()
		{
			this.menu.show(this.parentLayout);
		}

		update({ sender, phoneId, communication })
		{
			let isNeedToRerender = false;

			if (sender && Type.isString(sender.shortName))
			{
				isNeedToRerender = true;
				this.currentSender = sender;
			}

			if (Type.isString(phoneId) || Type.isNumber(phoneId))
			{
				isNeedToRerender = true;
				this.fromPhoneId = phoneId;
			}

			if (Type.isObject(communication) && Type.isString(communication.title))
			{
				isNeedToRerender = true;
				this.communicationName = communication.title;
				this.toPhoneId = communication.phone.id;
				this.toPhoneValue = communication.phone.value;
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
			const { communications } = this.props;

			CommunicationSelector.show({
				layout,
				communications,
				ownerInfo,
				typeId: this.entity.typeId,
				selectedPhoneId: this.toPhoneId,
				onPhoneSelectCallback: this.onContactPhoneSelect,
			});
		}

		onContactPhoneSelect(communication)
		{
			this.update({ communication });
			this.props.onContactPhoneSelect({ communication });
		}

		openSenderSelector()
		{
			if (!this.sendersSelector)
			{
				const { senders, contactCenterUrl } = this.props;

				this.sendersSelector = new SendersSelector({
					senders,
					contactCenterUrl,
					currentPhoneId: this.fromPhoneId,
					currentSender: this.currentSender,
					onChangeSenderCallback: this.onChangeSender,
					onChangePhoneCallback: this.onChangeSenderPhone,
				});
			}

			this.sendersSelector.show(this.layout);
		}

		onChangeSender({ sender, phoneId })
		{
			this.update({ sender, phoneId });
			this.props.onChangeSender({ sender, phoneId });
		}

		onChangeSenderPhone({ phoneId })
		{
			this.update({ phoneId });
			this.props.onChangeSenderPhone({ phoneId });
		}
	}

	module.exports = { SettingsMenu };
});