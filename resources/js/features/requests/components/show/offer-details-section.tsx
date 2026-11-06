import React, {useState} from 'react';
import {useForm, usePage} from '@inertiajs/react';
import {RequestOffer, Auth, Document} from '@/types';
import {OCDRequest} from '../../types/request.types';
import {Button} from '@ui/primitives/button';
import {Field, Label} from '@ui/primitives/fieldset';
import {Badge} from '@ui/primitives/badge';
import {DocumentArrowUpIcon} from '@heroicons/react/16/solid';
import {Heading} from "@ui/primitives/heading";
import {offerStatusBadgeRenderer} from '@shared/utils';
import {DocumentDropdownActions} from '@features/documents/components/document-dropdown-actions';

interface OfferDetailsSectionProps {
    request: OCDRequest;
    offer: RequestOffer;
}


export default function OfferDetailsSection({offer, request}: Readonly<OfferDetailsSectionProps>) {
    const {auth} = usePage<{ auth: Auth }>().props;
    const [documents, setDocuments] = useState<Document[]>(offer.documents || []);

    const form = useForm<{
        file: File | null;
        document_type: string
    }>({
        file: null,
        document_type: 'offer_document',
    });

    const handleDocumentUpload = (e: React.FormEvent) => {
        e.preventDefault();
        if (!form.data.file) return;
        form.post(route('user.offer.document.store', {offer: offer.id}), {
            forceFormData: true,
            onSuccess: (response) => {
                form.reset();
                // Refresh the page to get updated documents
                window.location.reload();
            },
            onError: (errors) => {
                console.error('Upload failed:', errors);
            }
        });
    };


    return (
        <section id="offer-details" className="my-8">
            <div className="bg-white dark:bg-gray-800">
                <Heading level={2}>
                    Offer Details
                </Heading>
                {/* Offer Information */}
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <dl className="space-y-3">
                            <div>
                                <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">
                                    Status
                                </dt>
                                <dd className="mt-1">
                                    {offerStatusBadgeRenderer(offer)}
                                </dd>
                            </div>

                            <div>
                                <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">
                                    Description
                                </dt>
                                <dd className="mt-1 text-sm text-gray-900 dark:text-white whitespace-pre-wrap">
                                    {offer.description}
                                </dd>
                            </div>
                        </dl>
                    </div>
                    <div>
                        <dl className="space-y-3">
                            <div>
                                <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">
                                    Partner Name
                                </dt>
                                <dd className="mt-1 text-sm text-gray-900 dark:text-white">
                                    {offer.matched_partner?.name || 'Unknown Partner'}
                                </dd>
                            </div>

                            {offer.matched_partner?.email && (
                                <div>
                                    <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">
                                        Email
                                    </dt>
                                    <dd className="mt-1 text-sm text-gray-900 dark:text-white">
                                        {offer.matched_partner.email}
                                    </dd>
                                </div>
                            )}
                        </dl>
                    </div>
                </div>

                {/* Documents Section */}
                <div className="border-t border-gray-200 dark:border-gray-700 pt-6">
                    <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-4">
                        Documents
                    </h3>
                    {request.status.status_label === 'in_implementation' && (
                        <form onSubmit={handleDocumentUpload}
                              className="mb-6 p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
                            <div className="flex items-end space-x-4">
                                <div className="flex-1">
                                    <Field>
                                        <Label>Upload Document</Label>
                                        <input
                                            type="file"
                                            accept=".pdf,.doc,.docx,.txt"
                                            className="mt-1 block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400"
                                            onChange={e => form.setData('file', e.currentTarget.files ? e.currentTarget.files[0] : null)}
                                        />
                                    </Field>
                                    {form.errors.file && (
                                        <p className="mt-1 text-sm text-red-600">{form.errors.file}</p>
                                    )}
                                </div>

                                <Button
                                    type="submit"
                                    disabled={form.processing || !form.data.file}
                                    className="shrink-0"
                                >
                                    <DocumentArrowUpIcon data-slot="icon"/>
                                    {form.processing ? 'Uploading...' : 'Upload'}
                                </Button>
                            </div>
                        </form>
                    )}


                    {/* Documents List */}
                    {documents.length > 0 ? (
                        <div className="space-y-3">
                            {documents.map((document) => (
                                <div
                                    key={document.id}
                                    className="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800"
                                >
                                    <div className="flex-1">
                                        <p className="text-sm font-medium text-gray-900 dark:text-white">
                                            {document.name}
                                        </p>
                                        <p className="text-xs text-gray-500 dark:text-gray-400">
                                            Uploaded {new Date(document.created_at).toLocaleDateString()}
                                        </p>
                                        {document.document_type && (
                                            <Badge color="blue" className="mt-1">
                                                {document.document_type.label.toUpperCase()}
                                            </Badge>
                                        )}
                                    </div>

                                    <div className="flex items-center space-x-2">
                                        <DocumentDropdownActions document={document} />
                                    </div>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <div className="text-center py-8 text-gray-500 dark:text-gray-400">
                            <p>No documents uploaded yet.</p>
                        </div>
                    )}
                </div>
            </div>
        </section>
    );
}
