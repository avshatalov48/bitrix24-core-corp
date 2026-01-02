(() => {
	const require = (ext) => jn.require(ext);
	const { Url } = require('in-app-url/url');
	const { describe, it, expect } = require('testing');

	describe('Url class', () => {
		it('should correctly identify local URLs', () => {
			const url = new Url('/local/path');
			expect(url.isLocal).toBe(true);
		});

		it('should correctly identify external URLs', () => {
			const url = new Url('https://example.com');
			expect(url.isExternal).toBe(true);
		});

		it('should correctly identify mobile view URLs', () => {
			const url = new Url('/mobile/some/path');
			expect(url.isMobileView).toBe(true);
		});

		it('should correctly identify email URLs', () => {
			const url = new Url('mailto:test@example.com');
			expect(url.isEmail).toBe(true);
		});

		it('should correctly identify phone number URLs', () => {
			const url = new Url('tel:+1234567890');
			expect(url.isPhoneNumber).toBe(true);
		});

		it('should correctly identify Bitrix24 URLs', () => {
			const url = new Url('bitrix24://some/path');
			expect(url.isBitrix24).toBe(true);
		});

		it('should correctly identify b24 URLs', () => {
			const url = new Url('b24://some/path');
			expect(url.isBitrix24).toBe(true);
		});

		it('should parse query parameters correctly', () => {
			const url = new Url('/path?param1=value1&param2=value2');
			expect(url.queryParams).toEqual({
				param1: 'value1',
				param2: 'value2',
			});
		});

		it('should return the correct string representation', () => {
			const url = new Url('https://example.com');
			expect(url.toString()).toBe('https://example.com');
		});

		it('should convert to HTTP correctly', () => {
			const url = new Url('bitrix24://some/path');
			expect(url.withHttp(url.value)).toBe('http://some/path');
		});

		it('should convert to HTTPS correctly', () => {
			const url = new Url('bitrix24://some/path');
			expect(url.withHttps(url.value)).toBe('https://some/path');
		});
	});
})();
