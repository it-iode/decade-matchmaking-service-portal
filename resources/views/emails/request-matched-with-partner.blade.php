@extends('emails.layout')

@section('content')
<h2>Hello {{ $recipient['name'] }},</h2>

@if($recipient['type'] === 'requester')
    <p>Great news! Your capacity development request has been matched with a partner.</p>
@elseif($recipient['type'] === 'partner')
    <p>Congratulations! You have been selected as a partner for a capacity development request.</p>
@endif

<div class="request-details">
    <h3>{{ $request->title }}</h3>
    
    <div class="detail-item">
        <span class="detail-label">Request ID:</span> {{ $request->id }}
    </div>
    
    @if($recipient['type'] === 'requester')
        <div class="detail-item">
            <span class="detail-label">Matched Partner:</span> {{ $partner->name }}
        </div>
        @if($partner->email)
        <div class="detail-item">
            <span class="detail-label">Partner Email:</span> {{ $partner->email }}
        </div>
        @endif
    @else
        <div class="detail-item">
            <span class="detail-label">Requester:</span> {{ $request->requester_name }}
        </div>
    @endif
    
    <div class="detail-item">
        <span class="detail-label">Match Date:</span> {{ now()->format('F j, Y \a\t g:i A') }}
    </div>
    
    @if($request->related_activity)
    <div class="detail-item">
        <span class="detail-label">Activity Type:</span> {{ $request->related_activity }}
    </div>
    @endif
    
    @if($request->delivery_format)
    <div class="detail-item">
        <span class="detail-label">Delivery Format:</span> {{ $request->delivery_format }}
    </div>
    @endif
    
    @if($request->completion_date)
    <div class="detail-item">
        <span class="detail-label">Expected Completion:</span> {{ \Carbon\Carbon::parse($request->completion_date)->format('F j, Y') }}
    </div>
    @endif
    
    @if(isset($request->decade_challenges) && is_array($request->decade_challenges))
    <div class="detail-item">
        <span class="detail-label">Decade Challenges:</span>
        <ul>
            @foreach(['primary' => 'Primary', 'secondary' => 'Secondary', 'tertiary' => 'Tertiary'] as $rank => $rankLabel)
                @if(!empty($request->decade_challenges[$rank]))
                    <li><strong>{{ $rankLabel }}:</strong> {{ \App\Domains\Request\Enums\DecadeChallenge::getLabelByValue($request->decade_challenges[$rank]) ?? $request->decade_challenges[$rank] }}</li>
                @endif
            @endforeach
        </ul>
    </div>
    @endif

    {{-- Legacy requests submitted before the Decade Challenge taxonomy --}}
    @if(isset($request->subthemes) && $request->subthemes && is_array($request->subthemes))
    <div class="detail-item">
        <span class="detail-label">Sub-themes:</span>
        <ul>
            @foreach($request->subthemes as $subtheme)
                <li>{{ $subtheme }}</li>
            @endforeach
        </ul>
    </div>
    @endif
    
    @if($request->gap_description)
    <div class="detail-item">
        <span class="detail-label">Gap Description:</span>
        <p>{{ Str::limit($request->gap_description, 200) }}</p>
    </div>
    @endif
    
    @if($request->expected_outcomes)
    <div class="detail-item">
        <span class="detail-label">Expected Outcomes:</span>
        <p>{{ Str::limit($request->expected_outcomes, 200) }}</p>
    </div>
    @endif
</div>

@if($recipient['type'] === 'requester')
    <p>Your partner will be in touch with you soon to discuss the next steps. Please check your request details for more information about the partnership.</p>
    <p><strong>Next Steps:</strong></p>
    <ul>
        <li>Review your partner's profile and expertise</li>
        <li>Prepare for initial discussions with your partner</li>
        <li>Finalize project timelines and deliverables</li>
    </ul>
@elseif($recipient['type'] === 'partner')
    <p>Please review the request details and reach out to the requester to begin planning your collaboration.</p>
    <p><strong>Next Steps:</strong></p>
    <ul>
        <li>Review the full request requirements</li>
        <li>Contact the requester to introduce yourself</li>
        <li>Discuss project scope, timeline, and deliverables</li>
        <li>Submit your detailed partnership proposal</li>
    </ul>
@endif

<a href="{{ config('app.url') }}/requests/{{ $request->id }}" class="cta-button">
    View Full Request Details
</a>

<p>We're excited to see this partnership develop and contribute to ocean science capacity building!</p>

<p>If you have any questions about this partnership, please don't hesitate to contact our support team.</p>

<p>Best regards,<br>
{{ config('app.name') }} Team</p>
@endsection