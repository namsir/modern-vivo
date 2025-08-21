<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { useUploader, type UploadMetadata } from '@/Composables/useUploader';
import AdminLayout from '@/Layouts/AdminLayout.vue';

// Import UI Components
import { Progress } from '@/components/ui/progress';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Badge } from '@/components/ui/badge';
import { Switch } from '@/components/ui/switch';
import { Label } from '@/components/ui/label';
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { UploadCloud, File as FileIcon, CheckCircle, AlertTriangle, X } from 'lucide-vue-next';

defineOptions({ layout: AdminLayout });

const props = defineProps<{
    allTags?: { id: number; name: string }[]
}>();

const { state: uploaderState, startUpload, clearErrorUploads, clearUploads } = useUploader();

const stagedFiles = ref<File[]>([]);
const metadata = ref<UploadMetadata>({
    title: '',
    description: '',
    tags: [],
    caption_requested: false,
});
const isDragging = ref<boolean>(false);
const fileInput = ref<HTMLInputElement | null>(null);
const tagInput = ref('');
const tagPopoverOpen = ref(false);

const hasVideoFile = computed(() => {
    return stagedFiles.value.some(file => file.type.startsWith('video/'));
});

const filteredTags = computed(() => {
    const availableTags = props.allTags || [];
    if (!tagInput.value) {
        return availableTags.filter(tag => !metadata.value.tags.includes(tag.name));
    }
    return availableTags.filter(tag =>
        !metadata.value.tags.includes(tag.name) &&
        tag.name.toLowerCase().includes(tagInput.value.toLowerCase())
    );
});

async function handleUpload(): Promise<void> {
    if (stagedFiles.value.length === 0) return;

    const filesToUpload = [...stagedFiles.value];
    let lastSuccessfulMedia = null;
    let allUploadsSuccessful = true;

    for (const file of filesToUpload) {
        const fileMetadata: UploadMetadata = {
            ...metadata.value,
            title: filesToUpload.length > 1 ? file.name.replace(/\.[^/.]+$/, '') : metadata.value.title,
            caption_requested: hasVideoFile.value && metadata.value.caption_requested,
        };

        const media = await startUpload(file, fileMetadata);
        if (media) {
            lastSuccessfulMedia = media;
        } else {
            allUploadsSuccessful = false;
        }
    }

    // Clear the list of files that were waiting to be uploaded
    stagedFiles.value = [];

    // If everything was successful and we have a media object to redirect to...
    if (allUploadsSuccessful && lastSuccessfulMedia) {
        // Reset the metadata form
        metadata.value = { title: '', description: '', tags: [], caption_requested: false };

        // Short delay to let the user see the "complete" status
        setTimeout(() => {
            // Clear the progress list from the UI
            clearUploads();
            // Navigate to the edit page
            router.visit(route('media.edit', lastSuccessfulMedia.id));
        }, 1000);
    }
}

function handleFileSelection(files: FileList | null): void {
    if (!files) return;

    clearErrorUploads();

    stagedFiles.value.push(...Array.from(files));
    if (stagedFiles.value.length === 1) {
        metadata.value.title = stagedFiles.value[0].name.replace(/\.[^/.]+$/, '');
    }
}

function onDrop(event: DragEvent): void {
    isDragging.value = false;
    if (event.dataTransfer) handleFileSelection(event.dataTransfer.files);
}

function onFileSelect(event: Event): void {
    const target = event.target as HTMLInputElement;
    handleFileSelection(target.files);
}

function triggerFileInput(): void {
    fileInput.value?.click();
}

function removeStagedFile(index: number): void {
    stagedFiles.value.splice(index, 1);
    if (stagedFiles.value.length === 0) metadata.value.title = '';
}

function addTag(tagName?: string): void {
    const tagToAdd = (typeof tagName === 'string' ? tagName : tagInput.value).trim();
    if (tagToAdd && !metadata.value.tags.includes(tagToAdd)) {
        metadata.value.tags.push(tagToAdd);
    }
    tagInput.value = '';
    tagPopoverOpen.value = false;
}

function removeTag(index: number): void {
    metadata.value.tags.splice(index, 1);
}
</script>

