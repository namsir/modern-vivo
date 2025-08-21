<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from "@/layouts/AdminLayout.vue";
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { PlusCircle } from 'lucide-vue-next';

// Define the layout for this page
defineOptions({ layout: AdminLayout });

defineProps<{
  profiles: {
    id: number;
    name: string;
    is_active: boolean;
  }[];
}>();

const deleteProfile = (profileId: number) => {
  if (confirm('Are you sure you want to delete this profile? This action cannot be undone.')) {
    router.delete(route('admin.caption-profiles.destroy', profileId));
  }
};
</script>

<template>
  <Head title="Caption Profiles" />
  <div class="container py-8 mx-auto">
    <div class="flex items-center justify-between mb-8">
      <h1 class="text-3xl font-bold tracking-tight">Caption Profiles</h1>
      <Link :href="route('admin.caption-profiles.create')">
        <Button>
          <PlusCircle class="w-4 h-4 mr-2" />
          Create Profile
        </Button>
      </Link>
    </div>

    <div class="border rounded-lg">
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead>Profile Name</TableHead>
            <TableHead>Status</TableHead>
            <TableHead class="text-right">Actions</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableRow v-if="profiles.length === 0">
            <TableCell colspan="3" class="text-center">No caption profiles found.</TableCell>
          </TableRow>
          <TableRow v-for="profile in profiles" :key="profile.id">
            <TableCell class="font-medium">{{ profile.name }}</TableCell>
            <TableCell>
              <Badge :variant="profile.is_active ? 'default' : 'outline'">
                {{ profile.is_active ? 'Active' : 'Inactive' }}
              </Badge>
            </TableCell>
            <TableCell class="text-right">
              <div class="flex items-center justify-end gap-2">
                <Link :href="route('admin.caption-profiles.edit', profile.id)">
                  <Button variant="outline" size="sm">Edit</Button>
                </Link>
                <Button @click="deleteProfile(profile.id)" variant="destructive" size="sm">
                  Delete
                </Button>
              </div>
            </TableCell>
          </TableRow>
        </TableBody>
      </Table>
    </div>
  </div>
</template>
