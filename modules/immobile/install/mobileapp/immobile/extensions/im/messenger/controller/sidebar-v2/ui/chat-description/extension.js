/**
 * @module im/messenger/controller/sidebar-v2/ui/chat-description
 */
jn.define('im/messenger/controller/sidebar-v2/ui/chat-description', (require, exports, module) => {
	const { Area } = require('ui-system/layout/area');
	const { Color } = require('tokens');
	const { BBCodeText } = require('ui-system/typography');
	const { shouldCrop, truncate } = require('im/messenger/controller/sidebar-v2/ui/chat-description/src/text-utils');
	const { ChatDescriptionViewer } = require('im/messenger/controller/sidebar-v2/ui/chat-description/src/viewer');

	const ChatDescription = ({ text, testId, parentWidget = PageManager }) => Area(
		{
			testId: `${testId}-clickable-container`,
			excludePaddingSide: {
				bottom: true,
			},
			onClick: () => {
				const [crop] = shouldCrop(text);

				if (crop)
				{
					ChatDescriptionViewer.open({ text, testId, parentWidget });
				}
			},
		},
		BBCodeText({
			testId: `${testId}-value`,
			value: truncate(text),
			size: 5,
			color: Color.base1,
			numberOfLines: 2,
		}),
	);

	module.exports = { ChatDescription };
});
