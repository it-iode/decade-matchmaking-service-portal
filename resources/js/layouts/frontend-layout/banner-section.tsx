import { usePage } from '@inertiajs/react';
import React, { useState } from 'react';
import YouTube from 'react-youtube';
import { ArrowTopRightOnSquareIcon } from '@heroicons/react/16/solid';
import { OpportunitiesDialog } from '@features/opportunities';
import { Opportunity } from '@/types';
import { Breadcrumb } from '@ui/molecules';

interface MetricCardProps {
    value: string | number;
    label: string;
    onClick?: () => void;
    isClickable?: boolean;
}

function MetricCard({ value, label, onClick, isClickable = false }: Readonly<MetricCardProps>) {
    const baseClasses = `
        backdrop-blur-sm bg-white/10 rounded-2xl p-6
        shadow-[0_8px_32px_rgba(0,0,0,0.3)]
        transition-all duration-300
        text-left
        relative
    `;

    const interactiveClasses = isClickable
        ? `
            border-2 border-white/40
            hover:bg-white/20 hover:scale-105
            hover:shadow-[0_12px_48px_rgba(255,255,255,0.2)]
            cursor-pointer
            group
        `
        : 'border border-white/20 hover:bg-white/15';

    if (isClickable) {
        return (
            <button
                type="button"
                className={`${baseClasses} ${interactiveClasses} w-full`}
                onClick={onClick}
            >
                {/* Corner icon indicator */}
                <ArrowTopRightOnSquareIcon
                    className="absolute top-4 right-4 w-5 h-5 text-white/60 group-hover:text-white transition-colors"
                    aria-hidden="true"
                />
                <span className="block text-5xl font-extrabold text-white drop-shadow-[0_2px_8px_rgba(0,0,0,0.5)]">
                    {value}
                </span>
                <span className="mt-3 block text-lg font-medium text-white/90 leading-tight">
                    {label}
                </span>
            </button>
        );
    }

    return (
        <div className={`${baseClasses} ${interactiveClasses}`}>
            <span className="block text-5xl font-extrabold text-white drop-shadow-[0_2px_8px_rgba(0,0,0,0.5)]">
                {value}
            </span>
            <span className="mt-3 block text-lg font-medium text-white/90 leading-tight">
                {label}
            </span>
        </div>
    );
}

export interface Banner {
    title: string;
    description: string;
    image: string;
}

export interface OCDMetrics {
    number_successful_matches: number;
    number_fully_closed_matches: number;
    number_user_requests_in_implementation: number;
    committed_funding_amount: number;
    number_of_open_partner_opportunities: number;
}

export interface YoutubeEmbed {
    title: string;
    src: string;
}

interface BannerSectionProps {
    bannerConfiguration?: Banner;
    YoutubeEmbed?: YoutubeEmbed;
    metrics?: OCDMetrics;
    recentOpportunities?: Opportunity[];
}

export default function BannerSection({
    bannerConfiguration,
    YoutubeEmbed,
    metrics,
    recentOpportunities,
}: Readonly<BannerSectionProps>) {
    const [showOpportunitiesDialog, setShowOpportunitiesDialog] = useState(false);

    const pageBannerConfiguration =
        bannerConfiguration ?? (usePage().props.banner as Banner);
    const pageYoutubeEmbed =
        YoutubeEmbed ?? (usePage().props.YoutubeEmbed as YoutubeEmbed);
    const pageMetrics = metrics ?? (usePage().props.metrics as OCDMetrics);
    const pageRecentOpportunities =
        recentOpportunities ??
        (usePage().props.recentOpportunities as Opportunity[]);

    const opts = {
        height: '100%',
        width: '100%',
        playerVars: {
            autoplay: 0,
        },
    };

    if (!pageBannerConfiguration?.image) {
        return null;
    }

    return (
        <section
            style={{ backgroundImage: `url(${pageBannerConfiguration.image})` }}
            className="relative bg-cover bg-center bg-casal-700 px-4 text-center text-white min-h-[300px] inset-shadow-[0_-20px_50px_rgba(0,0,0,0.5)]"
        >
            {/* Gradient overlay for better header text contrast */}
            <div className="absolute inset-x-0 top-0 h-40 bg-gradient-to-b from-black/40 to-transparent pointer-events-none" />

            <div className="relative z-10 max-w-5xl mx-auto pt-30 sm:pt-40">
                <h2 className="text-5xl font-bold mb-6">Ocean Connector</h2>
                <p className="text-xl mb-8">{pageBannerConfiguration.description}</p>
            </div>

            {pageYoutubeEmbed?.src && (
                <div className="relative  max-w-5xl mx-auto">
                    <div className="aspect-video">
                        <YouTube
                            videoId={pageYoutubeEmbed.src}
                            opts={opts}
                            className="inset-0 w-full h-full"
                        />
                    </div>
                </div>
            )}

            {pageMetrics && (
                <div className="relative py-10 px-4">
                    {/* Row 1: 3 cards */}
                    <div className="mx-auto max-w-6xl grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 mb-6">
                        <MetricCard
                            value={pageMetrics.number_successful_matches}
                            label="Successful Matches"
                        />
                        <MetricCard
                            value={pageMetrics.number_fully_closed_matches}
                            label="Completed Trainings & Workshops"
                        />
                        <MetricCard
                            value={pageMetrics.number_user_requests_in_implementation}
                            label="Requests in Implementation"
                        />
                        <MetricCard
                            value={new Intl.NumberFormat('en-US', {
                                style: 'currency',
                                currency: 'USD',
                                maximumFractionDigits: 0,
                            }).format(pageMetrics.committed_funding_amount)}
                            label="Committed Funding"
                        />
                        <MetricCard
                            value={pageMetrics.number_of_open_partner_opportunities}
                            label="Open Opportunities"
                            onClick={() => setShowOpportunitiesDialog(true)}
                            isClickable={true}
                        />
                    </div>
                </div>
            )}

            {pageRecentOpportunities && (
                <OpportunitiesDialog
                    open={showOpportunitiesDialog}
                    onClose={() => setShowOpportunitiesDialog(false)}
                    opportunities={pageRecentOpportunities}
                />
            )}

            <div className="px-4">
                <Breadcrumb />
            </div>
        </section>
    );
}
