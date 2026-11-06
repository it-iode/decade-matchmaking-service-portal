<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #333;
            line-height: 1.5;
        }
        h1 {
            font-size: 18px;
            color: #1a202c;
            margin-bottom: 5px;
            border-bottom: 2px solid #2d3748;
            padding-bottom: 5px;
        }
        h2 {
            font-size: 14px;
            color: #2d3748;
            margin-top: 15px;
            margin-bottom: 8px;
            background-color: #edf2f7;
            padding: 5px 8px;
            border-left: 4px solid #4299e1;
        }
        h3 {
            font-size: 12px;
            color: #4a5568;
            margin-top: 10px;
            margin-bottom: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        th, td {
            padding: 6px 8px;
            border: 1px solid #cbd5e0;
            text-align: left;
            vertical-align: top;
        }
        th {
            background-color: #f7fafc;
            font-weight: bold;
            width: 35%;
            color: #2d3748;
        }
        td {
            color: #4a5568;
        }
        ul {
            margin: 0;
            padding-left: 20px;
        }
        li {
            margin-bottom: 3px;
        }
        .header-info {
            margin-bottom: 15px;
        }
        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            background-color: #edf2f7;
            border-radius: 3px;
            font-weight: bold;
        }
        .section {
            page-break-inside: avoid;
        }
        .text-content {
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .empty-value {
            color: #a0aec0;
            font-style: italic;
        }
    </style>
</head>
<body>
    {{-- HEADER SECTION --}}
    <div class="header-info">
        <h1>Request #{{ $ocdRequest->id }}{{ $ocdRequest->detail?->capacity_development_title ? ': ' . $ocdRequest->detail->capacity_development_title : '' }}</h1>
        <table>
            <tr>
                <th>Status</th>
                <td><span class="status-badge">{{ $ocdRequest->status->status_label ?? 'Unknown' }}</span></td>
            </tr>
            <tr>
                <th>Submitted At</th>
                <td>{{ $ocdRequest->created_at?->format('F d, Y g:i A') ?? 'N/A' }}</td>
            </tr>
            @if($ocdRequest->updated_at && $ocdRequest->updated_at->ne($ocdRequest->created_at))
            <tr>
                <th>Last Updated</th>
                <td>{{ $ocdRequest->updated_at->format('F d, Y g:i A') }}</td>
            </tr>
            @endif
        </table>
    </div>

    {{-- REQUEST OWNER SECTION --}}
    @if($ocdRequest->user)
    <div class="section">
        <h2>Request Owner</h2>
        <table>
            <tr>
                <th>Name</th>
                <td>{{ $ocdRequest->user->name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Email</th>
                <td>{{ $ocdRequest->user->email ?? 'N/A' }}</td>
            </tr>
            @if($ocdRequest->user->country || $ocdRequest->user->city)
            <tr>
                <th>Location</th>
                <td>{{ collect([$ocdRequest->user->city, $ocdRequest->user->country])->filter()->join(', ') }}</td>
            </tr>
            @endif
        </table>
    </div>
    @endif

    {{-- PROJECT INFORMATION SECTION --}}
    @if($ocdRequest->detail)
    <div class="section">
        <h2>Project Information</h2>
        <table>
            @if($ocdRequest->detail->related_activity)
            <tr>
                <th>Related Activity</th>
                <td>{{ $ocdRequest->detail->related_activity }}</td>
            </tr>
            @endif
            @if($ocdRequest->detail->delivery_format)
            <tr>
                <th>Delivery Format</th>
                <td>{{ $ocdRequest->detail->delivery_format }}</td>
            </tr>
            @endif
            @if($ocdRequest->detail->project_stage)
            <tr>
                <th>Project Stage</th>
                <td>{{ $ocdRequest->detail->project_stage }}</td>
            </tr>
            @endif
            @if($ocdRequest->detail->project_url)
            <tr>
                <th>Project URL</th>
                <td>{{ $ocdRequest->detail->project_url }}</td>
            </tr>
            @endif
        </table>
    </div>
    @endif

    {{-- SUPPORT REQUIREMENTS SECTION --}}
    @if($ocdRequest->detail)
    <div class="section">
        <h2>Support Requirements</h2>
        <table>
            @php
                $decadeChallenges = is_array($ocdRequest->detail->decade_challenges) ? $ocdRequest->detail->decade_challenges : [];
                $rankedChallenges = collect(['primary' => 'Primary', 'secondary' => 'Secondary', 'tertiary' => 'Tertiary'])
                    ->map(fn ($rankLabel, $rank) => isset($decadeChallenges[$rank]) && is_string($decadeChallenges[$rank])
                        ? ['rank' => $rankLabel, 'label' => \App\Domains\Request\Enums\DecadeChallenge::getLabelByValue($decadeChallenges[$rank]) ?? $decadeChallenges[$rank]]
                        : null)
                    ->filter();
            @endphp
            @if($rankedChallenges->isNotEmpty())
            <tr>
                <th>Decade Challenges</th>
                <td>
                    <ul>
                        @foreach($rankedChallenges as $challenge)
                            <li><strong>{{ $challenge['rank'] }}:</strong> {{ $challenge['label'] }}</li>
                        @endforeach
                    </ul>
                </td>
            </tr>
            @endif
            @if($ocdRequest->detail->subthemes && count($ocdRequest->detail->subthemes) > 0)
            <tr>
                <th>Subthemes</th>
                <td>
                    <ul>
                        @foreach($ocdRequest->detail->subthemes as $subtheme)
                            <li>{{ is_object($subtheme) && method_exists($subtheme, 'label') ? $subtheme->label() : (is_array($subtheme) && isset($subtheme['label']) ? $subtheme['label'] : $subtheme) }}</li>
                        @endforeach
                    </ul>
                    @if($ocdRequest->detail->subthemes_other)
                    <div style="margin-top: 5px;"><strong>Other:</strong> {{ $ocdRequest->detail->subthemes_other }}</div>
                    @endif
                </td>
            </tr>
            @endif
            @if($ocdRequest->detail->support_types && count($ocdRequest->detail->support_types) > 0)
            <tr>
                <th>Support Types</th>
                <td>
                    <ul>
                        @foreach($ocdRequest->detail->support_types as $supportType)
                            <li>{{ is_object($supportType) && method_exists($supportType, 'label') ? $supportType->label() : (is_array($supportType) && isset($supportType['label']) ? $supportType['label'] : $supportType) }}</li>
                        @endforeach
                    </ul>
                    @if($ocdRequest->detail->support_types_other)
                    <div style="margin-top: 5px;"><strong>Other:</strong> {{ $ocdRequest->detail->support_types_other }}</div>
                    @endif
                </td>
            </tr>
            @endif
            @if($ocdRequest->detail->support_months)
            <tr>
                <th>Support Duration</th>
                <td>{{ $ocdRequest->detail->support_months }} month{{ $ocdRequest->detail->support_months !== 1 ? 's' : '' }}</td>
            </tr>
            @endif
            @if($ocdRequest->detail->completion_date)
            <tr>
                <th>Expected Completion Date</th>
                <td>{{ $ocdRequest->detail->completion_date }}</td>
            </tr>
            @endif
        </table>
    </div>
    @endif

    {{-- TARGET & DELIVERY SECTION --}}
    @if($ocdRequest->detail)
    <div class="section">
        <h2>Target Audience & Delivery</h2>
        <table>
            @if($ocdRequest->detail->target_audience && count($ocdRequest->detail->target_audience) > 0)
            <tr>
                <th>Target Audience</th>
                <td>
                    <ul>
                        @foreach($ocdRequest->detail->target_audience as $audience)
                            <li>{{ is_object($audience) && method_exists($audience, 'label') ? $audience->label() : (is_array($audience) && isset($audience['label']) ? $audience['label'] : $audience) }}</li>
                        @endforeach
                    </ul>
                    @if($ocdRequest->detail->target_audience_other)
                    <div style="margin-top: 5px;"><strong>Other:</strong> {{ $ocdRequest->detail->target_audience_other }}</div>
                    @endif
                </td>
            </tr>
            @endif
            @if($ocdRequest->detail->target_languages && count($ocdRequest->detail->target_languages) > 0)
            <tr>
                <th>Target Languages</th>
                <td>
                    <ul>
                        @foreach($ocdRequest->detail->target_languages as $language)
                            <li>{{ is_object($language) && method_exists($language, 'label') ? $language->label() : (is_array($language) && isset($language['label']) ? $language['label'] : $language) }}</li>
                        @endforeach
                    </ul>
                    @if($ocdRequest->detail->target_languages_other)
                    <div style="margin-top: 5px;"><strong>Other:</strong> {{ $ocdRequest->detail->target_languages_other }}</div>
                    @endif
                </td>
            </tr>
            @endif
            @if($ocdRequest->detail->delivery_countries && count($ocdRequest->detail->delivery_countries) > 0)
            <tr>
                <th>Delivery Countries</th>
                <td>
                    <ul>
                        @foreach($ocdRequest->detail->delivery_countries as $country)
                            <li>{{ is_object($country) && method_exists($country, 'label') ? $country->label() : (is_array($country) && isset($country['label']) ? $country['label'] : $country) }}</li>
                        @endforeach
                    </ul>
                </td>
            </tr>
            @endif
        </table>
    </div>
    @endif

    {{-- CAPACITY DEVELOPMENT GAP SECTION --}}
    @if($ocdRequest->detail && $ocdRequest->detail->gap_description)
    <div class="section">
        <h2>Capacity Development Gap</h2>
        <div class="text-content">{{ $ocdRequest->detail->gap_description }}</div>
    </div>
    @endif

    {{-- EXPECTED OUTCOMES & IMPACT SECTION --}}
    @if($ocdRequest->detail)
    <div class="section">
        <h2>Expected Outcomes & Impact</h2>
        @if($ocdRequest->detail->expected_outcomes)
        <h3>Expected Outcomes</h3>
        <div class="text-content">{{ $ocdRequest->detail->expected_outcomes }}</div>
        @endif

        @if($ocdRequest->detail->success_metrics)
        <h3>Success Metrics</h3>
        <div class="text-content">{{ $ocdRequest->detail->success_metrics }}</div>
        @endif

        @if($ocdRequest->detail->long_term_impact)
        <h3>Long-term Impact</h3>
        <div class="text-content">{{ $ocdRequest->detail->long_term_impact }}</div>
        @endif
    </div>
    @endif

    {{-- PRIVATE/DETAILED INFORMATION SECTION (if accessible) --}}
    @if($ocdRequest->detail && (
        $ocdRequest->detail->is_related_decade_action ||
        $ocdRequest->detail->first_name ||
        $ocdRequest->detail->has_partner ||
        $ocdRequest->detail->has_significant_changes ||
        $ocdRequest->detail->risks ||
        $ocdRequest->detail->personnel_expertise ||
        $ocdRequest->detail->direct_beneficiaries
    ))
    <div class="section">
        <h2>Additional Information</h2>
        <table>
            {{-- Contact Information --}}
            @if($ocdRequest->detail->first_name || $ocdRequest->detail->last_name || $ocdRequest->detail->email)
            <tr>
                <th>Contact Information</th>
                <td>
                    @if($ocdRequest->detail->first_name || $ocdRequest->detail->last_name)
                    <div><strong>Name:</strong> {{ collect([$ocdRequest->detail->first_name, $ocdRequest->detail->last_name])->filter()->join(' ') }}</div>
                    @endif
                    @if($ocdRequest->detail->email)
                    <div><strong>Email:</strong> {{ $ocdRequest->detail->email }}</div>
                    @endif
                </td>
            </tr>
            @endif

            {{-- Decade Action Information --}}
            @if($ocdRequest->detail->is_related_decade_action)
            <tr>
                <th>Related to Decade Action</th>
                <td>{{ $ocdRequest->detail->is_related_decade_action }}</td>
            </tr>
            @endif
            @if($ocdRequest->detail->unique_related_decade_action_id)
            <tr>
                <th>Decade Action ID</th>
                <td>{{ $ocdRequest->detail->unique_related_decade_action_id }}</td>
            </tr>
            @endif

            {{-- Partner Information --}}
            @if($ocdRequest->detail->has_partner)
            <tr>
                <th>Has Partner</th>
                <td>{{ $ocdRequest->detail->has_partner }}</td>
            </tr>
            @endif
            @if($ocdRequest->detail->partner_name)
            <tr>
                <th>Partner Name</th>
                <td>{{ $ocdRequest->detail->partner_name }}</td>
            </tr>
            @endif
            @if($ocdRequest->detail->partner_confirmed)
            <tr>
                <th>Partner Confirmed</th>
                <td>{{ $ocdRequest->detail->partner_confirmed }}</td>
            </tr>
            @endif

            {{-- Significant Changes --}}
            @if($ocdRequest->detail->has_significant_changes)
            <tr>
                <th>Has Significant Changes</th>
                <td>{{ $ocdRequest->detail->has_significant_changes }}</td>
            </tr>
            @endif
            @if($ocdRequest->detail->changes_description)
            <tr>
                <th>Changes Description</th>
                <td class="text-content">{{ $ocdRequest->detail->changes_description }}</td>
            </tr>
            @endif
            @if($ocdRequest->detail->change_effect)
            <tr>
                <th>Change Effect</th>
                <td class="text-content">{{ $ocdRequest->detail->change_effect }}</td>
            </tr>
            @endif

            {{-- Risk Assessment --}}
            @if($ocdRequest->detail->risks)
            <tr>
                <th>Identified Risks</th>
                <td class="text-content">{{ $ocdRequest->detail->risks }}</td>
            </tr>
            @endif

            {{-- Personnel & Expertise --}}
            @if($ocdRequest->detail->personnel_expertise)
            <tr>
                <th>Required Personnel Expertise</th>
                <td class="text-content">{{ $ocdRequest->detail->personnel_expertise }}</td>
            </tr>
            @endif

            {{-- Beneficiaries --}}
            @if($ocdRequest->detail->direct_beneficiaries)
            <tr>
                <th>Direct Beneficiaries</th>
                <td class="text-content">{{ $ocdRequest->detail->direct_beneficiaries }}</td>
            </tr>
            @endif
            @if($ocdRequest->detail->direct_beneficiaries_number)
            <tr>
                <th>Number of Beneficiaries</th>
                <td>{{ number_format($ocdRequest->detail->direct_beneficiaries_number) }}</td>
            </tr>
            @endif
        </table>
    </div>
    @endif

    {{-- FINANCIAL INFORMATION SECTION (if accessible) --}}
    @if($ocdRequest->detail && ($ocdRequest->detail->needs_financial_support || $ocdRequest->detail->budget_breakdown))
    <div class="section">
        <h2>Financial Information</h2>
        <table>
            @if($ocdRequest->detail->needs_financial_support)
            <tr>
                <th>Needs Financial Support</th>
                <td>{{ $ocdRequest->detail->needs_financial_support }}</td>
            </tr>
            @endif
            @if($ocdRequest->detail->budget_breakdown)
            <tr>
                <th>Budget Breakdown</th>
                <td class="text-content">{{ $ocdRequest->detail->budget_breakdown }}</td>
            </tr>
            @endif
        </table>
    </div>
    @endif

    {{-- MATCHED PARTNER SECTION --}}
    @if($ocdRequest->matchedPartner)
    <div class="section">
        <h2>Matched Partner</h2>
        <table>
            <tr>
                <th>Partner Name</th>
                <td>{{ $ocdRequest->matchedPartner->name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Email</th>
                <td>{{ $ocdRequest->matchedPartner->email ?? 'N/A' }}</td>
            </tr>
            @if($ocdRequest->matchedPartner->country || $ocdRequest->matchedPartner->city)
            <tr>
                <th>Location</th>
                <td>{{ collect([$ocdRequest->matchedPartner->city, $ocdRequest->matchedPartner->country])->filter()->join(', ') }}</td>
            </tr>
            @endif
        </table>
    </div>
    @endif

    {{-- ACTIVE OFFER SECTION --}}
    @if($ocdRequest->activeOffer)
    <div class="section">
        <h2>Active Offer</h2>
        <table>
            <tr>
                <th>Offer Status</th>
                <td><span class="status-badge">{{ $ocdRequest->activeOffer->status->label() ?? 'Unknown' }}</span></td>
            </tr>
            @if($ocdRequest->activeOffer->matchedPartner)
            <tr>
                <th>Offered By</th>
                <td>{{ $ocdRequest->activeOffer->matchedPartner->name ?? 'N/A' }} ({{ $ocdRequest->activeOffer->matchedPartner->email ?? 'N/A' }})</td>
            </tr>
            @endif
            @if($ocdRequest->activeOffer->description)
            <tr>
                <th>Offer Description</th>
                <td class="text-content">{{ $ocdRequest->activeOffer->description }}</td>
            </tr>
            @endif
            <tr>
                <th>Offer Created</th>
                <td>{{ $ocdRequest->activeOffer->created_at?->format('F d, Y g:i A') ?? 'N/A' }}</td>
            </tr>
            @if($ocdRequest->activeOffer->updated_at && $ocdRequest->activeOffer->updated_at->ne($ocdRequest->activeOffer->created_at))
            <tr>
                <th>Offer Updated</th>
                <td>{{ $ocdRequest->activeOffer->updated_at->format('F d, Y g:i A') }}</td>
            </tr>
            @endif
        </table>

        {{-- Offer Documents --}}
        @if($ocdRequest->activeOffer->documents && $ocdRequest->activeOffer->documents->count() > 0)
        <h3>Attached Documents</h3>
        <table>
            <tr>
                <th style="width: 40%;">Document Name</th>
                <th style="width: 25%;">Type</th>
                <th style="width: 20%;">Uploaded By</th>
                <th style="width: 15%;">Upload Date</th>
            </tr>
            @foreach($ocdRequest->activeOffer->documents as $document)
            <tr>
                <td>{{ $document->name ?? 'N/A' }}</td>
                <td>{{ $document->document_type?->label() ?? 'General' }}</td>
                <td>{{ $document->uploader?->name ?? 'N/A' }}</td>
                <td>{{ $document->created_at?->format('M d, Y') ?? 'N/A' }}</td>
            </tr>
            @endforeach
        </table>
        @endif
    </div>
    @endif

</body>
</html>
