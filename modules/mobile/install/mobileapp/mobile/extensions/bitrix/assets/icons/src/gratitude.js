/**
 * @module assets/icons/src/gratitude
 */
jn.define('assets/icons/src/gratitude', (require, exports, module) => {
	const { BaseIcon } = require('assets/icons/src/base');
	const { Loc } = require('loc');

	const BaseDir = '/bitrix/mobileapp/mobile/extensions/bitrix/assets/gratitude-icons';
	const ImageDir = `${currentDomain}${BaseDir}`;
	const FeedMedalDir = '/bitrix/templates/mobile_app/images/lenta/medal';

	class GratitudeIcon extends BaseIcon
	{
		static BEER = new GratitudeIcon('BEER', {
			name: Loc.getMessage('M_UI_GRATITUDE_ICON_BEER'),
			testId: 'beer-gratitude-image',
			path: `${ImageDir}/beer.svg`,
			content: '',
			imageUrl: '',
			feedId: 'beer',
			feedUrl: `${BaseDir}/beer.svg`,
			feedBackground: `${FeedMedalDir}/14_beer/background_mobile_mono.svg`,
			feedSort: '14',
		});

		static CAKE = new GratitudeIcon('CAKE', {
			name: Loc.getMessage('M_UI_GRATITUDE_ICON_CAKE'),
			testId: 'cake-gratitude-image',
			path: `${ImageDir}/cake.svg`,
			content: '',
			imageUrl: '',
			feedId: 'cake',
			feedUrl: `${BaseDir}/cake.svg`,
			feedBackground: `${FeedMedalDir}/10_cake/background_mobile_mono.svg`,
			feedSort: '10',
		});

		static COCKTAIL = new GratitudeIcon('COCKTAIL', {
			name: Loc.getMessage('M_UI_GRATITUDE_ICON_COCKTAIL'),
			testId: 'cocktail-gratitude-image',
			path: `${ImageDir}/cocktail.svg`,
			content: '',
			imageUrl: '',
			feedId: 'drink',
			feedUrl: `${BaseDir}/cocktail.svg`,
			feedBackground: `${FeedMedalDir}/11_drink/background_mobile_mono.svg`,
			feedSort: '11',
		});

		static CROWN = new GratitudeIcon('CROWN', {
			name: Loc.getMessage('M_UI_GRATITUDE_ICON_CROWN'),
			testId: 'crown-gratitude-image',
			path: `${ImageDir}/crown.svg`,
			content: '',
			imageUrl: '',
			feedId: 'crown',
			feedUrl: `${BaseDir}/crown.svg`,
			feedBackground: `${FeedMedalDir}/08_crown/background_mobile_mono.svg`,
			feedSort: '08',
		});

		static CUP = new GratitudeIcon('CUP', {
			name: Loc.getMessage('M_UI_GRATITUDE_ICON_CUP'),
			testId: 'cup-gratitude-image',
			path: `${ImageDir}/cup.svg`,
			content: '',
			imageUrl: '',
			feedId: 'cup',
			feedUrl: `${BaseDir}/cup.svg`,
			feedBackground: `${FeedMedalDir}/13_cup/background_mobile_mono.svg`,
			feedSort: '13',
		});

		static FLAG = new GratitudeIcon('FLAG', {
			name: Loc.getMessage('M_UI_GRATITUDE_ICON_FLAG'),
			testId: 'flag-gratitude-image',
			path: `${ImageDir}/flag.svg`,
			content: '',
			imageUrl: '',
			feedId: 'flag',
			feedUrl: `${BaseDir}/flag.svg`,
			feedBackground: `${FeedMedalDir}/05_flag/background_mobile_mono.svg`,
			feedSort: '05',
		});

		static FLOWER = new GratitudeIcon('FLOWER', {
			name: Loc.getMessage('M_UI_GRATITUDE_ICON_FLOWER'),
			testId: 'flower-gratitude-image',
			path: `${ImageDir}/flower.svg`,
			content: '',
			imageUrl: '',
			feedId: 'flowers',
			feedUrl: `${BaseDir}/flower.svg`,
			feedBackground: `${FeedMedalDir}/02_flowers/background_mobile_mono.svg`,
			feedSort: '02',
		});

		static GIFT = new GratitudeIcon('GIFT', {
			name: Loc.getMessage('M_UI_GRATITUDE_ICON_GIFT'),
			testId: 'gift-gratitude-image',
			path: `${ImageDir}/gift.svg`,
			content: '',
			imageUrl: '',
			feedId: 'gift',
			feedUrl: `${BaseDir}/gift.svg`,
			feedBackground: `${FeedMedalDir}/07_gift/background_mobile_mono.svg`,
			feedSort: '07',
		});

		static HEART = new GratitudeIcon('HEART', {
			name: Loc.getMessage('M_UI_GRATITUDE_ICON_HEART'),
			testId: 'heart-gratitude-image',
			path: `${ImageDir}/heart.svg`,
			content: '',
			imageUrl: '',
			feedId: 'heart',
			feedUrl: `${BaseDir}/heart.svg`,
			feedBackground: `${FeedMedalDir}/09_heart/background_mobile_mono.svg`,
			feedSort: '09',
		});

		static FIRST_PLACE = new GratitudeIcon('FIRST_PLACE', {
			name: Loc.getMessage('M_UI_GRATITUDE_ICON_FIRST_PLACE'),
			testId: 'first-place-gratitude-image',
			path: `${ImageDir}/medal.svg`,
			content: '',
			imageUrl: '',
			feedId: 'thefirst',
			feedUrl: `${BaseDir}/medal.svg`,
			feedBackground: `${FeedMedalDir}/06_thefirst/background_mobile_mono.svg`,
			feedSort: '06',
		});

		static MONEY = new GratitudeIcon('MONEY', {
			name: Loc.getMessage('M_UI_GRATITUDE_ICON_MONEY'),
			testId: 'money-gratitude-image',
			path: `${ImageDir}/money.svg`,
			content: '',
			imageUrl: '',
			feedId: 'money',
			feedUrl: `${BaseDir}/money.svg`,
			feedBackground: `${FeedMedalDir}/12_money/background_mobile_mono.svg`,
			feedSort: '12',
		});

		static SMILE = new GratitudeIcon('SMILE', {
			name: Loc.getMessage('M_UI_GRATITUDE_ICON_SMILE'),
			testId: 'smile-gratitude-image',
			path: `${ImageDir}/smile.svg`,
			content: '',
			imageUrl: '',
			feedId: 'smile',
			feedUrl: `${BaseDir}/smile.svg`,
			feedBackground: `${FeedMedalDir}/03_smile/background_mobile_mono.svg`,
			feedSort: '03',
		});

		static STAR = new GratitudeIcon('STAR', {
			name: Loc.getMessage('M_UI_GRATITUDE_ICON_STAR'),
			testId: 'star-gratitude-image',
			path: `${ImageDir}/star.svg`,
			content: '',
			imageUrl: '',
			feedId: 'star',
			feedUrl: `${BaseDir}/star.svg`,
			feedBackground: `${FeedMedalDir}/04_star/background_mobile_mono.svg`,
			feedSort: '04',
		});

		static SUCCESS = new GratitudeIcon('SUCCESS', {
			name: Loc.getMessage('M_UI_GRATITUDE_ICON_SUCCESS'),
			testId: 'success-gratitude-image',
			path: `${ImageDir}/success.svg`,
			content: '',
			imageUrl: '',
			feedId: 'thumbsup',
			feedUrl: `${BaseDir}/success.svg`,
			feedBackground: `${FeedMedalDir}/01_thumbsup/background_mobile_mono.svg`,
			feedSort: '01',
		});

		/**
		 * @param {string} name
		 * @returns {string|null}
		 */
		static getTestIdByName(name)
		{
			const badge = this.getEnums().find((item) => item.getIconName() === name);

			return badge ? badge.getValue().testId : null;
		}

		/**
		 * @param {string} feedId
		 * @returns {string|null}
		 */
		static getNameByFeedId(feedId)
		{
			const badge = this.getEnums().find((item) => item.getValue().feedId === feedId);

			return badge ? badge.getIconName() : null;
		}

		/**
		 * @param {string} name
		 * @returns {string|null}
		 */
		static getSvgUriByName(name)
		{
			const badge = this.getEnums().find((item) => item.getIconName() === name);

			return badge ? badge.getPath() : null;
		}

		/**
		 * @param {string} name
		 * @returns {GratitudeIcon|null}
		 */
		static getEnumByName(name)
		{
			return this.getEnums().find((item) => item.getIconName() === name) || null;
		}

		/**
		 * @returns {Object}
		 */
		static getFeedList()
		{
			return this.getEnums().reduce((acc, icon) => {
				acc[icon.getValue().feedId] = {
					medalUrl: icon.getValue().feedUrl,
					backgroundUrl: icon.getValue().feedBackground,
					medalSelectorUrl: icon.getValue().feedBackground,
					name: icon.getValue().name,
					sort: icon.getValue().feedSort,
				};

				return acc;
			}, {});
		}
	}

	module.exports = {
		GratitudeIcon,
	};
});
