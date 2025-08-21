<script setup lang="ts">
import { useColorMode } from '@vueuse/core'
import { Moon, Sun, SunMoon } from 'lucide-vue-next'

import CommandItemHasIcon from './command-item-has-icon.vue'
import CommandGroup from "@/components/ui/command/CommandGroup.vue";
import CommandItem from "@/components/ui/command/CommandItem.vue";

defineEmits<{
  (e: 'click'): void
}>()

const mode = useColorMode()
</script>

<template>
  <CommandGroup heading="Theme">
    <CommandItem value="light" @click="mode = 'light', $emit('click')">
      <CommandItemHasIcon name="Light" :icon="Sun" />
    </CommandItem>
    <CommandItem value="dark" @click="mode = 'dark', $emit('click')">
      <CommandItemHasIcon name="Dark" :icon="Moon" />
    </CommandItem>
    <CommandItem value="system" @click="mode = 'auto', $emit('click')">
      <CommandItemHasIcon name="System" :icon="SunMoon" />
    </CommandItem>
  </CommandGroup>
</template>

<style scoped>
</style>
