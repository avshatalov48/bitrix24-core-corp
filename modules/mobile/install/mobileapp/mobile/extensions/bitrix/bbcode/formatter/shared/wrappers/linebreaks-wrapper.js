/** @module bbcode/formatter/shared/wrappers/linebreaks-wrapper */
jn.define('bbcode/formatter/shared/wrappers/linebreaks-wrapper', (require, exports, module) => {
	const { DefaultBBCodeScheme } = require('bbcode/model');
	const { Type } = require('type');

	const scheme = new DefaultBBCodeScheme();

	class LinebreaksWrapper
	{
		static wrapNode({ sourceNode, targetNode })
		{
			const children = [];

			if (Type.isArrayFilled(sourceNode.getPreviewsSiblings()))
			{
				const prevLinebreaksCount = 2 - LinebreaksWrapper.getLeftLinebreaksCount(sourceNode);
				if (prevLinebreaksCount >= 0)
				{
					children.push(...LinebreaksWrapper.createLinebreaks(prevLinebreaksCount));
				}
			}

			children.push(targetNode);

			const nextLinebreaksCount = 2 - LinebreaksWrapper.getRightLinebreaksCount(sourceNode);
			if (nextLinebreaksCount >= 0)
			{
				children.push(...LinebreaksWrapper.createLinebreaks(nextLinebreaksCount));
			}

			return scheme.createFragment({
				children,
			});
		}

		static createLinebreaks(count)
		{
			return Array.from({ length: count }, () => {
				return scheme.createNewLine();
			});
		}

		static getLeftLinebreaksCount(node)
		{
			const previewsSiblings = node.getPreviewsSiblings();
			if (previewsSiblings)
			{
				const { count: prevLinebreaksCount } = previewsSiblings.reduceRight((acc, sibling) => {
					if (sibling.getName() === '#linebreak' && !acc.stop)
					{
						// eslint-disable-next-line no-param-reassign
						acc.count += 1;

						return acc;
					}

					// eslint-disable-next-line no-param-reassign
					acc.stop = true;

					return acc;
				}, { count: 0, stop: false });

				return prevLinebreaksCount;
			}

			return 0;
		}

		static getRightLinebreaksCount(node)
		{
			const nextSiblings = node.getNextSiblings();
			if (nextSiblings)
			{
				const { count: nextLinebreaksCount } = nextSiblings.reduce((acc, sibling) => {
					if (sibling.getName() === '#linebreak' && !acc.stop)
					{
						// eslint-disable-next-line no-param-reassign
						acc.count += 1;

						return acc;
					}

					// eslint-disable-next-line no-param-reassign
					acc.stop = true;

					return acc;
				}, { count: 0, stop: false });

				return nextLinebreaksCount;
			}

			return 0;
		}
	}

	module.exports = {
		LinebreaksWrapper,
	};
});
