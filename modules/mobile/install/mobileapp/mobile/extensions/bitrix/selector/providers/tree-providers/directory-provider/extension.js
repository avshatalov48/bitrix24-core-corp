/**
 * @module selector/providers/tree-providers/directory-provider
 */
jn.define('selector/providers/tree-providers/directory-provider', (require, exports, module) => {
	const { stringify } = require('utils/string');
	const { BaseSelectorProvider } = require('selector/providers/base');
	const { debounce } = require('utils/function');

	const { resolveFileIconUrl, resolveFolderIconUrl } = require('selector/providers/tree-providers/directory-provider/icons');
	const { filesUpsertedFromServer } = require('disk/statemanager/redux/slices/files');
	const store = require('statemanager/redux/store');

	/**
	 * @class DirectoryProvider
	 */
	class DirectoryProvider extends BaseSelectorProvider
	{
		constructor(context, options = {})
		{
			super(options);
			this.options = options;
			this.queryString = '';

			this.runSearchActionDebounced = debounce(this.runSearchAction, 300);

			this.#init();
		}

		#init()
		{
			this.loadRootId()
				.then(() => this.loadChildren({
					id: this.getRootId(),
				}))
				.then((children) => {
					this.options.onItemsLoaded?.(children);
				})
				.catch((e) => {
					console.error(e);
					this.loadPersonalStorage()
						.then(() => this.loadChildren({
							id: this.getRootId(),
						}))
						.then((children) => {
							this.options.onItemsLoaded?.(children);
						})
						.catch(console.error);
				});
		}

		getRootId = () => {
			return this.getStorage().rootObjectId;
		};

		getStorage = () => {
			return this.storage;
		};

		getOrder = () => {
			return this.options?.order ?? { NAME: 'DESC' };
		};

		getContext = () => {
			return this.options?.context ?? {};
		};

		#setStorage(storage)
		{
			this.storage = storage;

			this.options.onStorageLoaded?.(storage);
		}

		setQuery(text)
		{
			this.queryString = text;
		}

		resetQuery()
		{
			this.queryString = '';
		}

		loadPersonalStorage()
		{
			return BX.ajax.runAction('disk.api.storage.getPersonalStorage', {
				data: {
					id: this.options.storageId,
				},
			})
				.then((response) => {
					this.#setStorage(response.data.storage);
				})
				.catch(console.error);
		}

		loadRootId()
		{
			return BX.ajax.runAction('disk.api.storage.get', {
				data: {
					id: this.options.storageId,
				},
			})
				.then((response) => {
					this.#setStorage(response.data.storage);
				})
				.catch(console.error);
		}

		loadChildren = async ({ id, search = '' }) => {
			const response = await BX.ajax.runAction('diskmobile.Folder.getChildren', {
				data: {
					id,
					order: this.getOrder(),
					context: this.getContext(),
					search,
				},
			});

			const items = response?.data?.items;

			if (!items || items.length === 0)
			{
				return [];
			}

			store.dispatch(filesUpsertedFromServer(items));

			let children = items.map((child) => ({
				...child,
				type: child.typeFile === undefined ? 'button' : 'selectable',
				entityId: child.typeFile === undefined ? 'folder' : 'file',
			}));

			if (this.options.showDirectoriesOnly)
			{
				children = children.filter((child) => child.entityId === 'folder');
			}

			return children;
		};

		doSearch = (query) => {
			query = query.trim();

			if (this.queryString !== query)
			{
				this.runSearchActionDebounced(query);
			}
		};

		runSearchAction = (query) => {
			this.setQuery(query);

			let nodeId = this.options.getCurrentNode?.()?.id;
			if (!nodeId)
			{
				nodeId = this.getRootId();
			}

			this.loadChildren({
				id: nodeId,
				search: query,
			})
				.then((children) => {
					this.listener.onFetchResult(
						this.prepareItems(children),
					);
				})
				.catch(console.error);
		};

		prepareItemForDrawing(entity)
		{
			const preparedEntity = {
				title: stringify(entity.title || entity.name),
				subtitle: stringify(entity.subtitle),
				shortTitle: stringify(entity.shortTitle || entity.title || entity.name),
				sectionCode: 'common',
				height: 64,
				useLetterImage: false,
				id: `${entity.entityId}/${entity.id}`,
				params: {
					title: stringify(entity.title || entity.name),
					type: entity.entityId,
					id: entity.id,
					customData: entity.customData || {},
				},
				disabled: entity.customData?.isSelectable === false,
				type: entity.type ?? 'button',
				typeIconFrame: entity.typeIconFrame,
			};

			preparedEntity.styles = {
				...entity.styles,
				image: ImageStyle,
				selectedImage: ImageStyle,
			};

			preparedEntity.params.customData = {
				...preparedEntity.params.customData,
				sourceEntity: entity,
			};

			if (entity.entityId === 'folder')
			{
				preparedEntity.imageUrl = resolveFolderIconUrl(entity.folderContextType);
			}
			else if (entity.entityId === 'file')
			{
				preparedEntity.imageUrl = resolveFileIconUrl(entity.typeFile, entity.name);

				if (!this.options.canSelectFiles)
				{
					preparedEntity.type = 'info';
				}
			}

			if (preparedEntity.type === 'button')
			{
				preparedEntity.type = Application.getApiVersion() < 56 ? 'info' : 'department';
				preparedEntity.id += '/button';
			}
			else if (preparedEntity.type === 'selectable')
			{
				preparedEntity.type = null;
			}

			return preparedEntity;
		}
	}

	const ImageStyle = {
		image: {
			borderRadius: 0,
		},
		border: {
			width: 0,
		},
	};

	module.exports = { DirectoryProvider };
});
