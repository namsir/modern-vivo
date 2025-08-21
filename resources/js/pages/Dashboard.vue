<script setup lang="ts">
import {ref, onMounted, computed, watch, nextTick, onUnmounted} from 'vue';
import {useMouse, useMousePressed} from '@vueuse/core';
import {
    format,
    addMinutes,
    setHours,
    setMinutes,
    startOfDay,
    getMinutes,
    getHours,
    differenceInMinutes,
    areIntervalsOverlapping,
    isEqual,
    addDays,
    subDays,
    startOfWeek,
    isSameDay
} from 'date-fns';
import {cn} from '@/lib/utils'; // Assuming you have a utils file for cn

// Import Shadcn-Vue Components


import {Head, Link, router} from '@inertiajs/vue3'
import {debounce} from 'lodash-es' // npm install lodash-es
import {Command, CommandEmpty, CommandGroup, CommandInput, CommandItem} from '@/components/ui/command'
import {Check, ChevronsUpDown} from 'lucide-vue-next'


// Shadcn-vue UI Components
import {Button} from '@/components/ui/button'
import {Card, CardContent, CardFooter, CardHeader} from '@/components/ui/card'
import {Input} from '@/components/ui/input'
import {Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue} from '@/components/ui/select'
import {Avatar, AvatarFallback, AvatarImage} from '@/components/ui/avatar'
import {Calendar} from "@/components/ui/calendar";
import type {DateRange} from "reka-ui"
import {
    CalendarDate,
    DateFormatter,
    getLocalTimeZone,
} from "@internationalized/date"
import {Calendar as CalendarIcon} from "lucide-vue-next"
import {RangeCalendar} from "@/components/ui/range-calendar"

// Icons
import {Video, ImageIcon, FileText, Search} from 'lucide-vue-next'

import {Popover, PopoverContent, PopoverTrigger} from "@/layouts/ui/popover/index";
import AdminLayout from "@/layouts/AdminLayout.vue";

// Define the layout for this page
defineOptions({layout: AdminLayout});
// We use `withDefaults` to provide fallback values for our props.
// This is the key to fixing the error.

import { type User } from '@/types';
import Badge from "@/layouts/ui/badge/Badge.vue";

const props = withDefaults(defineProps<{
    media?: any
    filters?: any
    users?: Array<User>
}>(), {
    // If `videos` isn't passed, use an object that looks like a paginator.
    // This prevents `videos.data` from ever crashing.
    videos: () => ({data: [], links: []}),

    // If `filters` isn't passed, use an empty object.
    // This prevents `filters.search` or `filters.date_from` from crashing.
    filters: () => ({}),

    // If `users` isn't passed, use an empty array.
    users: () => [],
})

// The rest of your script setup can now safely assume the props exist.
const filterForm = ref({
    search: props.filters.search || '',
    status: props.filters.status || '',
    media_type: props.filters.media_type || '',
    user_id: props.filters.user_id || '',
    date_from: props.filters.date_from || null,
    date_to: props.filters.date_to || null,
})

const open = ref(false)
const df = new DateFormatter("en-US", {
    dateStyle: "medium",
})

const date = ref({
    start: new CalendarDate(2022, 1, 20),
    end: new CalendarDate(2022, 1, 20).add({days: 20}),
}) as Ref<DateRange>


// A map to associate media types with their corresponding icons
const mediaTypeIcons = {
    video: Video,
    image: ImageIcon,
    doc: FileText,
}

// A computed property to format the date range for clean display in the button
const dateRangeText = computed(() => {
    const from = filterForm.value.date_from ? new Date(filterForm.value.date_from) : null
    const to = filterForm.value.date_to ? new Date(filterForm.value.date_to) : null

    if (from && to) {
        return `${format(from, 'LLL d, y')} - ${format(to, 'LLL d, y')}`
    }
    if (from) {
        return format(from, 'LLL d, y')
    }
    return 'Pick a date range'
})
watch(date, (newDateRange) => {
    // When the calendar's date changes, update our main filterForm with formatted strings.
    // The backend expects strings in 'yyyy-MM-dd' format.
    filterForm.value.date_from = newDateRange.start ? format(newDateRange.start, 'yyyy-MM-dd') : null;
    filterForm.value.date_to = newDateRange.end ? format(newDateRange.end, 'yyyy-MM-dd') : null;
})

