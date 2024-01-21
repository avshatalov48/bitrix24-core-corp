/**
 * @module im/messenger/lib/parser/functions/smile
 */
jn.define('im/messenger/lib/parser/functions/smile', (require, exports, module) => {
	const { SmileManager } = require('im/messenger/lib/smile-manager');
	const { Type } = require('type');

	const Ratio = {
		default: 1,
		bigSmile: 3,
	};

	const parserSmile = {
		pattern: '',
		smiles: {},

		/**
		 *
		 * @param {string} text
		 * @param options
		 * @return {*}
		 */
		decodeSmile(text, options = {})// TODO add options types
		{
			if (!this.typings)
			{
				this.loadSmilePatterns();
			}

			if (!this.pattern)
			{
				return text;
			}

			let ratio = Ratio.default;
			const pattern = `(?:(?:${this.pattern})(?=(?:(?:${this.pattern})|\\s|&quot;|<|$)))`;
			const regExp = new RegExp(pattern, 'g');

			if (Type.isBoolean(options.enableBigSmile))
			{
				const smileCounter = text.match(regExp)?.length;
				text = text.replace(/ {2,}/, ' ');

				ratio = options.enableBigSmile && smileCounter <= 4 ? Ratio.bigSmile : Ratio.default;
			}


			return text.replaceAll(regExp, (match, offset) => {
				const behindMatching = this.lookBehind(text, match, offset);

				if (!behindMatching)
				{
					return match;
				}

				return this.createSmileCode({
					...this.smiles[match],
					ratio,
				});
			});
		},

		/**
		 * @private
		 */
		loadSmilePatterns()
		{
			const smileManager = SmileManager.getInstance();
			const smiles = Object.values(smileManager.getSmiles()) ?? [];
			if (smiles.length === 0)
			{
				return;
			}
			this.smiles = smileManager.getSmiles();
			this.pattern = smileManager.getPattern();
		},

		/**
		 * @private
		 * @param text
		 * @param match
		 * @param offset
		 * @return {*}
		 */
		lookBehind(text, match, offset)
		{
			const substring = text.slice(0, offset + match.length);
			const escaped = match.replaceAll(/[$()*+./?[\\\]^{|}-]/g, '\\$&');
			const regExp = new RegExp(`(?:^|&quot;|>|(?:${this.pattern})|\\s|<)(?:${escaped})$`);

			return substring.match(regExp);
		},

		/**
		 * @private
		 * @param smile
		 * @return {string}
		 */
		createSmileCode(smile)
		{
			const {
				imageUrl,
				width,
				height,
				ratio = Ratio.default,
			} = smile;

			let codeWidth = width;
			let codeHeight = height;
			if (ratio === Ratio.default)
			{
				if (width === 20)
				{
					codeWidth = 18;
				}

				if (height === 20)
				{
					codeHeight = 18;
				}
			}

			if (ratio === Ratio.bigSmile)
			{
				if (width <= 20)
				{
					codeWidth = 66;
				}

				if (height <= 20)
				{
					codeHeight = 66;
				}
			}

			return `[img width=${codeWidth} height=${codeHeight}]${currentDomain + imageUrl}[/img]`;
		},
	};

	module.exports = { parserSmile };
});
