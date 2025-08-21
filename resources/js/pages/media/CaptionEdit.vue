<script setup lang="ts">
// Add these functions inside your <script setup> block
function handleCaptionUpload(event) {
    const file = event.target.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = (e) => {
        const content = e.target.result;
        form.captions = parseVtt(content);
    };
    reader.readAsText(file);
}

function parseVtt(vttContent) {
    const lines = vttContent.split('\n');
    const captions = [];
    let i = 0;
    while (i < lines.length) {
        if (lines[i].includes('-->')) {
            const [startStr, endStr] = lines[i].split(' --> ');
            const text = lines[i + 1];

            if (startStr && endStr && text) {
                captions.push({
                    start_time: timeToSeconds(startStr),
                    end_time: timeToSeconds(endStr),
                    text: text,
                });
            }
            i += 2;
        } else {
            i++;
        }
    }
    return captions;
}

function timeToSeconds(timeStr) {
    const parts = timeStr.split(':');
    const secondsParts = parts[2].split('.');
    const hours = parseInt(parts[0], 10);
    const minutes = parseInt(parts[1], 10);
    const seconds = parseInt(secondsParts[0], 10);
    const milliseconds = parseInt(secondsParts[1], 10);
    return hours * 3600 + minutes * 60 + seconds + milliseconds / 1000;
}
</script>

<template>
    <div class="space-y-4">
        <div>
            <label for="captionFile">Upload VTT File</label>
            <Input id="captionFile" type="file" @change="handleCaptionUpload" accept=".vtt" />
        </div>
        <div v-for="(caption, index) in form.captions" :key="caption.id || index" class="p-3 space-y-2 border rounded-lg">
            <div class="grid grid-cols-2 gap-2">
                <Input type="number" step="0.1" placeholder="Start (s)" v-model="caption.start_time" />
                <Input type="number" step="0.1" placeholder="End (s)" v-model="caption.end_time" />
            </div>
            <Textarea placeholder="Caption text..." v-model="caption.text" rows="2" />
            <Button @click="form.captions.splice(index, 1)" variant="destructive" size="sm">Delete</Button>
        </div>
        <Button @click="form.captions.push({ start_time: 0, end_time: 0, text: '' })" variant="outline">Add Caption Line</Button>
    </div>
</template>

<style scoped>

</style>
