<x-mail::message>
    # AWS MediaConvert Job Failure

    An encoding job for a media file has failed.

    - **Media Title:** {{ $media->title }}
    - **Media ID:** {{ $media->id }}
    - **Uploader:** {{ $media->user->name }} ({{ $media->user->email }})

    **Error Message from AWS:**
    ```
    {{ $errorMessage }}
    ```

    You can view the media item in the admin panel to investigate further.

    <x-mail::button :url="route('media.edit', $media->id)">
        View Media
    </x-mail::button>

    Thanks,<br>
    {{ config('app.name') }}
</x-mail::message>
