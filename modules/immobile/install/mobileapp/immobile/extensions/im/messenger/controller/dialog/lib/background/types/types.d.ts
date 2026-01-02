
export type BackgroundConfigurationRecord = Record<DialogBackgroundType, BackgroundConfiguration>;

declare type BackgroundConfiguration = {
	bottomColor: string,
	gradientColors: Array<string>,
	angle: number,
}

type DialogBackgroundType = string;

