<script setup>
import { useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';

const props = defineProps({ media: Object });

const form = useForm({
  _method: 'PUT',
  title: props.media.title,
  description: props.media.description,
});

function saveChanges() {
  form.put(route('media.update', props.media.id));
}
</script>

<template>
  <div class="space-y-4">
    <div>
      <h3 class="text-lg font-medium">Edit Details</h3>
      <p class="text-sm text-muted-foreground">Update the title and description for this document.</p>
    </div>
    <div class="space-y-4">
      <div>
        <label>Title</label>
        <Input v-model="form.title" />
      </div>
      <div>
        <label>Description</label>
        <Textarea v-model="form.description" />
      </div>
    </div>
    <div class="flex justify-end pt-4">
      <Button @click="saveChanges" :disabled="form.processing">Save Changes</Button>
    </div>
  </div>
</template>
