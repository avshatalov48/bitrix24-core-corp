/**
 * @module text-editor/adapters/table-adapter
 */
jn.define('text-editor/adapters/table-adapter', (require, exports, module) => {
	const { BaseAdapter } = require('text-editor/adapters/base-adapter');
	const { scheme } = require('text-editor/internal/scheme');
	const { Loc } = require('loc');

	let tablesCounter = 0;

	class TableAdapter extends BaseAdapter
	{
		getPreview()
		{
			if (!this.previewSync)
			{
				tablesCounter++;

				this.previewSync = scheme.createElement({
					name: 'url',
					value: `#table-${tablesCounter}`,
					children: [
						scheme.createText(Loc.getMessage('MOBILEAPP_TEXT_EDITOR_TABLE_PLACEHOLDER')),
					],
				});
			}

			return this.previewSync;
		}
	}

	module.exports = {
		TableAdapter,
	};
});
