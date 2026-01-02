import {CommonRenderServiceProps} from "../service/render/types";

declare type RecentConfig = {
	services: {
		'quick-recent'?: {
			extension: string,
			props?: object,
		},
		'database-load'?: {
			extension: string,
			props?: object,
		},
		'server-load'?: {
			extension: string,
			props?: object,
		},
		'floating-button'?: {
			extension: string,
			props?: object,
		},
		'empty-state'?: {
			extension: string,
			props?: object,
		},
		'pagination'?: {
			extension: string,
			props?: object,
		},
		'search'?: {
			extension: string,
			props?: object,
		},
		'render': {
			extension: string,
			props: CommonRenderServiceProps,
		},
		'vuex'?: {
			extension: string,
			props?: object,
		},
		'action'?: {
			extension: string,
			props?: object,
		},
		'select'?: {
			extension: string,
			props?: object,
		},
		'external'?: {
			extension: string,
			props?: object,
		}
	},
};
