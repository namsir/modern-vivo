<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Sheet, SheetContent, SheetTrigger } from '@/components/ui/sheet';
import { Menu, Video, Upload, User } from 'lucide-vue-next';
import GlobalUploader from '@/Components/GlobalUploader.vue'; // We will create this next

const navigation = [
    { name: 'My Videos', href: '/videos', icon: Video },
    { name: 'Upload', href: '/videos/upload', icon: Upload },
    // ... other links
];
</script>

<template>
    <div class="grid min-h-screen w-full md:grid-cols-[220px_1fr] lg:grid-cols-[280px_1fr]">
        <aside class="hidden border-r bg-muted/40 md:block">
            <div class="flex h-full max-h-screen flex-col gap-2">
                <div class="flex h-14 items-center border-b px-4 lg:h-[60px] lg:px-6">
                    <Link href="/" class="flex items-center gap-2 font-semibold">
                        <Video class="h-6 w-6" />
                        <span>VIVO</span>
                    </Link>
                </div>
                <nav class="grid items-start px-2 text-sm font-medium lg:px-4">
                    <Link v-for="item in navigation" :key="item.name" :href="item.href"
                          class="flex items-center gap-3 rounded-lg px-3 py-2 text-muted-foreground transition-all hover:text-primary">
                        <component :is="item.icon" class="h-4 w-4" />
                        {{ item.name }}
                    </Link>
                </nav>
            </div>
        </aside>

        <div class="flex flex-col">
            <header class="flex h-14 items-center gap-4 border-b bg-muted/40 px-4 lg:h-[60px] lg:px-6">
                <Sheet>
                    <SheetTrigger as-child>
                        <Button variant="outline" size="icon" class="shrink-0 md:hidden">
                            <Menu class="h-5 w-5" />
                            <span class="sr-only">Toggle navigation menu</span>
                        </Button>
                    </SheetTrigger>
                    <SheetContent side="left" class="flex flex-col">
                    </SheetContent>
                </Sheet>
                <div class="w-full flex-1">
                </div>
            </header>

            <main class="flex flex-1 flex-col gap-4 p-4 lg:gap-6 lg:p-6">
                <slot />
            </main>

            <GlobalUploader />
        </div>
    </div>
</template>
