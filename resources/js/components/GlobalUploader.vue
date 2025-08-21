<script setup>
import { useUploader } from '@/Composables/useUploader';
import { Progress } from '@/components/ui/progress';
import { Button } from '@/components/ui/button';
import { X, ChevronsUp, ChevronsDown } from 'lucide-vue-next';
import { router } from '@inertiajs/vue3';

const { state, minimize, maximize, clearCompleted } = useUploader();

const goToUploadPage = () => {
    router.visit('/videos/upload');
    maximize();
};
</script>

<template>
    <div v-if="state.uploads.length > 0"
         class="fixed bottom-4 right-4 z-50 w-80 rounded-lg border bg-background p-4 shadow-lg transition-all"
         :class="{ 'h-12 overflow-hidden p-2': state.isMinimized }">

        <div class="flex items-center justify-between mb-2">
            <h3 class="font-semibold" @click="goToUploadPage" :class="{'cursor-pointer': state.isMinimized}">
                Uploads
            </h3>
            <div>
                <Button @click="state.isMinimized ? maximize() : minimize()" variant="ghost" size="icon">
                    <component :is="state.isMinimized ? ChevronsUp : ChevronsDown" class="h-4 w-4"/>
                </Button>
                <Button @click="clearCompleted" variant="ghost" size="icon">
                    <X class="h-4 w-4" />
                </Button>
            </div>
        </div>

        <div v-if="!state.isMinimized" class="space-y-4">
            <div v-for="upload in state.uploads" :key="upload.id">
                <p class="text-sm truncate">{{ upload.file.name }} ({{ upload.progress }}%)</p>
                <Progress :model-value="upload.progress" class="w-full h-2 mt-1" />
                <p v-if="upload.status === 'complete'" class="text-xs text-green-500">Completed!</p>
                <p v-if="upload.status === 'error'" class="text-xs text-red-500">Upload failed.</p>
            </div>
        </div>
    </div>
</template>
