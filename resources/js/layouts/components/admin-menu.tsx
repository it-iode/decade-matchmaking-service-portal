import React from 'react';
import { Link } from '@inertiajs/react';

export default function AdminMenu() {
    return (
        <nav className="space-y-2">
            <h3 className="text-lg font-semibold text-gray-900 mb-4">Admin Panel</h3>
            <Link
                href={route('admin.dashboard.index')}
                className="block px-3 py-2 text-sm font-medium text-gray-700 rounded-md hover:bg-gray-100 hover:text-gray-900"
            >
                Dashboard
            </Link>
            <Link
                href={route('admin.notifications.index')}
                className="block px-3 py-2 text-sm font-medium text-gray-700 rounded-md hover:bg-gray-100 hover:text-gray-900"
            >
                Notifications
            </Link>
            <Link
                href={route('admin.users.index')}
                className="block px-3 py-2 text-sm font-medium text-gray-700 rounded-md hover:bg-gray-100 hover:text-gray-900"
            >
                User Management
            </Link>
        </nav>
    );
} 