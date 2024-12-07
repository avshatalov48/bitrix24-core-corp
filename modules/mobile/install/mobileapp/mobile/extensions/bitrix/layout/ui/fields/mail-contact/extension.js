/**
 * @module layout/ui/fields/mail-contact
 */
jn.define('layout/ui/fields/mail-contact', (require, exports, module) => {
	const { EntitySelectorFieldClass } = require('layout/ui/fields/entity-selector');
	const { AnalyticsEvent } = require('analytics');
	const { Loc } = require('loc');
	const { clone, isEqual } = require('utils/object');
	const { Haptics } = require('haptics');
	const { ProfileView } = require('user/profile');
	const { ContextMenu } = require('layout/ui/context-menu');

	const CONTACT_TYPE_ID = 3;
	const COMPANY_TYPE_ID = 4;

	let deviceWidth = device.screen.width;
	if (!deviceWidth)
	{
		deviceWidth = 360;
	}

	/**
	 * @class MailContactField
	 */
	class MailContactField extends EntitySelectorFieldClass
	{
		constructor(props)
		{
			super(props);
			this.mode = null;
			this.companyMode = this.getConfig().companyMode ? this.getConfig().companyMode : false;
			this.userMode = this.getConfig().userMode ? this.getConfig().userMode : false;
			this.contactMode = this.getConfig().contactMode ? this.getConfig().contactMode : false;
		}

		getConfig()
		{
			const config = super.getConfig();

			const {
				idsForFilterCompany,
				idsForFilterContact,
			} = config;

			return {
				...config,
				provider: {
					options: {
						idsForFilterCompany,
						idsForFilterContact,
						onlyWithEmail: true,
					},
				},
			};
		}

		setStateEntityList(entities)
		{
			const entityList = this.prepareEntityList(entities);

			if (!isEqual(this.state.entityList, entityList))
			{
				const fields = clone(this.state.entityList);
				const entityType = this.mode;
				const crm = Object.fromEntries(entityList.map((item) => [`${item.type}_${item.id}`,
					{
						id: item.id,
						customData: item.customData,
						email: item.email,
						title: item.title,
						type: item.type,
					}]));

				const newCrm = Object.keys(crm);
				const oldCrm = fields.map((item) => (item.type === entityType) && `${item.type}_${item.id}`).filter(Boolean);

				const difference = [
					...newCrm.filter((id) => !oldCrm.includes(id)),
					...oldCrm.filter((id) => !newCrm.includes(id)),
				];

				if (difference.length > 0)
				{
					const toDelete = new Set(difference.map((key) => (oldCrm.includes(key) && key)).filter(Boolean));
					const newVal = fields.map((item) => (!toDelete.has(`${item.type}_${item.id}`) && item)).filter(Boolean);

					// Added the entities that appeared
					difference.forEach((key) => {
						if (!oldCrm.includes(key))
						{
							newVal.push(crm[key]);
						}
					});

					Haptics.impactLight();
					this.setState({ entityList: newVal }, () => {
						this.handleChange(
							newVal,
						);
					});
				}
			}
		}

		removeRecipient(entityId, entityType)
		{
			let fields = clone(this.state.entityList);
			fields = fields.filter((recipient) => !(recipient.id === entityId && recipient.type === entityType));

			Haptics.impactLight();
			this.setState({ entityList: fields }, () => {
				this.handleChange(
					fields,
				);
			});
		}

		getSelectedIds()
		{
			return this.state.entityList.map(({ id, type }) => (type && id && type === this.mode) && [type, id]).filter(Boolean);
		}

		openUserProfile(userId)
		{
			PageManager.openWidget('list', {
				groupStyle: true,
				backdrop: {
					bounceEnable: false,
					swipeAllowed: true,
					showOnTop: true,
					hideNavigationBar: false,
					horizontalSwipeAllowed: false,
				},
			}).then((list) => ProfileView.open({ userId, isBackdrop: true }, list));
		}

		async openDetail(id, typeNameId, isUser = false)
		{
			if (isUser)
			{
				this.openUserProfile(id);
			}
			else
			{
				const { EntityDetailOpener } = await requireLazy('crm:entity-detail/opener');
				const analytics = new AnalyticsEvent(BX.componentParameters.get('analytics', {}));
				EntityDetailOpener.open({
					payload: {
						entityTypeId: typeNameId,
						entityId: id,
					},
					analytics,
				});
			}
		}

		showRecipientSettingsMenu(
			recipientName,
			emailList,
			selectedEmailId,
			entityId,
			entityType,
			customData,
			isEmailHidden,
		)
		{
			if (isEmailHidden)
			{
				return;
			}

			if (!selectedEmailId)
			{
				selectedEmailId = 0;
			}

			let actions = [];

			const svgIconSelect = '<svg width="30" height="31" viewBox="0 0 30 31" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M17.1503 6.71608C16.9572 6.54226 16.6495 6.67925 16.6495 6.93902V11.3001C16.6291 11.2962 16.6081 11.2943 16.5866 11.2948C8.03278 11.4888 5.55567 19.8219 4.9578 22.6728C4.89734 22.9611 5.24978 23.1333 5.46082 22.9278C7.08721 21.3436 11.4872 17.5837 16.5795 17.6217C16.6035 17.6218 16.627 17.6193 16.6495 17.6144V22.6579C16.6495 22.9177 16.9572 23.0547 17.1503 22.8809L25.7961 15.0957C25.9725 14.9368 25.9725 14.6601 25.7961 14.5012L17.1503 6.71608Z" fill="#525C69"/></svg>';

			if (entityType === 'user')
			{
				const email = customData.email;
				if (email)
				{
					actions.push({
						id: email,
						isSelected: true,
						title: String(email),
						data: {
							svgIcon: svgIconSelect,
						},
						onClickCallback: () => {
							contextMenu.close();
						},
					});
				}
			}
			else
			{
				actions = emailList.map((email, index) => ({
					id: email.value,
					isSelected: (index === selectedEmailId),
					title: String(email.value),
					data: {
						svgIcon: svgIconSelect,
					},
					onClickCallback: () => {
						contextMenu.close(() => {
							this.selectEmailInRecipient(entityId, entityType, index);
						});
					},
				}));
			}

			if (entityType === 'user')
			{
				actions.push({
					id: 'open-recipient-card',
					title: Loc.getMessage('FIELDS_MAIL_CONTACT_RECIPIENT_MENU_OPEN_USER'),
					data: {
						svgIcon: '<svg width="30" height="31" viewBox="0 0 30 31" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M23.2627 23.978C23.8203 23.7754 24.1296 23.1894 24.0153 22.6073L23.6203 20.5957C23.6203 19.7563 22.5024 18.7975 20.3011 18.2387C19.5552 18.0345 18.8463 17.7179 18.1994 17.3001C18.058 17.2208 18.0795 16.4877 18.0795 16.4877L17.3705 16.3818C17.3705 16.3223 17.3099 15.4432 17.3099 15.4432C18.1582 15.1634 18.0709 13.5128 18.0709 13.5128C18.6096 13.8061 18.9605 12.5 18.9605 12.5C19.5977 10.6852 18.6432 10.795 18.6432 10.795C18.8101 9.68712 18.8101 8.56119 18.6432 7.45335C18.2188 3.77832 11.8297 4.776 12.5872 5.97625C10.7199 5.63862 11.146 9.80911 11.146 9.80911L11.551 10.8891C10.9897 11.2465 11.0999 11.6567 11.2231 12.1149C11.2744 12.3059 11.328 12.5053 11.3361 12.7127C11.3752 13.7534 12.024 13.5377 12.024 13.5377C12.064 15.2554 12.9269 15.4791 12.9269 15.4791C13.089 16.5578 12.988 16.3742 12.988 16.3742L12.2201 16.4653C12.2305 16.7107 12.2101 16.9563 12.1594 17.1967C11.7133 17.3919 11.4401 17.5472 11.1697 17.701C10.8928 17.8584 10.6188 18.0142 10.1649 18.2096C8.4313 18.9553 6.54723 19.9251 6.2123 21.2309C6.11438 21.6126 6.01848 22.1258 5.93178 22.661C5.84093 23.2218 6.15272 23.7708 6.68634 23.9657C9.01479 24.8164 11.6426 25.3205 14.4302 25.3803H15.5525C18.3267 25.3208 20.9426 24.8212 23.2627 23.978Z" fill="#525C69"/></svg>',
					},
					onClickCallback: () => {
						contextMenu.close(() => {
							this.openDetail(entityId, 0, true);
						});
					},
				});
			}

			if (entityType === 'company')
			{
				actions.push({
					id: 'open-recipient-card',
					title: Loc.getMessage('FIELDS_MAIL_CONTACT_RECIPIENT_MENU_OPEN_COMPANY'),
					data: {
						svgIcon: '<svg width="30" height="31" viewBox="0 0 30 31" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M18.75 13.741V21.5305H17.5V8C17.5 7.30964 16.9404 6.75 16.25 6.75C16.1828 6.75 16.1158 6.75541 16.0495 6.76619L7.29948 8.18823C6.69446 8.28656 6.25 8.80909 6.25 9.42204V21.5305H5V24.0305H25V21.5305H23.75V14.616C23.75 14.2719 23.5158 13.9719 23.1819 13.8884L19.6819 13.0134C19.2801 12.913 18.8729 13.1573 18.7724 13.5591C18.7575 13.6186 18.75 13.6797 18.75 13.741ZM13.75 21.5305H10V17.7805H13.75V21.5305ZM20 16.75H22.5V19.25H20V16.75ZM12.5 10.2805H15V12.7805H12.5V10.2805ZM8.75 10.2805H11.25V12.7805H8.75V10.2805ZM12.5 14.0305H15V16.5305H12.5V14.0305ZM8.75 14.0305H11.25V16.5305H8.75V14.0305Z" fill="#6A737F"/></svg>',
					},
					onClickCallback: () => {
						contextMenu.close(() => {
							this.openDetail(entityId, COMPANY_TYPE_ID);
						});
					},
				});
			}

			if (entityType === 'contact')
			{
				actions.push({
					id: 'open-recipient-card',
					title: Loc.getMessage('FIELDS_MAIL_CONTACT_RECIPIENT_MENU_OPEN_CONTACT'),
					data: {
						svgIcon: '<svg width="30" height="31" viewBox="0 0 30 31" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M25 15.5C25 21.0228 20.5228 25.5 15 25.5C9.47715 25.5 5 21.0228 5 15.5C5 9.97715 9.47715 5.5 15 5.5C20.5228 5.5 25 9.97715 25 15.5ZM20.132 20.7301C20.7577 20.5303 21.1007 19.8966 20.9738 19.2694L20.7954 18.3878C20.7058 17.8367 20.0459 17.2181 18.5701 16.8547C18.0701 16.7218 17.5948 16.5159 17.1611 16.2441C17.0663 16.1925 17.0807 15.7157 17.0807 15.7157L16.6054 15.6468C16.6054 15.6081 16.5647 15.0363 16.5647 15.0363C17.1335 14.8543 17.0749 13.7806 17.0749 13.7806C17.4361 13.9714 17.6713 13.1218 17.6713 13.1218C18.0985 11.9414 17.4586 12.0128 17.4586 12.0128C17.5706 11.2922 17.5706 10.5598 17.4586 9.83918C17.1741 7.44871 12.8907 8.09766 13.3986 8.87838C12.1467 8.65877 12.4324 11.3715 12.4324 11.3715L12.7039 12.074C12.3275 12.3065 12.4015 12.5733 12.484 12.8713L12.484 12.8714C12.5184 12.9956 12.5544 13.1253 12.5598 13.2602C12.586 13.9371 13.021 13.7968 13.021 13.7968C13.0478 14.9141 13.6263 15.0596 13.6263 15.0596C13.735 15.7613 13.6672 15.6418 13.6672 15.6418L13.1524 15.7011C13.1594 15.8607 13.1457 16.0205 13.1118 16.1769C12.8127 16.3038 12.6295 16.4049 12.4482 16.5049L12.4482 16.5049C12.2626 16.6073 12.0789 16.7087 11.7746 16.8357C11.558 16.9261 11.3413 17.0091 11.1307 17.0897C10.2107 17.442 9.40491 17.7505 9.22221 18.4416C9.17056 18.637 9.09599 18.9728 9.02431 19.328C8.90307 19.9287 9.24456 20.5222 9.84318 20.7155C11.3027 21.1866 12.9237 21.4644 14.6345 21.5H15.3864C17.0793 21.4648 18.6844 21.1924 20.132 20.7301Z" fill="#6A737F"/></svg>',
					},
					onClickCallback: () => {
						contextMenu.close(() => {
							this.openDetail(entityId, CONTACT_TYPE_ID);
						});
					},
				});
			}

			actions.push({
				id: 'remove-recipient',
				title: Loc.getMessage('FIELDS_MAIL_CONTACT_RECIPIENT_MENU_REMOVE'),
				data: {
					svgIcon: '<svg width="30" height="31" viewBox="0 0 30 31" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M10.9482 20.652H15.9535C16.2292 20.652 16.4937 20.5444 16.6886 20.3529L24.5022 12.6791C24.9082 12.2803 24.9082 11.6339 24.5022 11.2351L18.9672 5.79905C18.5612 5.40032 17.9029 5.40032 17.4969 5.79905L7.18069 15.9308C6.77469 16.3296 6.77469 16.976 7.18069 17.3748L10.2131 20.3529C10.408 20.5444 10.6725 20.652 10.9482 20.652ZM18.9998 15.3373L15.4932 18.7812H11.6706L9.36876 16.5206L14.7867 11.1995L18.9998 15.3373ZM22.7906 22.1614H6.69956C6.45127 22.1614 6.25 22.3591 6.25 22.6029V23.6148C6.25 23.8587 6.45127 24.0564 6.69956 24.0564H22.7906C23.0389 24.0564 23.2402 23.8587 23.2402 23.6148V22.6029C23.2402 22.3591 23.0389 22.1614 22.7906 22.1614Z" fill="#6A737F"/></svg>',
				},
				onClickCallback: () => {
					contextMenu.close(() => {
						this.removeRecipient(entityId, entityType);
					});
				},
			});

			const contextMenu = new ContextMenu({
				params: {
					showCancelButton: true,
					showActionLoader: false,
					title: recipientName,
					shouldResizeContent: true,
				},
				actions,
			});

			void contextMenu.show();
		}

		selectEmailInRecipient(entityId, entityType, selectedEmailId)
		{
			const fields = clone(this.state.entityList);

			for (const recipient of fields)
			{
				if (recipient.id === entityId && recipient.type === entityType)
				{
					recipient.selectedEmailId = selectedEmailId;
				}
			}

			Haptics.impactLight();
			this.setState({ entityList: fields }, () => {
				this.handleChange(
					fields,
				);
			});
		}

		renderEntity(recipient, showPadding = false)
		{
			const arrowWidth = 17;
			const allMarginsWidthInField = this.getConfig().allMarginsWidthInField ?? 0;
			const allMarginsWidth = allMarginsWidthInField + arrowWidth;

			const {
				type,
				title,
				email,
				id,
				customData,
				isEmailHidden,
			} = recipient;

			let {
				selectedEmailId,
			} = recipient;

			if (!selectedEmailId)
			{
				selectedEmailId = 0;
			}

			const recipientText = (type === 'user') ? title : email[selectedEmailId].value;

			return View(
				{
					testId: `MAIL_CONTACT_FIELD_RECIPIENT_${id}`,
					onClick: () => {
						this.showRecipientSettingsMenu(title, email, selectedEmailId, id, type, customData, isEmailHidden);
					},
					style: {
						...this.styles.capsule,
						backgroundColor: this.styles.backgroundColor[type],
					},
				},
				View(
					{
						style: {
							maxWidth: (deviceWidth - allMarginsWidth),
						},
					},
					Text({
						color: this.styles.textColor[type],
						style: this.styles.tagTitle,
						numberOfLines: 1,
						ellipsize: 'end',
						text: isEmailHidden ? Loc.getMessage('FIELDS_MAIL_CONTACT_RECIPIENT_HIDDEN') : recipientText,
					}),
				),
				!isEmailHidden && Image({
					style: {
						width: 14,
						height: 16,
					},
					svg: {
						content: `
							<svg width="14" height="16" viewBox="0 0 14 16" fill="none" xmlns="http://www.w3.org/2000/svg">
							<g opacity="0.5">
							<path fill-rule="evenodd" clip-rule="evenodd" d="M10.306 6.75439L7.66523 9.39517L6.99993 10.0502L6.34724 9.39517L3.70646 6.75439L2.77461 7.68625L7.0062 11.9178L11.2378 7.68625L10.306 6.75439Z" fill="#525C69"/>
							</g>
							</svg>
						`,
					},
				}),
			);
		}

		shouldShowEditIcon()
		{
			return BX.prop.getBoolean(this.props, 'showEditIcon', false);
		}

		renderLeftIcons()
		{
			return null;
		}

		getDefaultStyles()
		{
			const styles = super.getDefaultStyles();

			return {
				...styles,
				capsule: {
					flexDirection: 'row',
					alignItems: 'center',
					height: 24,
					borderRadius: 3,
					paddingVertical: 3,
					paddingLeft: 4,
					paddingRight: 3,
					marginRight: 4,
					marginBottom: 5,
					flexShrink: 2,
				},
				textColor: {
					email: '#525C69',
					company: '#7A5100',
					user: '#0B66C3',
					contact: '#506900',
				},
				backgroundColor: {
					email: '#E6E7E9',
					company: '#FFE9BE',
					user: '#D8EBFF',
					contact: '#EAF6C3',
				},
				tagTitle: {
					paddingRight: 1,
					color: '#525C69',
					fontSize: 14,
					flexShrink: 2,
				},
				wrapper: {
					paddingTop: (this.isLeftTitlePosition() ? 10 : 7),
					paddingBottom: (this.hasErrorMessage() ? 5 : 10),
				},
				readOnlyWrapper: {
					paddingTop: (this.isLeftTitlePosition() ? 10 : 7),
					paddingBottom: (this.hasErrorMessage() ? 5 : 10),
				},
			};
		}

		openEntitySelectionSlider(type)
		{
			this.mode = type;
			this.openSelector(type);
		}

		async openTypeSelectionMenu()
		{
			const actions = [];

			if (this.contactMode)
			{
				actions.push({
					id: 'contact',
					title: Loc.getMessage('FIELDS_MAIL_CONTACT_SELECT_MENU_CONTACT'),
					subTitle: '',
					data: {
						svgIcon: `
							<svg width="20" height="21" viewBox="0 0 20 21" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path fill-rule="evenodd" clip-rule="evenodd" d="M20 10.5C20 16.0228 15.5228 20.5 10 20.5C4.47715 20.5 0 16.0228 0 10.5C0 4.97715 4.47715 0.5 10 0.5C15.5228 0.5 20 4.97715 20 10.5ZM15.132 15.7301C15.7577 15.5303 16.1007 14.8966 15.9738 14.2694L15.7954 13.3878C15.7058 12.8367 15.0459 12.2181 13.5701 11.8547C13.0701 11.7218 12.5948 11.5159 12.1611 11.2441C12.0663 11.1925 12.0807 10.7157 12.0807 10.7157L11.6054 10.6468C11.6054 10.6081 11.5647 10.0363 11.5647 10.0363C12.1335 9.85427 12.0749 8.78065 12.0749 8.78065C12.4361 8.97143 12.6713 8.12181 12.6713 8.12181C13.0985 6.94141 12.4586 7.01278 12.4586 7.01278C12.5706 6.29217 12.5706 5.55979 12.4586 4.83918C12.1741 2.44871 7.89069 3.09766 8.39859 3.87838C7.14672 3.65877 7.43238 6.37151 7.43238 6.37151L7.70391 7.074C7.32755 7.30649 7.40145 7.5733 7.48401 7.87135L7.48402 7.87135C7.51843 7.99561 7.55435 8.12528 7.55978 8.26017C7.58601 8.93712 8.02098 8.79684 8.02098 8.79684C8.04779 9.91411 8.62631 10.0596 8.62631 10.0596C8.73498 10.7613 8.66724 10.6418 8.66724 10.6418L8.15243 10.7011C8.1594 10.8607 8.14575 11.0205 8.11178 11.1769C7.81267 11.3038 7.62954 11.4049 7.44823 11.5049L7.44821 11.5049C7.26261 11.6073 7.07889 11.7087 6.77459 11.8357C6.55803 11.9261 6.34135 12.0091 6.13066 12.0897C5.21065 12.442 4.40491 12.7505 4.22221 13.4416C4.17056 13.637 4.09599 13.9728 4.02431 14.328C3.90307 14.9287 4.24456 15.5222 4.84318 15.7155C6.30266 16.1866 7.92369 16.4644 9.63446 16.5H10.3864C12.0793 16.4648 13.6844 16.1924 15.132 15.7301Z" fill="#6A737F"/>
							</svg>
						`,
					},
					onClickCallback: () => {
						menu.close(() => {
							this.openEntitySelectionSlider(EntitySelectorFactory.Type.CRM_CONTACT);
						});
					},
				});
			}

			if (this.companyMode)
			{
				actions.push({
					id: 'company',
					title: Loc.getMessage('FIELDS_MAIL_CONTACT_SELECT_MENU_COMPANY'),
					subTitle: '',
					data: {
						svgIcon: `
							<svg width="20" height="19" viewBox="0 0 20 19" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path fill-rule="evenodd" clip-rule="evenodd" d="M13.75 7.74103V15.5305H12.5V2C12.5 1.30964 11.9404 0.75 11.25 0.75C11.1828 0.75 11.1158 0.755413 11.0495 0.766188L2.29948 2.18823C1.69446 2.28656 1.25 2.80909 1.25 3.42204V15.5305H0V18.0305H20V15.5305H18.75V8.61603C18.75 8.27188 18.5158 7.97189 18.1819 7.88843L14.6819 7.01343C14.2801 6.91296 13.8729 7.15728 13.7724 7.55913C13.7575 7.61862 13.75 7.67971 13.75 7.74103ZM8.75 15.5305H5V11.7805H8.75V15.5305ZM15 10.75H17.5V13.25H15V10.75ZM7.5 4.28045H10V6.78045H7.5V4.28045ZM3.75 4.28045H6.25V6.78045H3.75V4.28045ZM7.5 8.03045H10V10.5305H7.5V8.03045ZM3.75 8.03045H6.25V10.5305H3.75V8.03045Z" fill="#6A737F"/>
							</svg>
						`,
					},
					onClickCallback: () => {
						menu.close(() => {
							this.openEntitySelectionSlider(EntitySelectorFactory.Type.CRM_COMPANY);
						});
					},
				});
			}

			if (this.userMode)
			{
				actions.push({
					id: 'employee',
					title: Loc.getMessage('FIELDS_MAIL_CONTACT_SELECT_MENU_EMPLOYEE'),
					subTitle: '',
					data: {
						svgIcon: `
							<svg width="20" height="17" viewBox="0 0 20 17" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path fill-rule="evenodd" clip-rule="evenodd" d="M20 4.10595C20 3.20708 19.2725 2.4784 18.375 2.4784H1.625C0.727537 2.4784 0 3.20708 0 4.10595V14.6224C0 15.5213 0.727537 16.25 1.625 16.25H18.375C19.2725 16.25 20 15.5213 20 14.6224V4.10595ZM5.19112 7.07255C5.19112 7.07255 4.97425 5.22188 5.92667 5.37246C5.54039 4.84078 8.79911 4.39835 9.01551 6.02841C9.10068 6.51978 9.10068 7.01916 9.01551 7.51052C9.01551 7.51052 9.50239 7.46195 9.17733 8.26666L9.13127 8.38807C9.06136 8.54991 8.91599 8.80593 8.72366 8.71455C8.72366 8.71455 8.76827 9.4466 8.33548 9.57066L8.36632 9.98698L8.72817 10.0349C8.72817 10.0349 8.7182 10.36 8.78938 10.3952C9.11922 10.5805 9.48078 10.7209 9.86115 10.8115C10.9846 11.0593 11.5541 11.4845 11.5541 11.8569C11.5541 11.8569 12.3456 13.9032 12.3452 14.5055H1.99623L2.6727 12.139C2.81785 11.6491 3.52838 11.2656 4.27917 10.9589L4.68953 10.799C4.92064 10.7126 5.06006 10.6436 5.20096 10.5739C5.33896 10.5056 5.47837 10.4366 5.70672 10.3499C5.73249 10.2434 5.74285 10.1347 5.73757 10.026L6.1293 9.98557C6.1293 9.98557 6.16226 10.0376 6.13304 9.8135L6.09798 9.58847C6.09798 9.58847 5.65832 9.48829 5.63791 8.72649C5.63791 8.72649 5.30762 8.82222 5.28769 8.36057L5.27801 8.2761C5.26689 8.21382 5.24826 8.15329 5.23018 8.09456C5.16754 7.89108 5.11155 7.7092 5.39779 7.551L5.19112 7.07255ZM13.125 8.6343H16.875C17.2202 8.6343 17.5 8.91456 17.5 9.26028V10.1702L17.4899 10.2827C17.437 10.5748 17.1818 10.7962 16.875 10.7962H13.125L13.0127 10.7861C12.7211 10.7331 12.5 10.4775 12.5 10.1702V9.26028L12.5101 9.14776C12.563 8.85574 12.8182 8.6343 13.125 8.6343ZM13.125 4.98232H16.875C17.2202 4.98232 17.5 5.26259 17.5 5.60831V6.51824L17.4899 6.63076C17.437 6.92278 17.1818 7.14422 16.875 7.14422H13.125L13.0127 7.13413C12.7211 7.08113 12.5 6.82554 12.5 6.51824V5.60831L12.5101 5.49578C12.563 5.20376 12.8182 4.98232 13.125 4.98232ZM18.7655 1.25024C18.5955 0.533307 17.9521 0 17.1844 0H2.81564C2.04786 0 1.40445 0.533307 1.23453 1.25024H18.7655Z" fill="#6A737F"/>
							</svg>
						`,
					},
					onClickCallback: () => {
						menu.close(() => {
							this.openEntitySelectionSlider(EntitySelectorFactory.Type.USER);
						});
					},
				});
			}

			if (actions.length === 0)
			{
				return Promise.reject();
			}

			const menu = new ContextMenu({
				actions,
				params: {
					title: Loc.getMessage('FIELDS_MAIL_CONTACT_SELECT_MENU_TITLE'),
					showCancelButton: true,
				},
				onCancel: () => {
					this.removeFocus();
				},
			});
			await menu.show(this.getParentWidget());
		}

		handleAdditionalFocusActions()
		{
			return this.openTypeSelectionMenu();
		}
	}

	module.exports = {
		MailContactType: 'mail-contact',
		MailContactField: (props) => new MailContactField(props),
	};
});
