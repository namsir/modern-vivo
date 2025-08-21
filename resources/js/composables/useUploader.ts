import { reactive, readonly } from 'vue';
import { router } from '@inertiajs/vue3';

// Define the structure for a single upload object
export interface Upload {
    id: string;
    file: File;
    progress: number;
    status: 'uploading' | 'complete' | 'error';
    errorMessage?: string;
}

// Define the structure for the metadata
export interface UploadMetadata {
    title: string;
    description: string;
    tags: string[];
    caption_requested?: boolean;
}

interface UploaderState {
    uploads: Upload[];
    isMinimized: boolean;
}

const state = reactive<UploaderState>({
    uploads: [],
    isMinimized: false,
});

const CHUNK_SIZE = 5 * 1024 * 1024; // 5MB chunks

/**
 * Starts the chunked file upload process.
 * @param file The file to upload.
 * @param metadata The metadata for the file.
 * @returns A promise that resolves to the created media object on success, or null on failure.
 */
async function startUpload(file: File, metadata: UploadMetadata): Promise<any | null> {
    const uploadId = crypto.randomUUID();
    const totalChunks = Math.ceil(file.size / CHUNK_SIZE);

    const newUpload: Upload = { id: uploadId, file, progress: 0, status: 'uploading' };
    state.uploads.push(newUpload);

    const targetUpload = state.uploads.find((u) => u.id === uploadId);

    try {
        let finalMediaResult = null;

        for (let i = 0; i < totalChunks; i++) {
            const start = i * CHUNK_SIZE;
            const end = Math.min(start + CHUNK_SIZE, file.size);
            const chunk = file.slice(start, end);

            const formData = new FormData();
            formData.append('chunk', chunk, file.name);
            formData.append('chunk_index', String(i));
            formData.append('total_chunks', String(totalChunks));
            formData.append('upload_id', uploadId);
            formData.append('original_filename', file.name);
            formData.append('title', metadata.title || file.name.replace(/\.[^/.]+$/, ''));
            formData.append('description', metadata.description);
            formData.append('tags', JSON.stringify(metadata.tags));
            formData.append('caption_requested', metadata.caption_requested ? 'true' : 'false');

            const response = await fetch(route('media.upload.chunk'), {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'Accept': 'application/json',
                },
                body: formData,
            });

            if (!response.ok) {
                let serverMessage = 'An unknown server error occurred.';
                try {
                    const errorJson = await response.json();
                    serverMessage = errorJson.message || serverMessage;
                } catch (e) {
                    serverMessage = response.statusText;
                }
                throw new Error(`Upload failed: ${serverMessage} (Status: ${response.status})`);
            }

            if (targetUpload) {
                targetUpload.progress = Math.round(((i + 1) / totalChunks) * 100);
            }

            if (i === totalChunks - 1) {
                const result = await response.json();
                if (targetUpload) targetUpload.status = 'complete';
                finalMediaResult = result.media; // Store the final media object
            }
        }
        return finalMediaResult; // Return the media object on success
    } catch (error: any) {
        console.error('Full upload error details:', error);
        if (targetUpload) {
            targetUpload.status = 'error';
            targetUpload.errorMessage = error.message || 'The upload could not be completed.';
        }
        return null; // Return null on failure
    }
}

/**
 * Removes all uploads from the progress list.
 */
function clearUploads(): void {
    state.uploads = [];
}

/**
 * Removes only uploads that have an 'error' status from the list.
 */
function clearErrorUploads(): void {
    state.uploads = state.uploads.filter(upload => upload.status !== 'error');
}

export function useUploader() {
    return { state: readonly(state), startUpload, clearUploads, clearErrorUploads };
}
