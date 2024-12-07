/**
 * @module text-editor/adapters/mention-adapter
 */
jn.define('text-editor/adapters/mention-adapter', (require, exports, module) => {
	const { BaseAdapter } = require('text-editor/adapters/base-adapter');
	const { scheme } = require('text-editor/internal/scheme');

	let mentionCounter = 0;

	class MentionAdapter extends BaseAdapter
	{
		#typedHref = null;

		#getMentionType()
		{
			return this.getSource().getName();
		}

		#getMentionId()
		{
			return this.getSource().getValue();
		}

		#getTypedHref()
		{
			if (this.#typedHref === null)
			{
				const mentionType = this.#getMentionType();
				const mentionId = this.#getMentionId();
				const uid = mentionCounter++;

				this.#typedHref = `#${mentionType}-${uid}-${mentionId}`;
			}

			return this.#typedHref;
		}

		#getMentionText()
		{
			return this.getSource().toPlainText();
		}

		getPreview()
		{
			if (!this.previewSync)
			{
				this.previewSync = scheme.createElement({
					name: 'url',
					value: this.#getTypedHref(),
					children: [
						scheme.createText(this.#getMentionText()),
					],
				});
			}

			return this.previewSync;
		}
	}

	module.exports = {
		MentionAdapter,
	};
});
