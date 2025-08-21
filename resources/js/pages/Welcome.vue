<!-- App.vue -->
<template>
    <div id="app" class="min-h-screen bg-background text-foreground">
        <!-- Navigation -->
        <nav class="border-b border-border bg-card shadow-sm">
            <div class="container mx-auto px-4 py-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <h1 class="text-2xl font-bold text-primary">VIVO</h1>
                        <nav class="hidden md:flex space-x-6">
                            <button
                                @click="currentView = 'dashboard'"
                                :class="['px-3 py-2 rounded-md text-sm font-medium transition-colors',
                  currentView === 'dashboard' ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:text-foreground hover:bg-accent']"
                            >
                                Dashboard
                            </button>
                            <button
                                @click="currentView = 'upload'"
                                :class="['px-3 py-2 rounded-md text-sm font-medium transition-colors',
                  currentView === 'upload' ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:text-foreground hover:bg-accent']"
                            >
                                Upload
                            </button>
                        </nav>
                    </div>

                    <!-- Mobile menu button -->
                    <button
                        @click="mobileMenuOpen = !mobileMenuOpen"
                        class="md:hidden p-2 rounded-md hover:bg-accent"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>

                    <!-- User info -->
                    <div class="hidden md:flex items-center space-x-4">
                        <div class="w-8 h-8 rounded-full bg-primary text-primary-foreground flex items-center justify-center text-sm font-medium">
                            {{ user.name.charAt(0) }}
                        </div>
                        <span class="text-sm text-muted-foreground">{{ user.name }}</span>
                    </div>
                </div>

                <!-- Mobile menu -->
                <div v-if="mobileMenuOpen" class="mt-4 md:hidden">
                    <div class="flex flex-col space-y-2">
                        <button
                            @click="currentView = 'dashboard'; mobileMenuOpen = false"
                            :class="['px-3 py-2 rounded-md text-sm font-medium transition-colors text-left',
                currentView === 'dashboard' ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:text-foreground hover:bg-accent']"
                        >
                            Dashboard
                        </button>
                        <button
                            @click="currentView = 'upload'; mobileMenuOpen = false"
                            :class="['px-3 py-2 rounded-md text-sm font-medium transition-colors text-left',
                currentView === 'upload' ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:text-foreground hover:bg-accent']"
                        >
                            Upload
                        </button>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="container mx-auto px-4 py-6">
            <!-- Dashboard View -->
            <div v-if="currentView === 'dashboard'">
                <div class="mb-6">
                    <h2 class="text-3xl font-bold mb-2">My Videos</h2>
                    <p class="text-muted-foreground">Manage your uploaded videos and documents</p>
                </div>

                <!-- Video Grid -->
                <div v-if="videos.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div v-for="video in videos" :key="video.id" class="bg-card rounded-lg border border-border shadow-sm overflow-hidden">
                        <!-- Video Thumbnail -->
                        <div class="aspect-video bg-muted relative">
                            <video
                                v-if="video.type === 'video'"
                                :src="video.url"
                                class="w-full h-full object-cover"
                                preload="metadata"
                            ></video>
                            <div v-else class="w-full h-full flex items-center justify-center">
                                <svg class="w-16 h-16 text-muted-foreground" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </div>

                        <!-- Video Info -->
                        <div class="p-4">
                            <h3 class="font-semibold text-lg mb-2 truncate">{{ video.title }}</h3>
                            <p class="text-sm text-muted-foreground mb-3">{{ formatFileSize(video.size) }} • {{ formatDate(video.uploadDate) }}</p>

                            <!-- Actions -->
                            <div class="flex flex-wrap gap-2">
                                <button
                                    @click="editVideo(video)"
                                    class="px-3 py-1 bg-primary text-primary-foreground rounded-md text-sm hover:bg-primary/90 transition-colors"
                                >
                                    Edit
                                </button>
                                <button
                                    @click="deleteVideo(video.id)"
                                    class="px-3 py-1 bg-destructive text-destructive-foreground rounded-md text-sm hover:bg-destructive/90 transition-colors"
                                >
                                    Delete
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="text-center py-12">
                    <svg class="mx-auto w-16 h-16 text-muted-foreground mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                    <h3 class="text-lg font-medium mb-2">No videos uploaded yet</h3>
                    <p class="text-muted-foreground mb-4">Start by uploading your first video or document</p>
                    <button
                        @click="currentView = 'upload'"
                        class="px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90 transition-colors"
                    >
                        Upload Now
                    </button>
                </div>
            </div>

            <!-- Upload View -->
            <div v-if="currentView === 'upload'">
                <div class="mb-6">
                    <h2 class="text-3xl font-bold mb-2">Upload Files</h2>
                    <p class="text-muted-foreground">Upload videos, images, or documents</p>
                </div>

                <!-- Upload Area -->
                <div
                    @drop="handleDrop"
                    @dragover.prevent
                    @dragenter.prevent
                    class="border-2 border-dashed border-border rounded-lg p-12 text-center hover:border-primary/50 transition-colors"
                >
                    <svg class="mx-auto w-16 h-16 text-muted-foreground mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                    </svg>
                    <h3 class="text-lg font-medium mb-2">Drop files here or click to browse</h3>
                    <p class="text-muted-foreground mb-4">Supports videos (MP4, MOV, AVI), images (JPG, PNG, GIF), and documents (PDF, DOC, DOCX)</p>
                    <input
                        ref="fileInput"
                        type="file"
                        multiple
                        accept="video/*,image/*,.pdf,.doc,.docx"
                        @change="handleFileSelect"
                        class="hidden"
                    >
                    <button
                        @click="$refs.fileInput.click()"
                        class="px-6 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90 transition-colors"
                    >
                        Choose Files
                    </button>
                </div>
            </div>

            <!-- Edit Video View -->
            <div v-if="currentView === 'edit' && editingVideo">
                <div class="mb-6">
                    <button
                        @click="currentView = 'dashboard'"
                        class="flex items-center text-muted-foreground hover:text-foreground mb-4"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        Back to Dashboard
                    </button>
                    <h2 class="text-3xl font-bold mb-2">Edit Video</h2>
                    <p class="text-muted-foreground">Edit video details and captions</p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Video Preview -->
                    <div class="space-y-4">
                        <div class="aspect-video bg-muted rounded-lg overflow-hidden">
                            <video
                                v-if="editingVideo.type === 'video'"
                                :src="editingVideo.url"
                                controls
                                class="w-full h-full"
                            ></video>
                            <div v-else class="w-full h-full flex items-center justify-center">
                                <svg class="w-20 h-20 text-muted-foreground" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Edit Form -->
                    <div class="space-y-6">
                        <!-- Title -->
                        <div>
                            <label class="block text-sm font-medium mb-2">Title</label>
                            <input
                                v-model="editingVideo.title"
                                type="text"
                                class="w-full px-3 py-2 border border-border rounded-md bg-background focus:ring-2 focus:ring-primary focus:border-transparent"
                            >
                        </div>

                        <!-- Captions -->
                        <div v-if="editingVideo.type === 'video'">
                            <div class="flex items-center justify-between mb-4">
                                <label class="block text-sm font-medium">Captions</label>
                                <button
                                    @click="addCaption"
                                    class="px-3 py-1 bg-primary text-primary-foreground rounded-md text-sm hover:bg-primary/90 transition-colors"
                                >
                                    Add Caption
                                </button>
                            </div>

                            <div v-if="editingVideo.captions && editingVideo.captions.length > 0" class="space-y-3">
                                <div
                                    v-for="(caption, index) in editingVideo.captions"
                                    :key="index"
                                    class="p-4 border border-border rounded-lg bg-card"
                                >
                                    <div class="grid grid-cols-2 gap-3 mb-3">
                                        <div>
                                            <label class="block text-xs text-muted-foreground mb-1">Start Time</label>
                                            <input
                                                v-model="caption.startTime"
                                                type="text"
                                                placeholder="00:00"
                                                class="w-full px-2 py-1 border border-border rounded text-sm bg-background focus:ring-2 focus:ring-primary focus:border-transparent"
                                            >
                                        </div>
                                        <div>
                                            <label class="block text-xs text-muted-foreground mb-1">End Time</label>
                                            <input
                                                v-model="caption.endTime"
                                                type="text"
                                                placeholder="00:05"
                                                class="w-full px-2 py-1 border border-border rounded text-sm bg-background focus:ring-2 focus:ring-primary focus:border-transparent"
                                            >
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="block text-xs text-muted-foreground mb-1">Caption Text</label>
                                        <textarea
                                            v-model="caption.text"
                                            rows="2"
                                            placeholder="Enter caption text..."
                                            class="w-full px-2 py-1 border border-border rounded text-sm bg-background focus:ring-2 focus:ring-primary focus:border-transparent resize-none"
                                        ></textarea>
                                    </div>
                                    <button
                                        @click="removeCaption(index)"
                                        class="px-2 py-1 bg-destructive text-destructive-foreground rounded text-xs hover:bg-destructive/90 transition-colors"
                                    >
                                        Remove
                                    </button>
                                </div>
                            </div>

                            <div v-else class="text-center py-8 border border-dashed border-border rounded-lg">
                                <p class="text-muted-foreground">No captions added yet</p>
                            </div>
                        </div>

                        <!-- Save Button -->
                        <div class="flex gap-3">
                            <button
                                @click="saveVideo"
                                class="px-6 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90 transition-colors"
                            >
                                Save Changes
                            </button>
                            <button
                                @click="currentView = 'dashboard'"
                                class="px-6 py-2 border border-border rounded-md hover:bg-accent transition-colors"
                            >
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Upload Progress (Bottom Right) -->
        <div
            v-if="uploadProgress.show"
            @click="goToOriginalView"
            class="fixed bottom-4 right-4 bg-card border border-border rounded-lg shadow-lg p-4 cursor-pointer hover:shadow-xl transition-shadow max-w-sm"
        >
            <div class="flex items-center justify-between mb-2">
                <h4 class="font-medium">Uploading {{ uploadProgress.fileName }}</h4>
                <span class="text-sm text-muted-foreground">{{ uploadProgress.progress }}%</span>
            </div>
            <div class="w-full bg-muted rounded-full h-2">
                <div
                    class="bg-primary h-2 rounded-full transition-all duration-300"
                    :style="{ width: uploadProgress.progress + '%' }"
                ></div>
            </div>
            <p class="text-xs text-muted-foreground mt-1">Click to view details</p>
        </div>

        <!-- Delete Confirmation Modal -->
        <div v-if="showDeleteModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
            <div class="bg-card rounded-lg p-6 w-full max-w-md border border-border">
                <h3 class="text-lg font-semibold mb-2">Delete Video</h3>
                <p class="text-muted-foreground mb-6">Are you sure you want to delete this video? This action cannot be undone.</p>
                <div class="flex gap-3">
                    <button
                        @click="confirmDelete"
                        class="flex-1 px-4 py-2 bg-destructive text-destructive-foreground rounded-md hover:bg-destructive/90 transition-colors"
                    >
                        Delete
                    </button>
                    <button
                        @click="showDeleteModal = false"
                        class="flex-1 px-4 py-2 border border-border rounded-md hover:bg-accent transition-colors"
                    >
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'

