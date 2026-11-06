@extends('emails.layout')

@section('content')
<h2>Dear {{ $recipient['name'] }},</h2>

<p>The status of your request, [Title], has been changed to: [Status].</p>

<div class="request-details">
    <h3>{{ $request->title }}</h3>

    <div class="detail-item">
        <span class="detail-label">Request ID:</span> {{ $request->id }}
    </div>

    <div class="detail-item">
        <span class="detail-label">Requester:</span> {{ $request->requester_name }}
    </div>

    @if($previousStatus)
    <div class="detail-item">
        <span class="detail-label">Previous Status:</span>
        <span class="status-badge">{{ $previousStatus }}</span>
    </div>
    @endif

    <div class="detail-item">
        <span class="detail-label">Current Status:</span>
        <span class="status-badge status-{{ strtolower($request->status->status_code ?? '') }}">
            {{ $request->status->status_label ?? 'Unknown' }}
        </span>
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
</div>

@if($recipient['type'] === 'requester')
    <p>You will be notified of any further updates to your request.</p>
@elseif($recipient['type'] === 'partner')
    <p>As the matched partner for this request, please review the status change and take any necessary actions.</p>
@elseif($recipient['type'] === 'admin')
    <p>Please review this status change and ensure appropriate follow-up actions are taken.</p>
@endif

<a href="{{ config('app.url') }}/requests/{{ $request->id }}" class="cta-button">
    View Request Details
</a>

<p>If you have any questions, please don't hesitate to contact our support team.</p>

<p>Best regards,<br>
{{ config('app.name') }} Team</p>
@endsection
