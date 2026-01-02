/**
 * @module selector/widget/entity/intranet/department
 */
jn.define('selector/widget/entity/intranet/department', (require, exports, module) => {
	const { Loc } = require('loc');
	const { mergeImmutable, clone } = require('utils/object');
	const { BaseSelectorEntity } = require('selector/widget/entity');
	const { isModuleInstalled } = require('module');

	/**
	 * @class DepartmentSelector
	 */
	class DepartmentSelector extends BaseSelectorEntity
	{
		/**
		 * @returns {Object}
		 */
		static get selectModes()
		{
			return {
				MODE_DEPARTMENTS_ONLY: 'departmentsOnly',
				MODE_USERS_ONLY: 'usersOnly',
				MODE_USERS_AND_DEPARTMENTS: 'usersAndDepartments',
			};
		}

		/**
		 * @returns {string}
		 */
		static getEntityId()
		{
			return 'structure-node';
		}

		/**
		 * @returns {string}
		 */
		static getContext()
		{
			return 'mobile-department';
		}

		/**
		 * @returns {string}
		 */
		static getStartTypingText()
		{
			return Loc.getMessage('SELECTOR_COMPONENT_START_TYPING_TO_SEARCH_DEPARTMENT');
		}

		/**
		 * @returns {string}
		 */
		static getTitle()
		{
			return Loc.getMessage('SELECTOR_COMPONENT_PICK_DEPARTMENT');
		}

		/**
		 * @param {Object} providerOptions
		 * @param {Array} entityIds
		 * @returns {Array}
		 */
		static getEntitiesOptions(providerOptions, entityIds)
		{
			return [
				{
					id: entityIds[0],
					options: mergeImmutable(this.getDefaultProviderOption(), providerOptions),
					searchable: true,
					dynamicLoad: true,
					dynamicSearch: true,
				},
			];
		}

		/**
		 * @param {Object} providerOptions
		 * @param {Object} createOptions
		 * @returns {boolean}
		 */
		static isCreationEnabled(providerOptions, createOptions)
		{
			return false;
		}

		static canCreateWithEmptySearch()
		{
			return false;
		}

		static getCreateText()
		{
			return Loc.getMessage('SELECTOR_COMPONENT_CREATE_DEPARTMENT');
		}

		static getCreatingText()
		{
			return Loc.getMessage('SELECTOR_COMPONENT_CREATE_DEPARTMENT');
		}

		static getCreateEntityHandler(providerOptions, createOptions)
		{
			return async (text, allowMultipleSelection) => {
				if (!isModuleInstalled('intranet'))
				{
					return Promise.reject(new Error('Module "intranet" is not installed'));
				}

				const { CreateDepartment } = require('intranet/create-department');
				const { shouldCheckNeedToBeMemberOfNewDepartment, getParentLayout } = createOptions;

				return new Promise((resolve, reject) => {
					CreateDepartment.open({
						parentWidget: getParentLayout?.() ?? null,
						showToastAfterCreation: false,
						showToastAfterCreationError: false,
						shouldCheckNeedToBeMemberOfNewDepartment,
						onSave: (department) => {
							if (department)
							{
								resolve({
									...department,
									id: String(department.id),
								});
							}
							else
							{
								reject();
							}
						},
						onError: reject,
						onClose: reject,
					});
				});
			};
		}

		/**
		 * @returns {Object}
		 */
		static getDefaultProviderOption()
		{
			return {
				selectMode: this.selectModes.MODE_DEPARTMENTS_ONLY,
				allowOnlyUserDepartments: false,
				allowFlatDepartments: true,
				allowSelectRootDepartment: true,
				fillRecentTab: true,
				fillDepartmentsTab: false,
				depthLevel: 2,
			};
		}

		static make(props)
		{
			const originalProps = clone(props);
			const instance = super.make(mergeImmutable(props, {
				events: {
					onInfoIconClicked: ({ item, text, scope }) => {
						const { DepartmentInfo } = require('intranet/department-info');

						const departmentInfoInstance = DepartmentInfo.open({
							departmentId: item.id,
							parentWidget: instance.widget,
							onSelectButtonClick: (departmentId) => {
								const selectedDepartment = instance.getCurrentItems().find(
									(department) => String(department.id) === String(departmentId),
								);
								const selectedDepartments = instance.getCurrentSelectedItems();
								if (!selectedDepartments.some(
									(department) => String(department.id) === String(departmentId),
								))
								{
									let newSelectedDepartments = [];
									if (instance.getAllowMultipleSelection())
									{
										newSelectedDepartments = [
											...selectedDepartments,
											selectedDepartment,
										].map((department) => ({
											...department,
											id: String(department.id),
										}));
									}
									else
									{
										newSelectedDepartments = [{
											...selectedDepartment,
											id: String(selectedDepartment.id),
										}];
									}
									instance.setShouldSetInitiallySelectedItems(true);
									instance.onSelectedChangedListener({
										items: newSelectedDepartments,
									});
								}

								departmentInfoInstance.close();
							},
						});

						props?.events?.onInfoIconClicked?.({ item, text, scope });
					},
				},
			}));

			void DepartmentSelector.loadPermissions(instance, originalProps);

			return instance;
		}

		static async loadPermissions(selectorInstance, originalProps)
		{
			if (isModuleInstalled('intranet'))
			{
				const { fetchDepartmentPermissions } = require('intranet/create-department');
				await fetchDepartmentPermissions((response) => {
					if (!response || response?.status === 'error')
					{
						return;
					}

					const permissions = response.data;
					if (originalProps?.createOptions?.enableCreation !== false && permissions.canCreateNewDepartment)
					{
						selectorInstance.enableCreation(DepartmentSelector.getCreateEntityHandler(
							selectorInstance.provider.options.entities[0].options,
							selectorInstance.createOptions,
						));
					}
					else
					{
						selectorInstance.disableCreation();
					}
				});
			}
			else
			{
				selectorInstance.disableCreation();
			}
		}

		static prepareItemForDrawing(item)
		{
			return {
				...item,
				showInfoIcon: isModuleInstalled('intranet'),
			};
		}
	}

	module.exports = { DepartmentSelector };
});
