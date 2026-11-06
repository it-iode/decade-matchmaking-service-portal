import React, { createContext, useState, useCallback, ReactNode } from 'react';
import { ConfirmationState, ConfirmationOptions, ConfirmationContextValue } from '@/types/confirmation';
import { ConfirmationDialog } from './confirmation-dialog';

const defaultState: ConfirmationState = {
  isOpen: false,
  options: null,
  isProcessing: false,
};

export const ConfirmationContext = createContext<ConfirmationContextValue | undefined>(undefined);

interface ConfirmationProviderProps {
  children: ReactNode;
}

export function ConfirmationProvider({ children }: ConfirmationProviderProps) {
  const [state, setState] = useState<ConfirmationState>(defaultState);
  const [resolvePromise, setResolvePromise] = useState<((value: boolean) => void) | null>(null);

  const confirm = useCallback((options: ConfirmationOptions): Promise<boolean> => {
    return new Promise((resolve) => {
      setState({
        isOpen: true,
        options,
        isProcessing: false,
      });
      setResolvePromise(() => resolve);
    });
  }, []);

  const handleConfirm = useCallback(async () => {
    if (!state.options) return;

    setState(prev => ({ ...prev, isProcessing: true }));

    try {
      if (state.options.onConfirm) {
        await state.options.onConfirm();
      }
      resolvePromise?.(true);
      close();
    } catch (error) {
      console.error('Confirmation action failed:', error);
      setState(prev => ({ ...prev, isProcessing: false }));
    }
  }, [state.options, resolvePromise]);

  const handleCancel = useCallback(() => {
    if (state.options?.onCancel) {
      state.options.onCancel();
    }
    resolvePromise?.(false);
    close();
  }, [state.options, resolvePromise]);

  const close = useCallback(() => {
    setState(defaultState);
    setResolvePromise(null);
  }, []);

  const contextValue: ConfirmationContextValue = {
    state,
    confirm,
    close,
  };

  return (
    <ConfirmationContext.Provider value={contextValue}>
      {children}
      <ConfirmationDialog 
        {...state}
        onConfirm={handleConfirm}
        onCancel={handleCancel}
      />
    </ConfirmationContext.Provider>
  );
}