<script setup lang="ts">
import { useMagicKeys } from '@vueuse/core'
import { Search } from 'lucide-vue-next'
import { ref, watch, computed } from 'vue'

import CommandChangeTheme from './command-change-theme.vue'
import CommandToPage from './command-to-page.vue'
import CommandDialog from "@/components/ui/command/CommandDialog.vue";
import CommandInput from "@/components/ui/command/CommandInput.vue";
import CommandList from "@/components/ui/command/CommandList.vue";
import CommandEmpty from "@/components/ui/command/CommandEmpty.vue";
import CommandSeparator from "@/components/ui/command/CommandSeparator.vue";
import Button from "@/components/ui/button/Button.vue"
const open = ref(false)

const { Meta_K, Ctrl_K } = useMagicKeys({
  passive: false,
  onEventFired(e) {
    if (e.key === 'k' && (e.metaKey || e.ctrlKey))
      e.preventDefault()
  },
})

watch([Meta_K, Ctrl_K], (v) => {
  if (v[0] || v[1])
    handleOpenChange()
})

function handleOpenChange() {
  open.value = !open.value
}

const firstKey = computed(() => navigator?.userAgent.includes('Mac OS') ? '⌘' : 'Ctrl')
</script>

<template>
  <div>
    <div
      class="text-sm items-center justify-between text-muted-foreground border border-border bg-muted/5 px-4 py-2 rounded md:min-w-[220px] cursor-pointer hidden md:flex"
      @click="handleOpenChange"
    >
      <div class="flex items-center gap-2">
        <Search class="size-4" />
        <span class="text-xs font-semibold text-muted-foreground">Search Menu</span>
      </div>
      <kbd
        class="pointer-events-none inline-flex h-5 select-none items-center gap-1 rounded bg-muted px-1.5 font-mono text-[10px] font-medium text-muted-foreground opacity-100"
      >
        <span class="text-xs">{{ firstKey }}</span>K
      </kbd>
    </div>
    <Button variant="outline" size="icon" class="md:hidden" @click="handleOpenChange">
      <Search />
    </Button>

    <CommandDialog v-model:open="open">
      <CommandInput placeholder="Type a command or search..." />
      <CommandList>
        <CommandEmpty>
          No results found.
        </CommandEmpty>

        <CommandToPage @click="handleOpenChange" />
        <CommandSeparator />
        <CommandChangeTheme @click="handleOpenChange" />
      </CommandList>
    </CommandDialog>
  </div>
</template>
