<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { format } from 'date-fns';

// Define the layout for this admin page
defineOptions({ layout: AdminLayout });

// Define the shape of the data coming from the controller
const props = defineProps<{
  captionRequests: {
    id: number;
    status: string;
    requested_by: string;
    created_at: string;
    media: {
      id: number;
      title: string;
      user: { name: string };
    };
  }[];
}>();

// State for controlling the approval/rejection modal
const isModalOpen = ref(false);
const selectedRequest = ref<any>(null);

// Form for submitting the approval or rejection
const form = useForm({
  status: '',
  reason: '',
});

/**
 * Sets up the form and opens the modal for an admin to take action.
 * @param request The caption request object to be processed.
 * @param status The action being taken ('approved' or 'rejected').
 */
const openApprovalModal = (request: any, status: 'approved' | 'rejected') => {
  selectedRequest.value = request;
  form.status = status;
  form.reason = ''; // Reset reason field
  isModalOpen.value = true;
};

/**
 * Submits the approval or rejection to the backend.
 */
const submitApproval = () => {
  if (!selectedRequest.value) return;

  form.put(route('admin.caption-requests.update', selectedRequest.value.id), {
    onSuccess: () => {
      isModalOpen.value = false;
      form.reset();
    },
  });
};
</script>

<template>
  <Head title="Caption Requests" />
  <div class="container py-8 mx-auto">
    <h1 class="mb-8 text-3xl font-bold tracking-tight">Pending Caption Requests</h1>

    <div class="border rounded-lg">
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead>Media Title</TableHead>
            <TableHead>Uploader</TableHead>
            <TableHead>Requested By</TableHead>
            <TableHead>Date Requested</TableHead>
            <TableHead class="text-right">Actions</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableRow v-if="captionRequests.length === 0">
            <TableCell colspan="5" class="py-12 text-center text-muted-foreground">
              There are no pending caption requests.
            </TableCell>
          </TableRow>
          <TableRow v-for="request in captionRequests" :key="request.id">
            <TableCell class="font-medium">{{ request.media.title }}</TableCell>
            <TableCell>{{ request.media.user.name }}</TableCell>
            <TableCell>{{ request.requested_by }}</TableCell>
            <TableCell>{{ format(new Date(request.created_at), 'MMM d, yyyy') }}</TableCell>
            <TableCell class="text-right">
              <div class="flex items-center justify-end gap-2">
                <Button @click="openApprovalModal(request, 'approved')" variant="outline" size="sm">Approve</Button>
                <Button @click="openApprovalModal(request, 'rejected')" variant="destructive" size="sm">Reject</Button>
              </div>
            </TableCell>
          </TableRow>
        </TableBody>
      </Table>
    </div>

    <!-- Approval/Rejection Modal -->
    <Dialog v-model:open="isModalOpen">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Confirm {{ form.status === 'approved' ? 'Approval' : 'Rejection' }}</DialogTitle>
          <DialogDescription>
            You are about to {{ form.status }} the caption request for "{{ selectedRequest?.media.title }}". An email will be sent to the uploader.
          </DialogDescription>
        </DialogHeader>
        <div class="py-4 space-y-2">
          <Label for="reason">Reason / Notes (Optional)</Label>
          <Textarea id="reason" v-model="form.reason" placeholder="Add any notes or a reason for rejection..." />
          <div v-if="form.errors.reason" class="text-sm text-destructive">{{ form.errors.reason }}</div>
        </div>
        <DialogFooter>
          <Button variant="outline" @click="isModalOpen = false">Cancel</Button>
          <Button @click="submitApproval" :disabled="form.processing">
            {{ form.processing ? 'Submitting...' : `Confirm ${form.status}` }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </div>
</template>
