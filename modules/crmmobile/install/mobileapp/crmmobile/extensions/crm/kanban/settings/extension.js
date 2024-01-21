/**
 * @module crm/kanban/settings
 */
jn.define('crm/kanban/settings', (require, exports, module) => {
	const { Type } = require('type');
	const { Haptics } = require('haptics');
	const { isEqual } = require('utils/object');
	const { NotifyManager } = require('notify-manager');

	const {
		KanbanSettings,
		Stage,
	} = require('layout/ui/kanban/settings');
	const { CategoryPermissions } = require('crm/category-permissions');
	const { CrmStageList } = require('crm/stage-list');

	const { connect } = require('statemanager/redux/connect');
	const {
		selectStatus,
		selectById,
		fetchCrmKanban,
		selectStagesIdsBySemantics,
		updateCrmKanban,
		deleteCrmKanban,
	} = require('crm/statemanager/redux/slices/kanban-settings');
	const {
		createCrmStage,
	} = require('crm/statemanager/redux/slices/stage-settings');

	/**
	 * @class CrmKanbanSettings
	 */
	class CrmKanbanSettings extends KanbanSettings
	{
		static open(params, parentWidget = PageManager)
		{
			return new Promise((resolve, reject) => {
				parentWidget
					.openWidget('layout', this.getWidgetParams())
					.then((layout) => {
						layout.enableNavigationBarBorder(false);
						layout.showComponent(connect(mapStateToProps, mapDispatchToProps)(this)({ layout, ...params }));
						resolve(layout);
					})
					.catch(reject);
			});
		}

		get access()
		{
			return BX.prop.get(this.props, 'access', null);
		}

		get entityTypeId()
		{
			return BX.prop.getInteger(this.props, 'entityTypeId', 0);
		}

		get documentFields()
		{
			return BX.prop.getObject(this.props, 'documentFields', {});
		}

		get tunnelsEnabled()
		{
			return BX.prop.getBoolean(this.props, 'tunnelsEnabled', false);
		}

		get categoriesEnabled()
		{
			return BX.prop.getBoolean(this.props, 'categoriesEnabled', true);
		}

		get tunnels()
		{
			return BX.prop.getArray(this.props, 'tunnels', []);
		}

		get categoryId()
		{
			return BX.prop.getNumber(this.props, 'categoryId', null);
		}

		get isDefault()
		{
			return BX.prop.getBoolean(this.props, 'isDefault', false);
		}

		componentDidMount()
		{
			if (this.stageIdsBySemantics.processStages.length === 0 && Number.isInteger(this.categoryId))
			{
				this.navigationLoader.setLoading(true);
				this.props.fetchCrmKanban({
					entityTypeId: this.entityTypeId,
					categoryId: this.categoryId,
				}).then(() => {
					this.navigationLoader.setLoading(false);
				}).catch(() => {
					this.navigationLoader.setLoading(false);
					NotifyManager.showDefaultError();
				});
			}

			super.componentDidMount();
		}

		renderStageList()
		{
			return new CrmStageList({
				tunnels: this.tunnels,
				readOnly: false,
				canMoveStages: true,
				stageParams: {
					showTunnels: this.tunnelsEnabled,
				},
				onStageMove: (processStages) => {
					this.changedFields = {
						...this.changedFields,
						stageIdsBySemantics: {
							...this.stageIdsBySemantics,
							processStages,
						},
					};
					this.setState({});
				},
				onOpenStageDetail: this.onOpenStageDetail,
				kanbanSettingsId: this.kanbanSettingsId,
				stageIdsBySemantics: this.changedFields.stageIdsBySemantics || this.stageIdsBySemantics,
			});
		}

		renderCategoryName()
		{
			if (!this.categoriesEnabled)
			{
				return null;
			}

			return super.renderCategoryName();
		}

		renderDeleteButton()
		{
			if (!this.categoriesEnabled)
			{
				return null;
			}

			return super.renderDeleteButton();
		}

		getTitleForNavigation()
		{
			let name = '';
			if ('name' in this.changedFields)
			{
				name = this.changedFields.name;
			}
			else
			{
				name = this.kanbanSettingsName;
			}

			if (!name)
			{
				return BX.message('CATEGORY_DETAIL_FUNNEL_NOT_LOADED2');
			}

			if (!this.categoriesEnabled)
			{
				return name;
			}

			return (
				name === ''
					? BX.message('CRM_CATEGORY_DETAIL_FUNNEL_EMPTY2')
					: BX.message('CRM_CATEGORY_DETAIL_FUNNEL2').replace('#CATEGORY_NAME#', name)
			);
		}

		onLayoutClose()
		{
			// todo remove after refactoring entity-tab
			// BX.postComponentEvent('Crm.CategoryDetail::onClose', [{ ...this.state }]);
			super.onLayoutClose();
		}

		renderContent()
		{
			return View(
				{
					onClick: () => Keyboard.dismiss(),
					onPan: () => Keyboard.dismiss(),
				},
				this.renderCategoryName(),
				this.renderPermissions(),
				this.renderStageList(),
				this.renderStageButtons(),
				!this.isDefault && this.renderDeleteButton(),
			);
		}

		renderPermissions()
		{
			return new CategoryPermissions({
				access: this.access,
				categoriesEnabled: this.categoriesEnabled,
				entityTypeId: this.entityTypeId,
				kanbanSettingsId: this.kanbanSettingsId,
				layout: this.layout,
				onChange: (access) => {
					this.changedFields.access = access;
				},
			});
		}

		async openStageDetail({ id })
		{
			const { CrmKanbanStageSettings } = await requireLazy('crm:kanban/stage-settings') || {};

			if (CrmKanbanStageSettings)
			{
				await CrmKanbanStageSettings.open(
					{
						entityTypeId: this.entityTypeId,
						categoryId: this.categoryId,
						stageId: id,
						kanbanSettingsId: this.kanbanSettingsId,
						tunnelsEnabled: this.tunnelsEnabled,
						documentFields: this.documentFields,
					},
					this.layout,
				);
			}
		}

		save()
		{
			return new Promise((resolve, reject) => {
				if (this.changedFields.name && !Type.isStringFilled(this.changedFields.name))
				{
					Haptics.notifyWarning();

					if (this.scrollViewRef)
					{
						this.scrollViewRef.scrollToBegin(true);
					}

					if (this.categoryNameRef)
					{
						this.categoryNameRef.focus();
					}

					reject();

					return;
				}

				NotifyManager.showLoadingIndicator();

				this.props.updateCrmKanban(
					{
						entityTypeId: this.entityTypeId,
						kanbanSettingsId: this.kanbanSettingsId,
						categoryId: this.categoryId,
						fields: {
							id: this.categoryId,
							...this.changedFields,
						},
					},
				)
					.unwrap()
					.then(() => {
						NotifyManager.hideLoadingIndicator(true);
						resolve();
					})
					.catch((errors) => {
						NotifyManager.showDefaultError();
						reject(errors);
					});
			});
		}

		hasChangedFields()
		{
			const hasNameChanger = (typeof this.changedFields.name === 'string')
				&& this.changedFields.name !== this.kanbanSettingsName;
			const hasStageSortChanged = this.changedFields.stageIdsBySemantics
				&& !isEqual(
					this.changedFields.stageIdsBySemantics.processStages,
					this.stageIdsBySemantics.processStages,
				);
			const hasAccessChanged = this.changedFields.access && this.changedFields.access !== this.access;

			return hasNameChanger || hasStageSortChanged || hasAccessChanged;
		}

		createStage(stageSemantics, semanticsType)
		{
			const stage = new Stage({ semantics: stageSemantics });
			const { name, semantics, color } = stage;
			const stagesBySemantics = this.stageIdsBySemantics[semanticsType];
			const lastStageId = stagesBySemantics[stagesBySemantics.length - 1];

			return new Promise((resolve, reject) => {
				this.props.createCrmStage(
					{
						entityTypeId: this.entityTypeId,
						categoryId: this.categoryId,
						fields: {
							name,
							semantics,
							color,
						},
						stageId: lastStageId,
						kanbanSettingsId: this.kanbanSettingsId,
					},
				)
					.unwrap()
					.then((response) => {
						NotifyManager.hideLoadingIndicator(
							true,
							BX.message('CATEGORY_DETAIL_SUCCESS_CREATION'),
							1000,
						);
						setTimeout(() => resolve(response.data), 1300);
					})
					.catch(() => {
						NotifyManager.showDefaultError();
						reject();
					});
			});
		}

		deleteCategory(kanbanSettingsId)
		{
			return new Promise((resolve, reject) => {
				NotifyManager.showLoadingIndicator();
				this.props.deleteCrmKanban(
					{
						entityTypeId: this.entityTypeId,
						categoryId: this.categoryId,
						kanbanSettingsId,
					},
				)
					.unwrap()
					.then(() => {
						if (this.props.onDeleteCategory)
						{
							this.props.onDeleteCategory(this.kanbanSettingsId);
						}

						BX.postComponentEvent('Crm.CategoryDetail::onDeleteCategory', [this.kanbanSettingsId]);
						NotifyManager.hideLoadingIndicator(true);
						resolve();
					})
					.catch((rejectedValueOrSerializedError) => {
						NotifyManager.hideLoadingIndicator(true);
						NotifyManager.showErrors(rejectedValueOrSerializedError.errors);
						reject();
					})
				;
			});
		}
	}

	const mapStateToProps = (state, ownProps) => {
		const {
			name,
			categoryId,
			access,
			documentFields,
			tunnelsEnabled,
			categoriesEnabled,
			tunnels,
			isDefault,
		} = selectById(state, ownProps.kanbanSettingsId) || {};

		return {
			name,
			categoryId,
			access,
			documentFields,
			tunnelsEnabled,
			categoriesEnabled,
			tunnels,
			status: selectStatus(state),
			stageIdsBySemantics: selectStagesIdsBySemantics(state, ownProps.kanbanSettingsId),
			isDefault,
		};
	};

	const mapDispatchToProps = ({
		updateCrmKanban,
		createCrmStage,
		deleteCrmKanban,
		fetchCrmKanban,
	});

	module.exports = {
		CrmKanbanSettings,
	};
});