// This original watcher now works correctly, as it's triggered by the watch above.
watch(filterForm, debounce(() => {
    router.get('/dashboard', filterForm.value, {
        preserveState: true,
        replace: true,
    })
}, 300), {deep: true})
</script>
<template>

    <div>
        <div class="p-4 mb-6 space-y-4 border rounded-lg bg-card text-card-foreground">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <Input v-model="filterForm.search" type="text" placeholder="Search title or description..."/>

                <Popover v-model:open="open">
                    <PopoverTrigger as-child>
                        <Button
                            variant="outline"
                            role="combobox"
                            :aria-expanded="open"
                            class="w-full justify-between"
                        >
                            {{
                                filterForm.user_id ? users.find((user) => String(user.id) === filterForm.user_id)?.name : "Select uploader..."
                            }}
                            <ChevronsUpDown class="w-4 h-4 ml-2 opacity-50 shrink-0"/>
                        </Button>
                    </PopoverTrigger>
                    <PopoverContent class="w-[var(--radix-popover-trigger-width)] p-0">
                        <Command>
                            <CommandInput placeholder="Search for user..."/>
                            <CommandEmpty>No user found.</CommandEmpty>
                            <CommandGroup>
                                <CommandItem
                                    v-for="user in users"
                                    :key="user.id"
                                    :value="user.name"
                                    @select="(ev) => {
                        if (ev.detail.value === users.find(u => String(u.id) === filterForm.user_id)?.name) {
                            filterForm.user_id = ''
                        } else {
                            filterForm.user_id = String(users.find(u => u.name === ev.detail.value)?.id)
                        }
                        open = false
                    }"
                                >
                                    <Check
                                        :class="cn('mr-2 h-4 w-4', String(user.id) === filterForm.user_id ? 'opacity-100' : 'opacity-0')"/>
                                    {{ user.name }}
                                </CommandItem>
                            </CommandGroup>
                        </Command>
                    </PopoverContent>
                </Popover>

                <Select v-model="filterForm.status">
                    <SelectTrigger>
                        <SelectValue placeholder="Filter by Status"/>
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="Published">Published</SelectItem>
                        <SelectItem value="Private">Private</SelectItem>
                        <SelectItem value="Processing">Processing</SelectItem>
                    </SelectContent>
                </Select>

                <Select v-model="filterForm.media_type">
                    <SelectTrigger>
                        <SelectValue placeholder="Filter by Type"/>
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="video">Video</SelectItem>
                        <SelectItem value="image">Image</SelectItem>
                        <SelectItem value="doc">Document</SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <Popover>
                    <PopoverTrigger as-child>
                        <Button
                            variant="outline"
                            :class="cn(
          'w-[280px] justify-start text-left font-normal',
          !date && 'text-muted-foreground',
        )"
                        >
                            <CalendarIcon class="mr-2 h-4 w-4"/>
                            <template v-if="date.start">
                                <template v-if="date.end">
                                    {{ df.format(date.start.toDate(getLocalTimeZone())) }} -
                                    {{ df.format(date.end.toDate(getLocalTimeZone())) }}
                                </template>

                                <template v-else>
                                    {{ df.format(date.start.toDate(getLocalTimeZone())) }}
                                </template>
                            </template>
                            <template v-else>
                                Pick a date
                            </template>
                        </Button>
                    </PopoverTrigger>
                    <PopoverContent class="w-auto p-0">
                        <RangeCalendar v-model="value" initial-focus :number-of-months="2"
                                       @update:start-value="(startDate) => value.start = startDate"/>
                    </PopoverContent>
                </Popover>
            </div>
        </div>

        <div v-if="media.data.length > 0">
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                <Link v-for="item in media.data" :key="item.id" :href="route('media.edit', item.id)">
                    <Card
                        class="relative flex flex-col h-full transition-all duration-200 ease-in-out group hover:border-primary hover:shadow-lg">
                        <!-- Icon overlay with primary color -->
                        <div
                            class="absolute top-3 right-3 z-10 p-1.5 bg-primary text-primary-foreground rounded-full shadow-md">
                            <component
                                :is="mediaTypeIcons[item.media_type] || FileText"
                                class="w-4 h-4"
                            />
                        </div>
                        <CardHeader class="p-4">
                            <!-- Dynamic Thumbnail Section -->
                            <div
                                class="flex items-center justify-center w-full overflow-hidden rounded-md bg-muted aspect-video">
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
                                <Badge v-for="tag in item.tags.slice(0, 3)" :key="tag.id" variant="secondary">
                                    {{ tag.name }}
                                </Badge>
                            </div>
                        </CardContent>
                        <CardFooter
                            class="flex items-center justify-between p-6 pt-0 text-sm border-t text-muted-foreground">
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
            <Video class="w-16 h-16 mx-auto text-muted-foreground"/>
            <h3 class="mt-4 font-semibold">No Media Found</h3>
            <p class="mt-2 text-sm text-muted-foreground">Get started by uploading your first file.</p>
            <Link :href="route('media.upload')" class="inline-block mt-6">
                <Button size="lg">Upload Your First File</Button>
            </Link>
        </div>
    </div>


</template>
<style>
</style>
