(() => {
	const require = (ext) => jn.require(ext);
	const { Url } = require('in-app-url/url');
	const { Route } = require('in-app-url/route');
	const { describe, it, expect } = require('testing');

	const inAppHandler = () => {};

	describe('Route match method', () => {
		it('should return true for a matching URL', () => {
			const route = new Route({
				handler: inAppHandler,
				routes: [
					{ pattern: 'bitrix24://goTo' },
					{ pattern: 'lalala://goTo' },
				],
			});
			const validUrl = 'bitrix24://goTo';
			const invalidUrl = '/invalid/path/';
			expect(route.match(validUrl)).toBe(true);
			expect(route.match(invalidUrl)).toBe(false);
		});

		it('should handle dynamic parameters correctly', () => {
			const route = new Route({ pattern: '/user/:userId/profile/:section/' });
			const url = '/user/123/profile/settings/';
			expect(route.match(url)).toBe(true);
		});
	});

	describe('Route regexp getter', () => {
		it('should convert route pattern to RegExp correctly', () => {
			const route = new Route({ pattern: '/work/group/:groupId/tasks/view/:taskId/' });
			const regexp = route.regexp;

			expect(regexp).toBeInstanceOf(RegExp);
			expect(regexp.source).toBe('\\/work\\/group\\/([^&=$/?]+)\\/tasks\\/view\\/([^&=$/?]+)\\/');
		});

		it('should match a URL using the generated RegExp', () => {
			const route = new Route({ pattern: '/work/group/:groupId/tasks/view/:taskId/' });
			const regexp = route.regexp;

			const url = '/work/group/1714/tasks/view/597690/';
			expect(regexp.test(url)).toBe(true);
		});

		it('should not match an invalid URL', () => {
			const route = new Route({ pattern: '/work/group/:groupId/tasks/view/:taskId/' });
			const regexp = route.regexp;

			const url = '/invalid/path/';
			expect(regexp.test(url)).toBe(false);
		});
	});

	describe('in-app-url testing for signedAttachId and messageIds', () => {
		it('should match the URL using the generated RegExp', () => {
			const route = new Route({ pattern: '/task/\\?signedAttachId=:dialogId&messageIds=:messageId' });
			const regexp = route.regexp;

			const url = '/task/?signedAttachId=vote.123.233&messageIds=1234567890';
			expect(regexp.test(url)).toBe(true);
		});

		it('should not match an invalid URL', () => {
			const route = new Route({ pattern: '/task/\\?signedAttachId=:dialogId&messageIds=:messageId' });
			const regexp = route.regexp;

			const url = '/task/?invalidParam=123';
			expect(regexp.test(url)).toBe(false);
		});
	});

	describe('Route.getPatternVariables', () => {
		it('should return an array of variable names from the pattern', () => {
			const route = new Route({ pattern: '/company/personal/user/:userId/tasks/task/:taskId/' });
			const variables = route.getPatternVariables(route.pattern);
			expect(variables).toEqual(['userId', 'taskId']);
		});

		it('should return an array of variable names from the pattern', () => {
			const route = new Route({ pattern: '/company/personal/user/:user-Id/tasks/task/:task-Id/' });
			const variables = route.getPatternVariables(route.pattern);
			expect(variables).toEqual(['user-Id', 'task-Id']);
		});

		it('should return an empty array if there are no variables', () => {
			const route = new Route({ pattern: '/about/company/' });
			const variables = route.getPatternVariables(route.pattern);
			expect(variables).toEqual([]);
		});
	});

	describe('Route.makeUrl', () => {
		it('should generate a URL by substituting variables', () => {
			const route = new Route({ pattern: '/company/personal/user/:userId/tasks/task/:taskId/' });
			const url = route.makeUrl({ userId: 1, taskId: 12345 });
			expect(url).toBe('/company/personal/user/1/tasks/task/12345/');
		});

		it('should throw an error if a variable is not provided', () => {
			const route = new Route({ pattern: '/company/personal/user/:userId/tasks/task/:taskId/' });
			expect(() => route.makeUrl({ userId: 1 })).toThrow();
		});
	});

	describe('Route.dispatch', () => {
		it('should call the handler with parsed path params and context', () => {
			let handlerCalled = false;
			let receivedParams = null;
			let receivedOptions = null;
			const handler = (params, options) => {
				handlerCalled = true;
				receivedParams = params;
				receivedOptions = options;
			};
			const route = new Route({
				pattern: '/workgroups/group/:groupId/tasks/view/:taskId/',
				handler,
			});

			const url = new Url('/workgroups/group/1714/tasks/view/597690/');
			const context = { user: 'John Doe' };
			route.dispatch(url, context);
			expect(handlerCalled).toBe(true);
			expect(receivedParams).toEqual({ groupId: '1714', taskId: '597690' });
			expect(receivedOptions.context).toEqual(context);
			expect(receivedOptions.url).toBe(url.toString());
		});

		it('should throw an error if no handler is defined', () => {
			const route = new Route({ pattern: '/crm/deal/details/:deal_id/' });
			const url = new Url('/crm/deal/details/5/');
			let error = null;
			try
			{
				route.dispatch(url, {});
			}
			catch (e)
			{
				error = e;
			}
			expect(error).not.toBeNull();
			expect(error.message).toBe('Route  does not have a handler');
		});

		it('should throw an error if url does not match', () => {
			const handler = () => {
			};
			const route = new Route({ pattern: '/crm/deal/details/:deal_id/', handler });
			const url = new Url('/another/path/abc/');
			let error = null;
			try
			{
				route.dispatch(url, {});
			}
			catch (e)
			{
				error = e;
			}
			expect(error).not.toBeNull();
			expect(error.message).toBe('Route  does not match url /another/path/abc/');
		});
	});

	describe('Route.name and Route.hasName', () => {
		it('should set and check the route name', () => {
			const route = new Route({ pattern: '/crm/deal/details/:deal_id/' });
			route.name('crm:deal:details');
			expect(route.hasName('crm:deal:details')).toBe(true);
			expect(route.hasName('crm:deal:list')).toBe(false);
		});
	});

	describe('Route.addRoute', () => {
		it('should add a new route pattern and match against it', () => {
			const route = new Route({ pattern: '/tasks/view/:taskId' });
			route.addRoute('/company/personal/user/:userId/tasks/task/view/:taskId/');
			const url1 = '/tasks/view/123';
			const url2 = '/company/personal/user/1/tasks/task/view/456/';
			const url3 = '/something/else/';
			expect(route.match(url1)).toBe(true);
			expect(route.match(url2)).toBe(true);
			expect(route.match(url3)).toBe(false);
		});
	});

	describe('Route.findRoute', () => {
		it('should find the correct route object for a given URL', () => {
			const route = new Route({
				routes: [
					{ pattern: '/mobile/crm/deal/details/:deal_id/' },
					{ pattern: '/mobile/tasks/snmrouter/router.php' },
				],
			});
			const url = '/mobile/crm/deal/details/5/';
			const foundRoute = route.findRoute(url);
			expect(foundRoute).toEqual({ pattern: '/mobile/crm/deal/details/:deal_id/' });
		});

		it('should return undefined if no route matches', () => {
			const route = new Route({
				routes: [
					{ pattern: '/mobile/crm/deal/details/:deal_id/' },
					{ pattern: '/mobile/tasks/snmrouter/router.php' },
				],
			});
			const url = '/non/existent/path';
			const foundRoute = route.findRoute(url);
			expect(foundRoute).toBe(undefined);
		});
	});
})();
