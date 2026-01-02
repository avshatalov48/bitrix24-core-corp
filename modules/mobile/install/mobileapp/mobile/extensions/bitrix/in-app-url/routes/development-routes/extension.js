/**
 * @module in-app-url/routes/development-routes
 */
jn.define('in-app-url/routes/development-routes', (require, exports, module) => {
	module.exports = function(inAppUrl) {
		inAppUrl.register('/development/storybook', () => {
			// eslint-disable-next-line no-undef
			ComponentHelper.openLayout({
				name: 'dev:storybook',
				object: 'layout',
				widgetParams: {
					title: 'StoryBook',
				},
			});
		}).name('storybook');

		inAppUrl.register('/development/unit.tests', ({ component }) => {
			// eslint-disable-next-line no-undef
			ComponentHelper.openLayout({
				name: 'unit.tests',
				object: 'layout',
				widgetParams: {
					title: 'Frontend Unit Tests',
				},
			});
		}).name('unit-tests');

		inAppUrl.register('/development/playground', () => {
			// eslint-disable-next-line no-undef
			ComponentHelper.openLayout({
				name: 'playground',
				object: 'layout',
				widgetParams: {
					title: 'Playground',
				},
			});
		}).name('playground');

		inAppUrl.register('/development/fields.test', () => {
			// eslint-disable-next-line no-undef
			ComponentHelper.openLayout({
				name: 'fields.test',
				version: '1',
				object: 'layout',
				componentParams: {},
				widgetParams: {
					title: 'Fields Test',
				},
			});
		}).name('fields-test');

		inAppUrl.register('/development/listview.benchmark', () => {
			// eslint-disable-next-line no-undef
			ComponentHelper.openLayout({
				name: 'listview.benchmark',
				object: 'layout',
				widgetParams: {
					title: 'ListView Benchmark',
				},
			});
		}).name('listview-benchmark');

		inAppUrl.register('/development/text-editor.demo', () => {
			// eslint-disable-next-line no-undef
			ComponentHelper.openLayout({
				name: 'text-editor.demo',
				object: 'layout',
				widgetParams: {
					title: 'Text Editor Demo',
				},
			});
		}).name('text-editor-demo');

		inAppUrl.register('/development/formatter.sandbox', () => {
			// eslint-disable-next-line no-undef
			ComponentHelper.openLayout({
				name: 'formatter.sandbox',
				object: 'layout',
				widgetParams: {
					title: 'Formatter Sandbox',
				},
			});
		}).name('formatter-sandbox');
	};
});
