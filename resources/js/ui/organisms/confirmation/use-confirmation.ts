import { useContext } from 'react';
import { ConfirmationContext } from './confirmation-context';
import { ConfirmationOptions } from '@/types/confirmation';

export function useConfirmation() {
  const context = useContext(ConfirmationContext);

  if (!context) {
    throw new Error('useConfirmation must be used within a ConfirmationProvider');
  }

  return {
    confirm: context.confirm,
    close: context.close,
    isOpen: context.state.isOpen,
    isProcessing: context.state.isProcessing,
  };
}

// Convenience methods for common confirmation types
export function useDeleteConfirmation() {
  const { confirm } = useConfirmation();

  return (entityName: string, onConfirm: () => void | Promise<void>) => {
    return confirm({
      title: `Delete ${entityName}?`,
      message: `Are you sure you want to delete this ${entityName.toLowerCase()}? This action cannot be undone.`,
      type: 'danger',
      confirmText: 'Delete',
      confirmButtonColor: 'red',
      onConfirm,
    });
  };
}

export function useDangerConfirmation() {
  const { confirm } = useConfirmation();

  return (title: string, message: string, onConfirm: () => void | Promise<void>) => {
    return confirm({
      title,
      message,
      type: 'danger',
      confirmText: 'Confirm',
      confirmButtonColor: 'red',
      onConfirm,
    });
  };
}