import { SidePanel } from 'main.sidepanel';

export const SidePanelInstance = window === top ? SidePanel.Instance : new SidePanel.Manager({});
