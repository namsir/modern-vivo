<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue'; // Assuming you have an AdminLayout
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';

// Define the layout for this page
defineOptions({ layout: AdminLayout });

const props = defineProps<{
  profile: {
    id: number;
    name: string;
    api_key: string | null;
    profile: string | null;
    vendor: string | null;
    is_active: boolean;
  };
}>();

const form = useForm({
  name: props.profile.name,
  api_key: '', // API key is intentionally left blank for security
  profile: props.profile.profile || '',
  vendor: props.profile.vendor || '',
  is_active: props.profile.is_active,
});

const submit = () => {
  form.put(route('admin.caption-profiles.update', props.profile.id));
};
</script>

<template>
  <Head :title="`Edit: ${form.name}`" />
  <div class="container py-8 mx-auto">
    <h1 class="mb-8 text-3xl font-bold tracking-tight">Edit Caption Profile</h1>

    <form @submit.prevent="submit">
      <Card class="max-w-2xl">
        <CardHeader>
          <CardTitle>Vendor Details</CardTitle>
          <CardDescription>
            Update the details for the {{ form.name }} captioning service.
          </CardDescription>
        </CardHeader>
        <CardContent class="space-y-6">
          <div class="space-y-2">
            <Label for="name">Profile Name</Label>
            <Input
                id="name"
                v-model="form.name"
                type="text"
                placeholder="e.g., Rev.com"
                required
            />
            <div v-if="form.errors.name" class="text-sm text-destructive">{{ form.errors.name }}</div>
          </div>

          <div class="space-y-2">
            <Label for="api_key">API Key</Label>
            <Input
                id="api_key"
                v-model="form.api_key"
                type="password"
                placeholder="Enter new API key or leave blank"
            />
            <p class="text-xs text-muted-foreground">Leave this field blank to keep the existing key.</p>
            <div v-if="form.errors.api_key" class="text-sm text-destructive">{{ form.errors.api_key }}</div>
          </div>

          <div class="space-y-2">
            <Label for="profile">Profile</Label>
            <Input
                id="profile"
                v-model="form.profile"
                type="text"
                placeholder="e.g., Main Profile"
            />
            <div v-if="form.errors.profile" class="text-sm text-destructive">{{ form.errors.profile }}</div>
          </div>

          <div class="space-y-2">
            <Label for="vendor">Vendor</Label>
            <Input
                id="vendor"
                v-model="form.vendor"
                type="text"
                placeholder="e.g., Captioning Co."
            />
            <div v-if="form.errors.vendor" class="text-sm text-destructive">{{ form.errors.vendor }}</div>
          </div>

          <div class="flex items-center space-x-2">
            <Switch id="is_active" v-model="form.is_active" />
            <Label for="is_active">Set as active profile</Label>
          </div>
          <p class="text-xs text-muted-foreground">
            Note: If you set this profile as active, any other currently active profile will be deactivated.
          </p>
        </CardContent>
        <CardFooter class="flex justify-end gap-4">
          <Link :href="route('admin.caption-profiles.index')">
            <Button variant="outline">Cancel</Button>
          </Link>
          <Button type="submit" :disabled="form.processing">
            {{ form.processing ? 'Saving...' : 'Update Profile' }}
          </Button>
        </CardFooter>
      </Card>
    </form>
  </div>
</template>
