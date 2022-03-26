(() => {
	const GroupingType = {
		DEFAULT: 'DEFAULT',
		MODERATORS: 'MODERATORS'
	};

	const CastType = {
		STRING: 'string',
		INT: 'int'
	};

	/**
	 * @class Fields.EntitySelector
	 */
	class EntitySelector extends Fields.BaseField
	{
		constructor(props)
		{
			super(props);

			this.state.entityList = this.prepareEntityListFromConfig(this.getConfig().entityList);
		}

		getConfig()
		{
			const config = super.getConfig();

			return {
				...config,
				selectorTitle: BX.prop.getString(config, 'selectorTitle', ''),
				selectorType: BX.prop.getString(config, 'selectorType', ''),
				castType: BX.prop.getString(config, 'castType', CastType.INT),
				provider: {
					...BX.prop.getObject(config, 'provider', {}),
					context: BX.prop.getString(config.provider, 'context', '')
				},
				enableCreation: BX.prop.getBoolean(config, 'enableCreation', false),
				reloadEntityListFromProps: BX.prop.getBoolean(config, 'reloadEntityListFromProps', false),
				entityList: BX.prop.getArray(config, 'entityList', []),
				parentWidget: BX.prop.get(config, 'parentWidget', PageManager),
				groupingFrom: BX.prop.getInteger(config, 'groupingFrom', 0),
				groupingType: BX.prop.getString(config, 'groupingType', GroupingType.DEFAULT)
			};
		}

		showEditIcon()
		{
			return BX.prop.getBoolean(this.props, 'showEditIcon', true);
		}

		componentWillReceiveProps(newProps)
		{
			super.componentWillReceiveProps(newProps);

			if (this.getConfig().reloadEntityListFromProps)
			{
				const entityList = BX.prop.getArray(newProps.config, 'entityList', []);
				this.setState({entityList});
			}
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
			const list =
				Utils.objectClone(entityList)
					.filter((entity) => BX.type.isPlainObject(entity) && !this.isNil(entity.id))
			;

			if (this.getConfig().castType === CastType.STRING)
			{
				return list.map((entity) => {
					entity.id = String(entity.id);

					return entity;
				});
			}

			return (
				list
					.map((entity) => {
						entity.id = Number(entity.id);

						return entity;
					})
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
						.filter((entity) => entitiesIds.indexOf(entity.id) !== -1)
				);
			}

			return [];
		}

		isNil(value)
		{
			return value === undefined || value === null;
		}

		getEntitiesIds()
		{
			let values = this.isMultiple() ? this.props.value : [this.props.value];

			if (Array.isArray(values))
			{
				values = Utils.objectClone(values).filter((value) => !this.isNil(value));

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
						alignItems: 'center'
					}
				},
				Text({
					style: this.styles.emptyValue,
					numberOfLines: 1,
					ellipsize: 'end',
					text: BX.message('FIELDS_SELECTOR_CONTROL_SELECT')
				})
			);
		}

		renderEntityContent()
		{
			if (
				this.getConfig().groupingFrom
				&& this.state.entityList.length >= this.getConfig().groupingFrom
			)
			{
				return this.renderGroupedEntities();
			}

			return this.renderUngroupedEntities();
		}

		renderGroupedEntities()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center'
					},
				},
				Text({
					style: this.styles.entityText,
					numberOfLines: 1,
					ellipsize: 'end',
					text: this.getGroupedEntitiesText(),
				})
			);
		}

		getGroupedEntitiesText()
		{
			const entitiesCount = this.state.entityList.length;
			let groupingType = this.getConfig().groupingType;
			groupingType = (Object.keys(GroupingType).includes(groupingType) ? groupingType : GroupingType.DEFAULT);

			const pluralForm = Utils.getPluralForm(entitiesCount);
			const message = BX.message(`FIELDS_SELECTOR_GROUPING_TYPE_${groupingType}_PLURAL_${pluralForm}`);

			return message.replace('#COUNT#', entitiesCount);
		}

		renderUngroupedEntities()
		{
			return View(
				{
					style: this.styles.entityContent
				},
				...this.prepareEntities()
			);
		}

		prepareEntities()
		{
			if (this.isReadOnly())
			{
				return this.renderEntities(this.getConfig().entityList);
			}

			return this.renderEntities(this.state.entityList);
		}

		renderEntities(entityList)
		{
			return entityList.map((entity, index) => {
				const showDot = this.isMultiple() && entityList.length - 1 !== index;
				return this.renderEntity(entity, showDot);
			});
		}

		renderEntity(entity, showDot = false)
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center'
					},
				},
				Text({
					style: this.styles.entityText,
					numberOfLines: 1,
					ellipsize: 'end',
					text: entity.title
				}),
				showDot && View({
					style: {
						padding: 2.5,
						borderRadius: 2.5,
						marginLeft: 5,
						marginRight: 5,
						backgroundColor: '#a8adb4'
					}
				})
			);
		}

		focus()
		{
			super.focus();

			this.openSelector();
		}

		openEntity(id)
		{

		}

		openSelector()
		{
			const config = this.getConfig();
			const selector = EntitySelectorFactory.createByType(config.selectorType, {
				provider: config.provider,
				createOptions: {
					enableCreation: config.enableCreation
				},
				initSelectedIds: this.state.entityList.map(entity => entity.id),
				allowMultipleSelection: this.isMultiple(),
				events: {
					onClose: this.updateSelectorState.bind(this),
					onViewHidden: this.removeFocus.bind(this)
				},
				widgetParams: {
					title: this.getConfig().selectorTitle,
					backdrop: {
						mediumPositionPercent: 70
					}
				}
			});

			selector.show({}, this.getConfig().parentWidget);
		}

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

		hasArrayDifference(first, second)
		{
			if (first.length !== second.length)
			{
				return true;
			}

			const firstDiff = first.filter((id) => !second.includes(id));
			if (firstDiff.length)
			{
				return true;
			}

			const secondDiff = second.filter((id) => !first.includes(id));
			if (secondDiff.length)
			{
				return true;
			}

			return false;
		}

		handleMultipleChoice(entities)
		{
			const selectedIds = entities.map((entity) => entity.id);
			const currentIds = this.state.entityList.map((entity) => entity.id);

			if (this.hasArrayDifference(selectedIds, currentIds))
			{
				this.setState({entityList: this.prepareEntityList(entities)}, () => {
					this.handleChange(
						this.getValueFromEntityList(entities),
						this.prepareEntityList(entities)
					);
				});
			}
		}

		handleSingleChoice(entity)
		{
			const currentEntity = this.state.entityList[0];
			const hasNextEntity = Boolean(entity) && !this.isNil(entity.id);
			const hasCurrentEntity = Boolean(currentEntity) && !this.isNil(currentEntity.id);

			if (!hasCurrentEntity && !hasNextEntity)
			{
				return;
			}

			if (hasCurrentEntity && hasNextEntity)
			{
				const castType = this.getConfig().castType;

				if (
					(castType === CastType.STRING && String(entity.id) === String(currentEntity.id))
					|| (castType === CastType.INT && Number(entity.id) === Number(currentEntity.id))
				)
				{
					return;
				}
			}

			if (hasNextEntity)
			{
				this.setState({entityList: this.prepareEntityList([entity])}, () => {
					this.handleChange(
						this.getValueFromEntityList([entity]),
						this.prepareEntityList([entity])
					);
				});
			}
			else
			{
				this.setState({entityList: []}, () => {
					this.handleChange(null, []);
				});
			}
		}

		getDefaultStyles()
		{
			const styles = super.getDefaultStyles();

			return {
				...styles,
				entityContent: {
					flex: 1,
					flexDirection: 'row',
					flexWrap: 'wrap'
				},
				entityText: {
					color: this.isReadOnly() ? '#333333' : '#0b66c3',
					fontSize: this.isReadOnly() ? 19 : 16
				},
				wrapper: {
					paddingTop: 7,
					paddingBottom: this.hasErrorMessage() ? 5 : 10
				},
				readOnlyWrapper: {
					paddingTop: 7,
					paddingBottom: this.hasErrorMessage() ? 5 : 9
				},
			}
		}
	}

	this.Fields = this.Fields || {};
	this.Fields.EntitySelector = EntitySelector;
	this.Fields.EntitySelector.GroupingType = GroupingType;
})();
