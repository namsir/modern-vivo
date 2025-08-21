<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { format } from 'date-fns';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardFooter, CardHeader } from '@/components/ui/card';
import { PlusCircle, Video, FileText, ImageIcon } from 'lucide-vue-next';
import AdminLayout from "@/layouts/AdminLayout.vue";
// Define the layout for this page
defineOptions({layout: AdminLayout});

const props = defineProps<{
    media: {
        data: any[];
        links: any[];
    };
    pageTitle: string;
}>();

const mediaTypeIcons = {
    video: Video,
    image: ImageIcon,
    doc: FileText,
    pdf: FileText,
};
</script>

<template>
    <Head :title="pageTitle" />
    <div class="container p-5 py-12 mx-auto">
        <!-- Page Header -->
        <div class="flex items-center justify-between mb-8">
            <h1 class="text-3xl font-bold tracking-tight">{{ pageTitle }}</h1>
            <Link :href="route('media.upload')">
                <Button>
                    <PlusCircle class="w-4 h-4 mr-2" />
                    Upload File
                </Button>
            </Link>
        </div>

        <!-- Media Grid -->
        <div v-if="media.data.length > 0">
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                <Link v-for="item in media.data" :key="item.id" :href="route('media.edit', item.id)">
                    <Card class="relative flex flex-col h-full transition-all duration-200 ease-in-out group hover:border-primary hover:shadow-lg">
                        <!-- Icon overlay with primary color -->
                        <div class="absolute top-3 right-3 z-10 p-1.5 bg-primary text-primary-foreground rounded-full shadow-md">
                            <component
                                :is="mediaTypeIcons[item.media_type] || FileText"
                                class="w-4 h-4"
                            />
                        </div>
                        <CardHeader class="p-4">
                            <!-- Dynamic Thumbnail Section -->
                            <div class="flex items-center justify-center w-full overflow-hidden rounded-md bg-muted aspect-video">
                                <!-- Show image preview if it's an image -->
                                <img
                                    v-if="item.media_type === 'image' && item.encodes?.length"
                                    :src="`/storage/${item.encodes[0].url}`"
                                    :alt="item.title"
                                    class="object-cover w-full h-full transition-transform duration-300 group-hover:scale-105"
                                />
                                <!-- Show video preview if it's a video -->
                                <video
                                    v-else-if="item.media_type === 'video' && item.encodes?.length"
                                    :src="`/storage/${item.encodes[0].url}#t=0.1`"
                                    muted
                                    preload="metadata"
                                    playsinline
                                    class="object-cover w-full h-full transition-transform duration-300 group-hover:scale-105"
                                ></video>
                                <!-- Otherwise, show the icon placeholder for PDFs, Docs, etc. -->
                                <component
                                    v-else
                                    :is="mediaTypeIcons[item.media_type] || FileText"
                                    class="w-16 h-16 text-muted-foreground/50"
                                />
                            </div>
                        </CardHeader>
                        <CardContent class="flex-grow p-6 pt-0">
                            <h3 class="font-semibold truncate">{{ item.title }}</h3>
                            <div v-if="item.tags.length" class="flex flex-wrap gap-1 mt-2">
                                <Badge v-for="tag in item.tags.slice(0, 3)" :key="tag.id" variant="secondary">{{ tag.name }}</Badge>
                            </div>
                        </CardContent>
                        <CardFooter class="flex items-center justify-between p-6 pt-0 text-sm border-t text-muted-foreground">
                            <span class="truncate">{{ item.user.name }}</span>
                            <span>{{ format(new Date(item.created_at), 'MMM d, yyyy') }}</span>
                        </CardFooter>
                    </Card>
                </Link>
            </div>

            <!-- Pagination (now always visible) -->
            <div class="flex justify-center mt-12 space-x-1">
                <template v-for="(link, index) in media.links" :key="index">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="flex items-center justify-center px-4 py-2 text-sm font-medium border rounded-md hover:bg-accent"
                        :class="{ 'bg-primary text-primary-foreground': link.active }"
                        v-html="link.label"
                    />
                    <span
                        v-else
                        class="flex items-center justify-center px-4 py-2 text-sm font-medium border rounded-md opacity-50"
                        v-html="link.label"
                    />
                </template>
            </div>
        </div>

        <!-- Empty State -->
        <div v-else class="py-16 mt-6 text-center border-2 border-dashed rounded-lg border-muted-foreground/30">
            <Video class="w-16 h-16 mx-auto text-muted-foreground" />
            <h3 class="mt-4 font-semibold">No Media Found</h3>
            <p class="mt-2 text-sm text-muted-foreground">Get started by uploading your first file.</p>
            <Link :href="route('media.upload')" class="inline-block mt-6">
                <Button size="lg">Upload Your First File</Button>
            </Link>
        </div>
    </div>
</template>
