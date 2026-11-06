import React from 'react';
import { usePage, useForm } from '@inertiajs/react';
import { Auth, NavigationConfig, NavigationItem } from '@/types';
import * as HeroIcons from '@heroicons/react/16/solid';
import {
    Dropdown,
    DropdownButton,
    DropdownMenu,
    DropdownItem,
    DropdownDivider,
    DropdownLabel
} from '@/ui/primitives/dropdown';
import { Avatar } from '@/ui/primitives/avatar';
import { Badge } from '@/ui/primitives/badge';
import { ChevronDownIcon } from '@heroicons/react/16/solid';

export default function NavigationMenu() {
    const { auth, navigation } = usePage<{
        auth: Auth;
        navigation?: NavigationConfig;
    }>().props;

    const user = auth.user;
    const form = useForm({});

    if (!user || !navigation?.user) {
        return null;
    }

    // Helper to get user initials from name
    const getUserInitials = (name: string): string => {
        const parts = name.trim().split(' ');
        if (parts.length >= 2) {
            return `${parts[0][0]}${parts[parts.length - 1][0]}`.toUpperCase();
        }
        return name.substring(0, 2).toUpperCase();
    };

    // Helper to get icon component
    const getIconComponent = (iconName?: string) => {
        if (!iconName) return null;
        return HeroIcons[iconName as keyof typeof HeroIcons] || null;
    };

    // Handle form actions (like sign-out)
    const handleAction = (item: NavigationItem, e: React.FormEvent) => {
        e.preventDefault();
        if (item.action === 'sign-out') {
            form.post(route('sign.out'));
        }
    };

    // Map badge variants to Catalyst UI badge colors
    const getBadgeColor = (variant?: string): 'red' | 'amber' | 'blue' | 'zinc' => {
        const colorMap = {
            danger: 'red' as const,
            warning: 'amber' as const,
            info: 'blue' as const,
            primary: 'blue' as const,
        };
        return colorMap[variant as keyof typeof colorMap] ?? 'blue';
    };

    // Render badge using Catalyst UI Badge component
    const renderBadge = (badge?: NavigationItem['badge']) => {
        if (!badge) return null;

        return (
            <Badge color={getBadgeColor(badge.variant)} className="ml-auto">
                {badge.value}
            </Badge>
        );
    };

    // Render navigation item with Catalyst UI components
    const renderNavigationItem = (item: NavigationItem, index: number) => {
        // Handle divider
        if (item.divider) {
            return <DropdownDivider key={`${item.id}-${index}`} />;
        }

        // Skip if not visible
        if (!item.visible) {
            return null;
        }

        const Icon = getIconComponent(item.icon);

        // Handle action-based items (e.g., sign-out)
        if (item.action) {
            return (
                <DropdownItem
                    key={item.id}
                    onClick={(e: React.MouseEvent) => {
                        e.preventDefault();
                        handleAction(item, e as unknown as React.FormEvent);
                    }}
                >
                    {Icon && <Icon data-slot="icon" />}
                    <DropdownLabel>{item.label}</DropdownLabel>
                    {renderBadge(item.badge)}
                </DropdownItem>
            );
        }

        // Handle link-based items
        const href = item.route ? route(item.route) : item.href;

        return (
            <DropdownItem key={item.id} href={href ?? '#'}>
                {Icon && <Icon data-slot="icon" />}
                <DropdownLabel>{item.label}</DropdownLabel>
                {renderBadge(item.badge)}
            </DropdownItem>
        );
    };

    return (
        <Dropdown>
            {/* Modern dropdown button with avatar */}
            <DropdownButton  plain className="inline-flex items-center gap-2 px-3 py-2  text-white focus:outline-none">
                <Avatar
                    initials={getUserInitials(navigation.user.displayName)}
                    alt={navigation.user.displayName}
                    className="size-8 text-white bg-firefly-600"
                />
                <span className="text-base text-white font-medium">
                    {navigation.user.displayName}
                </span>
                <ChevronDownIcon className="size-4 text-white" />
            </DropdownButton>

            {/* Dropdown menu with Catalyst UI styling */}
            <DropdownMenu anchor="bottom end" className="min-w-64 z-60">
                {navigation.items.map((item, index) =>
                    renderNavigationItem(item, index)
                )}
            </DropdownMenu>
        </Dropdown>
    );
}
