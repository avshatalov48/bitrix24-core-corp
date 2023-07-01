(() => {

	const { FieldFactory, CrmStageType, MoneyType, ClientType, StatusType } = jn.require('layout/ui/fields');
	const { clone } = jn.require('utils/object');
	const { Loc } = jn.require('loc');

	/**
	 * @class ListItems.Kanban
	 */
	class Kanban extends ListItems.Base
	{
		constructor(props)
		{
			super(props);

			this.state.columnId = this.getColumnId(props);

			this.forceUpdateCrmStagesHandler = this.forceUpdateCrmStages.bind(this);
		}

		componentWillReceiveProps(props)
		{
			this.state.columnId = this.getColumnId(props);
		}

		getColumnId(props, columnId = null)
		{
			if (columnId === null)
			{
				columnId = props.item.data.columnId;
			}

			if (!columnId || !columnId.length)
			{
				return null;
			}

			const column = this.params.columns.get(columnId);

			return column ? column.id : null;
		}

		updateColumnId(columnId)
		{
			this.setState(state => {
				if (state.columnId !== columnId)
				{
					return { columnId };
				}
			});
		}

		renderSubTitle(data)
		{
			return View(
				{
					style: {
						flex: 1,
						flexDirection: 'row',
					},
				},
				...this.getSubTitleComponents(data),
			);
		}

		getSubTitleComponents(data)
		{
			return [
				this.renderDate(data),
				this.renderSubTitleText(data),
			];
		}

		renderSubTitleText(data)
		{
			if (!data.subTitleText || data.subTitleText === '')
			{
				return null;
			}

			return Text({
				style: {
					...this.styles.date,
					paddingTop: 4,
					flexShrink: 2,
				},
				numberOfLines: 1,
				ellipsize: 'end',
				text: ', ' + data.subTitleText,
			});
		}

		renderContent(itemData)
		{
			return View(
				{
					style: {
						flexDirection: 'column',
					},
				},
				this.renderCrmStageField(itemData),
				super.renderContent(itemData),
			);
		}

		renderCrmStageField(data)
		{
			if (this.params.hasOwnProperty('categoryId') && data.columnId)
			{
				const emptyCallback = () => {
				};
				const column = this.params.columns.get(data.columnId);
				if (column)
				{
					const permissions = BX.prop.getObject(data, 'permissions', {});

					return FieldFactory.create(CrmStageType, {
						showTitle: false,
						value: this.state.columnId,
						readOnly: (!permissions.write || false),
						config: {
							entityId: data.id,
							entityTypeId: this.params.entityTypeId,
							categoryId: this.params.categoryId,
							data: {
								itemId: data.id,
							},
							uid: this.getUidForStageSliderField(data.id, data.columnId),
							useStageChangeMenu: true,
							showReadonlyNotification: true,
							animationMode: 'animateBeforeUpdate',
						},
						onChange: (this.params.onChange || emptyCallback),
						forceUpdate: this.forceUpdateCrmStagesHandler,
					});
				}
			}

			return null;
		}

		forceUpdateCrmStages(params)
		{
			const { columnId, data: { itemId: id } } = params;
			const itemsData = { id, columnId };
			this.props.modifyItemsListHandler([itemsData]);
		}

		renderSpecialFields(data)
		{
			const fields = [];

			if (data.money)
			{
				fields.push(FieldFactory.create(MoneyType, {
					title: this.getTotalSumFieldTitle(),
					value: data.money,
					readOnly: true,
					config: {
						largeFont: true,
						deepMergeStyles: this.getFieldDeepMergeStyles(),
					},
				}));
			}

			if (data.client && !data.client.hidden && ClientType)
			{
				const client = clone(data.client);
				this.prepareClientField(client);

				if (!client.hidden)
				{
					fields.push(FieldFactory.create(ClientType, {
						title: client.title || BX.message('SIMPLELIST_KANBAN_CLIENT'),
						value: client,
						readOnly: true,
						config: {
							entityList: client,
							deepMergeStyles: this.getFieldDeepMergeStyles(),
							owner: {
								id: data.id,
							},
						},
					}));
				}
			}

			return fields;
		}

		prepareClientField(client)
		{
			let isCompanyHidden = true;
			let isContactHidden = true;

			if (Array.isArray(client.company))
			{
				client.company = client.company.filter(item => item.hiddenInKanbanFields !== true);
				isCompanyHidden = (client.company.filter(company => !(company.hidden && company.title === '')).length === 0);
			}

			if (Array.isArray(client.contact))
			{
				client.contact = client.contact.filter(item => item.hiddenInKanbanFields !== true);
				isContactHidden = (client.contact.filter(contact => !(contact.hidden && contact.title === '')).length === 0);
			}

			if (isCompanyHidden && isContactHidden)
			{
				client.hidden = true;
			}
		}

		renderBottomSpecialFields(data)
		{
			const fields = [];
			if (Array.isArray(data.badges))
			{
				data.badges.forEach(badge => {
					fields.push(FieldFactory.create(StatusType, {
						title: badge.fieldName,
						value: [{
							name: badge.textValue,
							color: badge.textColor,
							backgroundColor: badge.backgroundColor,
						}],
						readOnly: true,
						config: {
							deepMergeStyles: this.getFieldDeepMergeStyles(),
						},
					}));
				});
			}

			return fields;
		}

		/**
		 * @param {Number} itemId
		 * @param {String} columnId
		 * @returns {string}
		 */
		getUidForStageSliderField(itemId, columnId)
		{
			return `stage-slider-item-${columnId}-${itemId}`;
		}

		getTotalSumFieldTitle()
		{
			if (!this.params.entityTypeName)
			{
				return '';
			}

			const code = 'SIMPLELIST_KANBAN_TOTAL_SUM';
			const entityCode = `${code}_${this.params.entityTypeName}`;

			return Loc.getMessage(entityCode) || Loc.getMessage(code);
		}

		prepareActions(actions)
		{
			const { permissions } = this.props.item.data;
			if (!permissions.delete)
			{
				const deleteAction = this.findAction(actions, 'delete');
				deleteAction.isDisabled = true;
			}

			const { counters } = this.props.item.data;
			if (!counters)
			{
				return;
			}

			const showActivityTabAction = this.findAction(actions, 'showActivityDetailTab');
			if (!showActivityTabAction)
			{
				return;
			}

			const activityCounterTotal = BX.prop.getNumber(
				counters,
				'activityCounterTotal',
				0,
			);
			let label = '';
			if (activityCounterTotal > 99)
			{
				label = '99+';
			}
			else if (activityCounterTotal > 0)
			{
				label = String(activityCounterTotal);
			}

			showActivityTabAction.label = label;
		}

		findAction(actions, id)
		{
			return actions.find(action => action.id === id);
		}
	}

	this.ListItems = this.ListItems || {};
	this.ListItems.Kanban = Kanban;
})();
