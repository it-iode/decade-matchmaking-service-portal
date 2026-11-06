import React from 'react';
import { Alert, AlertTitle, AlertDescription, AlertActions } from '@ui/primitives/alert';
import { Button } from '@ui/primitives/button';
import { ExclamationTriangleIcon, InformationCircleIcon, CheckCircleIcon, XCircleIcon } from '@heroicons/react/16/solid';
import { ConfirmationOptions, ConfirmationType } from '@/types/confirmation';

interface ConfirmationDialogProps {
  isOpen: boolean;
  options: ConfirmationOptions | null;
  isProcessing: boolean;
  onConfirm: () => void;
  onCancel: () => void;
}

const typeConfig: Record<ConfirmationType, {
  icon: React.ComponentType<{ className?: string; 'data-slot'?: string }>;
  iconColor: string;
  confirmColor: 'red' | 'orange' | 'yellow' | 'green' | 'blue' | 'zinc';
}> = {
  danger: {
    icon: XCircleIcon,
    iconColor: 'text-red-600 dark:text-red-400',
    confirmColor: 'red',
  },
  warning: {
    icon: ExclamationTriangleIcon,
    iconColor: 'text-orange-600 dark:text-orange-400',
    confirmColor: 'orange',
  },
  info: {
    icon: InformationCircleIcon,
    iconColor: 'text-blue-600 dark:text-blue-400',
    confirmColor: 'blue',
  },
  success: {
    icon: CheckCircleIcon,
    iconColor: 'text-green-600 dark:text-green-400',
    confirmColor: 'green',
  },
};

export function ConfirmationDialog({
  isOpen,
  options,
  isProcessing,
  onConfirm,
  onCancel,
}: ConfirmationDialogProps) {
  if (!options) return null;

  const type = options.type || 'info';
  const config = typeConfig[type];
  const Icon = options.icon || config.icon;
  const confirmButtonColor = options.confirmButtonColor || config.confirmColor;

  return (
    <Alert open={isOpen} onClose={onCancel} size={options.size || 'md'}>
      <div className="flex gap-3">
        <Icon 
          className={`h-5 w-5 flex-shrink-0 ${config.iconColor}`} 
          data-slot="icon"
          aria-hidden="true"
        />
        <div className="flex-1">
          <AlertTitle>{options.title}</AlertTitle>
          <AlertDescription>{options.message}</AlertDescription>
        </div>
      </div>
      <AlertActions>
        {options.showCancel !== false && (
          <Button
            type="button"
            outline
            onClick={onCancel}
            disabled={isProcessing}
          >
            {options.cancelText || 'Cancel'}
          </Button>
        )}
        <Button
          type="button"
          color={confirmButtonColor}
          onClick={onConfirm}
          disabled={isProcessing}
        >
          {isProcessing ? 'Processing...' : (options.confirmText || 'Confirm')}
        </Button>
      </AlertActions>
    </Alert>
  );
}