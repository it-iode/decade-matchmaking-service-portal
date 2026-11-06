@extends('emails.layout')

@section('content')
<h2>Dear {{ $recipient['name'] }},</h2>

<p>A partner has expressed interest in delivering services for a capacity development request.</p>

<div class="request-details">
    <h3>{{ $request->capacity_development_title }}</h3>
    
    <div class="detail-item">
        <span class="detail-label">Request ID:</span> {{ $request->id }}
    </div>
    
    <div class="detail-item">
        <span class="detail-label">Original Requester:</span> {{ $request->requester_name }}
    </div>
    
    <div class="detail-item">
        <span class="detail-label">Interested Partner:</span> {{ $interestedUser->name }}
    </div>
    
    <div class="detail-item">
        <span class="detail-label">Partner Email:</span> {{ $interestedUser->email }}
    </div>
    
    <div class="detail-item">
        <span class="detail-label">Interest Expressed:</span> {{ now()->format('F j, Y \a\t g:i A') }}
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
    
    @if($request->gap_description)
    <div class="detail-item">
        <span class="detail-label">Gap Description:</span>
        <p>{{ Str::limit($request->gap_description, 300) }}</p>
    </div>
    @endif
</div>

<p>Please review this expression of interest and consider facilitating a connection between the requester and the interested partner.</p>

<p><strong>Recommended Next Steps:</strong></p>
<ul>
    <li>Review the partner's profile and qualifications</li>
    <li>Assess compatibility with the request requirements</li>
    <li>Facilitate initial contact between the parties</li>
    <li>Monitor the partnership development process</li>
</ul>

<a href="{{ route('request.show', ['id' => $request->id]) }}" class="cta-button">
    View Full Request Details
</a>

<p>The CDF Secretariat will follow up within three business days to coordinate next steps.</p>

<p>If you have any questions about this expression of interest, please don't hesitate to contact our support team.</p>

<p>Best regards,<br>
{{ config('app.name') }} Team</p>
@endsection