// User state
const user = reactive({
    id: 1,
    name: 'John Doe',
    email: 'john@example.com'
})

// App state
const currentView = ref('dashboard') // 'dashboard', 'upload', 'edit'
const mobileMenuOpen = ref(false)
const videos = ref([])
const editingVideo = ref(null)
const showDeleteModal = ref(false)
const videoToDelete = ref(null)

// Upload progress state
const uploadProgress = reactive({
    show: false,
    fileName: '',
    progress: 0,
    originalView: 'dashboard'
})

// Sample data for demonstration
onMounted(() => {
    // Load sample videos
    videos.value = [
        {
            id: 1,
            title: 'Sample Video 1',
            url: 'data:video/mp4;base64,', // Placeholder URL
            type: 'video',
            size: 15728640, // 15MB
            uploadDate: new Date('2024-01-15'),
            captions: [
                { startTime: '00:00', endTime: '00:05', text: 'Welcome to our presentation' },
                { startTime: '00:05', endTime: '00:10', text: 'Today we will discuss...' }
            ]
        },
        {
            id: 2,
            title: 'Document.pdf',
            url: '#',
            type: 'document',
            size: 2097152, // 2MB
            uploadDate: new Date('2024-01-10'),
            captions: []
        }
    ]
})

// File handling
const handleDrop = (e) => {
    e.preventDefault()
    const files = Array.from(e.dataTransfer.files)
    processFiles(files)
}

