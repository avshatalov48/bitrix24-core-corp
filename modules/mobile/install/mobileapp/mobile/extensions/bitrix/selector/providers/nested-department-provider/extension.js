/**
 * @module selector/providers/nested-department-provider
 */
jn.define('selector/providers/nested-department-provider', (require, exports, module) => {
	const { Loc } = require('loc');
	const { CommonSelectorProvider } = require('selector/providers/common');
	const { Type } = require('type');
	const { Color } = require('tokens');
	const { Icon } = require('assets/icons');
	const { withCurrentDomain } = require('utils/url');

	const ScopesIds = {
		RECENT: 'recent',
		DEPARTMENT: 'department',
	};

	/**
	 * @class NestedDepartmentProvider
	 */
	class NestedDepartmentProvider extends CommonSelectorProvider
	{
		constructor(context, options = {})
		{
			super(context);
			this.setOptions(options);

			this.preselectedItems = [];

			this.recentItems = null;
		}

		setOptions(options)
		{
			const preparedOptions = options || {};
			preparedOptions.entities = preparedOptions.entities || [];

			if (!Array.isArray(preparedOptions.entities))
			{
				preparedOptions.entities = Object.keys(preparedOptions.entities)
					.map((entityId) => ({
						...preparedOptions.entities[entityId],
						id: entityId,
					}));
			}

			this.options = preparedOptions;
			this.options.useLettersForEmptyAvatar = Boolean(preparedOptions.useLettersForEmptyAvatar);

			this.options.allowFlatDepartments ??= true;
			this.options.allowSelectRootDepartment ??= true;
		}

		getOptions()
		{
			return this.options || {};
		}

		loadItems()
		{
			return BX.ajax.runAction('ui.entityselector.load', {
				json: {
					dialog: this.getAjaxDialog(),
				},
				getParameters: {
					context: this.context,
				},
			})
				.then((response) => {
					const { items, recentItems } = response.data.dialog;
					this.items = items;

					if (Type.isFunction(this.options.onItemsLoadedFromServer))
					{
						this.options.onItemsLoadedFromServer(items);
					}

					this.recentItems = this.mapRecentItemsByIds(items, recentItems);
				})
				.catch(console.error);
		}

		async loadDepartmentChildren(department)
		{
			const response = await BX.ajax.runAction('ui.entityselector.getChildren', {
				json: {
					dialog: this.getAjaxDialog(),
					parentItem: { id: department.id, entityId: 'department' },
				},
				getParameters: {
					context: this.context,
				},
			});

			let children = response.data?.dialog?.items;

			if (!children)
			{
				return [];
			}

			children = children.map((child) => ({
				...child,
				type: child.entityId === 'department' ? 'button' : 'selectable',
			}));

			return children;
		}

		mapRecentItemsByIds(items, recentItemsIdsAndEntities)
		{
			const departmentRoot = items.find(({ entityId }) => entityId === 'department');

			let metaUser = false;

			const recentItems = recentItemsIdsAndEntities
				.map(([entity, id]) => {
					switch (entity)
					{
						case 'department':
							return this.options.findInTree(departmentRoot, (currentNode) => (
								currentNode.id === id && currentNode.entityId === entity
							));
						case 'user':

							return items.find((item) => item.id === id && item.entityId === entity);
						case 'meta-user':
							metaUser = items.find((item) => item.id === id && item.entityId === entity);

							return null;
						default:
							return null;
					}
				})
				.filter(Boolean);

			if (metaUser)
			{
				recentItems.unshift(metaUser);
			}

			return recentItems;
		}

		loadRecent()
		{
			const onRecentLoaded = () => {
				this.listener.onRecentResult(
					this.recentItems.map((recentItem) => this.prepareItemForDrawing(recentItem)),
					false,
				);

				if (Type.isFunction(this.options.onRecentLoaded))
				{
					this.options.onRecentLoaded(this.items);
				}
			};

			if (this.recentItems)
			{
				onRecentLoaded();

				return;
			}

			this.loadItems()
				.then(() => {
					onRecentLoaded();
				})
				.catch(console.error);
		}

		getAjaxDialog()
		{
			return {
				id: 'mobile',
				context: this.context,
				preselectedItems: this.preselectedItems,
				entities: this.getEntities(),
				recentItemsLimit: this.options.recentItemsLimit,
			};
		}

		getEntities()
		{
			return [
				this.options.addMetaUser && this.getMetaUserEntity(),
				this.getUserEntity(),
				this.getDepartmentEntity(),
			].filter(Boolean);
		}

		getUserEntity()
		{
			return {
				id: 'user',
				dynamicLoad: true,
				dynamicSearch: true,
				filters: [],
				options: {
					emailUsers: true,
					inviteEmployeeLink: false,
				},
				searchable: true,
				substituteEntityId: null,
			};
		}

		getDepartmentEntity()
		{
			const scopeId = this.options.getScope().id;

			return {
				id: 'department',
				dynamicLoad: true,
				dynamicSearch: true,
				filters: [],
				options: {
					allowFlatDepartments: this.options.allowFlatDepartments,
					allowSelectRootDepartment: this.options.allowSelectRootDepartment,
					selectMode: 'usersAndDepartments',
					shouldCountSubdepartments: scopeId === ScopesIds.DEPARTMENT,
					shouldCountUsers: scopeId === ScopesIds.DEPARTMENT,
				},
				searchable: true,
				substituteEntityId: null,
			};
		}

		getMetaUserEntity()
		{
			return {
				id: 'meta-user',
				dynamicLoad: true,
				dynamicSearch: false,
				filters: [],
				options: {
					'all-users': {
						allowView: true,
					},
				},
				searchable: true,
				substituteEntityId: null,
			};
		}

		getRecentItems()
		{
			return this.recentItems;
		}

		prepareItemForDrawing(entity)
		{
			const preparedEntity = super.prepareItemForDrawing(entity);

			preparedEntity.params.customData = {
				...preparedEntity.params.customData,
				sourceEntity: entity,
			};

			if (entity.entityId === 'meta-user')
			{
				preparedEntity.imageUrl = this.getAvatarImage('THREE_PERSONS');
				preparedEntity.color = Color.accentMainSuccess.getValue();
				preparedEntity.typeIconFrame = 1;
			}

			if (entity.entityId === 'department')
			{
				preparedEntity.shortTitle = entity.shortTitle;

				preparedEntity.imageUrl = this.getAvatarImage('GROUP');
				preparedEntity.subtitle = entity.subtitle;
				preparedEntity.color = Color.accentExtraAqua.getValue();
				preparedEntity.typeIconFrame ??= 1;
			}

			if (entity.type === 'button')
			{
				preparedEntity.type = Application.getApiVersion() < 56 ? 'info' : 'department';
				preparedEntity.id += '/button';
			}

			if (entity.type === 'selectable')
			{
				preparedEntity.type = null;
			}

			return preparedEntity;
		}

		prepareItems(items)
		{
			const preparedItems = items.map((item) => {
				const { subdepartmentsCount, usersCount } = (item.customData || {});

				if (item.type !== 'button' || item.entityId !== 'department')
				{
					return item;
				}

				const usersCountStr = usersCount
					? Loc.getMessagePlural(
						'NESTED_DEPARTMENT_PROVIDER_USERS_COUNT',
						usersCount,
						{
							'#COUNT#': usersCount,
						},
					)
					: '';
				const subdepartmentsCountStr = subdepartmentsCount
					? Loc.getMessagePlural(
						'NESTED_DEPARTMENT_PROVIDER_SUBDEPARTMENTS_COUNT',
						subdepartmentsCount,
						{
							'#COUNT#': subdepartmentsCount,
						},
					)
					: '';
				const delimiter = usersCountStr === '' || subdepartmentsCountStr === '' ? '' : ' | ';

				return {
					...item,
					subtitle: `${usersCountStr}${delimiter}${subdepartmentsCountStr}`,
				};
			});

			return super.prepareItems(preparedItems);
		}

		getAvatarImage(name)
		{
			return withCurrentDomain(Icon[name].getPath());
		}
	}

	module.exports = { NestedDepartmentProvider, ScopesIds };
});
