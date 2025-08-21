<script setup lang="ts">
import {ref, computed, onMounted, onUnmounted} from 'vue';
import {Head, Link, router} from '@inertiajs/vue3';


// --- Editor Components ---
import VideoEditorControls from '@/components/media-editors/VideoEditorControls.vue';
import ImageEditorControls from '@/components/media-editors/ImageEditorControls.vue';
import DocumentEditorControls from '@/components/media-editors/DocumentEditorControls.vue';

// --- Vidstack Imports ---
// Import styles.
import 'vidstack/player/styles/default/theme.css';
import 'vidstack/player/styles/default/layouts/audio.css';
import 'vidstack/player/styles/default/layouts/video.css';
// Register elements.
import 'vidstack/player';
import 'vidstack/player/layouts';
import 'vidstack/player/ui';

import {isHLSProvider, type MediaCanPlayEvent, type MediaProviderChangeEvent} from 'vidstack';
import type {MediaPlayerElement} from 'vidstack/elements';


// --- UI Components for Previewer ---
import {Button} from '@/components/ui/button';
import {FileText, Image as ImageIcon} from 'lucide-vue-next';
import AdminLayout from "@/layouts/AdminLayout.vue";

import {usePoll} from "@inertiajs/vue3";
// Define the layout for this page
defineOptions({layout: AdminLayout});
const props = withDefaults(defineProps<{
    media?: any,
    allTags?: any,
    users?: any
}>(), {
    media: () => null,
    allTags: () => [],
    users: () => [],
});

const videoPlayer = ref<HTMLVideoElement | null>(null); // Ref to the <video> DOM element
const player = ref<videojs.Player | null>(null); // Ref to the Video.js instance
const previewStyle = ref({}); // Holds dynamic CSS filters from child components

let pollingInterval: number | null = null;
const stopPolling = () => {
    if (pollingInterval) {
        clearInterval(pollingInterval);
        pollingInterval = null;
    }
};

const startPolling = () => {
    pollingInterval = setInterval(() => {
        // Check the status on each poll
        if (props.media?.status !== 'Processing') {
            stopPolling();
        } else {
            // Reload only the 'media' prop to avoid a full page refresh
            router.reload({ only: ['media'] });
        }
    }, 5000); // Poll every 5 seconds
};
// Dynamically selects the correct editor component based on media type
const editorComponent = computed(() => {
    if (!props.media) return null;
    switch (props.media.media_type) {
        case 'video':
            return VideoEditorControls;
        case 'image':
            return ImageEditorControls;
        default:
            return DocumentEditorControls;
    }
});


// This computed property formats the 'encodes' data for Video.js
const videoSources = computed(() => {
    return (props.media?.encodes || []).map(encode => ({
        src: encode.url,
        type: 'video/mp4',
        label: `${encode.height}p`, // The plugin uses this label
        width: Number(encode.width),
        height: Number(encode.height)
    }));
});

const captionUrl = ref(route('media.captions.vtt', props.media.id));
const playerRef = ref(null);
let currentBlobUrl: string | null = null;

/**
 * Called when the player is ready. Fetches and loads initial captions.
 */
async function handlePlayerReady(readyPlayer: videojs.Player) {
    player.value = readyPlayer; // Save the player instance

    try {
        const response = await fetch(route('media.captions.vtt', props.media.id));
        if (response.ok) {
            const vttContent = await response.text();
            // Only load if there's actual caption content
            if (vttContent && vttContent.trim() !== 'WEBVTT') {
                handleCaptionUpdate(vttContent);
            }
        }
    } catch (error) {
        console.error('Failed to load initial captions:', error);
    }
}

function handleCaptionUpdate(newContent: string) {
    if (!player.value) return;

    const existingTracks = player.value.remoteTextTracks();
    for (let i = existingTracks.length - 1; i >= 0; i--) {
        if (existingTracks[i].kind === 'captions') {
            player.value.removeRemoteTextTrack(existingTracks[i]);
        }
    }

    if (currentBlobUrl) {
        URL.revokeObjectURL(currentBlobUrl);
    }

    const blob = new Blob([newContent], {type: 'text/vtt'});
    currentBlobUrl = URL.createObjectURL(blob);

    player.value.addRemoteTextTrack({
        kind: 'captions',
        label: 'English',
        srclang: 'en',
        src: currentBlobUrl,
    }, true);
}


const $player = ref<MediaPlayerElement>(),
    $src = ref('');

// Initialize src.
changeSource('video');



onMounted(() => {
// Start polling only if the initial status is 'Processing'
    if (props.media?.status === 'Processing') {
        startPolling();
    }
    /**
     * You can add these tracks using HTML as well.
     *
     * @example
     * ```html
     * <media-provider>
     *   <track label="..." src="..." kind="..." srclang="..." default />
     *   <track label="..." src="..." kind="..." srclang="..." />
     * </media-provider>
     * ```
     */

    const track = {
        src: 'https://files.vidstack.io/sprite-fight/subs/english.vtt',
        label: 'English',
        language: 'en-US',
        kind: 'subtitles',
        default: true,
    }
    $player.value!.textTracks.add(track);

    // Subscribe to state updates - you can connect them to Vue refs if needed.
    return $player.value!.subscribe(({paused, viewType}) => {
        // console.log('is paused?', '->', paused);
        // console.log('is audio view?', '->', viewType === 'audio');
    });


});

