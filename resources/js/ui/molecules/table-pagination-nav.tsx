import React from 'react';
import {PaginationLinkProps} from '@/types';
import {
    Pagination,
    PaginationPrevious,
    PaginationNext,
    PaginationList,
    PaginationPage,
    PaginationGap
} from '@ui/primitives/pagination';


interface RequestsPaginationNavProps {
    links: PaginationLinkProps[];
    prevPageUrl: string | null;
    nextPageUrl: string | null;
    from: number;
    to: number;
    total: number;
}

export function TablePaginationNav({
                                          links,
                                          prevPageUrl,
                                          nextPageUrl,
                                          from,
                                          to,
                                          total
                                      }: Readonly<RequestsPaginationNavProps>) {
    const pageLinks = links.filter(link =>
        link.label !== '&laquo; Previous' &&
        link.label !== 'Next &raquo;'
    );
    return (
        <div className="bg-white dark:bg-zinc-900">
            {/* Results summary */}
            <div
                className="flex items-center justify-between border-t border-zinc-200 px-4 py-3 sm:px-6 dark:border-zinc-700">
                {/* Mobile pagination - Simple Previous/Next */}
                <div className="flex flex-1 justify-between sm:hidden">
                    <PaginationPrevious href={prevPageUrl}/>
                    <PaginationNext href={nextPageUrl}/>
                </div>

                {/* Desktop pagination */}
                <div className="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                    <div>
                        <p className="text-sm text-zinc-700 dark:text-zinc-300">
                            Showing{' '}
                            <span className="font-medium">{from}</span>
                            {' '}to{' '}
                            <span className="font-medium">{to}</span>
                            {' '}of{' '}
                            <span className="font-medium">{total}</span>
                            {' '}results
                        </p>
                    </div>

                    <Pagination>
                        <PaginationPrevious href={prevPageUrl}/>
                        <PaginationList>
                            {pageLinks.map((link) => {
                                const isActive = link.active;
                                const isEllipsis = link.label.includes('...');

                                if (isEllipsis) {
                                    return <PaginationGap key={`gap-${link.label}`}/>;
                                }

                                return (
                                    <PaginationPage
                                        key={link.url}
                                        href={link.url}
                                        current={isActive}
                                    >
                                        {link.label}
                                    </PaginationPage>
                                );
                            })}
                        </PaginationList>
                        <PaginationNext href={nextPageUrl}/>
                    </Pagination>
                </div>
            </div>
        </div>
    )
}
