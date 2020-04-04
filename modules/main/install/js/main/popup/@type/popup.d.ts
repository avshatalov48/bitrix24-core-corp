declare type popupOptions = any;

declare type popupWindowButtonOptions = {
    id?: string,
    text?: string,
    className?: string,
    events?: {[event: string]: (event) => {}}
};

declare type popupMenuItemOptions = {
    id?: string,
    text?: string,
    title?: string,
    disabled?: boolean,
    href?: string,
    target?: string,
    className?: string,
    delimiter?: boolean,
    menuShowDelay?: number,
    subMenuOffsetX?: number,
    events?: {[event: string]: (event) => {}},
    dataset?: {[key: string]: string},
    onclick?: () => {} | string,
    items?: []
};

declare type pos = {
    left: number,
    top: number,
    bottom: number,
    windowSize: number,
    windowScroll: number,
    popupWidth: number,
    popupHeight: number
};

declare module 'main.popup' {
    namespace PopupWindowManager {
        function create(id: string, element?: Element, options?: popupOptions): PopupWindow;
        function getCurrentPopup(): PopupWindow | null;
        function isPopupExists(id): boolean;
        function isAnyPopupShown(): boolean;
        function getMaxZIndex(): number;
    }

    class PopupWindow {
        constructor(id, element?: Element, options?: popupOptions);
        setContent(content: string | Element | Node): void;
        setButtons(setButtons: PopupWindowButton[]): void;
        getButtons(): PopupWindowButton[];
        getButton(id): PopupWindowButton | null;
        setBindElement(element: Element): void;
        getBindElementPos(element: Element | any): pos;
        setAngle(params: {offset: number, position?: 'top' | 'bottom' | 'left' | 'right'}): void;
        getWidth(): number;
        setWidth(width: number): void;
        getHeight(): number;
        setHeight(height: number): void;
        getMinWidth(): number;
        setMinWidth(minWidth: number): void;
        getMinHeight(): number;
        setMinHeight(minHeight: number): void;
        getMaxWidth(): number;
        setMaxWidth(maxWidth: number): void;
        getMaxHeight(): number;
        setMaxHeight(maxHeight: number): void;
        setWidthProperty(property: string, width: number): void;
        setHeightProperty(property: string, height: number): void;
        setResizeMode(mode: boolean): void;
        getPopupContainer(): Element;
        isTopAngle(): boolean;
        isBottomAngle(): boolean;
        isTopOrBottomAngle(): boolean;
        getAngleHeight(): number;
        setOffset(offset: {offsetTop: number, offsetLeft: number}): void;
        setTitleBar(title: string | {content: string}): void;
        setClosingByEsc(boolean): void;
        setAutoHide(boolean): void;
        bindAutoHide(): void;
        unbindAutoHide(): void;
        setOverlay(params: {backgroundColor?: string, opacity?: number}): void;
        removeOverlay(): void;
        hideOverlay(): void;
        showOverlay(): void;
        resizeOverlay(): void;
        getZindex(): number;
        adjustOverlayZindex(): void;
        show(): void;
        close(): void;
        showAnimation(): void;
        closeAnimation(): void;
        isShown(): void;
        destroy(): void;
        enterFullScreen(): void;
        adjustPosition(params: {
            forceBindPosition?: boolean,
            forceLeft?: boolean,
            forceTop?: boolean,
            position: 'top' | 'bootom'
        }): void;
        move(offsetX: number, offsetY: number, pageX: number, pageY: number): void;
        setOptions(options: popupOptions): void;
        getOptions(options: string, defaultValue?: any): void;
    }

    class PopupWindowButton {
        constructor(options: popupWindowButtonOptions);
        render(): Element;
        getId(): string;
        getName(): string;
        getContainer(): Element;
        setName(name: string): void;
        setClassName(className: string): void;
        addClassName(className: string): void;
        removeClassName(className: string): void;
    }

    class PopupWindowButtonLink extends PopupWindowButton {}
    class PopupWindowCustomButton extends PopupWindowButton {}

    interface PopupMenu {
        Data: {[id: string]: PopupMenuWindow};
        currentItem: PopupMenuWindow | null;
        show(id: string, bindElement: HTMLFormElement | null, menuItems: {}[], params: popupOptions): void;
        create(id: string, bindElement: HTMLFormElement | null, menuItems: {}[], params: popupOptions): PopupMenuWindow;
        getCurrentMenu(): PopupMenuWindow | null;
        getMenuById(id): PopupMenuWindow | null;
        destroy(id): void;
    }

    class PopupMenuWindow {
        constructor(id: string, bindElement: HTMLFormElement | null, menuItems: {}[], params: popupOptions);
        getPopupWindow(): PopupMenuWindow;
        show(): void;
        close(): void;
        destroy(): void;
        containsTarget(target: Element): boolean;
        setParentMenuWindow(parent: PopupMenuWindow): void;
        getParentMenuWindow(): PopupMenuWindow | null;
        getRootMenuWindow(): PopupMenuWindow | null;
        setParentMenuItem(item: PopupMenuItem): void;
        getParentMenuItem(): PopupMenuItem | null;
        addMenuItem(menuItem: any, targetId: string): void;
        addMenuItemInternal(menuItem: any, targetId: string): void;
        removeMenuItem(id: string): void;
        getMenuItem(id: string): PopupMenuItem | null;
        getMenuItems(): PopupMenuItem[];
        getMenuItemPosition(): number;
    }
    
    class PopupMenuItem {
        constructor(options: popupMenuItemOptions);
        getLayout(): Element;
        getContainer(): Element;
        getTextContainer(): Element;
        hasSubMenu(): boolean;
        showSubMenu(): void;
        addSubMenu(items: []): void;
        closeSubMenu(): void;
        closeSiblings(): void;
        closeChildren(): void;
        destroySubMenu(): void;
        destroyChildren(): void;
        adjustSubMenu(): void;
        getPopupPadding(): number;
        getSubMenu(): PopupMenuWindow | null;
        getId(): string;
        setMenuWindow(menu: PopupMenuWindow): string;
        getMenuWindow(): PopupMenuWindow | null;
        getMenuShowDelay(): number;
        enable(): void;
        disable(): void;
        isDisabled(): boolean;
    }
}