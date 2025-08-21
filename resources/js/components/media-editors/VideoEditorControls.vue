<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Slider } from '@/components/ui/slider';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Badge } from '@/components/ui/badge';
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { X, Upload } from 'lucide-vue-next';

const props = defineProps({
  media: Object,
  allTags: Array,
});

const emit = defineEmits(['update:style', 'update:caption']);

// Main form for details (title, description, tags)
const detailsForm = useForm({
  _method: 'PUT',
  title: props.media.title,
  description: props.media.description,
  tags: props.media.tags.map(tag => tag.name) || [],
});

// Separate form for editing caption text
const captionEditForm = useForm({
  caption_content: props.media.captions[0]?.caption || '',
});

// Separate form for uploading a new caption file
const captionUploadForm = useForm({
  caption_file: null as File | null,
});

// --- State for effects and tags ---
const brightness = ref([100]);
const contrast = ref([100]);
const sepia = ref([0]);
const grayscale = ref([0]);
const tagInput = ref('');
const tagPopoverOpen = ref(false);

const videoStyle = computed(() => ({
  filter: `
    brightness(${brightness.value[0] / 100})
    contrast(${contrast.value[0] / 100})
    sepia(${sepia.value[0] / 100})
    grayscale(${grayscale.value[0] / 100})
  `,
}));

const filteredTags = computed(() => {
  const availableTags = props.allTags || [];
  if (!tagInput.value) {
    return availableTags.filter(tag => !detailsForm.tags.includes(tag.name));
  }
  return availableTags.filter(tag =>
      !detailsForm.tags.includes(tag.name) &&
      tag.name.toLowerCase().includes(tagInput.value.toLowerCase())
  );
});

watch([brightness, contrast, sepia, grayscale], () => {
  emit('update:style', videoStyle.value);
}, { deep: true });

watch(() => captionEditForm.caption_content, (newContent) => {
  emit('update:caption', newContent);
});


// --- Submission Handlers ---
function saveDetails() {
  detailsForm.put(route('media.update', props.media.id));
}

function saveCaptions() {
  captionEditForm.put(route('media.captions.update', props.media.id));
}

function uploadCaptionFile() {
  if (captionUploadForm.caption_file) {
    captionUploadForm.post(route('media.captions.store', props.media.id), {
      onSuccess: () => {
        captionUploadForm.reset('caption_file');
      }
    });
  }
}

function applyPermanentFilters() {
  if (confirm('This will create a new video file with the applied filters. This process can take time. Continue?')) {
    const filtersToApply = {
      brightness: brightness.value[0],
      contrast: contrast.value[0],
      sepia: sepia.value[0],
      grayscale: grayscale.value[0],
    };
    router.post(`/media/${props.media.id}/apply-filters`, { filters: filtersToApply });
  }
}

// --- Tag and Caption Helpers ---
function addTag(tagName: string): void {
  const trimmedTag = tagName.trim();
  if (trimmedTag && !detailsForm.tags.includes(trimmedTag)) {
    detailsForm.tags.push(trimmedTag);
  }
  tagInput.value = '';
  tagPopoverOpen.value = false;
}

function removeTag(index: number): void {
  detailsForm.tags.splice(index, 1);
}

function handleCaptionFileSelect(event: Event) {
  const target = event.target as HTMLInputElement;
  const file = target.files?.[0];

  if (file) {
    captionUploadForm.caption_file = file;
    const reader = new FileReader();
    reader.onload = (e) => {
      const newContent = e.target?.result as string;
      captionEditForm.caption_content = newContent;
      emit('update:caption', newContent);
    };
    reader.readAsText(file);
  }
}

</script>

