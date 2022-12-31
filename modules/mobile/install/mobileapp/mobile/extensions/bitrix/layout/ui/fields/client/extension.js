/**
 * @module layout/ui/fields/client
 */
jn.define('layout/ui/fields/client', (require, exports, module) => {

	const { Alert } = require('alert');
	const { magnifier } = require('assets/common');
	const { EventEmitter } = require('event-emitter');
	const { AddButton } = require('layout/ui/buttons/add-button');
	const { BaseField } = require('layout/ui/fields/base');
	const { ClientItem } = require('layout/ui/fields/client/elements');
	const { uniqBy, mergeBy } = require('utils/array');
	const { get, isEqual, isEmpty, mergeImmutable } = require('utils/object');
	const { stringify } = require('utils/string');

	let SelectorProcessing;
	let EntityDetailOpener;
	let Type;
	let TypeId;

	try
	{
		SelectorProcessing = require('crm/selector/utils/processing').SelectorProcessing;
		EntityDetailOpener = require('crm/entity-detail/opener').EntityDetailOpener;
		Type = require('crm/type').Type;
		TypeId = require('crm/type').TypeId;
	}
	catch (e)
	{
		console.warn(e);

		return;
	}

	const COMPANY_LAYOUT_NUMBER = 2;
	const CREATE = 'create';

	const { CRM_COMPANY, CRM_CONTACT } = EntitySelectorFactory.Type;

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
			this.isCreateContact = false;
			this.uid = Random.getString();

			this.state.clientValue = {};

			/** @type {EventEmitter} */
			this.customEventEmitter = EventEmitter.createWithUid(this.uid);
			this.parentCustomEventEmitter = EventEmitter.createWithUid(this.props.uid || this.uid);

			this.onEditClient = this.handleEditClient.bind(this);
			this.handleUpdateEntity = this.handleUpdateEntity.bind(this);
			this.handleCloseEntity = this.handleCloseEntity.bind(this);
		}

		componentDidMount()
		{
			super.componentDidMount();

			this.customEventEmitter.on('DetailCard::onClose', this.handleCloseEntity);
			this.customEventEmitter.on('DetailCard::onUpdate', this.handleUpdateEntity);
			this.customEventEmitter.on('Duplicate::onUpdate', this.handleUpdateEntity);
		}

		getConfig()
		{
			const config = super.getConfig();

			return {
				...config,
				...{
					categoryParams: BX.prop.getObject(config, 'categoryParams', {}),
					showClientInfo: BX.prop.getBoolean(config, 'showClientInfo', false),
					showClientAdd: BX.prop.getBoolean(config, 'showClientAdd', false),
					selectorTitle: '',
				},
			};
		}

		useHapticOnChange()
		{
			return true;
		}

		canFocusTitle()
		{
			return BX.prop.getBoolean(this.props, 'canFocusTitle', false);
		}

		isShowClientInfo()
		{
			return this.getConfig().showClientInfo;
		}

		isShowClientAdd(entityTypeName)
		{
			const { showClientAdd } = this.getConfig();

			if (!showClientAdd)
			{
				return false;
			}

			if (!entityTypeName)
			{
				const { entityList } = this.getConfig();
				return Object.keys(entityList).some((entityName) => this.checkPermissions(entityName, 'read'));
			}

			return this.checkPermissions(entityTypeName, 'read');
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

			return View({
					style: {
						flex: 1,
					},
				},
				...this.renderContacts(),
				...this.renderSelectors(),
			);
		}

		renderSelectors()
		{
			if (!this.isShowClientAdd())
			{
				return [];
			}

			const { entityList } = this.getConfig();
			const fixedLayoutType = this.getFixedLayoutType();

			if (this.isClientType(fixedLayoutType))
			{
				return [this.createSelector(fixedLayoutType)];
			}

			return Object.keys(entityList)
				.filter(this.isShowSelectorInDeal.bind(this))
				.map(this.createSelector.bind(this));
		}

		renderContacts()
		{
			if (this.isEmpty())
			{
				return [];
			}

			const {
				[CRM_COMPANY]: companies,
				[CRM_CONTACT]: contacts,
			} = this.getValue();

			const { clientLayout } = this.getConfig();
			const companyFirst = clientLayout === COMPANY_LAYOUT_NUMBER;
			const [first, second] = companyFirst ? [companies, contacts] : [contacts, companies];

			return [
				this.renderVisibleContacts(first),
				this.renderVisibleContacts(second),
			];
		}

		renderVisibleContacts(contacts)
		{
			const visibleContacts = this.getVisibleContacts(contacts);

			return View(
				{
					style: {
						flexDirection: 'column',
					},
				},
				...visibleContacts.map((contact, index) => {
					if (!contact.id && !contact.title && contact.hidden)
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
							onEdit: this.onEditClient,
							readOnly: this.isReadOnly(),
							showClientInfo: this.isShowClientInfo(),
							onOpenBackdrop: () => {
								this.handleOnOpenBackDrop(contact);
							},
							actionParams: {
								show: !index,
								onClick: this.onEditClient,
							},
						}),
					);
				}),
				this.renderShowAllButton(contacts.length - visibleContacts.length),
			);
		}

		getVisibleContacts(contacts)
		{
			if (!this.state.showAll)
			{
				return contacts.filter((item, index) => index < 4);
			}

			return contacts;
		}

		getValue()
		{
			const { clientValue } = this.state;
			const value = mergeImmutable(super.getValue(), clientValue);

			return {
				[CRM_COMPANY]: Array.isArray(value[CRM_COMPANY]) ? value[CRM_COMPANY] : [],
				[CRM_CONTACT]: Array.isArray(value[CRM_CONTACT]) ? value[CRM_CONTACT] : [],
			};
		}

		handleEditClient(type)
		{
			this.openClientSelector(type);
		}

		parseEntityDataFromQueryString(type, queryText = '')
		{
			if (!queryText)
			{
				return;
			}

			const emailMatch = queryText.match(/[A-Z0-9._%-]+@[A-Z0-9.-]+\.[A-Z]{2,63}/i);
			if (emailMatch)
			{
				const email = stringify(emailMatch[0]).trim();
				if (email !== '')
				{
					return {
						EMAIL: [{
							id: 'n' + Math.round(Math.random() * 1000),
							value: {
								VALUE: email,
								VALUE_TYPE: 'WORK',
							},
						}],
					};
				}
			}

			const phoneMatch = queryText.match(/^[+\d()]{6,}$/i);
			if (phoneMatch)
			{
				const phone = stringify(phoneMatch[0]).trim();
				if (phone !== '')
				{
					return {
						PHONE: [{
							id: 'n' + Math.round(Math.random() * 1000),
							value: {
								VALUE: phone,
								VALUE_TYPE: 'WORK',
							},
						}],
					};
				}
			}

			const entityName = type === CRM_COMPANY ? 'TITLE' : 'NAME';

			return {
				[entityName]: queryText,
			};

		}

		handleOnOpenBackDrop(params, method)
		{
			const type = params.type;
			this.isCreateContact = method === CREATE;
			if (!Type.existsByName(type) && !this.isReadOnly())
			{
				return false;
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

			EntityDetailOpener.open(
				{
					entityTypeId: Type.resolveIdByName(type),
					entityId: id || entityId,
					categoryId: this.getCategoryId(type),
					uid: this.uid,
					isCreationFromSelector: this.isCreateContact,
					closeOnSave: this.isCreateContact,
					tabsExternalData,
					owner: BX.prop.getObject(this.getConfig(), 'owner', {}),
				},
				{
					titleParams: { text: title },
				},
			);
		}

		handleUpdateEntity(params)
		{
			const { entityTypeId, entityId } = params;
			const selectorName = SELECTOR_TYPES_BY_ID[entityTypeId];

			if (!selectorName || !entityId)
			{
				return;
			}

			this
				.getClientInfo(selectorName, entityId)
				.then((entityData) => {
					const { [selectorName]: prevEntityList } = this.getValue();
					const entityList =
						Array.isArray(prevEntityList)
						&& !isEmpty(entityData)
						&& this.isMultipleSelector(selectorName)
							? mergeBy(prevEntityList, entityData, 'id')
							: [entityData];

					this.communicationUpdate({ [selectorName.toUpperCase()]: [entityData] });

					if (!this.isEqualEntities(selectorName, entityList))
					{
						if (!this.isCreateContact)
						{
							this.handleOnChange({
								[selectorName]: entityList,
							});
						}
						else
						{
							this.currentEntities = entityList;
							this.changeClientsList(selectorName);
						}
					}
					else if (!isEqual(entityList, prevEntityList))
					{
						this.setState({
							clientValue: {
								...this.state.clientValue,
								[selectorName]: entityList,
							},
						});
					}
				})
				.catch(console.error);
		}

		communicationUpdate(clientData)
		{
			this.parentCustomEventEmitter.emit('Communication::onUpdate', [clientData]);
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

		handleCloseEntity(params)
		{
			if (!this.isCreateContact)
			{
				return;
			}

			const { entityTypeId, entityId } = params;
			const selectorName = SELECTOR_TYPES_BY_ID[entityTypeId];
			if (!selectorName)
			{
				return;
			}

			if (entityId > 0)
			{
				this.isCreateContact = true;
				this.handleUpdateEntity(params);
			}
		}

		changeClientsByEntityType(entityType)
		{
			const { [entityType]: prevEntityList } = this.getValue();

			const currentIds = this.selectedIds(this.currentEntities);
			const prevIDs = this.selectedIds(prevEntityList);

			if (!isEqual(currentIds, prevIDs))
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
				svg: magnifier('#828b95'),
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
						color: '#525c69',
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
				onClick: () => this.openClientSelector(selectorType),
			});
		}

		openClientSelector(selectorType, value = {})
		{
			const { [selectorType]: prevEntityList } = this.getValue();

			this.currentEntities = Array.isArray(prevEntityList) && !isEmpty(value)
				? mergeBy(prevEntityList, value, 'id')
				: prevEntityList;

			const selector = EntitySelectorFactory.createByType(selectorType, {
				createOptions: {
					enableCreation: this.checkPermissions(selectorType, 'add'),
				},
				provider: this.makeClientSelectorProvider(selectorType),
				initSelectedIds: this.selectedIds(this.currentEntities),
				allowMultipleSelection: this.isMultipleSelector(selectorType),
				events: {
					onCreate: (createParams) => {
						this.handleOnOpenBackDrop(
							{
								...createParams,
								type: selectorType,
								entityId: null,
							},
							CREATE,
						);
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
			if (selectorType === CRM_COMPANY)
			{
				options.excludeMyCompany = true;
			}

			const categoryId = this.getCategoryId(selectorType);
			if (categoryId)
			{
				options.categoryId = categoryId;
			}

			return { options };
		}

		isShowSelectorInDeal(selectorType)
		{
			const isCompany = selectorType === CRM_COMPANY;
			const isContact = selectorType === CRM_CONTACT;
			const { [CRM_COMPANY]: company } = this.getValue();

			return isContact || (isCompany && isEmpty(company));
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
					const contacts = ENTITY_INFOS.length
						? ENTITY_INFOS.map(SelectorProcessing.prepareContact)
						: [];

					const clientData = {
						[CRM_CONTACT]: contacts,
						[CRM_COMPANY]: companies,
					};

					if (prevContacts.length && this.isContainInStateContacts(contacts))
					{
						this.showConfirmUpdateContacts(
							() => this.handleOnChange(clientData),
							() => this.handleOnChange({
								...clientData,
								[CRM_CONTACT]: uniqBy([...contacts, ...prevContacts], 'id'),
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
			let title, text, buttonTextOk, buttonTextCancel;

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
			return selectorType === CRM_CONTACT || this.isCompanyLayout();
		}

		getValueFromEntityList(entityList)
		{
			return entityList.map(({ id }) => Number(id)).filter(Boolean);
		}

		handleOnChange(value)
		{
			const currentValue = this.getValue();
			this.handleChange({ ...currentValue, ...value });
		}

		isContainInStateContacts(contacts)
		{
			const { [CRM_CONTACT]: prevContacts } = this.getValue();
			const contactsIds = contacts.map(({ id }) => id);
			const differenceArr = prevContacts.filter(({ id }) => !contactsIds.includes(id));
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

		checkPermissions(entityName, permissionType = 'read')
		{
			if (!entityName)
			{
				return false;
			}

			const permissions = get(this.getConfig(), ['permissions', entityName.toUpperCase(), permissionType], false);

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
				return entityList.map(({ id }) => id);
			}

			return [];
		}
	}

	module.exports = {
		ClientType: 'client',
		ClientField: (props) => new ClientField(props),
	};

});
