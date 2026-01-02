/**
 * @module settings-v2/structure/src/item-create-helper
 */
jn.define('settings-v2/structure/src/item-create-helper', (require, exports, module) => {
	const { SettingsPageId, SettingItemType } = require('settings-v2/const');
	const { Type } = require('type');

	function assertDefined(expectedKeys, props, entityName)
	{
		expectedKeys.forEach((key) => {
			if (Type.isNil(props[key]))
			{
				throw new TypeError(`${entityName} must have ${key}, but got ${JSON.stringify(props)}`);
			}
		});
	}

	/**
	 * @param {SettingLink} props
	 * @returns {SettingLink}
	 */
	function createLink(props)
	{
		const {
			id,
			title,
			nextPage,
			subtitle,
			icon,
			prefilter,
			nextPageParams,
		} = props;

		assertDefined(['id', 'title', 'nextPage'], props, 'Link');

		if (!Object.values(SettingsPageId).includes(nextPage))
		{
			throw new Error(`Invalid nextPage "${nextPage}". Expected one of: ${Object.values(SettingsPageId).join(', ')}`);
		}

		return {
			id,
			title,
			nextPage,
			subtitle,
			icon,
			prefilter,
			nextPageParams,
			type: SettingItemType.LINK,
		};
	}

	/**
	 * @param {SettingInfo} props
	 * @returns {SettingInfo}
	 */
	function createCacheInfo(props)
	{
		const {
			id,
			title,
			subtitle,
			onClick,
			controller,
			icon,
			iconColor,
			modeText,
			prefilter,
		} = props;

		assertDefined(['id', 'title'], props, 'Info');

		return {
			id,
			title,
			subtitle,
			onClick,
			controller,
			icon,
			iconColor,
			modeText,
			prefilter,
			type: SettingItemType.CACHE_INFO,
		};
	}

	/**
	 * @param {SettingSection} props
	 * @returns {SettingSection}
	 */
	function createSection(props)
	{
		const {
			id,
			title,
			items,
			prefilter,
			prepareItems,
			divider,
		} = props;

		assertDefined(['id', 'items'], props, 'Section');

		return {
			id,
			title,
			items,
			prefilter,
			prepareItems,
			divider,
			type: SettingItemType.SECTION,
		};
	}

	/**
	 * @param {SettingToggle} props
	 * @returns {SettingToggle}
	 */
	function createToggle(props)
	{
		const {
			id,
			title,
			subtitle,
			divider,
			icon,
			prefilter,
			controller,
		} = props;

		assertDefined(['id', 'title', 'controller'], props, 'Toggle');

		return {
			id,
			title,
			subtitle,
			divider,
			icon,
			prefilter,
			type: SettingItemType.TOGGLE,
			controller,
		};
	}

	/**
	 * @param {SettingButton} props
	 * @returns {SettingButton}
	 */
	function createButton(props)
	{
		const {
			id,
			title,
			onClick,
			subtitle,
			divider,
			icon,
			prefilter,
			color,
		} = props;

		assertDefined(['id', 'title', 'onClick'], props, 'Button');

		return {
			id,
			title,
			onClick,
			subtitle,
			divider,
			icon,
			color,
			type: SettingItemType.BUTTON,
			prefilter,
		};
	}

	/**
	 * @param {SettingThemeSwitch} props
	 * @returns {SettingThemeSwitch}
	 */
	function createThemeSwitch(props)
	{
		const {
			id,
			controller,
			divider,
		} = props;

		assertDefined(['id', 'controller'], props, 'Theme');

		return {
			id,
			controller,
			divider,
			type: SettingItemType.THEME,
		};
	}

	/**
	 * @param {SettingStyleSwitch} props
	 * @returns {SettingStyleSwitch}
	 */
	function createStyleSwitch(props)
	{
		const {
			id,
			controller,
		} = props;

		assertDefined(['id', 'controller'], props, 'StyleSwitch');

		return {
			id,
			controller,
			type: SettingItemType.STYLE,
		};
	}

	/**
	 * @param {SettingVideoQualitySwitch} props
	 * @returns {SettingVideoQualitySwitch}
	 */
	function createVideoQualitySwitch(props)
	{
		const {
			id,
			controller,
		} = props;

		assertDefined(['id', 'controller'], props, 'VideoQuality');

		return {
			id,
			controller,
			type: SettingItemType.VIDEO_QUALITY,
		};
	}

	/**
	 * @param {SettingDescription} props
	 * @return {SettingDescription}
	 */
	function createDescription(props)
	{
		const {
			id,
			text,
		} = props;

		assertDefined(['id', 'text'], props, 'Description');

		return {
			id,
			text,
			type: SettingItemType.DESCRIPTION,
		};
	}

	/**
	 * @param {SettingVideoBanner} props
	 * @return {SettingVideoBanner}
	 */
	function createVideoBanner(props)
	{
		const {
			id,
			controller,
		} = props;

		assertDefined(['id', 'controller'], props, 'VideoBanner');

		return {
			id,
			controller,
			type: SettingItemType.VIDEO_BANNER,
		};
	}

	/**
	 * @param {SettingLocSelector} props
	 * @return {SettingLocSelector}
	 */
	function createLocSelector(props)
	{
		const {
			id,
			controller,
			title,
			icon,
			divider,
		} = props;

		assertDefined(['id', 'controller'], props, 'LocSelector');

		return {
			id,
			controller,
			title,
			icon,
			divider,
			type: SettingItemType.LOC_SELECTOR,
		};
	}

	/**
	 * @param {SettingCacheIntervalSelector} props
	 * @return {SettingCacheIntervalSelector}
	 */
	function createCacheIntervalSelector(props)
	{
		const {
			id,
			controller,
			title,
			icon,
		} = props;

		assertDefined(['id', 'controller'], props, 'CacheIntervalSelector');

		return {
			id,
			controller,
			title,
			icon,
			type: SettingItemType.CACHE_INTERVAL,
		};
	}

	/**
	 * @param {SettingCacheBanner} props
	 * @return {SettingCacheBanner}
	 */
	function createCacheBanner(props)
	{
		const {
			id,
		} = props;

		assertDefined(['id'], props, 'CacheIntervalSelector');

		return {
			id,
			type: SettingItemType.CACHE_BANNER,
		};
	}

	/**
	 * @param {SettingBanner} props
	 * @return {SettingBanner}
	 */
	function createBanner(props)
	{
		const {
			id,
			bannerImageName,
			text,
			divider,
		} = props;

		assertDefined(['id', 'text'], props, 'CacheIntervalSelector');

		return {
			id,
			bannerImageName,
			text,
			divider,
			type: SettingItemType.BANNER,
		};
	}

	/**
	 * @param {SettingImage} props
	 * @return {SettingImage}
	 */
	function createImage(props)
	{
		const {
			id,
			name,
			externalStyle,
		} = props;

		assertDefined(['id', 'name'], props, 'Image');

		return {
			id,
			name,
			externalStyle,
			type: SettingItemType.IMAGE,
		};
	}

	module.exports = {
		createLink,
		createCacheInfo,
		createSection,
		createToggle,
		createButton,
		createThemeSwitch,
		createStyleSwitch,
		createVideoQualitySwitch,
		createDescription,
		createVideoBanner,
		createLocSelector,
		createCacheIntervalSelector,
		createCacheBanner,
		createBanner,
		createImage,
	};
});