<template>
  <div class="space-y-6">
    <Tabs default-value="details" class="w-full">
      <TabsList class="grid w-full grid-cols-3">
        <TabsTrigger value="details">Details</TabsTrigger>
        <TabsTrigger value="captions">Captions</TabsTrigger>
        <TabsTrigger value="effects">Effects</TabsTrigger>
      </TabsList>

      <!-- Details Tab -->
      <TabsContent value="details" class="mt-6 space-y-4">
        <div>
          <label for="title" class="text-sm font-medium">Title</label>
          <Input id="title" v-model="detailsForm.title" />
        </div>
        <div>
          <label for="description" class="text-sm font-medium">Description</label>
          <Textarea id="description" v-model="detailsForm.description" />
        </div>
        <div>
          <label class="text-sm font-medium">Tags</label>
          <div class="flex flex-wrap items-center gap-2 p-2 mt-1 border rounded-lg min-h-11">
            <Badge v-for="(tag, index) in detailsForm.tags" :key="index" variant="secondary" class="relative rounded-full py-1 pl-3 pr-7 text-sm font-normal">
              {{ tag }}
              <button @click.prevent="removeTag(index)" class="absolute top-0.5 right-0.5 flex h-4 w-4 items-center justify-center rounded-full transition-colors hover:bg-destructive/20">
                <X class="h-3 w-3 text-destructive" />
              </button>
            </Badge>
            <Popover v-model:open="tagPopoverOpen">
              <PopoverTrigger as-child>
                <input placeholder="Add a tag..." class="flex-grow bg-transparent outline-none" @focus="tagPopoverOpen = true" />
              </PopoverTrigger>
              <PopoverContent class="w-[--radix-popover-trigger-width] p-0" align="start">
                <Command>
                  <CommandInput placeholder="Search for a tag..." v-model="tagInput" @keydown.enter.prevent="addTag(tagInput)" />
                  <CommandList>
                    <CommandEmpty>No results. Press Enter to create.</CommandEmpty>
                    <CommandGroup>
                      <CommandItem v-for="tag in filteredTags" :key="tag.id" :value="tag.name" @select="addTag(tag.name)">
                        {{ tag.name }}
                      </CommandItem>
                    </CommandGroup>
                  </CommandList>
                </Command>
              </PopoverContent>
            </Popover>
          </div>
        </div>
      </TabsContent>

      <!-- Captions Tab -->
      <TabsContent value="captions" class="mt-6 space-y-4">
        <div>
          <h4 class="font-medium">Upload New Caption File</h4>
          <p class="text-sm text-muted-foreground">Upload a .vtt file. This will overwrite any existing captions.</p>
          <div class="flex items-center gap-2 mt-2">
            <Input
                id="captionFile"
                type="file"
                @change="handleCaptionFileSelect"
                accept=".vtt"
            />
            <Button @click="uploadCaptionFile" :disabled="!captionUploadForm.caption_file || captionUploadForm.processing">
              <Upload class="w-4 h-4 mr-2" /> Upload
            </Button>
          </div>
          <div v-if="captionUploadForm.errors.caption_file" class="mt-1 text-sm text-destructive">
            {{ captionUploadForm.errors.caption_file }}
          </div>
        </div>

        <div class="pt-4 border-t">
          <h4 class="font-medium">Edit Existing Captions</h4>
          <p class="text-sm text-muted-foreground">Modify the content of the current caption file directly.</p>
          <div v-if="captionEditForm.errors.caption_content" class="mt-2 text-sm font-bold text-destructive">
            {{ captionEditForm.errors.caption_content }}
          </div>
          <Textarea
              v-model="captionEditForm.caption_content"
              class="mt-2 font-mono h-96"
              placeholder="No captions found. Upload a .vtt file to begin."
          />
          <div class="flex justify-end mt-2">
            <Button @click="saveCaptions" :disabled="captionEditForm.processing">
              {{ captionEditForm.processing ? 'Saving...' : 'Save Captions' }}
            </Button>
          </div>
        </div>
      </TabsContent>

      <!-- Effects Tab -->
      <TabsContent value="effects" class="mt-6 space-y-4">
        <div class="text-sm text-muted-foreground">Preview filters in real-time.</div>
        <div class="grid w-full items-center gap-1.5">
          <label class="text-sm font-medium">Brightness ({{ brightness[0] }}%)</label>
          <Slider v-model="brightness" :max="200" :step="1" />
        </div>
        <div class="grid w-full items-center gap-1.5">
          <label class="text-sm font-medium">Contrast ({{ contrast[0] }}%)</label>
          <Slider v-model="contrast" :max="200" :step="1" />
        </div>
        <div class="grid w-full items-center gap-1.5">
          <label class="text-sm font-medium">Sepia ({{ sepia[0] }}%)</label>
          <Slider v-model="sepia" :max="100" :step="1" />
        </div>
        <div class="grid w-full items-center gap-1.5">
          <label class="text-sm font-medium">Grayscale ({{ grayscale[0] }}%)</label>
          <Slider v-model="grayscale" :max="100" :step="1" />
        </div>
        <Button @click="applyPermanentFilters" variant="secondary" class="w-full !mt-6">
          Apply Filters Permanently
        </Button>
      </TabsContent>
    </Tabs>

    <div class="flex justify-end pt-4 border-t">
      <Button @click="saveDetails" :disabled="detailsForm.processing" size="lg">
        {{ detailsForm.processing ? 'Saving...' : 'Save Details' }}
      </Button>
    </div>
  </div>
</template>
