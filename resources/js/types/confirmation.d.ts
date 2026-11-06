export type ConfirmationType = 'danger' | 'warning' | 'info' | 'success';

export interface ConfirmationOptions {
  title: string;
  message: string;
  type?: ConfirmationType;
  confirmText?: string;
  cancelText?: string;
  confirmButtonColor?: 'red' | 'orange' | 'yellow' | 'green' | 'blue' | 'zinc';
  icon?: React.ComponentType<{ className?: string; 'data-slot'?: string }>;
  onConfirm?: () => void | Promise<void>;
  onCancel?: () => void;
  showCancel?: boolean;
  size?: 'xs' | 'sm' | 'md' | 'lg' | 'xl';
}

export interface ConfirmationState {
  isOpen: boolean;
  options: ConfirmationOptions | null;
  isProcessing: boolean;
}

export interface ConfirmationContextValue {
  state: ConfirmationState;
  confirm: (options: ConfirmationOptions) => Promise<boolean>;
  close: () => void;
}