(() => {
	const require = (ext) => jn.require(ext);

	const { describe, test, expect } = require('testing');
	const { isValidEmail } = require('utils/email');

	describe('utils/email isValidEmail', () => {
		test('should return true for valid email addresses', () => {
			expect(isValidEmail('test@example.com')).toBe(true);
			expect(isValidEmail('user.name+tag+sorting@example.com')).toBe(true);
			expect(isValidEmail('user_name@example.co.uk')).toBe(true);
			expect(isValidEmail('user-name@domain.com')).toBe(true);
			expect(isValidEmail('user@sub.domain.com')).toBe(true);
		});

		test('should return false for email addresses without @ symbol', () => {
			expect(isValidEmail('testexample.com')).toBe(false);
			expect(isValidEmail('user.name.example.com')).toBe(false);
		});

		test('should return false for email addresses without domain', () => {
			expect(isValidEmail('test@')).toBe(false);
			expect(isValidEmail('user.name@')).toBe(false);
		});

		test('should return false for email addresses with invalid characters', () => {
			expect(isValidEmail('test@exa!mple.com')).toBe(false);
			expect(isValidEmail('user.name@exam#ple.com')).toBe(false);
		});

		test('should return true for email addresses with different domain zones', () => {
			expect(isValidEmail('user@example.org')).toBe(true);
			expect(isValidEmail('user@example.net')).toBe(true);
			expect(isValidEmail('user@example.io')).toBe(true);
		});

		test('should return true for email addresses with different lengths', () => {
			expect(isValidEmail('a@b.co')).toBe(true);
			expect(isValidEmail('very.common@example.com')).toBe(true);
			expect(isValidEmail('disposable.style.email.with+symbol@example.com')).toBe(true);
		});

		test('should return false for email addresses with invalid domain part', () => {
			expect(isValidEmail('user@.example.com')).toBe(false);
			expect(isValidEmail('user@com')).toBe(false);
		});
	});
})();
