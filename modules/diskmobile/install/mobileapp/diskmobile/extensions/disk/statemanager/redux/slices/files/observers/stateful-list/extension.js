/**
 * @module disk/statemanager/redux/slices/files/observers/stateful-list
 */
jn.define('disk/statemanager/redux/slices/files/observers/stateful-list', (require, exports, module) => {
	const { isEqual } = require('utils/object');
	const { selectEntities } = require('disk/statemanager/redux/slices/files/selector');

	const observeListChange = (store, onChange) => {
		let prevFiles = selectEntities(store.getState());

		return store.subscribe(() => {
			const nextFiles = selectEntities(store.getState());

			const {
				moved,
				removed,
				added,
				created,
			} = getDiffForFilesObserver(prevFiles, nextFiles);
			if (moved.length > 0 || removed.length > 0 || added.length > 0 || created.length > 0)
			{
				onChange({ moved, removed, added, created });
			}

			prevFiles = nextFiles;
		});
	};

	/**
	 * Exported for tests
	 *
	 * @private
	 * @param {Object.<number, FileReduxModel>} prevFiles
	 * @param {Object.<number, FileReduxModel>} nextFiles
	 * @return {{
	 * moved: FileReduxModel[],
	 * removed: FileReduxModel[],
	 * added: FileReduxModel[],
	 * created: FileReduxModel[]
	 * }}
	 */
	const getDiffForFilesObserver = (prevFiles, nextFiles) => {
		const moved = [];
		const removed = [];
		const added = [];
		const created = [];

		if (prevFiles === nextFiles)
		{
			return { moved, removed, added, created };
		}

		// Find added or restored tasks
		Object.values(nextFiles).forEach((nextFile) => {
			if (!nextFile.isRemoved)
			{
				const prevFile = prevFiles[nextFile.id];
				if (!prevFile || prevFile.isRemoved)
				{
					added.push(nextFile);
				}
			}
		});

		// Find removed
		Object.values(prevFiles).forEach((prevFile) => {
			if (!prevFile.isRemoved)
			{
				const nextFile = nextFiles[prevFile.id];

				if (!nextFile || nextFile.isRemoved || nextFile.parentId !== prevFile.parentId)
				{
					removed.push(nextFile || prevFile);
				}
			}
		});

		const processedFileIds = new Set([...removed, ...added].map(({ id }) => id));
		Object.values(nextFiles).forEach((nextFile) => {
			const prevFile = prevFiles[nextFile.id];
			if (!prevFile || processedFileIds.has(nextFile.id))
			{
				return;
			}

			const { isRemoved: prevIsRemoved, ...prevFileWithoutIsRemoved } = prevFile;
			const { isRemoved: nextIsRemoved, ...nextFileWithoutIsRemoved } = nextFile;

			if (!isEqual(prevFileWithoutIsRemoved, nextFileWithoutIsRemoved))
			{
				moved.push(nextFile);
			}
		});

		return { moved, removed, added, created };
	};

	module.exports = { observeListChange };
});
