/**
 * @module text-editor/adapters/base-adapter
 */
jn.define('text-editor/adapters/base-adapter', (require, exports, module) => {
	class BaseAdapter
	{
		previewSync = null;
		previewAsync = null;

		constructor(options)
		{
			this.setOptions(options);
		}

		setOptions(options)
		{
			this.options = { ...options };
		}

		getOptions()
		{
			return { ...this.options };
		}

		getPreview()
		{}

		getPreviewSync()
		{
			return this.previewSync;
		}

		isPreview(node)
		{
			const previewSync = this.getPreviewSync();
			if (node && previewSync)
			{
				return node.toString() === previewSync.toString();
			}

			return false;
		}

		getSource()
		{
			return this.getOptions().node;
		}
	}

	module.exports = {
		BaseAdapter,
	};
});
