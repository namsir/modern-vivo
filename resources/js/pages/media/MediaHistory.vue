<script setup lang="ts">
import { format } from 'date-fns';
import { Badge } from '@/components/ui/badge';
import { CheckCircle, AlertTriangle, Info } from 'lucide-vue-next';

defineProps<{
    events: {
        id: number;
        event_type: string;
        status: 'success' | 'error' | 'info';
        details: string | null;
        created_at: string;
    }[];
}>();

const statusMap = {
    success: { icon: CheckCircle, class: 'bg-green-500' },
    error: { icon: AlertTriangle, class: 'bg-destructive' },
    info: { icon: Info, class: 'bg-sky-500' },
};
</script>

<template>
    <div class="mt-6 space-y-4">
        <div v-if="!events || events.length === 0" class="text-center text-muted-foreground py-8">
            No history events found for this media item.
        </div>
        <div v-else v-for="event in events" :key="event.id" class="flex items-start gap-4">
            <div class="flex flex-col items-center">
                <span class="flex items-center justify-center w-8 h-8 rounded-full text-white" :class="statusMap[event.status].class">
                    <component :is="statusMap[event.status].icon" class="w-4 h-4" />
                </span>
            </div>
            <div>
                <p class="font-semibold">{{ event.event_type }}</p>
                <p class="text-sm text-muted-foreground">{{ format(new Date(event.created_at), 'MMM d, yyyy @ h:mm a') }}</p>
                <p v-if="event.details" class="mt-1 text-xs p-2 rounded-md bg-muted whitespace-pre-wrap font-mono">{{ event.details }}</p>
            </div>
        </div>
    </div>
</template>
