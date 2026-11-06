import { requestFormFields } from '../../config';
import {OCDRequest} from '@features/requests';
import {Disclosure, DisclosureButton, DisclosurePanel} from '@headlessui/react'
import {ChevronDownIcon} from '@heroicons/react/20/solid'

export interface RequestDetailsSectionProps {
    request: OCDRequest;
}

const RANKED_POS = ['Primary', 'Secondary', 'Tertiary'] as const;

const isRankedChallenges = (value: any): boolean =>
    !!value && typeof value === 'object' && !Array.isArray(value) &&
    ('primary' in value || 'secondary' in value || 'tertiary' in value);

// Helper function to render field values (extracted to reduce nesting)
const renderFieldValue = (value: any, fieldKey: string) => {
    if (isRankedChallenges(value)) {
        const ranks = ['primary', 'secondary', 'tertiary'] as const;
        const entries = ranks
            .map((rank, index) => ({rank: RANKED_POS[index], entry: value?.[rank]}))
            .filter((r) => r.entry && (r.entry.label || r.entry.value));

        if (entries.length === 0) {
            return <span className="text-firefly-900/80">None specified</span>;
        }

        return (
            <ul className="mt-1 ml-4 list-disc list-inside">
                {entries.map(({rank, entry}) => (
                    <li key={rank} className="text-firefly-900/80 py-1">
                        {rank}: {entry.label || entry.value}
                    </li>
                ))}
            </ul>
        );
    }

    if (Array.isArray(value)) {
        if (value.length === 0) {
            return <span className="text-firefly-900/80">None specified</span>;
        }

        return (
            <ul className="mt-1 ml-4 list-disc list-inside">
                {value.map((item, index) => (
                    <li key={`${fieldKey}-${item.label || item}-${index}`} className="text-firefly-900/80 py-1">
                        {item.label || item}
                    </li>
                ))}
            </ul>
        );
    }

    return <span className="text-firefly-900/80">{value ?? 'N/A'}</span>;
};

export default function RequestDetails({request}: Readonly<RequestDetailsSectionProps>) {
    return (
        <section id="request_details" className='my-8'>
            <div className="grid grid-cols-3 gap-4">
                <div className="col-span-2">
                    <div className="max-w-screen-xl mx-auto px-5 bg-white min-h-sceen">
                        <div className="grid divide-y divide-neutral-200 mx-auto">
                            {requestFormFields.map(step => {
                                const fields = Object.entries(step.fields).filter(([key, field]) => {
                                    if (!field.label || field.type === 'hidden') return false;
                                    if (field.show && !field.show(request)) return false;
                                    const value = (request.detail as any)[key];
                                    return !(value === undefined || value === '');
                                });

                                if (fields.length === 0) return null;

                                return (
                                    <Disclosure key={step.label} as="div" className="p-6" defaultOpen={false}>
                                        <DisclosureButton
                                            className="group flex w-full items-center justify-between">
                                            <span
                                                className="text-xl font-medium group-data-hover:text-firefly-700">
                                                {step.label}
                                            </span>
                                            <ChevronDownIcon
                                                className="size-5 fill-firefly-700/60 group-data-hover:fill-firefly-600 group-data-open:rotate-180"/>
                                        </DisclosureButton>
                                        <DisclosurePanel className="mt-2 text-xl/5 text-firefly-900/80">
                                            <ul className="group-open:animate-fadeIn list-none">
                                                {fields.map(([key, field]) => {
                                                    const value = (request.detail as any)[key];
                                                    return (
                                                        <li key={key} className='py-2 text-xl capitalize'>
                                                            <span className="text-firefly-800">{field.label}: </span>
                                                            <br/>
                                                            {renderFieldValue(value, key)}
                                                        </li>
                                                    );
                                                })}
                                            </ul>
                                        </DisclosurePanel>
                                    </Disclosure>
                                );
                            })}
                        </div>
                    </div>

                </div>
                <div>
                    <div className='py-2'>
                        <h2 className="text-2xl text-firefly-800">Submission Date</h2>
                        <p className="mt-1 text-xl text-gray-900">
                            {new Date(request.created_at).toLocaleDateString()}
                        </p>
                    </div>
                    <div>
                        <h2 className="text-2xl text-firefly-800">Status</h2>
                        <p className="mt-1 text-xl text-gray-900">
                            {request.status.status_label}
                        </p>
                        {request.status.status_code == "rejected" && (
                            <p className="text-red-500 mt-2 text-lg">
                                Your request has been carefully reviewed IOC Review Panel, and we regret to tell you
                                that Capacity Development Facility is not able to meet your request.
                            </p>
                        )}
                        {request.status.status_code == "unmatched" && (
                            <p className="text-red-500 mt-2 text-lg">
                                The CDF Secretariat explored multiple potential partnerships on your behalf. However,
                                after three months of outreach, your request was not accepted by any of our partners.
                                That said, your submission has helped inform both the CDF and IOC/UNESCO of an important
                                gap in current training and workshop offerings, and we recognize its relevance. We will
                                continue to keep your request in mind and will reach out if similar opportunities arise
                                in the future. We encourage you to check back with us for updates.
                            </p>
                        )}
                    </div>
                </div>
            </div>
        </section>
    );
}
