<ul>
    @forelse($opportunities as $opportunity)
        <li aria-level="1" dir="ltr">
            <p dir="ltr" role="presentation">
                <span style="color:#ffffff">
                    {{ $opportunity['title'] }} related to {{ $opportunity['type'] }}  is open. <br/>
                    The application deadline is {{ \Carbon\Carbon::parse($opportunity['closing_date'])->format('F j, Y') }} <br/><br/>
                    <strong>
                         <a style="color:#D9D9D9" href="{{ route('opportunity.show', $opportunity['id']) }}">
                             Click here for more details about the opportunity
                         </a>
                 </strong>
                </span>
            </p>
        </li>
    @empty
        <p>No new opportunities match your preferences this week.</p>
    @endforelse
</ul>

@if(!empty($view_all_url) && ($total ?? 0) > ($shown ?? 0))
    <p dir="ltr" role="presentation">
        <span style="color:#ffffff">
            Showing the {{ $shown }} opportunities closing soonest of {{ $total }} currently open.
        </span>
    </p>
@endif

@if(!empty($view_all_url))
    <p dir="ltr" role="presentation">
        <strong>
            <a style="color:#D9D9D9" href="{{ $view_all_url }}">
                View all opportunities on the website
            </a>
        </strong>
    </p>
@endif
