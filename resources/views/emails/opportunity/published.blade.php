@extends('emails.layout')

@section('content')
    @if($recipientType === 'creator')
        <h2>Dear {{ $opportunity->user->name }},</h2>

        <p>We are pleased to inform you that your opportunity "<strong>{{ $opportunity->title }}</strong>" has been successfully published on the Ocean Decade Portal.</p>

        <div class="info-box">
            <h3>Opportunity Details:</h3>
            <ul>
                <li><strong>Type:</strong> {{ $opportunity->type->label() }}</li>
                <li><strong>Closing Date:</strong> {{ $opportunity->closing_date->format('F j, Y') }}</li>
                <li><strong>Coverage:</strong> {{ $opportunity->coverage_activity?->label() ?? 'N/A' }}</li>
                <li><strong>Status:</strong> {{ $opportunity->status->label() }}</li>
            </ul>
        </div>

        <p>Your opportunity is now visible to all portal users and potential collaborators.</p>

        <a href="{{ $viewUrl }}" class="cta-button">
            View Your Opportunity
        </a>

        <p>Thank you for using the Ocean Decade Portal to share your opportunity with the global ocean community.</p>

    @else
        <h2>New Opportunity Published</h2>

        <p>A new opportunity has been published on the Ocean Decade Portal.</p>

        <div class="info-box">
            <h3>Opportunity Details:</h3>
            <ul>
                <li><strong>Title:</strong> {{ $opportunity->title }}</li>
                <li><strong>Submitted by:</strong> {{ $opportunity->user->name }}</li>
                <li><strong>Type:</strong> {{ $opportunity->type->label() }}</li>
                <li><strong>Closing Date:</strong> {{ $opportunity->closing_date->format('F j, Y') }}</li>
            </ul>
        </div>

        <a href="{{ $viewUrl }}" class="cta-button">
            View Opportunity
        </a>

        <p>Please review this opportunity as needed.</p>
    @endif

    <p>Best regards,<br>
        IOC Secretariat - Ocean Decade Portal</p>
@endsection