/**
 * @module crm/simple-list/items/crm-entity
 */
jn.define('crm/simple-list/items/crm-entity', (require, exports, module) => {
	const { Extended } = require('layout/ui/simple-list/items/extended');
	const { FieldFactory, CrmStageSelectorType, ClientType, StatusType } = require('layout/ui/fields');
	const { get } = require('utils/object');
	const { TabType } = require('layout/ui/detail-card/tabs/factory/type');
	const { CounterComponent } = require('layout/ui/kanban/counter');
	const { CommunicationButton } = require('crm/communication/button');
	const { Haptics } = require('haptics');

	/**
	 * @class CrmEntity
	 */
	class CrmEntity extends Extended
	{
		/**
		 * @param {Number} itemId
		 * @param {String} columnId
		 * @returns {string}
		 */
		static getUidForStageSliderField(itemId, columnId)
		{
			return `stage-slider-item-${columnId}-${itemId}`;
		}

		constructor(props)
		{
			super(props);

			this.state.columnId = this.getColumnId(props);

			this.forceUpdateCrmStagesHandler = this.forceUpdateCrmStages.bind(this);

			/** @var {CommunicationButton} */
			this.communicationButtonRef = null;
			this.showCommunicationButton = this.showCommunicationButton.bind(this);
			this.onChangeStageHandler = this.onChangeStage.bind(this);
		}

		componentWillReceiveProps(props)
		{
			this.state.columnId = this.getColumnId(props);
		}

		getColumnId(props)
		{
			const columnId = props.item.data.columnId;

			if (!columnId || columnId.length === 0)
			{
				return null;
			}

			const column = this.params.columns.get(columnId);

			return (column ? column.id : null);
		}

		updateColumnId(columnId)
		{
			if (columnId !== this.state.columnId)
			{
				this.setState({ columnId });
			}
		}

		getSubTitleComponents()
		{
			return [
				this.renderDate(),
				this.renderSubTitleText(),
			];
		}

		renderSubTitleText()
		{
			const { subTitleText } = this.props.item.data;
			if (!subTitleText || subTitleText === '')
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
				text: `, ${subTitleText}`,
			});
		}

		renderBody()
		{
			return View(
				{
					style: {
						flexDirection: 'column',
					},
				},
				this.renderCrmStageField(),
				View(
					{
						style: {
							flexDirection: 'row',
							alignItems: 'flex-start',
						},
					},
					View(
						{
							style: {
								flex: 1,
							},
						},
						this.renderFields('fields'),
						this.renderFields('userFields'),
						...this.renderBadges(),
					),
					this.renderRightBlock(),
				),
			);
		}

		renderCrmStageField()
		{
			const has = Object.prototype.hasOwnProperty;
			const { data } = this.props.item;

			if (has.call(this.params, 'categoryId') && data.columnId)
			{
				const permissions = BX.prop.getObject(data, 'permissions', {});

				return FieldFactory.create(CrmStageSelectorType, this.getStageProps(data, permissions));
			}

			return null;
		}

		getStageProps(data, permissions)
		{
			return {
				showTitle: false,
				value: this.state.columnId,
				columnCode: data.columnId,
				readOnly: (!permissions.write || false),
				entityTypeId: this.params.entityTypeId,
				categoryId: this.params.categoryId,
				showReadonlyNotification: true,
				config: {
					uid: CrmEntity.getUidForStageSliderField(data.id, data.columnId),
					useStageChangeMenu: true,
					showReadonlyNotification: true,
					animationMode: 'animateBeforeUpdate',
					parentWidget: this.layout,
					entityId: data.id,
				},
				onChange: this.onChangeStageHandler,
				forceUpdate: this.forceUpdateCrmStagesHandler,
			};
		}

		onChangeStage(stageId)
		{
			const emptyCallback = () => {};

			return this.params.onChangeItemStage
				? this.params.onChangeItemStage(stageId, {}, { itemId: this.props.item.id })
				: emptyCallback();
		}

		forceUpdateCrmStages(params)
		{
			const { columnId, data: { itemId: id } } = params;
			const itemsData = { id, columnId };
			this.props.modifyItemsListHandler([itemsData]);
		}

		/**
		 * @return {array}
		 */
		renderBadges()
		{
			const { data } = this.props.item;
			const badgeFields = [];

			if (data.badges && Array.isArray(data.badges))
			{
				data.badges.forEach((badge) => {
					badgeFields.push(
						FieldFactory.create(StatusType, {
							title: badge.fieldName,
							value: [{
								name: badge.textValue,
								color: badge.textColor,
								backgroundColor: badge.backgroundColor,
							}],
							readOnly: true,
							config: {
								deepMergeStyles: Extended.getFieldDeepMergeStyles(),
							},
						}),
					);
				});
			}

			return badgeFields;
		}

		renderRightBlock()
		{
			const useConnectsBlock = this.blockManager.can('useConnectsBlock');
			const useCountersBlock = this.blockManager.can('useCountersBlock');

			if (!useConnectsBlock && !useCountersBlock)
			{
				return null;
			}

			return View(
				{
					style: {
						justifyContent: 'center',
						flexDirection: 'column',
						alignItems: 'center',
						width: (Application.getPlatform() === 'android' ? 80 : 76),
					},
				},
				(useCountersBlock && this.renderCounterComponent()),
				(useConnectsBlock && this.renderConnectionComponent()),
			);
		}

		renderCounterComponent()
		{
			const { item, itemDetailOpenHandler, itemCounterLongClickHandler } = this.props;

			return new CounterComponent({
				...item.data.counters,
				onClick: itemDetailOpenHandler && (() => itemDetailOpenHandler(
					item.id,
					item.data,
					{ ...this.params, activeTab: TabType.TIMELINE },
				)),
				onLongClick: itemCounterLongClickHandler && (() => {
					Haptics.impactLight();
					itemCounterLongClickHandler('activity', item.id);
				}),
			});
		}

		renderConnectionComponent()
		{
			const { item } = this.props;
			const { isClientEnabled, entityTypeName: ownerTypeName } = this.params;

			if (typeof isClientEnabled === 'boolean' && !isClientEnabled)
			{
				return null;
			}

			const hasTelegramConnection = get(this.params, 'connectors.telegram', true);
			const openLinesAccess = get(this.params, 'entityPermissions.openLinesAccess', false);

			return View(
				{
					style: {
						flexDirection: 'column',
						alignItems: 'center',
						justifyContent: 'center',
						width: 76,
					},
					testId: 'CrmListItemCommunicationButton',
					onClick: this.showCommunicationButton,
				},
				new CommunicationButton({
					ref: (ref) => {
						this.communicationButtonRef = ref;
					},
					border: false,
					horizontal: false,
					showTelegramConnection: !hasTelegramConnection,
					value: item.data[ClientType],
					permissions: {
						openLinesAccess,
					},
					ownerInfo: {
						ownerId: item.id,
						ownerTypeName,
					},
				}),
			);
		}

		showCommunicationButton()
		{
			if (this.communicationButtonRef)
			{
				this.communicationButtonRef.showMenu();
			}
		}

		prepareActions(actions)
		{
			const { permissions } = this.props.item.data;
			if (!permissions.delete)
			{
				const deleteAction = actions.find((action) => action.id === 'delete');
				deleteAction.isDisabled = true;
			}

			const { counters } = this.props.item.data;
			if (!counters)
			{
				return;
			}

			const showActivityTabAction = actions.find((action) => action.id === 'showActivityDetailTab');
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
	}

	module.exports = { CrmEntity };
});