const handleFileSelect = (e) => {
    const files = Array.from(e.target.files)
    processFiles(files)
    e.target.value = '' // Reset input
}

const processFiles = async (files) => {
    for (const file of files) {
        await uploadFile(file)
    }
}

const uploadFile = async (file) => {
    // Show upload progress
    uploadProgress.originalView = currentView.value
    uploadProgress.show = true
    uploadProgress.fileName = file.name
    uploadProgress.progress = 0

    // Simulate upload progress
    const interval = setInterval(() => {
        uploadProgress.progress += Math.random() * 10
        if (uploadProgress.progress >= 100) {
            uploadProgress.progress = 100
            clearInterval(interval)

            // Add to videos list
            const newVideo = {
                id: Date.now(),
                title: file.name,
                url: URL.createObjectURL(file),
                type: file.type.startsWith('video/') ? 'video' :
                    file.type.startsWith('image/') ? 'image' : 'document',
                size: file.size,
                uploadDate: new Date(),
                captions: []
            }

            videos.value.unshift(newVideo)

            // Hide progress after 2 seconds
            setTimeout(() => {
                uploadProgress.show = false
                uploadProgress.progress = 0
            }, 2000)
        }
    }, 200)
}

const goToOriginalView = () => {
    currentView.value = uploadProgress.originalView
}

