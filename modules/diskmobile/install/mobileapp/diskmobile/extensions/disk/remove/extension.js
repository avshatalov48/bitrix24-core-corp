/**
 * @module disk/remove
 */
jn.define('disk/remove', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Feature } = require('feature');
	const { showRemoveToast } = require('toast/remove');

	const store = require('statemanager/redux/store');
	const { dispatch } = store;
	const {
		markAsRemoved,
		unmarkAsRemoved,
	} = require('disk/statemanager/redux/slices/files');
	const { remove } = require('disk/statemanager/redux/slices/files/thunk');
	const { selectById } = require('disk/statemanager/redux/slices/files/selector');

	function removeObject(objectId)
	{
		const file = selectById(store.getState(), objectId);
		if (!file)
		{
			return;
		}

		if (!Feature.isToastSupported())
		{
			dispatch(remove({ objectId }));

			return;
		}

		dispatch(
			markAsRemoved({ objectId }),
		);

		showRemoveToast(
			{
				message: file.isFolder
					? Loc.getMessage('M_DISK_FOLDER_REMOVE_TOAST_MESSAGE')
					: Loc.getMessage('M_DISK_FILE_REMOVE_TOAST_MESSAGE'),
				offset: 86,
				onButtonTap: () => {
					dispatch(
						unmarkAsRemoved({ objectId }),
					);
				},
				onTimerOver: () => {
					dispatch(remove({ objectId }));
				},
			},
		);
	}

	module.exports = { removeObject };
});
