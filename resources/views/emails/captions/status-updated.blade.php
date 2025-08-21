<x-mail::message>
    # Caption Request Update

    Hello {{ $captionRequest->media->user->name }},

    This is an update regarding your caption request for the media file: **"{{ $captionRequest->media->title }}"**.

    **Status: {{ ucfirst($captionRequest->status) }}**

    @if($captionRequest->reason)
        **Admin Notes:**
        {{ $captionRequest->reason }}
    @endif

    You can view the media file by clicking the button below.

    <x-mail::button :url="route('media.edit', $captionRequest->media->id)">
        View Media
    </x-mail::button>

    Thanks,<br>
    {{ config('app.name') }}
</x-mail::message>
