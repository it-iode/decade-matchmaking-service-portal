<div style="padding: 10px;">
    <h3 style="color: #0066cc; margin-bottom: 20px;">New Capacity Development Requests</h3>
    @forelse($requests as $request)
        <div style="margin-bottom: 20px; padding: 15px; border: 1px solid #e5e5e5; border-radius: 5px; background-color: #ffffff;">
            <h4 style="margin: 0 0 10px 0; color: #333333;">{{ $request['title'] ?? 'Untitled Request' }}</h4>
            <p style="margin: 5px 0; color: #666666;">{{ Str::limit($request['description'] ?? '', 200) }}</p>
            @if(isset($request['detail']['subthemes']) && is_array($request['detail']['subthemes']) && count($request['detail']['subthemes']) > 0)
                <p style="margin: 5px 0; color: #666666;">
                    <strong>Themes:</strong> {{ implode(', ', array_slice($request['detail']['subthemes'], 0, 3)) }}
                    @if(count($request['detail']['subthemes']) > 3)
                        <span style="color: #999999;">+{{ count($request['detail']['subthemes']) - 3 }} more</span>
                    @endif
                </p>
            @endif
            @if(isset($request['detail']['support_types']) && is_array($request['detail']['support_types']) && count($request['detail']['support_types']) > 0)
                <p style="margin: 5px 0; color: #666666;">
                    <strong>Support Types:</strong> {{ implode(', ', array_slice($request['detail']['support_types'], 0, 3)) }}
                    @if(count($request['detail']['support_types']) > 3)
                        <span style="color: #999999;">+{{ count($request['detail']['support_types']) - 3 }} more</span>
                    @endif
                </p>
            @endif
            @if(!empty($request['detail']['location']))
                <p style="margin: 5px 0; color: #666666;"><strong>Location:</strong> {{ $request['detail']['location'] }}</p>
            @endif
            <a href="{{ config('app.url') }}/requests/{{ $request['id'] }}"
               style="display: inline-block; margin-top: 10px; color: #0066cc; text-decoration: none; font-weight: bold;">
                View Request &rarr;
            </a>
        </div>
    @empty
        <p style="color: #666666;">No new requests match your preferences this week.</p>
    @endforelse
</div>
