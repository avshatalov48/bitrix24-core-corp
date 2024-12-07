/**
 * @module layout/ui/fields/client
 */
jn.define('layout/ui/fields/client', (require, exports, module) => {
	const { Alert } = require('alert');
	const { AnalyticsEvent } = require('analytics');
	const AppTheme = require('apptheme');
	const { magnifier } = require('assets/common');
	const { EventEmitter } = require('event-emitter');
	const { AddButton } = require('layout/ui/buttons/add-button');
	const { BaseField } = require('layout/ui/fields/base');
	const { ClientItem } = require('layout/ui/fields/client/elements');
	const { uniqBy, mergeBy, replaceBy } = require('utils/array');
	const { get, isEqual, isEmpty, mergeImmutable } = require('utils/object');
	const { stringify } = require('utils/string');
	const { EntitySelectorFactory, EntitySelectorFactoryType } = require('selector/widget/factory');
	const { Random } = require('utils/random');

	let SelectorProcessing = null;
	let Type = null;
	let TypeId = null;
	let TypeName = null;

	try
	{
		SelectorProcessing = require('crm/selector/utils/processing').SelectorProcessing;
		Type = require('crm/type').Type;
		TypeId = require('crm/type').TypeId;
		TypeName = require('crm/type').TypeName;
	}
	catch (e)
	{
		console.warn(e);

		return;
	}

	const COMPANY_LAYOUT_NUMBER = 2;
	const CREATE = 'create';

	const { CRM_COMPANY, CRM_CONTACT } = EntitySelectorFactoryType;

	const SELECTOR_TYPES_BY_ID = {
		[TypeId.Contact]: CRM_CONTACT,
		[TypeId.Company]: CRM_COMPANY,
	};

	/**
	 * @class ClientField
	 */
	class ClientField extends BaseField
	{
		constructor(props)
		{
			super(props);

			// this value is used to render client data that does not cause the field value itself to change
			this.state.additionalValue = {};

			this.isCreateContact = false;
			this.uid = Random.getString();
			this.analytics = BX.componentParameters.get('analytics', null);

			/** @type {EventEmitter} */
			this.customEventEmitter = EventEmitter.createWithUid(this.uid);
			this.parentCustomEventEmitter = EventEmitter.createWithUid(this.props.uid || this.uid);

			this.onEditClient = this.handleEditClient.bind(this);
			this.handleUpdateEntity = this.handleUpdateEntity.bind(this);
			this.handleCloseEntity = this.handleCloseEntity.bind(this);
			this.handleClientSelection = this.handleClientSelection.bind(this);
			this.handleMultiFieldChange = this.handleMultiFieldChange.bind(this);
		}

		componentDidMount()
		{
			super.componentDidMount();

			this.customEventEmitter.on('Duplicate::onUpdate', this.handleUpdateEntity);
			this.customEventEmitter.on('DetailCard::onUpdate', this.handleUpdateEntity);
			this.customEventEmitter.on('DetailCard::onClose', this.handleCloseEntity);
			this.customEventEmitter.on('UI.Fields.Client::select', this.handleClientSelection);

			BX.addCustomEvent('MultiFieldDrawer::onSave', this.handleMultiFieldChange);

			this.emitClientFieldUpdateEvent();
		}

		componentDidUpdate(prevProps, prevState)
		{
			super.componentDidUpdate(prevProps, prevState);

			if (!isEqual(this.props.value, prevProps.value))
			{
				this.emitClientFieldUpdateEvent();
			}
		}

		emitClientFieldUpdateEvent()
		{
			const { permissions } = this.getConfig();
			const compound = this.getCompound();
			const contactCompound = compound.find(({ entityTypeName }) => entityTypeName === TypeName.Contact);
			const companyCompound = compound.find(({ entityTypeName }) => entityTypeName === TypeName.Company);

			const compoundWithLayoutOrder = this.isFirstCompanyLayout()
				? [companyCompound, contactCompound]
				: [contactCompound, companyCompound];

			this.parentCustomEventEmitter.emit('UI.Fields.Client::onUpdate', [
				{
					uid: this.uid,
					isEmpty: this.isEmpty(),
					canAdd: this.isShowClientAdd(),
					isMyCompany: this.isMyCompany(),
					value: this.getValue(),
					compound: compoundWithLayoutOrder.filter(Boolean),
					permissions,
				},
			]);
		}

		getConfig()
		{
			const config = super.getConfig();

			return {
				...config,

				categoryParams: BX.prop.getObject(config, 'categoryParams', {}),
				showClientInfo: BX.prop.getBoolean(config, 'showClientInfo', false),
				showClientType: BX.prop.getBoolean(config, 'showClientType', true),
				showClientAdd: BX.prop.getBoolean(config, 'showClientAdd', false),
				enableMyCompanyOnly: BX.prop.getBoolean(config, 'enableMyCompanyOnly', false),
				compound: BX.prop.getArray(config, 'compound', []),
				selectorTitle: '',
				entityList: this.getItems(config),
			};
		}

		/**
		 * @private
		 * @param {object} config
		 * @return {object}
		 */
		getItems(config)
		{
			if (config.items)
			{
				return BX.prop.getObject(config, 'items', {});
			}

			return BX.prop.getObject(config, 'entityList', {});
		}

		useHapticOnChange()
		{
			return true;
		}

		canFocusTitle()
		{
			return BX.prop.getBoolean(this.props, 'canFocusTitle', false);
		}

		isShowClientInfo(isHiddenContact)
		{
			return this.getConfig().showClientInfo && !isHiddenContact;
		}

		isShowClientType()
		{
			return this.getConfig().showClientType;
		}

		isMyCompany()
		{
			return this.getConfig().enableMyCompanyOnly;
		}

		getCompound()
		{
			return this.getConfig().compound;
		}

		getClientLayout()
		{
			return this.getConfig().clientLayout;
		}

		isFirstCompanyLayout()
		{
			return this.getClientLayout() === COMPANY_LAYOUT_NUMBER;
		}

		isShowClientAdd(entityTypeName)
		{
			const { showClientAdd } = this.getConfig();

			if (!showClientAdd)
			{
				return false;
			}

			if (entityTypeName)
			{
				return this.checkPermissions(entityTypeName, 'read');
			}

			const { entityList } = this.getConfig();

			return Object.keys(entityList).some((entityName) => this.checkPermissions(entityName, 'read'));
		}

		isEmpty()
		{
			const {
				[CRM_CONTACT]: contacts,
				[CRM_COMPANY]: companies,
			} = this.getValue();

			return isEmpty(contacts) && isEmpty(companies);
		}

		renderReadOnlyContent()
		{
			return this.renderBody();
		}

		renderEditableContent()
		{
			return this.renderBody();
		}

		renderBody()
		{
			if (this.isEmpty() && !this.isShowClientAdd())
			{
				return this.renderEmptyContent();
			}

			return View(
				{
					style: {
						flex: 1,
						flexShrink: 2,
					},
				},
				...this.renderClients(),
				...this.renderSelectors(),
			);
		}

		renderSelectors()
		{
			if (!this.isShowClientAdd())
			{
				return [];
			}

			if (this.isMyCompany())
			{
				if (this.isShowSelectorInDeal(CRM_COMPANY))
				{
					return [this.createSelector(CRM_COMPANY)];
				}

				return [];
			}

			const fixedLayoutType = this.getFixedLayoutType();

			if (this.isClientType(fixedLayoutType))
			{
				return [this.createSelector(fixedLayoutType)];
			}

			const selectorEntities = this.isFirstCompanyLayout()
				? [CRM_COMPANY, CRM_CONTACT]
				: [CRM_CONTACT, CRM_COMPANY];

			return selectorEntities
				.filter(this.isShowSelectorInDeal.bind(this))
				.map(this.createSelector.bind(this));
		}

		renderClients()
		{
			if (this.isEmpty())
			{
				return [];
			}

			const {
				[CRM_COMPANY]: companies,
				[CRM_CONTACT]: contacts,
			} = this.getValue();

			const [first, second] = this.isFirstCompanyLayout() ? [companies, contacts] : [contacts, companies];

			return [
				this.renderVisibleClients(first),
				this.renderVisibleClients(second),
			];
		}

		renderVisibleClients(clients)
		{
			const { testId } = this.props;
			const visibleClients = this.getVisibleClients(clients);

			return View(
				{
					style: {
						flexDirection: 'column',
					},
				},
				...visibleClients.map((contact, index) => {
					if (!this.shouldShowClient(contact))
					{
						return null;
					}

					return View(
						{
							style: {
								marginBottom: 10,
							},
						},
						new ClientItem({
							...contact,
							testId: `${testId}-${contact.type}-${contact.id}`,
							onEdit: this.onEditClient,
							readOnly: this.isReadOnly(),
							showClientInfo: this.isShowClientInfo(contact.hidden),
							showClientType: this.isShowClientType(),
							onOpenBackdrop: () => {
								this.handleOnOpenBackDrop(contact);
							},
							actionParams: {
								show: !index,
								onClick: this.onEditClient,
							},
							styles: BX.prop.getObject(this.styles, 'element', {}),
						}),
					);
				}),
				this.renderShowAllButton(clients.length - visibleClients.length),
			);
		}

		shouldShowClient(client)
		{
			return client.id && client.title && !client.deleted;
		}

		getVisibleClients(contacts)
		{
			if (!this.state.showAll)
			{
				return contacts.filter((item, index) => index < 4);
			}

			return contacts;
		}

		getValue()
		{
			const { additionalValue } = this.state;
			const value = super.getValue();

			let companyValue = Array.isArray(value[CRM_COMPANY]) ? value[CRM_COMPANY] : [];
			const companyAdditionalValue = Array.isArray(additionalValue[CRM_COMPANY])
				? additionalValue[CRM_COMPANY]
				: [];

			if (companyAdditionalValue.length > 0)
			{
				companyAdditionalValue.forEach((addValue) => {
					companyValue = mergeBy(companyValue, addValue, 'id', false);
				});
			}

			let contactValue = Array.isArray(value[CRM_CONTACT]) ? value[CRM_CONTACT] : [];
			const contactAdditionalValue = Array.isArray(additionalValue[CRM_CONTACT])
				? additionalValue[CRM_CONTACT]
				: [];

			if (contactAdditionalValue.length > 0)
			{
				contactAdditionalValue.forEach((addValue) => {
					contactValue = mergeBy(contactValue, addValue, 'id', false);
				});
			}

			return {
				[CRM_COMPANY]: companyValue,
				[CRM_CONTACT]: contactValue,
			};
		}

		handleEditClient(type)
		{
			this.openClientSelector(type);
		}

		handleClientSelection({ fieldName, entityTypeName, analytics })
		{
			const hasField = this.getCompound().some(({ name }) => fieldName === name);
			if (hasField)
			{
				this.openClientSelector(entityTypeName, analytics);
			}
		}

		parseEntityDataFromQueryString(type, queryText = '')
		{
			if (!queryText)
			{
				return null;
			}

			const emailMatch = queryText.match(/[\w%.-]+@[\d.a-z-]+\.[a-z]{2,63}/i);
			if (emailMatch)
			{
				const email = stringify(emailMatch[0]).trim();
				if (email !== '')
				{
					return {
						EMAIL: [
							{
								id: `n${Math.round(Math.random() * 1000)}`,
								value: {
									VALUE: email,
									VALUE_TYPE: 'WORK',
								},
							},
						],
					};
				}
			}

			const phoneMatch = queryText.match(/^[\d()+]{6,}$/i);
			if (phoneMatch)
			{
				const phone = stringify(phoneMatch[0]).trim();
				if (phone !== '')
				{
					return {
						PHONE: [
							{
								id: `n${Math.round(Math.random() * 1000)}`,
								value: {
									VALUE: phone,
									VALUE_TYPE: 'WORK',
								},
							},
						],
					};
				}
			}

			const entityName = type === CRM_COMPANY ? 'TITLE' : 'NAME';

			return {
				[entityName]: queryText,
			};
		}

		async handleOnOpenBackDrop(params, method, analytics = null)
		{
			const type = params.type;
			this.isCreateContact = method === CREATE;
			if (!Type.existsByName(type) && !this.isReadOnly())
			{
				return;
			}

			const { id, entityId, title, queryText = '' } = params;

			const entityData = this.parseEntityDataFromQueryString(type, queryText);
			let tabsExternalData = null;

			if (entityData)
			{
				tabsExternalData = {
					main: {
						editor: {
							ENTITY_DATA: entityData,
						},
					},
				};
			}

			const { EntityDetailOpener } = await requireLazy('crm:entity-detail/opener');
			const openerConfig = BX.prop.getObject(this.getConfig(), 'entityDetailOpener', {});

			const analyticsToSend = analytics || this.analytics;
			EntityDetailOpener.open({
				payload: {
					entityTypeId: Type.resolveIdByName(type),
					entityId: id || entityId,
					categoryId: this.getCategoryId(type),
					uid: this.uid,
					context: this.getUserCountryCodeInEditorOptions(),
					isCreationFromSelector: this.isCreateContact,
					closeOnSave: this.isCreateContact,
					tabsExternalData,
					owner: BX.prop.getObject(this.getConfig(), 'owner', {}),
				},
				widgetParams: mergeImmutable(
					{ titleParams: { text: title } },
					BX.prop.getObject(openerConfig, 'widgetParams', {}),
				),
				parentWidget: this.getParentWidget(),
				analytics: analyticsToSend && new AnalyticsEvent(analyticsToSend)
					.setSubSection('element_card')
					.setEvent('entity_add_open')
					.setType(type?.toLowerCase()),
			});
		}

		handleMultiFieldChange({ entityTypeId, entityId })
		{
			const selectorName = SELECTOR_TYPES_BY_ID[entityTypeId];
			if (!selectorName)
			{
				return;
			}

			const { [selectorName]: clientList } = this.getValue();
			if (!clientList)
			{
				return;
			}

			const isUpdateNeeded = clientList.some(({ id }) => parseInt(id, 10) === parseInt(entityId, 10));
			if (isUpdateNeeded)
			{
				this.handleUpdateEntity({ entityTypeId, entityId });
			}
		}

		handleUpdateEntity({ entityTypeId, entityId })
		{
			const selectorName = SELECTOR_TYPES_BY_ID[entityTypeId];

			if (!selectorName || !entityId)
			{
				return;
			}

			this
				.getClientInfo(selectorName, entityId)
				.then((entityData) => {
					const { [selectorName]: prevEntityList } = this.getValue();

					let entityList = [];

					if (
						Array.isArray(prevEntityList)
						&& !isEmpty(entityData)
						&& this.isMultipleSelector(selectorName)
					)
					{
						entityList = replaceBy(prevEntityList, entityData, 'id');
					}
					else
					{
						entityList = [entityData];
					}

					if (!this.isEqualEntities(selectorName, entityList))
					{
						if (this.isCreateContact)
						{
							this.currentEntities = entityList;
							this.changeClientsList(selectorName);
						}
						else
						{
							this.handleOnChange({
								[selectorName]: entityList,
							});
						}
					}
					else if (!isEqual(entityList, prevEntityList))
					{
						this.setState({
							additionalValue: {
								...this.state.additionalValue,
								[selectorName]: entityList,
							},
						});
					}
				})
				.catch(console.error);
		}

		getUserCountryCodeInEditorOptions()
		{
			const defaultCountry = get(this.getConfig(), ['options', 'defaultCountry'], null);

			return {
				defaultCountry,
			};
		}

		changeClientsList(selectorType)
		{
			if (!this.isEqualEntities(selectorType, this.currentEntities))
			{
				const setEntity = selectorType === CRM_CONTACT
					? this.setEntityContacts
					: this.setEntityCompany;

				setEntity.call(this, this.currentEntities);
				this.currentEntities = null;
			}
		}

		isEqualEntities(selectorType, entities)
		{
			const { [selectorType]: prevEntityList } = this.getValue();

			const currentIds = this.selectedIds(entities);
			const prevEntityIds = this.selectedIds(prevEntityList);

			return isEqual(currentIds, prevEntityIds);
		}

		handleCloseEntity({ entityTypeId, entityId })
		{
			if (!this.isCreateContact)
			{
				return;
			}

			const selectorName = SELECTOR_TYPES_BY_ID[entityTypeId];
			if (!selectorName)
			{
				return;
			}

			if (entityId > 0)
			{
				this.isCreateContact = true;
				this.handleUpdateEntity({ entityTypeId, entityId });
			}
		}

		changeClientsByEntityType(entityType)
		{
			const { [entityType]: prevEntityList } = this.getValue();

			const prevIds = this.selectedIds(prevEntityList);
			const currentIds = this.selectedIds(this.currentEntities);

			if (!isEqual(prevIds, currentIds))
			{
				const setEntity = entityType === CRM_CONTACT
					? this.setEntityContacts
					: this.setEntityCompany;

				setEntity.call(this, this.currentEntities);
				this.currentEntities = null;
			}
		}

		createSelector(selectorType)
		{
			if (!this.checkPermissions(selectorType, 'read'))
			{
				return null;
			}

			return AddButton({
				svg: magnifier(AppTheme.colors.base3),
				text: BX.message(`FIELDS_CLIENT_PLACEHOLDER_${selectorType.toUpperCase()}`),
				deepMergeStyles: {
					view: {
						paddingTop: 4,
						paddingBottom: 8,
						height: null,
						alignItems: 'center',
					},
					text: {
						...this.styles.emptyValue,
						color: AppTheme.colors.base2,
						fontSize: 15,
						marginLeft: 7,
					},
					image: {
						width: 12.75,
						height: 12.75,
						marginLeft: 4,
						marginTop: 3,
						marginBottom: 2,
					},
				},
				onClick: () => {
					this.openClientSelector(
						selectorType,
						new AnalyticsEvent(this.analytics || {})
							.setSubSection('element_card')
							.setElement('client_field'),
					);
				},
			});
		}

		openClientSelector(selectorType, analytics)
		{
			const { [selectorType]: prevEntityList } = this.getValue();

			this.currentEntities = prevEntityList;

			const selector = EntitySelectorFactory.createByType(selectorType, {
				createOptions: {
					enableCreation: this.checkPermissions(selectorType, 'add'),
				},
				provider: this.makeClientSelectorProvider(selectorType),
				initSelectedIds: this.selectedIds(this.currentEntities),
				allowMultipleSelection: this.isMultipleSelector(selectorType),
				events: {
					onCreate: (createParams) => {
						const itemsLength = createParams?.items?.length ?? 0;
						if (itemsLength > 0)
						{
							this.handleOnOpenBackDrop(
								{
									...createParams.items[0],
									queryText: createParams.queryText,
									type: selectorType,
									entityId: null,
								},
								CREATE,
								analytics,
							);
						}
					},
					onClose: (currentEntities) => {
						this.currentEntities = currentEntities;
					},
					onViewHidden: () => {
						this.changeClientsByEntityType(selectorType);
					},
				},
				widgetParams: {
					backdrop: {
						mediumPositionPercent: 70,
						horizontalSwipeAllowed: false,
					},
				},
			});

			return selector.show({}, this.getParentWidget());
		}

		makeClientSelectorProvider(selectorType)
		{
			const options = {};

			if (this.isMyCompany())
			{
				options.enableMyCompanyOnly = true;
			}
			else if (selectorType === CRM_COMPANY)
			{
				options.excludeMyCompany = true;
			}

			const categoryId = this.getCategoryId(selectorType);
			if (categoryId)
			{
				options.categoryId = categoryId;
			}

			if (this.hasHiddenEntities(selectorType))
			{
				if (selectorType === CRM_CONTACT)
				{
					options.idsForFilterContact = this.getItems(this.getConfig())[selectorType].map((contact) => contact.id);
				}
				else if (selectorType === CRM_COMPANY)
				{
					options.idsForFilterCompany = this.getItems(this.getConfig())[selectorType].map((contact) => contact.id);
				}
			}

			return { options };
		}

		hasHiddenEntities(type)
		{
			return this.getValue()[type].find((entity) => entity.hidden);
		}

		isShowSelectorInDeal(selectorType)
		{
			const isCompany = selectorType === CRM_COMPANY;
			const isContact = selectorType === CRM_CONTACT;
			const { [CRM_COMPANY]: companies } = this.getValue();

			return (
				(
					isContact
					|| (isCompany && !companies.some((company) => this.shouldShowClient(company)))
				)
				&& (
					this.checkPermissions(selectorType, 'add')
					|| this.checkPermissions(selectorType, 'update')
				)
			);
		}

		setEntityContacts(contacts)
		{
			this.state.showAll = true;

			this.handleOnChange({
				[CRM_CONTACT]: contacts,
			});
		}

		setEntityCompany(companies)
		{
			this.state.showAll = true;

			this.handleOnChange({
				[CRM_COMPANY]: companies,
			});

			if (this.isCompanyLayout() || isEmpty(companies))
			{
				return;
			}

			const companyIds = this.selectedIds(companies);

			this
				.getSecondaryEntityInfos(...companyIds)
				.then(({ ENTITY_INFOS }) => {
					const { [CRM_CONTACT]: prevContacts } = this.getValue();
					const contacts = ENTITY_INFOS.length > 0
						? ENTITY_INFOS.map((entityInfo) => SelectorProcessing.prepareContact(entityInfo))
						: [];

					const clientData = {
						[CRM_CONTACT]: contacts,
						[CRM_COMPANY]: companies,
					};

					if (prevContacts.length > 0 && this.isContainInStateContacts(contacts))
					{
						this.showConfirmUpdateContacts(
							() => this.handleOnChange(clientData),
							() => this.handleOnChange({
								...clientData,
								[CRM_CONTACT]: uniqBy([...prevContacts, ...contacts], 'id'),
							}),
							contacts.length,
						);
					}
					else
					{
						this.handleOnChange(clientData);
					}
				})
				.catch(console.error);
		}

		showConfirmUpdateContacts(onSuccess, onFailed, contactsCount)
		{
			let title = '';
			let text = '';
			let buttonTextOk = '';
			let buttonTextCancel = '';

			if (contactsCount > 0)
			{
				title = BX.message('FIELDS_CLIENT_CONFIRM_UPDATE_CONTACTS_TITLE');
				text = BX.message('FIELDS_CLIENT_CONFIRM_UPDATE_CONTACTS');
				buttonTextOk = BX.message('FIELDS_CLIENT_CONFIRM_UPDATE_OK');
				buttonTextCancel = BX.message('FIELDS_CLIENT_CONFIRM_UPDATE_NO');
			}
			else
			{
				title = BX.message('FIELDS_CLIENT_CONFIRM_CLEAR_CONTACTS_TITLE');
				text = BX.message('FIELDS_CLIENT_CONFIRM_CLEAR_CONTACTS');
				buttonTextOk = BX.message('FIELDS_CLIENT_CONFIRM_CLEAR_OK');
				buttonTextCancel = BX.message('FIELDS_CLIENT_CONFIRM_CLEAR_NO');
			}

			Alert.confirm(
				title,
				text,
				[
					{
						text: buttonTextOk,
						onPress: onSuccess,
					},
					{
						type: 'cancel',
						text: buttonTextCancel,
						onPress: onFailed,
					},
				],
			);
		}

		getSecondaryEntityInfos(PRIMARY_ID)
		{
			return BX.ajax.runComponentAction('bitrix:crm.deal.edit', 'GET_SECONDARY_ENTITY_INFOS', {
				mode: 'ajax',
				data: {
					ACTION: 'GET_SECONDARY_ENTITY_INFOS',
					PARAMS: {
						PRIMARY_TYPE_NAME: CRM_COMPANY.toUpperCase(),
						PRIMARY_ID,
						SECONDARY_TYPE_NAME: CRM_CONTACT.toUpperCase(),
						OWNER_TYPE_NAME: 'DEAL',
					},
				},
			});
		}

		getClientInfo(entityTypeName, entityId)
		{
			if (!this.isClientType(entityTypeName))
			{
				return null;
			}

			return BX.ajax.runComponentAction(
				`bitrix:crm.${entityTypeName}.show`,
				'GET_CLIENT_INFO',
				{
					mode: 'ajax',
					data: {
						ACTION: 'GET_CLIENT_INFO',
						PARAMS: {
							ENTITY_ID: entityId,
							ENTITY_TYPE_NAME: entityTypeName.toUpperCase(),
							NORMALIZE_MULTIFIELDS: 'Y',
						},
					},
				},
			).then(({ DATA }) => {
				return SelectorProcessing.prepareContact(DATA);
			});
		}

		isMultipleSelector(selectorType)
		{
			if (selectorType === CRM_CONTACT)
			{
				return true;
			}

			return this.isCompanyLayout() && !this.isMyCompany();
		}

		handleOnChange(value)
		{
			const currentValue = this.getValue();
			this.handleChange({ ...currentValue, ...value });
		}

		isContainInStateContacts(contacts)
		{
			const { [CRM_CONTACT]: prevContacts } = this.getValue();
			const contactsIds = new Set(contacts.map(({ id }) => id));
			const differenceArr = prevContacts.filter(({ id }) => !contactsIds.has(id));

			return differenceArr.length;
		}

		isClientType(entityTypeName)
		{
			if (!entityTypeName)
			{
				return false;
			}

			const type = entityTypeName.toLowerCase();

			return type === CRM_COMPANY || type === CRM_CONTACT;
		}

		isCompanyLayout()
		{
			return this.getFixedLayoutType() === CRM_COMPANY;
		}

		getFixedLayoutType()
		{
			const { fixedLayoutType } = this.getConfig();

			return fixedLayoutType && fixedLayoutType.toLowerCase();
		}

		getCategoryId(type)
		{
			const entityTypeId = Type.resolveIdByName(type);
			const { categoryParams } = this.getConfig();
			const category = categoryParams[entityTypeId];

			if (!entityTypeId || !categoryParams[entityTypeId])
			{
				return null;
			}

			return category.categoryId;
		}

		checkPermissions(entityTypeName, permissionType)
		{
			if (!entityTypeName)
			{
				return false;
			}

			const permissions = get(
				this.getConfig(),
				['permissions', entityTypeName.toUpperCase(), permissionType],
				false,
			);

			return Boolean(permissions);
		}

		getDefaultStyles()
		{
			const styles = super.getDefaultStyles();

			return {
				...styles,
				wrapper: {
					...styles.wrapper,
					paddingBottom: 4,
					paddingTop: 8,
				},
				readOnlyWrapper: {
					...styles.readOnlyWrapper,
					paddingBottom: 4,
					paddingTop: 8,
				},
				container: {
					...styles.container,
					height: null,
					opacity: 1,
				},
				title: {
					...styles.title,
					fontSize: 10,
					marginBottom: this.isReadOnly() ? 2 : 4,
				},
			};
		}

		hasCapitalizeTitleInEmpty()
		{
			return false;
		}

		selectedIds(entityList)
		{
			if (Array.isArray(entityList) && entityList.length > 0)
			{
				return entityList.filter(({ deleted }) => !deleted).map(({ id }) => id);
			}

			return [];
		}
	}

	ClientField.propTypes = {
		...BaseField.propTypes,
		permissions: PropTypes.object, // { [entityTypeName]: { read: boolean, add: boolean } }
		canFocusTitle: PropTypes.bool,
		analytics: PropTypes.object,

		config: PropTypes.shape({
			// base field props
			showAll: PropTypes.bool, // show more button with count if it's multiple
			styles: PropTypes.shape({
				externalWrapperBorderColor: PropTypes.string,
				externalWrapperBorderColorFocused: PropTypes.string,
				externalWrapperBackgroundColor: PropTypes.string,
				externalWrapperMarginHorizontal: PropTypes.number,
			}),
			deepMergeStyles: PropTypes.object, // override styles
			parentWidget: PropTypes.object, // parent layout widget
			copyingOnLongClick: PropTypes.bool,
			titleIcon: PropTypes.object,

			categoryParams: PropTypes.object,
			showClientInfo: PropTypes.bool,
			showClientType: PropTypes.bool,
			showClientAdd: PropTypes.bool,
			enableMyCompanyOnly: PropTypes.bool,
			compound: PropTypes.array,

			clientLayout: PropTypes.number,
			fixedLayoutType: PropTypes.string,
			entityDetailOpener: PropTypes.object,
			options: PropTypes.object,
			owner: PropTypes.object,

			entityList: PropTypes.object, // { [entityTypeName]: { title: string, icon: string } }
		}),
	};

	ClientField.defaultProps = {
		...BaseField.defaultProps,
		permissions: {},
		canFocusTitle: false,
		analytics: null,

		config: {
			...BaseField.defaultProps.config,
			showClientInfo: false,
			showClientType: true,
			showClientAdd: false,
			enableMyCompanyOnly: false,
		},
	};

	module.exports = {
		ClientType: 'client',
		ClientFieldClass: ClientField,
		ClientField: (props) => new ClientField(props),
	};
});
