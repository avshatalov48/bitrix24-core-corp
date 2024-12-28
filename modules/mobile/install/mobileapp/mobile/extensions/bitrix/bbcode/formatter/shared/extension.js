/** @module bbcode/formatter/shared */
jn.define('bbcode/formatter/shared', (require, exports, module) => {
	const { DiskNodeFormatter } = require('bbcode/formatter/shared/node-formatters/disk-formatter');
	const { MentionFormatter } = require('bbcode/formatter/shared/node-formatters/mention-formatter');
	const { TextFormatter } = require('bbcode/formatter/shared/node-formatters/text-formatter');
	const { TableFormatter } = require('bbcode/formatter/shared/node-formatters/table-formatter');
	const { CodeFormatter } = require('bbcode/formatter/shared/node-formatters/code-formatter');
	const { StripTagFormatter } = require('bbcode/formatter/shared/node-formatters/strip-tag-formatter');
	const { ListFormatter } = require('bbcode/formatter/shared/node-formatters/list-formatter');
	const { ListItemFormatter } = require('bbcode/formatter/shared/node-formatters/list-item-formatter');
	const { LinebreaksWrapper } = require('bbcode/formatter/shared/wrappers/linebreaks-wrapper');

	module.exports = {
		DiskNodeFormatter,
		MentionFormatter,
		TextFormatter,
		TableFormatter,
		CodeFormatter,
		StripTagFormatter,
		ListFormatter,
		ListItemFormatter,
		LinebreaksWrapper,
	};
});
