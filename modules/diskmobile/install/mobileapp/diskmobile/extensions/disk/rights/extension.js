/**
 * @module disk/rights
 */
jn.define('disk/rights', (require, exports, module) => {
	const { isEmpty } = require('utils/object');

	const { filesUpsertedFromServer } = require('disk/statemanager/redux/slices/files');
	const { selectById } = require('disk/statemanager/redux/slices/files/selector');
	const store = require('statemanager/redux/store');
	const { dispatch } = store;

	async function fetchObjectWithRights(id)
	{
		let diskObject = selectById(store.getState(), id);
		if (!diskObject || isEmpty(diskObject.rights))
		{
			try
			{
				const response = await BX.ajax.runAction('diskmobile.Common.getByIdWithRights', {
					data: { id },
				});

				if (response.errors.length > 0)
				{
					console.error(response.errors);

					return null;
				}

				diskObject = response.data.diskObject;
				dispatch(filesUpsertedFromServer([diskObject]));

				return selectById(store.getState(), id);
			}
			catch (error)
			{
				console.error(error);

				return null;
			}
		}

		return diskObject;
	}

	module.exports = { fetchObjectWithRights };
});
