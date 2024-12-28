/**
 * @module communication/menu
 */
jn.define('communication/menu', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { AnalyticsEvent } = require('analytics');
	const { ContextMenu } = require('layout/ui/context-menu');
	const ConnectionTypeSvg = require('assets/communication/menu');
	const { ImType, PhoneType, EmailType, isOpenLine, getOpenLineTitle } = require('communication/connection');
	const { CommunicationEvents } = require('communication/events');
	const { EventEmitter } = require('event-emitter');
	const { WarningBlock, BlockType } = require('layout/ui/warning-block');
	const { Loc } = require('loc');
	const { Type } = require('type');
	const { get, isEmpty } = require('utils/object');
	const { getFormattedNumber } = require('utils/phone');
	const { stringify } = require('utils/string');

	let CrmType = null;
	/** @var TypeName * */
	let TypeName = null;
	let EntitySvg = null;

	try
	{
		CrmType = require('crm/type').Type;
		TypeName = require('crm/type').TypeName;
		EntitySvg = require('crm/assets/entity').EntitySvg;
	}
	catch (e)
	{
		console.warn(e);
	}

	const ENTITY_ICONS = {
		[TypeName.Contact]: EntitySvg.contactCreate(AppTheme.colors.base3),
		[TypeName.Company]: EntitySvg.companyCreate(AppTheme.colors.base3),
	};

	const CLIENT_ACTIONS_CODE = 'CLIENT_ACTIONS';

	/**
	 * @class CommunicationMenu
	 */
	class CommunicationMenu
	{
		constructor(props)
		{
			this.connections = BX.prop.getArray(props, 'connections', []);
			this.value = BX.prop.getObject(props, 'value', {});
			this.ownerInfo = BX.prop.getObject(props, 'ownerInfo', {});
			this.isConnectionStubsEnabled = BX.prop.getBoolean(props, 'showConnectionStubs', true);
			this.clientOptions = BX.prop.getArray(props, 'clientOptions', []);
			this.permissions = BX.prop.getObject(props, 'permissions', {});

			const uid = BX.prop.getString(props, 'uid', null);
			this.setUid(uid);

			this.titlesBySectionCode = {};
			this.title = Type.isStringFilled(props.title) ? props.title : Loc.getMessage(
				'M_CRM_COMMUNICATION_MENU_TITLE',
			);
			this.additionalItems = Array.isArray(props.additionalItems) ? props.additionalItems : [];

			this.onCloseCommunicationMenu = this.handleOnCloseMenu.bind(this);
			this.onClickCallback = this.handleOnClickCallback.bind(this);
			this.analyticsSection = BX.prop.getString(props, 'analyticsSection', null);
		}

		handleOnClickCallback(params, connectionType)
		{
			return Promise.resolve({
				closeCallback: this.handleOnCloseMenu({
					type: connectionType,
					props: this.prepareValue(params, connectionType),
				}),
			});
		}

		handleOnCloseMenu(params)
		{
			return () => CommunicationEvents.execute(params);
		}

		/**
		 * @public
		 * @param parentWidget
		 * @return {Promise}
		 */
		show(parentWidget = PageManager)
		{
			const actions = this.getActions();

			if (actions.length === 0)
			{
				return Promise.reject();
			}

			this.parentWidget = parentWidget;

			return this.create(actions).show(parentWidget);
		}

		/**
		 * @public
		 * @param {String} uid
		 * @return {void}
		 */
		setUid(uid)
		{
			if (uid)
			{
				this.customEventEmitter = EventEmitter.createWithUid(uid);
			}
			else
			{
				this.customEventEmitter = null;
			}
		}

		/**
		 * @public
		 * @param {Object} value
		 * @return {void}
		 */
		setValue(value)
		{
			this.value = value;
		}

		/**
		 * @private
		 * @see CommunicationMenu::show()
		 * @param actions
		 */
		create(actions)
		{
			return new ContextMenu({
				testId: 'COMMUNICATION_MENU',
				customSection: this.getInfoSection(actions),
				actions,
				titlesBySectionCode: this.titlesBySectionCode,
				params: {
					shouldResizeContent: true,
					showCancelButton: true,
					title: this.title,
				},
			});
		}

		getInfoSection(actions)
		{
			const hasClientActions = actions.some((action) => action.sectionCode === CLIENT_ACTIONS_CODE);
			if (hasClientActions && !this.hasHiddenValues())
			{
				const { ownerTypeName } = this.ownerInfo;
				const messagePrefix = 'M_CRM_COMMUNICATION_MENU_INFO_TITLE';
				const title = Loc.hasMessage(`${messagePrefix}_${ownerTypeName}`)
					? Loc.getMessage(`${messagePrefix}_${ownerTypeName}`)
					: Loc.getMessage(messagePrefix);

				return {
					height: 85,
					layout: new WarningBlock({
						title,
						description: Loc.getMessage('M_CRM_COMMUNICATION_MENU_INFO_DESCRIPTION'),
						type: BlockType.info,
					}),
				};
			}

			return null;
		}

		getActions()
		{
			const actions = [];

			this.getClientsWithLayoutOrder().forEach((clientType) => {
				const clientValues = this.value[clientType];
				if (!Array.isArray(clientValues))
				{
					return;
				}

				clientValues.forEach((clientValue) => {
					const filledActions = [];
					const stubActions = [];

					this.connections.forEach((connectionType) => {
						const connectionValue = clientValue[connectionType] || [];
						const connectionValues = Array.isArray(connectionValue) ? connectionValue : [connectionValue];

						if (connectionValues.length === 0)
						{
							const stubItem = this.createStubItem(clientValue, connectionType);
							if (stubItem)
							{
								stubActions.push(stubItem);
							}
						}
						else
						{
							const clientItems = connectionValues
								.map((connectionValue) => this.createItem({
									...clientValue,
									[connectionType]: connectionValue,
									connectionType,
								}))
								.filter(Boolean);

							filledActions.push(...clientItems);
						}
					});

					actions.push(...filledActions, ...stubActions);
				});
			});

			actions.push(...this.addClientActions(actions), ...this.addAdditionalItems(actions));

			return actions;
		}

		getClientsWithLayoutOrder()
		{
			const valueOrder = Object.keys(this.value);
			const entityTypeOrder = this.clientOptions.map(({ entityTypeName }) => entityTypeName);

			if (entityTypeOrder.length === 0)
			{
				return valueOrder;
			}

			const valueOrderWithoutClientOptions = valueOrder.filter((value) => !entityTypeOrder.includes(value));

			return [
				...valueOrderWithoutClientOptions,
				...entityTypeOrder,
			];
		}

		/**
		 * @param {object} entityValue
		 * @param {string} connectionType
		 * @return {object}
		 */
		createStubItem(entityValue, connectionType)
		{
			if (connectionType !== PhoneType && connectionType !== EmailType)
			{
				return null;
			}

			const { type, id: entityId, title: entityTitle } = entityValue;
			const entityTypeName = type.toUpperCase();

			if (!this.shouldShowStubForEntityType(entityTypeName))
			{
				return null;
			}

			const { ownerTypeName } = this.ownerInfo;
			if (ownerTypeName && ownerTypeName === entityTypeName)
			{
				return null;
			}

			const title = this.getStubTitle(connectionType);
			if (!Type.isStringFilled(title))
			{
				return null;
			}

			const sectionCode = `${entityTypeName}_${entityId}`;

			this.setTitleForSection(sectionCode, entityTitle, entityTypeName);

			return {
				id: `${sectionCode}_${connectionType}_STUB`,
				sectionCode,
				title,
				data: {
					svgIcon: ConnectionTypeSvg[connectionType](AppTheme.colors.base3),
				},
				isSemitransparent: true,
				isCustomIconColor: true,
				onClickCallback: (action, itemId, { parentWidget }) => parentWidget.close(async () => {
					const { MultiFieldDrawer } = await requireLazy('crm:multi-field-drawer') || {};

					if (MultiFieldDrawer)
					{
						const description = Loc.getMessage(`M_CRM_COMMUNICATION_MENU_${connectionType.toUpperCase()}_DESCRIPTION`);
						const multiFieldDrawer = new MultiFieldDrawer({
							entityTypeId: CrmType.resolveIdByName(entityTypeName),
							entityId,
							fields: [connectionType.toUpperCase()],
							warningBlock: { description },
						});

						multiFieldDrawer.show(this.parentWidget);
					}
				}),
			};
		}

		shouldShowStubForEntityType(entityTypeName)
		{
			if (!this.isConnectionStubsEnabled)
			{
				return false;
			}

			if (entityTypeName !== TypeName.Contact && entityTypeName !== TypeName.Company)
			{
				return false;
			}

			return (
				get(this.permissions, [entityTypeName, 'read'], false)
				&& get(this.permissions, [entityTypeName, 'update'], false)
			);
		}

		getStubTitle(connectionType)
		{
			return Loc.getMessage(`M_CRM_COMMUNICATION_MENU_${connectionType.toUpperCase()}_STUB`);
		}

		/**
		 * @param {object} params
		 * @return {object}
		 */
		createItem(params)
		{
			const { type, id: entityId, title: entityTitle = '', connectionType, hidden = false } = params;

			const itemValue = params[connectionType];
			if (!itemValue)
			{
				return null;
			}

			const value = itemValue.value || itemValue;
			const complexName = itemValue.complexName || '';
			const isSelected = itemValue.isSelected || false;
			const showSelectedImage = itemValue.showSelectedImage || false;
			const onClickCallback = itemValue.onClickCallback || this.onClickCallback;

			if (connectionType === ImType && !isOpenLine(value))
			{
				return null;
			}

			const title = hidden ? Loc.getMessage('M_CRM_COMMUNICATION_HIDDEN') : this.getTitle(itemValue, connectionType);
			if (!Type.isStringFilled(title))
			{
				return null;
			}

			const entityTypeName = type.toUpperCase();
			const sectionCode = `${entityTypeName}_${entityId}`;

			this.setTitleForSection(sectionCode, entityTitle, entityTypeName);

			return {
				id: `${sectionCode}_${connectionType}`,
				sectionCode,
				title,
				subtitle: hidden ? null : this.getSubtitle(complexName, value, connectionType),
				isSelected,
				showSelectedImage,
				data: {
					svgIcon: ConnectionTypeSvg[connectionType](),
				},
				closeCallback: this.onCloseCommunicationMenu,
				onClickCallback: () => {
					return onClickCallback(params, connectionType);
				},
			};
		}

		getSvg(connectionType)
		{
			if (EntitySvg.hasOwnProperty(connectionType))
			{
				return EntitySvg[connectionType]();
			}

			return null;
		}

		getTitle(itemValue, type)
		{
			const { title, value } = itemValue;
			const preparers = {
				[PhoneType]: getFormattedNumber,
				[ImType]: () => {
					if (isOpenLine(value) && BX.type.isNotEmptyString(title))
					{
						return title;
					}

					return Loc.getMessage('M_CRM_COMMUNICATION_MENU_OPENLINE');
				},
			};

			if (preparers.hasOwnProperty(type))
			{
				const preparer = preparers[type];

				return preparer(value);
			}

			return stringify(value);
		}

		getSubtitle(complexName, value, connectionType)
		{
			if (connectionType === ImType && isOpenLine(value))
			{
				const title = getOpenLineTitle(value, false);
				if (title)
				{
					return title;
				}
			}

			return complexName;
		}

		setTitleForSection(sectionCode, entityTitle, entityTypeName)
		{
			const titleContactType = Loc.getMessage(`M_CRM_COMMUNICATION_MENU_${entityTypeName}`);

			if (Type.isStringFilled(titleContactType))
			{
				this.titlesBySectionCode[sectionCode] = `[COLOR=${AppTheme.colors.base2}]${titleContactType}[/COLOR] ${entityTitle}`.trim();
			}
		}

		prepareValue(menuValue, connectionType)
		{
			let connectionValue = menuValue[connectionType];
			if (!connectionValue)
			{
				return null;
			}

			if (Array.isArray(connectionValue))
			{
				connectionValue = connectionValue[0];
			}

			// eslint-disable-next-line default-case
			switch (connectionType)
			{
				case PhoneType:
				{
					const entityId = menuValue.id;
					const entityTypeName = menuValue.type.toUpperCase();

					const params = {
						NAME: menuValue.title,
						ENTITY_TYPE_NAME: entityTypeName,
						ENTITY_ID: entityId,
					};

					if (
						!isEmpty(this.ownerInfo)
						&& (this.ownerInfo.ownerId !== entityId || this.ownerInfo.ownerTypeName !== entityTypeName)
					)
					{
						params.BINDINGS = [
							{
								OWNER_ID: this.ownerInfo.ownerId,
								OWNER_TYPE_NAME: this.ownerInfo.ownerTypeName,
							},
						];
					}

					return {
						number: typeof connectionValue === 'string' ? connectionValue : connectionValue.value,
						params,
						isNumberHidden: menuValue.hidden,
						analyticsSection: this.analyticsSection,
					};
				}

				case EmailType:
				{
					let { ownerId, ownerTypeName: ownerType } = this.ownerInfo;

					ownerId = ownerId || menuValue.id;
					ownerType = ownerType || menuValue.type.toUpperCase();

					return {
						email: typeof connectionValue === 'string' ? connectionValue : connectionValue.value,
						params: {
							owner: { ownerType, ownerId },
						},
						isEmailHidden: menuValue.hidden,
					};
				}

				case ImType:
					return {
						event: 'openline',
						params: {
							userCode: connectionValue.value,
							titleParams: {
								name: connectionValue.complexName,
							},
						},
					};
			}

			return null;
		}

		addClientActions(actions)
		{
			if (!this.customEventEmitter || this.clientOptions.length === 0)
			{
				return [];
			}

			const { ownerTypeName } = this.ownerInfo;
			if (ownerTypeName && (ownerTypeName === TypeName.Contact || ownerTypeName === TypeName.Company))
			{
				return [];
			}

			const hasContacts = actions.some(({ id }) => id.startsWith('CONTACT_'));
			const hasCompanies = actions.some(({ id }) => id.startsWith('COMPANY_'));

			if (hasContacts || hasCompanies)
			{
				return [];
			}

			const filterToShowIfMultipleOrHasNoClientsByType = ({ type }) => {
				if (type === 'contact' && hasContacts)
				{
					return false;
				}

				return !(type === 'company' && hasCompanies);
			};

			return this.clientOptions
				.filter(filterToShowIfMultipleOrHasNoClientsByType)
				.map(({ entityTypeName, name }) => {
					return {
						id: `CLIENT_ADD_${entityTypeName}`,
						sectionCode: CLIENT_ACTIONS_CODE,
						title: Loc.getMessage(`M_CRM_COMMUNICATION_MENU_ADD_${entityTypeName}`),
						data: { svgIcon: ENTITY_ICONS[entityTypeName] },
						onClickCallback: () => {
							const closeCallback = () => {
								const analytics = new AnalyticsEvent(BX.componentParameters.get('analytics', {}))
									.setSubSection('element_card')
									.setElement('communication_channels_floating_button')
									.setEvent('entity_add_open');
								this.customEventEmitter.emit('UI.Fields.Client::select', [
									{
										fieldName: name,
										entityTypeName: entityTypeName.toLowerCase(),
										analytics,
									},
								]);
							};

							return Promise.resolve({ closeCallback });
						},
					};
				});
		}

		addAdditionalItems(actions)
		{
			const hasClientActions = actions.some((action) => action.sectionCode === CLIENT_ACTIONS_CODE);
			if (hasClientActions)
			{
				return [];
			}

			return this.additionalItems;
		}

		hasHiddenValues()
		{
			const entityTypeOrder = this.clientOptions.map(({ entityTypeName }) => entityTypeName);

			return entityTypeOrder.some((entityType) => {
				const clientValues = this.value[entityType];

				return Array.isArray(clientValues) && clientValues.find((value) => value.hidden);
			});
		}
	}

	module.exports = { CommunicationMenu };
});