<template>
    <Head title="Upload Media" />
    <div class="container py-8 mx-auto space-y-8">
        <div>
            <h1 class="text-3xl font-bold tracking-tight">Upload Media</h1>
        </div>

        <div
            @click="triggerFileInput"
            @dragover.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false"
            @drop.prevent="onDrop"
            class="flex flex-col items-center justify-center p-12 text-center border-2 border-dashed rounded-lg cursor-pointer transition-colors"
            :class="{ 'border-primary bg-primary/10': isDragging, 'border-muted-foreground/30': !isDragging }"
        >
            <UploadCloud class="w-16 h-16 mb-4 text-muted-foreground" />
            <p class="text-lg font-semibold">Drag & drop files here</p>
            <p class="text-muted-foreground">or click to browse</p>
            <input ref="fileInput" type="file" multiple @change="onFileSelect" class="hidden" />
        </div>

        <div v-if="stagedFiles.length > 0" class="p-6 border rounded-lg bg-card text-card-foreground">
            <div class="grid gap-8 md:grid-cols-2">
                <div>
                    <h3 class="mb-4 text-lg font-semibold">Files to Upload ({{ stagedFiles.length }})</h3>
                    <div class="space-y-2 pr-4 max-h-96 overflow-y-auto">
                        <div v-for="(file, index) in stagedFiles" :key="index" class="flex items-center justify-between p-2 rounded bg-muted">
                            <span class="truncate">{{ file.name }}</span>
                            <Button @click="removeStagedFile(index)" variant="ghost" size="icon" class="w-6 h-6 shrink-0"><X class="w-4 h-4" /></Button>
                        </div>
                    </div>
                </div>
                <div class="space-y-4">
                    <div>
                        <Label for="title" class="text-sm font-medium">Title</Label>
                        <Input id="title" v-model="metadata.title" :disabled="stagedFiles.length > 1" placeholder="Title for your upload" />
                        <p v-if="stagedFiles.length > 1" class="mt-1 text-xs text-muted-foreground">Title will be based on individual filenames.</p>
                    </div>
                    <div>
                        <Label for="description" class="text-sm font-medium">Description</Label>
                        <Textarea id="description" v-model="metadata.description" placeholder="Add a description..." />
                    </div>
                    <div>
                        <Label class="text-sm font-medium">Tags</Label>
                        <div class="flex flex-wrap items-center gap-2 p-2 mt-1 border rounded-lg min-h-11">
                            <Badge v-for="(tag, index) in metadata.tags" :key="index" variant="secondary" class="relative rounded-full py-1 pl-3 pr-7 text-sm font-normal">
                                {{ tag }}
                                <button @click.prevent="removeTag(index)" class="absolute top-0.5 right-0.5 flex h-4 w-4 items-center justify-center rounded-full transition-colors hover:bg-destructive/20"><X class="h-3 w-3 text-destructive" /></button>
                            </Badge>
                            <Popover v-model:open="tagPopoverOpen">
                                <PopoverTrigger as-child><input placeholder="Add a tag..." class="flex-grow bg-transparent outline-none" @focus="tagPopoverOpen = true" /></PopoverTrigger>
                                <PopoverContent class="w-[--radix-popover-trigger-width] p-0" align="start">
                                    <Command>
                                        <CommandInput placeholder="Search for a tag..." v-model="tagInput" @keydown.enter.prevent="addTag()" />
                                        <CommandList>
                                            <CommandEmpty>No results. Press Enter to create.</CommandEmpty>
                                            <CommandGroup>
                                                <CommandItem v-for="tag in filteredTags" :key="tag.id" :value="tag.name" @select="addTag(tag.name)">{{ tag.name }}</CommandItem>
                                            </CommandGroup>
                                        </CommandList>
                                    </Command>
                                </PopoverContent>
                            </Popover>
                        </div>
                    </div>
                    <div v-if="hasVideoFile">
                        <div class="flex items-center space-x-2">
                            <Switch id="caption-request" v-model:checked="metadata.caption_requested" />
                            <Label for="caption-request">Request professional captions</Label>
                        </div>
                        <p class="text-xs text-muted-foreground">If enabled, an admin will be notified to send this file for captioning.</p>
                    </div>
                </div>
            </div>
            <div class="flex justify-end pt-6 mt-6 border-t">
                <Button @click="handleUpload" :disabled="stagedFiles.length === 0" size="lg" class="w-full md:w-auto">Upload {{ stagedFiles.length }} File(s)</Button>
            </div>
        </div>

        <div v-if="uploaderState.uploads.length > 0" class="mt-8">
            <h2 class="mb-4 text-xl font-semibold">Uploads in Progress</h2>
            <div class="space-y-4">
                <div v-for="upload in uploaderState.uploads" :key="upload.id" class="flex items-center gap-4 p-4 border rounded-lg bg-card">
                    <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-muted">
                        <FileIcon v-if="upload.status === 'uploading'" class="w-5 h-5 text-muted-foreground" />
                        <CheckCircle v-if="upload.status === 'complete'" class="w-5 h-5 text-green-500" />
                        <AlertTriangle v-if="upload.status === 'error'" class="w-5 h-5 text-destructive" />
                    </div>
                    <div class="flex-grow">
                        <p class="font-medium truncate">{{ upload.file.name }}</p>
                        <div class="flex items-center gap-2 text-sm text-muted-foreground">
                            <Progress :model-value="upload.progress" class="w-full h-2" />
                            <span>{{ upload.progress }}%</span>
                        </div>
                        <p v-if="upload.errorMessage" class="mt-1 text-xs text-destructive">
                            {{ upload.errorMessage }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