// Video management
const editVideo = (video) => {
    editingVideo.value = { ...video, captions: [...(video.captions || [])] }
    currentView.value = 'edit'
}

const saveVideo = () => {
    const index = videos.value.findIndex(v => v.id === editingVideo.value.id)
    if (index !== -1) {
        videos.value[index] = { ...editingVideo.value }
    }
    currentView.value = 'dashboard'
    editingVideo.value = null
}

const deleteVideo = (id) => {
    videoToDelete.value = id
    showDeleteModal.value = true
}

const confirmDelete = () => {
    videos.value = videos.value.filter(v => v.id !== videoToDelete.value)
    showDeleteModal.value = false
    videoToDelete.value = null
}

// Caption management
const addCaption = () => {
    if (!editingVideo.value.captions) {
        editingVideo.value.captions = []
    }
    editingVideo.value.captions.push({
        startTime: '00:00',
        endTime: '00:05',
        text: ''
    })
}

const removeCaption = (index) => {
    editingVideo.value.captions.splice(index, 1)
}

// Utility functions
const formatFileSize = (bytes) => {
    if (bytes === 0) return '0 Bytes'
    const k = 1024
    const sizes = ['Bytes', 'KB', 'MB', 'GB']
    const i = Math.floor(Math.log(bytes) / Math.log(k))
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

const formatDate = (date) => {
    return new Intl.DateTimeFormat('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    }).format(date)
}
</script>

<style scoped>
/* Custom scrollbar styles */
::-webkit-scrollbar {
    width: 6px;
}

::-webkit-scrollbar-track {
    background: hsl(var(--muted));
}

::-webkit-scrollbar-thumb {
    background: hsl(var(--muted-foreground));
    border-radius: 3px;
}

::-webkit-scrollbar-thumb:hover {
    background: hsl(var(--foreground));
}

/* CSS Variables for Shadcn/ui compatibility */
:root {
    --background: 0 0% 100%;
    --foreground: 240 10% 3.9%;
    --card: 0 0% 100%;
    --card-foreground: 240 10% 3.9%;
    --popover: 0 0% 100%;
    --popover-foreground: 240 10% 3.9%;
    --primary: 240 5.9% 10%;
    --primary-foreground: 0 0% 98%;
    --secondary: 240 4.8% 95.9%;
    --secondary-foreground: 240 5.9% 10%;
    --muted: 240 4.8% 95.9%;
    --muted-foreground: 240 3.8% 46.1%;
    --accent: 240 4.8% 95.9%;
    --accent-foreground: 240 5.9% 10%;
    --destructive: 0 84.2% 60.2%;
    --destructive-foreground: 0 0% 98%;
    --border: 240 5.9% 90%;
    --input: 240 5.9% 90%;
    --ring: 240 5.9% 10%;
    --radius: 0.5rem;
}

.dark {
    --background: 240 10% 3.9%;
    --foreground: 0 0% 98%;
    --card: 240 10% 3.9%;
    --card-foreground: 0 0% 98%;
    --popover: 240 10% 3.9%;
    --popover-foreground: 0 0% 98%;
    --primary: 0 0% 98%;
    --primary-foreground: 240 5.9% 10%;
    --secondary: 240 3.7% 15.9%;
    --secondary-foreground: 0 0% 98%;
    --muted: 240 3.7% 15.9%;
    --muted-foreground: 240 5% 64.9%;
    --accent: 240 3.7% 15.9%;
    --accent-foreground: 0 0% 98%;
    --destructive: 0 62.8% 30.6%;
    --destructive-foreground: 0 0% 98%;
    --border: 240 3.7% 15.9%;
    --input: 240 3.7% 15.9%;
    --ring: 240 4.9% 83.9%;
}

/* Utility classes */
.bg-background { background-color: hsl(var(--background)); }
.text-foreground { color: hsl(var(--foreground)); }
.bg-card { background-color: hsl(var(--card)); }
.text-card-foreground { color: hsl(var(--card-foreground)); }
.bg-primary { background-color: hsl(var(--primary)); }
.text-primary { color: hsl(var(--primary)); }
.text-primary-foreground { color: hsl(var(--primary-foreground)); }
.bg-secondary { background-color: hsl(var(--secondary)); }
.text-secondary-foreground { color: hsl(var(--secondary-foreground)); }
.bg-muted { background-color: hsl(var(--muted)); }
.text-muted-foreground { color: hsl(var(--muted-foreground)); }
.bg-accent { background-color: hsl(var(--accent)); }
.text-accent-foreground { color: hsl(var(--accent-foreground)); }
.bg-destructive { background-color: hsl(var(--destructive)); }
.text-destructive-foreground { color: hsl(var(--destructive-foreground)); }
.border-border { border-color: hsl(var(--border)); }
.bg-primary\/90 { background-color: hsl(var(--primary) / 0.9); }
.bg-destructive\/90 { background-color: hsl(var(--destructive) / 0.9); }
.hover\:bg-primary\/90:hover { background-color: hsl(var(--primary) / 0.9); }
.hover\:bg-destructive\/90:hover { background-color: hsl(var(--destructive) / 0.9); }
.hover\:text-foreground:hover { color: hsl(var(--foreground)); }
.hover\:bg-accent:hover { background-color: hsl(var(--accent)); }
.border-primary\/50 { border-color: hsl(var(--primary) / 0.5); }
.focus\:ring-primary:focus { --tw-ring-color: hsl(var(--primary)); }
</style>
