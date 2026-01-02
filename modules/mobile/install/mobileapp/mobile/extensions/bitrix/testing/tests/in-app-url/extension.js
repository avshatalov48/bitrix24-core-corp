(() => {
	const require = (ext) => jn.require(ext);
	const { inAppUrl } = require('in-app-url');
	const { describe, it, expect } = require('testing');

	const registerRoutes = () => {
		const inAppHandler = () => {};
		inAppUrl.register(
			'/work/group/:groupId/tasks/view/:taskId/',
			inAppHandler,
		).name('tasks:task:workGroup');

		inAppUrl
			.addRoute('/task/\\?signedAttachId=:dialogId&messageIds=:messageId')
			.addRoute('bitrix24://goTo')
			.addRoute('lalala://goTo')
			.handler(inAppHandler)
			.name('tasks:task:goto');
	};

	describe('in-app-url testing', () => {
		registerRoutes();

		it('should find a route for a valid URL', () => {
			const route = inAppUrl.findRoute('https://modules24.team/work/group/1714/tasks/view/597690/');
			expect(Boolean(route)).toBe(true);
			expect(route.pattern).toBe('/work/group/:groupId/tasks/view/:taskId/');
		});

		it('should return null for an invalid URL', () => {
			const route = inAppUrl.findRoute('https://bitrix24.team/invalid/path/');
			expect(route).toBeUndefined();
		});

		it('should generate a URL from route variables', () => {
			const route = inAppUrl.findRoute('https://modules24.team/work/group/1714.dasda#12/tasks/view/597690/');
			const generatedUrl = route.makeUrl({ groupId: '1714', taskId: '597690' });
			expect(generatedUrl).toBe('/work/group/1714/tasks/view/597690/');
		});
	});

	describe('in-app-url testing for signedAttachId and messageIds', () => {
		registerRoutes();

		it('should find a route for the given URL', () => {
			const route = inAppUrl.findRoute(
				'https://modules24.team/task/?signedAttachId=vote.123.233&messageIds=1234567890',
			);
			expect(Boolean(route)).toBe(true);
			expect(route.pattern).toBe('/task/\\?signedAttachId=:dialogId&messageIds=:messageId');
		});

		it('should extract parameters from the URL', () => {
			const url = 'https://modules24.team/task/?signedAttachId=vote.123.233&messageIds=1234567890';
			const route = inAppUrl.findRoute(url);

			let dispatchedParams = null;
			route.handler((params) => {
				dispatchedParams = params;
			});

			route.dispatch(inAppUrl.url(url));

			expect(dispatchedParams.dialogId).toBe('vote.123.233');
			expect(dispatchedParams.messageId).toBe('1234567890');
		});

		it('should generate a URL from route variables', () => {
			const route = inAppUrl.findRoute(
				'https://modules24.team/task/?signedAttachId=vote.123.233&messageIds=1234567890',
			);
			const generatedUrl = route.makeUrl({ dialogId: 'vote.123.233', messageId: '1234567890' });
			expect(generatedUrl).toBe('/task/\\?signedAttachId=vote.123.233&messageIds=1234567890');
		});
	});
})();
