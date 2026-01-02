(() => {
	const require = (ext) => jn.require(ext);
	const { GratitudeIcon } = require('assets/icons');
	const { describe, expect, test, it } = require('testing');
	const { Loc } = require('loc');

	describe('GratitudeIcon', () => {
		const TEST_NAME = Loc.getMessage('M_UI_GRATITUDE_ICON_BEER');
		const UNKNOWN_NAME = 'UNKNOWN';
		const EXPECTED_ELEMENTS_COUNT = GratitudeIcon.getEnums().length;
		const ImageDir = `${currentDomain}/bitrix/mobileapp/mobile/extensions/bitrix/assets/gratitude-icons`;

		it('should have unique names', () => {
			const names = new Set(
				GratitudeIcon.getEnums().map((e) => e.getValue().name),
			);
			expect(names.size).toBe(EXPECTED_ELEMENTS_COUNT);
		});

		it('should have unique testIds', () => {
			const testIds = new Set(
				GratitudeIcon.getEnums().map((e) => e.getValue().testId),
			);
			expect(testIds.size).toBe(EXPECTED_ELEMENTS_COUNT);
		});

		it('should return correct testId for existing name', () => {
			const expected = 'beer-gratitude-image';
			const result = GratitudeIcon.getTestIdByName(TEST_NAME);
			expect(result).toBe(expected);
		});

		it('should return null for unknown name', () => {
			const result = GratitudeIcon.getTestIdByName(UNKNOWN_NAME);
			expect(result).toBeNull();
		});

		it('should return enum instance for existing name', () => {
			const result = GratitudeIcon.getEnumByName(TEST_NAME);
			expect(result).toBeInstanceOf(GratitudeIcon);
			expect(result.getValue().name).toBe(TEST_NAME);
		});

		it('should return correct svgUri for existing name', () => {
			const expected = `${ImageDir}/beer.svg`;
			const result = GratitudeIcon.getSvgUriByName(TEST_NAME);
			expect(result).toBe(expected);
		});

		it('getFeedList should return a valid list of feed items', () => {
			const feedList = GratitudeIcon.getFeedList();
			Object.values(feedList).forEach((item) => {
				expect(item).toBeDefined('backgroundUrl');
				expect(item).toBeDefined('medalSelectorUrl');
				expect(item).toBeDefined('medalUrl');
				expect(item).toBeDefined('name');
				expect(item).toBeDefined('sort');

				expect(typeof item.backgroundUrl).toBe('string');
				expect(typeof item.medalSelectorUrl).toBe('string');
				expect(typeof item.medalUrl).toBe('string');
				expect(typeof item.name).toBe('string');
				expect(typeof item.sort).toBe('string');

				expect(item.medalUrl).toMatchRegex(/^\/bitrix\/mobileapp\/mobile\/extensions\/bitrix\/assets\/gratitude-icons\/.*\.svg$/);
				expect(item.sort).toMatchRegex(/^\d+$/);
			});
		});

		GratitudeIcon.getEnums().forEach((enumItem) => {
			const { name, testId, path, imageUrl, content } = enumItem.getValue();

			test(`Element ${name} should have valid structure`, () => {
				expect(name).toBeDefined();
				expect(content).toBeDefined();
				expect(imageUrl).toBeDefined();
				expect(testId).toMatchRegex(/^[a-z-]+-gratitude-image$/);
				expect(path).toMatchRegex(new RegExp(`^${ImageDir}/[a-z-]+\\.svg$`));
			});
		});
	});
})();
