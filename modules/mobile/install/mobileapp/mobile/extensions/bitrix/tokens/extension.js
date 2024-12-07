/**
 * @module tokens
 */
jn.define('tokens', (require, exports, module) => {
	const { Color } = require('tokens/src/color');
	const { Corner } = require('tokens/src/corner');
	const { Indent } = require('tokens/src/indent');
	const { Component } = require('tokens/src/component');
	const { Typography } = require('tokens/src/typography');

	module.exports = { Corner, Indent, Color, Component, Typography };
});
