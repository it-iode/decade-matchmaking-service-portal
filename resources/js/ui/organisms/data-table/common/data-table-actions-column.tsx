import React from 'react';
import { Dropdown, DropdownButton, DropdownItem, DropdownMenu, DropdownDivider } from '@ui/primitives/dropdown';
import { ChevronDownIcon } from '@heroicons/react/16/solid';

export interface DataTableAction<T = any> {
    key: string;
    label: string;
    onClick: (row: T) => void;
    divider?: boolean;
    className?: string;
    disabled?: boolean;
}

interface DataTableActionsColumnProps<T = any> {
    row: T;
    actions: DataTableAction<T>[];
    buttonLabel?: string;
    buttonColor?: "dark/zinc" | "dark/white" | "white" | "dark" | "zinc" | "red" | "orange" | "amber" | "yellow" | "lime" | "green" | "emerald" | "teal" | "cyan" | "sky" | "blue" | "indigo" | "violet" | "purple" | "fuchsia" | "pink" | "rose";
    buttonClassName?: string;
    menuAnchor?: 'bottom' | 'bottom start' | 'bottom end' | 'top' | 'top start' | 'top end';
}

export function DataTableActionsColumn<T = any>({
                                                    row,
                                                    actions,
                                                    buttonLabel = 'Actions',
                                                    buttonColor = 'white',
                                                    buttonClassName = 'flex items-center gap-2',
                                                    menuAnchor = 'bottom end'
                                                }: Readonly<DataTableActionsColumnProps<T>>) {
    if (!actions || actions.length === 0) {
        return null;
    }

    return (
        <Dropdown>
            <DropdownButton
                color={buttonColor}
                className={buttonClassName}
            >
                {buttonLabel}
                <ChevronDownIcon className="h-4 w-4" />
            </DropdownButton>
            <DropdownMenu anchor={menuAnchor}>
                {actions.map((action, index) => (
                    <React.Fragment key={action.key}>
                        {action.divider && index > 0 && <DropdownDivider />}
                        <DropdownItem
                            onClick={() => action.onClick(row)}
                            className={action.className}
                            disabled={action.disabled}
                        >
                            {action.label}
                        </DropdownItem>
                    </React.Fragment>
                ))}
            </DropdownMenu>
        </Dropdown>
    );
}
