
<script setup lang="ts">
import AppSidebar from "@/components/AppSidebar.vue"
import {
  Breadcrumb,
  BreadcrumbItem,
  BreadcrumbLink,
  BreadcrumbList,
  BreadcrumbPage,
  BreadcrumbSeparator,
} from "@/components/ui/breadcrumb"
import { Separator } from "@/components/ui/separator"
import {
  SidebarInset,
  SidebarProvider,
  SidebarTrigger,
} from "@/components/ui/sidebar"

import CommandMenuPanel from '@/components/command-menu-panel/index.vue'
import ThemePopover from "@/components/custom-theme/theme-popover.vue";
import ToggleTheme from "@/components/toggle-theme.vue";
</script>

<template>
  <SidebarProvider>
    <AppSidebar />
    <SidebarInset>
      <header class="flex h-16 shrink-0 items-center gap-2 transition-[width,height] ease-linear group-has-[[data-collapsible=icon]]/sidebar-wrapper:h-12">
<!--        <div class="flex items-center gap-1 px-4">-->
          <SidebarTrigger class="ml-2.5" />
<!--          <Separator orientation="vertical" />-->
          <CommandMenuPanel/>
<!--          <Breadcrumb>-->
<!--            <BreadcrumbList>-->
<!--              <BreadcrumbItem class="hidden md:block">-->
<!--                <BreadcrumbLink href="#">-->
<!--                  Building Your Application-->
<!--                </BreadcrumbLink>-->
<!--              </BreadcrumbItem>-->
<!--              <BreadcrumbSeparator class="hidden md:block" />-->
<!--              <BreadcrumbItem>-->
<!--                <BreadcrumbPage>Data Fetching</BreadcrumbPage>-->
<!--              </BreadcrumbItem>-->
<!--            </BreadcrumbList>-->
<!--          </Breadcrumb>-->
          <div class="ml-auto flex items-center space-x-4 mr-4">
            <ToggleTheme />
            <ThemePopover class=""/>
          </div>
<!--        </div>-->
      </header>
      <div class="flex flex-1 w-full flex-col gap-4 p-4 pt-0">
<!--        <div class="grid auto-rows-min gap-4 md:grid-cols-3">-->
<!--          <div class="aspect-video rounded-xl bg-muted/50" />-->
<!--          <div class="aspect-video rounded-xl bg-muted/50" />-->
<!--          <div class="aspect-video rounded-xl bg-muted/50" />-->
<!--        </div>-->
        <div class="h-auto  flex-1 rounded-xl bg-muted/50 md:min-h-min" >
          <slot/>
        </div>
      </div>
    </SidebarInset>
  </SidebarProvider>
</template>
