<script setup>
import { ref, computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Slider } from '@/components/ui/slider';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';

const props = defineProps({ media: Object });
const emit = defineEmits(['update:style']);

const form = useForm({
  _method: 'PUT',
  title: props.media.title,
  description: props.media.description,
});

const brightness = ref([100]);
const contrast = ref([100]);
const sepia = ref([0]);
const grayscale = ref([0]);

const imageStyle = computed(() => ({
  filter: `
    brightness(${brightness.value[0] / 100})
    contrast(${contrast.value[0] / 100})
    sepia(${sepia.value[0] / 100})
    grayscale(${grayscale.value[0] / 100})
  `,
}));

watch([brightness, contrast, sepia, grayscale], () => {
  emit('update:style', imageStyle.value);
}, { deep: true });

function saveChanges() {
  form.put(route('media.update', props.media.id));
}
</script>

<template>
  <div class="space-y-6">
    <Tabs default-value="details" class="w-full">
      <TabsList class="grid w-full grid-cols-2">
        <TabsTrigger value="details">Details</TabsTrigger>
        <TabsTrigger value="adjustments">Adjustments</TabsTrigger>
      </TabsList>
      <TabsContent value="details" class="mt-6 space-y-4">
        <div>
          <label>Title</label>
          <Input v-model="form.title" />
        </div>
        <div>
          <label>Description</label>
          <Textarea v-model="form.description" />
        </div>
      </TabsContent>
      <TabsContent value="adjustments" class="mt-6 space-y-4">
      </TabsContent>
    </Tabs>
    <div class="flex justify-end">
      <Button @click="saveChanges" :disabled="form.processing">Save Changes</Button>
    </div>
  </div>
</template>
