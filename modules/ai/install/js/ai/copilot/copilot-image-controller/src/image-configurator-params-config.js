import type { EngineInfo, ImageCopilotFormat } from 'ai.engine';
import { Loc } from 'main.core';
import { Editor, Main } from 'ui.icon-set.api.core';

export type ImageConfiguratorParam = {
	title: string;
	icon: string;
	options?: ImageConfiguratorParamOption[];
}

export type ImageConfiguratorParamOption = {
	title: string,
	value: string,
}

export type ImageConfiguratorParamsCurrentValues = {
	format: string;
	engine: string;
}

type getParamsOptions = {
	formats: ImageCopilotFormat[];
	engines: EngineInfo[];
}

export const getParams = (options: getParamsOptions): {[code: string]: ImageConfiguratorParam} => {
	return {
		format: {
			title: Loc.getMessage('AI_COPILOT_IMAGE_FORMAT_OPTION_TITLE'),
			icon: Editor.INCERT_IMAGE,
			options: getOptionsFromFormats(options.formats),
		},
		engine: {
			title: Loc.getMessage('AI_COPILOT_IMAGE_ENGINE_OPTION_TITLE'),
			icon: Main.ROBOT,
			options: getOptionsFromEngines(options.engines),
		},
	};
};

const getOptionsFromFormats = (formats: ImageCopilotFormat[]): ImageConfiguratorParamOption[] => {
	return formats.map((format) => {
		return {
			title: format.name,
			value: format.code,
		};
	});
};

const getOptionsFromEngines = (engines: EngineInfo[]): ImageConfiguratorParamOption[] => {
	return engines.map((engine) => {
		return {
			title: engine.title,
			value: engine.code,
		};
	});
};