// Ensure we stop polling when the user navigates away
onUnmounted(() => {
    stopPolling();
});

function onProviderChange(event: MediaProviderChangeEvent) {
    const provider = event.detail;
    // We can configure provider's here.
    if (isHLSProvider(provider)) {
        provider.config = {};
    }
}

// We can listen for the `can-play` event to be notified when the player is ready.
function onCanPlay(event: MediaCanPlayEvent) {
    // ...
}

function changeSource(type: string) {
    switch (type) {
        case 'audio':
            $src.value = 'https://files.vidstack.io/sprite-fight/audio.mp3';
            break;
        case 'video':
            $src.value = 'https://files.vidstack.io/sprite-fight/720p.mp4';
            break;
        case 'hls':
            $src.value = 'https://files.vidstack.io/sprite-fight/hls/stream.m3u8';
            break;
        case 'youtube':
            $src.value = 'youtube/_cMxraX_5RE';
            break;
        case 'vimeo':
            $src.value = 'vimeo/640499893';
            break;
    }
}

</script>

<template>
    <Head :title="media ? `Edit: ${media.title}` : 'Edit Media'"/>

    <div v-if="media" class="container py-8 mx-auto space-y-8 p-5">
        {{ captionUrl }}
        <div
            class="w-full bg-slate-100 dark:bg-slate-900 rounded-lg shadow-lg flex items-center justify-center aspect-video"
            :style="previewStyle">


            <div v-if="media.media_type === 'video'" class="w-full h-full">
                <media-player
                    class="player"
                    title="Sprite Fight"
                    :src="videoSources"
                    crossOrigin
                    playsInline
                    @provider-change="onProviderChange"
                    @can-play="onCanPlay"
                    ref="$player"
                >
                    <media-provider>
                        <media-poster
                            class="vds-poster"
                            src="https://files.vidstack.io/sprite-fight/poster.webp"
                            alt="Girl walks into campfire with gnomes surrounding her friend ready for their next meal!"
                        />
                    </media-provider>
                    <!-- Layouts -->
                    <media-audio-layout/>
                    <media-video-layout thumbnails="https://files.vidstack.io/sprite-fight/thumbnails.vtt"/>
                </media-player>
            </div>

            <img v-else-if="media.media_type === 'image' && media.encodes?.length"
                 :src="`/storage/${media.encodes[0].url}`"
                 :alt="media.title" class="object-contain w-full h-full rounded-lg"/>
            <iframe v-else-if="media.media_type === 'pdf' && media.encodes?.length"
                    :src="`/storage/${media.encodes[0].url}`"
                    class="w-full h-full border-0 rounded-lg"></iframe>
            <div v-else-if="media.encodes?.length" class="text-center">
                <FileText class="w-24 h-24 mx-auto text-muted-foreground"/>
                <p class="mt-4 font-semibold">{{ media.title }}</p>
                <a :href="`/storage/${media.encodes[0].url}`" download>
                    <Button class="mt-4">Download File</Button>
                </a>
            </div>
            <div v-else class="text-center text-muted-foreground">
                <ImageIcon class="w-24 h-24 mx-auto"/>
                <p class="mt-4 font-semibold">Media preview is not available.</p>
                <p class="text-sm">This file may be processing or missing source data.</p>
            </div>
        </div>

        <div class="p-6 border rounded-lg bg-card text-card-foreground">
            <VideoEditorControls
                v-if="media.media_type === 'video'"
                :media="media"
                :all-tags="allTags"
                @update:style="newStyle => previewStyle = newStyle"
                @update:caption="handleCaptionUpdate"
            />
            <ImageEditorControls v-else-if="media.media_type === 'image'" :media="media" :all-tags="allTags"
                                 @update:style="newStyle => previewStyle = newStyle"/>
            <DocumentEditorControls v-else :media="media" :all-tags="allTags"/>
        </div>

    </div>

    <div v-else class="container py-16 mx-auto text-center">
        <h1 class="text-2xl font-bold">Media Not Found</h1>
        <p class="mt-2 text-muted-foreground">The media item you are trying to edit could not be loaded.</p>
        <Link :href="route('media.index')">
            <Button variant="outline" class="mt-6">Return to My Media</Button>
        </Link>
    </div>
</template>
<style>
.player {
    --brand-color: #f5f5f5;
    --focus-color: #4e9cf6;

    --audio-brand: var(--brand-color);
    --audio-focus-ring-color: var(--focus-color);
    --audio-border-radius: 2px;

    --video-brand: var(--brand-color);
    --video-focus-ring-color: var(--focus-color);
    --video-border-radius: 2px;

    /* 👉 https://vidstack.io/docs/player/components/layouts/default#css-variables for more. */
}

.player[data-view-type='audio'] media-poster {
    display: none;
}

.player[data-view-type='video'] {
    aspect-ratio: 16 /9;
}

</style>
