/**
 * @module layout/ui/fields/entity-selector
 */
jn.define('layout/ui/fields/entity-selector', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { chevronDown, pen } = require('assets/common');
	const { Icon } = require('assets/icons');
	const { Haptics } = require('haptics');
	const { BaseField } = require('layout/ui/fields/base');
	const { isEqual, clone } = require('utils/object');
	const { isNil } = require('utils/type');
	const { EntitySelectorFactory, EntitySelectorFactoryType } = require('selector/widget/factory');
	const { PropTypes } = require('utils/validation');

	const CastType = {
		STRING: 'string',
		INT: 'int',
	};

	const MAX_VISIBLE_ENTITY = 3;

	/**
	 * @typedef {Object} EntitySelectorFieldProps
	 * @property {boolean} [withRedux=false]
	 *
	 * @class EntitySelectorField
	 */
	class EntitySelectorField extends BaseField
	{
		constructor(props)
		{
			super(props);
			this.initSelectedIds = null;
			this.state.entityList = this.prepareEntityListFromConfig(this.getItemsFromProps(this.getConfig()));
			this.state.showAll = true;
			this.openSelector = this.openSelector.bind(this);
			this.onSelectorHidden = this.onSelectorHidden.bind(this);
			this.layout = null;
			this.removeEntity = this.removeEntity.bind(this);
		}

		/**
		 * @private
		 * @return {array}
		 */
		getItemsFromProps(config)
		{
			const items = BX.prop.getArray(config, 'items', []);
			if (items.length > 0)
			{
				return items;
			}

			return BX.prop.getArray(config, 'entityList', []);
		}

		getEntityList()
		{
			return this.state.entityList;
		}

		componentWillReceiveProps(newProps)
		{
			super.componentWillReceiveProps(newProps);

			const reloadEntityListFromProps = BX.prop.getBoolean(
				BX.prop.getObject(newProps, 'config', {}),
				'reloadEntityListFromProps',
				false,
			);
			if (reloadEntityListFromProps)
			{
				this.state.entityList = this.getItemsFromProps(newProps.config);
			}
		}

		getConfig()
		{
			const config = super.getConfig();

			const entityIds = BX.prop.getArray(config, 'entityIds', null);
			const isComplex = BX.prop.getBoolean(config, 'isComplex', entityIds !== null);

			return {
				...config,
				selectorTitle: BX.prop.getString(config, 'selectorTitle', ''),
				selectorType: BX.prop.getString(config, 'selectorType', ''),
				castType: BX.prop.getString(config, 'castType', CastType.INT),
				provider: {
					...BX.prop.getObject(config, 'provider', {}),
					context: BX.prop.getString(config.provider, 'context', ''),
				},
				enableCreation: BX.prop.getBoolean(config, 'enableCreation', false),
				closeAfterCreation: BX.prop.getBoolean(config, 'closeAfterCreation', true),
				reloadEntityListFromProps: BX.prop.getBoolean(config, 'reloadEntityListFromProps', false),
				entityList: this.getItemsFromProps(config),
				canUnselectLast: BX.prop.getBoolean(config, 'canUnselectLast', true),
				canUseRecent: BX.prop.getBoolean(config, 'canUseRecent', true),
				getNonSelectableErrorText: BX.prop.getFunction(config, 'getNonSelectableErrorText'),
				entityIds,
				isComplex,
			};
		}

		getValuesArray()
		{
			const value = this.getValue();

			if (this.isMultiple())
			{
				return value;
			}

			return this.isEmptyValue(value) ? [] : [value];
		}

		shouldShowEditIcon()
		{
			return BX.prop.getBoolean(this.props, 'showEditIcon', true);
		}

		shouldShowChevronDown()
		{
			return BX.prop.getBoolean(this.props, 'showChevronDown', false);
		}

		getValueFromEntityList(entityList)
		{
			const value = this.prepareEntityList(entityList).map((entity) => entity.id);

			if (!this.isMultiple())
			{
				return value[0] || null;
			}

			return value;
		}

		prepareEntityList(entityList)
		{
			const list = clone(entityList).filter((entity) => BX.type.isPlainObject(entity) && !isNil(entity.id));

			if (this.getConfig().castType === CastType.STRING)
			{
				return list.map((entity) => ({ ...entity, id: String(entity.id) }));
			}

			return (
				list
					.map((entity) => ({ ...entity, id: Number(entity.id) }))
					.filter((entity) => !Number.isNaN(entity.id))
			);
		}

		prepareEntityListFromConfig(entityList)
		{
			const entitiesIds = this.getEntitiesIds();
			if (entitiesIds)
			{
				return (
					this.prepareEntityList(entityList)
						.filter((entity) => entitiesIds.includes(entity.id))
				);
			}

			return [];
		}

		getEntitiesIds()
		{
			let values = this.getValuesArray();

			if (Array.isArray(values))
			{
				values = clone(values).filter((value) => !isNil(value));

				if (this.getConfig().castType === CastType.STRING)
				{
					return values.map((value) => String(value));
				}

				return (
					values
						.map((value) => Number(value))
						.filter((value) => !Number.isNaN(value))
				);
			}

			return [];
		}

		/**
		 * @public
		 * @return {boolean}
		 */
		isEmpty()
		{
			return this.state.entityList.length === 0;
		}

		renderReadOnlyContent()
		{
			if (this.isEmpty())
			{
				return this.renderEmptyContent();
			}

			return this.renderEntityContent();
		}

		renderEditableContent()
		{
			if (this.isEmpty())
			{
				return this.renderEmptyEntity();
			}

			return this.renderEntityContent();
		}

		renderEmptyEntity()
		{
			return View(
				{
					style: {
						flex: 1,
						flexDirection: 'row',
						alignItems: 'center',
					},
				},
				Text({
					style: this.styles.emptyValue,
					numberOfLines: 1,
					ellipsize: 'end',
					text: this.getEmptyText(),
				}),
				this.shouldShowChevronDown() && Image({
					tintColor: AppTheme.colors.base3,
					style: {
						height: 5,
						width: 7,
						marginTop: 4,
						marginLeft: 5,
					},
					svg: {
						content: chevronDown(),
					},
				}),
			);
		}

		/**
		 * @private
		 * @return {string}
		 */
		getEditableEmptyValue()
		{
			return BX.message('FIELDS_SELECTOR_CONTROL_SELECT');
		}

		renderEntityContent()
		{
			let showAllButton = null;
			if (this.isMultiple())
			{
				const hiddenEntitiesCount = (this.getVisibleEntities() || this.getValue()).filter((
					item,
					index,
				) => index > 3).length;
				showAllButton = this.renderShowAllButton(hiddenEntitiesCount);
			}

			return View(
				{
					style: {
						flexDirection: 'column',
						flex: 1,
					},
				},
				View(
					{
						style: {
							flexDirection: 'row',
						},
					},
					View(
						{
							style: this.styles.entityContent,
						},
						...this.renderEntities(this.getVisibleEntities()),
					),
					this.shouldShowChevronDown() && Image({
						style: {
							height: 5,
							width: 7,
							marginTop: 9,
							marginLeft: 5,
						},
						svg: {
							content: chevronDown(),
						},
					}),
				),
				showAllButton,
			);
		}

		getVisibleEntities()
		{
			if (this.props.showHiddenEntities === false)
			{
				return this.state.entityList.filter((entity) => !entity.hidden);
			}

			return this.state.entityList;
		}

		renderEntities(entityList)
		{
			return entityList.map((entity, index) => {
				if (!entity.title)
				{
					entity.hidden = true;
				}

				if (!this.state.showAll && index > MAX_VISIBLE_ENTITY)
				{
					return null;
				}

				const isLastEntity = entityList.length - 1 === index;
				const showDot = (this.isMultiple() && !isLastEntity) || (!this.state.showAll && index === MAX_VISIBLE_ENTITY);

				return this.renderEntity(entity, showDot);
			});
		}

		renderEntity(entity, showDot = false)
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center',
					},
				},
				Text({
					style: this.styles.value,
					numberOfLines: 1,
					ellipsize: 'end',
					text: this.getEntityTitle(entity),
				}),
				showDot && View({
					style: {
						padding: 2.5,
						borderRadius: 2.5,
						marginLeft: 5,
						marginRight: 5,
						backgroundColor: AppTheme.colors.base4,
					},
				}),
			);
		}

		handleAdditionalFocusActions()
		{
			return this.openSelector();
		}

		getValueWhileReady()
		{
			return new Promise((resolve) => {
				if (this.isComplexSelector())
				{
					resolve(this.getSelectedIds());
				}
				else
				{
					resolve(super.getValue());
				}
			});
		}

		canOpenEntity()
		{
			return false;
		}

		openEntity(id)
		{}

		openSelector(forceSelectorType = false)
		{
			const {
				provider,
				enableCreation,
				closeAfterCreation,
				canUnselectLast,
				canUseRecent,
				selectorTitle,
				useLettersForEmptyAvatar,
				getNonSelectableErrorText,
				analytics,
			} = this.getConfig();

			let {
				selectorType,
			} = this.getConfig();

			if (forceSelectorType !== false)
			{
				selectorType = forceSelectorType;
			}

			provider.options ||= {};
			provider.options = {
				...provider.options,
				useLettersForEmptyAvatar: Boolean(useLettersForEmptyAvatar),
			};

			return (
				EntitySelectorFactory
					.createByType(selectorType, {
						provider,
						canUseRecent,
						createOptions: {
							enableCreation,
							closeAfterCreation,
							analytics,
							getParentLayout: () => this.layout,
						},
						selectOptions: {
							canUnselectLast,
							getNonSelectableErrorText,
						},
						entityIds: this.getEntityTypeIds(),
						initSelectedIds: this.getSelectedIds(),
						allowMultipleSelection: this.isMultiple(),
						events: {
							onCreate: this.onCreateEntity.bind(this),
							onClose: (currentEntities) => {
								this.currentEntities = currentEntities;
							},
							onViewHidden: () => {
								this.removeFocus().then(() => {
									if (this.currentEntities)
									{
										this.updateSelectorState(this.currentEntities);
										this.currentEntities = null;
									}
								}).catch(console.error);
							},
							onViewHiddenStrict: this.onSelectorHidden,
						},
						widgetParams: {
							titleParams: {
								text: selectorTitle,
							},
							backdrop: {
								mediumPositionPercent: 70,
								horizontalSwipeAllowed: false,
							},
						},
					})
					.show({}, this.getParentWidget())
					.then((widget) => {
						this.layout = widget;
					})
			);
		}

		onSelectorHidden()
		{
			if (this.props.onSelectorHidden)
			{
				this.props.onSelectorHidden(this);
			}
		}

		onRestrictionHidden()
		{
			this.onSelectorHidden();
		}

		isComplexSelector()
		{
			const { isComplex } = this.getConfig();
			if (isComplex)
			{
				return true;
			}

			const entityTypeIds = this.getEntityTypeIds();

			return Array.isArray(entityTypeIds) && entityTypeIds.length > 0;
		}

		getEntityTypeIds()
		{
			return this.getConfig().entityIds;
		}

		getSelectedIds()
		{
			if (this.isComplexSelector())
			{
				return this.getVisibleEntities().map(({ id, type }) => (type && id) && [type, id]).filter(Boolean);
			}

			return this.getVisibleEntities().map((entity) => entity.id);
		}

		/**
		 * @public
		 * @param {object} entities
		 */
		updateSelectorState(entities)
		{
			if (this.isMultiple())
			{
				this.handleMultipleChoice(entities);
			}
			else
			{
				this.handleSingleChoice(entities[0]);
			}
		}

		handleMultipleChoice(entities)
		{
			this.state.showAll = true;
			this.setStateEntityList(entities);
		}

		handleSingleChoice(entity)
		{
			const hasNextEntity = Boolean(entity) && !isNil(entity.id);

			if (hasNextEntity)
			{
				this.setStateEntityList([entity]);
			}
			else
			{
				this.setStateEntityList([]);
			}
		}

		onCreateEntity(entity)
		{
			return null;
		}

		shouldUseState()
		{
			return this.props.useState ?? true;
		}

		setStateEntityList(entities)
		{
			const entityList = this.prepareEntityList(entities);
			const { entityList: stateEntityList } = this.state;

			if (isEqual(stateEntityList, entityList))
			{
				return;
			}

			const setState = () => {
				const entityListValue = this.getValueFromEntityList(entities);

				if (this.shouldUseState())
				{
					this.setState(
						{ entityList },
						() => this.handleChange(entityListValue, entityList),
					);
				}
				else
				{
					this.handleChange(entityListValue, entityList);
				}

				if (this.withRedux())
				{
					this.setReduxState(entityList);
				}
			};

			if (this.hasDifferentIds(stateEntityList, entityList))
			{
				Haptics.impactLight();
				this.onBeforeHandleChange()
					.then(() => setState())
					.catch(console.error);
			}
			else
			{
				setState();
			}
		}

		setReduxState(entityList)
		{}

		hasDifferentIds(source, target)
		{
			const sourceIds = source.map((entity) => String(entity.id));
			const targetIds = target.map((entity) => String(entity.id));

			return !isEqual(sourceIds, targetIds);
		}

		renderEditIcon()
		{
			if (this.props.editIcon)
			{
				return this.props.editIcon;
			}

			if (this.isEmpty() && this.hasHiddenEmptyView())
			{
				return View(
					{
						style: {
							justifyContent: 'center',
							alignItems: 'center',
							width: 16,
							height: 16,
							marginLeft: 2,
						},
					},
					Image(
						{
							style: {
								height: 5,
								width: 7,
							},
							svg: {
								content: chevronDown(this.getTitleColor()),
							},
						},
					),
				);
			}

			return View(
				{
					style: {
						width: 24,
						height: 24,
						justifyContent: 'center',
						alignItems: 'center',
						alignSelf: 'flex-start',
						marginLeft: 5,
						marginTop: (this.isLeftTitlePosition() ? undefined : 15),
					},
				},
				Image(
					{
						style: {
							height: 15,
							width: 14,
						},
						svg: {
							content: pen(),
						},
					},
				),
			);
		}

		getDefaultStyles()
		{
			const styles = this.getChildFieldStyles();
			if (this.hasHiddenEmptyView())
			{
				return this.getHiddenEmptyChildFieldStyles(styles);
			}

			return styles;
		}

		getChildFieldStyles()
		{
			const styles = super.getDefaultStyles();
			const hasErrorMessage = this.hasErrorMessage();

			return {
				...styles,
				entityContent: {
					flexDirection: 'row',
					flexWrap: 'wrap',
					flexShrink: 2,
				},
				value: {
					...styles.value,
					flex: null,
					color: this.isReadOnly() && this.canOpenEntity() ? AppTheme.colors.accentMainLinks : styles.value.color,
				},
				wrapper: {
					...styles.wrapper,
					paddingBottom: hasErrorMessage ? 5 : 10,
				},
				readOnlyWrapper: {
					...styles.readOnlyWrapper,
					paddingBottom: hasErrorMessage ? 5 : 9,
				},
				title: {
					...styles.title,
					marginBottom: (this.isLeftTitlePosition() ? 0 : styles.title.marginBottom),
				},
			};
		}

		getHiddenEmptyChildFieldStyles(styles)
		{
			const isEmptyEditable = this.isEmptyEditable();
			const hasErrorMessage = this.hasErrorMessage();
			const isEmpty = this.isEmpty();
			const paddingBottomWithoutError = isEmpty ? 18 : 9;

			return {
				...styles,
				title: {
					...styles.title,
					marginBottom: isEmptyEditable ? 0 : styles.title.marginBottom,
				},
				innerWrapper: {
					flex: isEmptyEditable ? null : 1,
					flexShrink: 2,
				},
				container: {
					...styles.container,
					height: isEmptyEditable ? 0 : null,
				},
				wrapper: {
					...styles.wrapper,
					paddingTop: isEmpty ? 12 : 8,
					paddingBottom: hasErrorMessage ? 5 : paddingBottomWithoutError,
				},
			};
		}

		getSvgImages()
		{
			return {
				defaultAvatar: (color = AppTheme.colors.base4) => {
					return `<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M12 23.9989C18.6275 23.9989 24 18.6266 24 11.9995C24 5.37234 18.6275 0 12 0C5.37258 0 0 5.37234 0 11.9995C0 18.6266 5.37258 23.9989 12 23.9989Z" fill="${color}"/><path d="M17.5985 17.7266C18.2812 17.5141 18.6554 16.84 18.5169 16.1729L18.3223 15.2352C18.2245 14.6491 17.5047 13.9911 15.8947 13.6045C15.3492 13.4632 14.8307 13.2442 14.3576 12.9551C14.2542 12.9002 14.2699 12.3931 14.2699 12.3931L13.7514 12.3198C13.7514 12.2786 13.707 11.6704 13.707 11.6704C14.3275 11.4768 14.2636 10.3349 14.2636 10.3349C14.6576 10.5378 14.9142 9.63411 14.9142 9.63411C15.3803 8.37859 14.6822 8.4545 14.6822 8.4545C14.8043 7.68804 14.8043 6.90905 14.6822 6.14258C14.3718 3.6 9.69898 4.29025 10.2531 5.12064C8.88737 4.88706 9.199 7.77243 9.199 7.77243L9.49522 8.51962C9.08464 8.7669 9.16527 9.0507 9.25533 9.36771C9.29288 9.49987 9.33207 9.6378 9.33799 9.78127C9.3666 10.5013 9.84112 10.3521 9.84112 10.3521C9.87036 11.5405 10.5015 11.6952 10.5015 11.6952C10.62 12.4415 10.5461 12.3145 10.5461 12.3145L9.98451 12.3776C9.99211 12.5473 9.97722 12.7172 9.94017 12.8836C9.61386 13.0186 9.41409 13.1261 9.2163 13.2325C9.01381 13.3414 8.81339 13.4492 8.48141 13.5843C7.21353 14.1003 5.94196 14.3891 5.697 15.2925C5.64066 15.5002 5.55931 15.8574 5.48111 16.2352C5.34884 16.8741 5.72138 17.5055 6.37443 17.711C7.96659 18.2121 9.73498 18.5076 11.6013 18.5455H12.4216C14.2684 18.508 16.0194 18.2183 17.5985 17.7266Z" fill="white"/></svg>`;
				},
			};
		}

		getDisplayedValue()
		{
			if (this.isEmpty() || (this.isMultiple() && this.state.entityList.length > 1))
			{
				return this.getTitleText();
			}

			return this.state.entityList[0].title;
		}

		/**
		 * @public
		 * @return {{uri: string}|{icon: string|Icon}|{}}
		 */
		getLeftIcon()
		{
			if (this.isRestricted())
			{
				return {
					icon: Icon.LOCK,
				};
			}

			if (this.isEmpty() || this.isMultiple())
			{
				return {};
			}

			return {
				uri: this.state.entityList[0].imageUrl || this.getDefaultEntityAvatar(),
			};
		}

		getDefaultEntityAvatar()
		{
			return '';
		}

		getDefaultLeftIcon()
		{
			return this.getConfig().defaultLeftIcon || 'group';
		}

		/**
		 * @public
		 * @param {string} imageUrl
		 * @return {string|null}
		 */
		getImageUrl(imageUrl)
		{
			return null;
		}

		/**
		 * @public
		 * @return {string|null}
		 */
		getDefaultAvatar()
		{
			return null;
		}

		/**
		 * @public
		 * @return {string|null}
		 */
		getAddButtonText()
		{
			return null;
		}

		/**
		 * @public
		 * @param {string} entityId
		 * @param {string} type
		 */
		removeEntity(entityId, type)
		{
			const entityList = this.state.entityList.filter((entity) => !(entity.id === entityId && entity.type === type));

			this.updateSelectorState(entityList);
		}

		/**
		 * @public
		 * @param entity
		 * @return {string}
		 */
		getEntityTitle(entity)
		{
			return entity.title;
		}

		withRedux()
		{
			const { withRedux } = this.props;

			return Boolean(withRedux);
		}
	}

	EntitySelectorField.defaultProps = {
		...BaseField.defaultProps,
		showHiddenEntities: true,
		showChevronDown: false,
		withRedux: false,
		config: {
			...BaseField.defaultProps.config,
			selectorTitle: '',
			// selectorType: '',
			castType: CastType.INT,
			provider: {
				context: '',
			},
			enableCreation: false,
			closeAfterCreation: true,
			reloadEntityListFromProps: false,
			entityList: [],
			items: [],
			canUnselectLast: true,
			canUseRecent: true,
			// entityIds: null,
			isComplex: false,
		},
	};

	EntitySelectorField.propTypes = {
		...BaseField.propTypes,
		withRedux: PropTypes.bool,
		showChevronDown: PropTypes.bool,
		showHiddenEntities: PropTypes.bool,
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

			// entity selector props
			selectorTitle: PropTypes.string,
			selectorType: PropTypes.oneOf(Object.values(EntitySelectorFactoryType)),
			castType: PropTypes.oneOf(Object.values(CastType)),
			provider: PropTypes.object,
			enableCreation: PropTypes.bool,
			closeAfterCreation: PropTypes.bool,
			reloadEntityListFromProps: PropTypes.bool,
			entityList: PropTypes.array, // deprecated - use "items" for universal using of fields
			items: PropTypes.array, // same as entityList but for universal using of fields
			canUnselectLast: PropTypes.bool,
			canUseRecent: PropTypes.bool,
			getNonSelectableErrorText: PropTypes.func,
			entityIds: PropTypes.array,
			isComplex: PropTypes.bool,
			// string or Icon from assets/icons
			defaultLeftIcon: PropTypes.any,
		}),
	};

	module.exports = {
		EntitySelectorType: 'entity-selector',
		EntitySelectorFieldClass: EntitySelectorField,
		EntitySelectorField: (props) => new EntitySelectorField(props),
		CastType,
	};
});
