import { IBaseRecentService } from '../base/type';

export interface IFloatingButtonService extends IBaseRecentService
{
    renderButton: () => Promise<void>;
    renderAccentButton: () => Promise<void>;
}

declare type CommonFloatingButtonServiceProps = {
    checkShouldShowButton?: () => boolean,
    onTap: () => Promise<void>,
}