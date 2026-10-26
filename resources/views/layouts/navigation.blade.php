<flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
    <div class="flex items-center justify-between mb-6 mt-2 px-2">
        <a href="{{ route('dashboard') }}" class="workspace-brand">
            <span class="brand-symbol"><flux:icon.ticket class="size-6" /></span>
            <span>Event Tickets<span class="block text-xs font-normal tracking-normal ui-muted mt-0.5">Every entry, accounted for.</span></span>
        </a>
        <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />
    </div>
    <p class="eyebrow px-3 mb-1">Workspace</p>
    <flux:navlist variant="outline">
        @if($isAdmin)
            <flux:navlist.item icon="squares-2x2" :href="route('admin.dashboard')" :current="request()->routeIs('admin.dashboard')">Overview</flux:navlist.item>
            <flux:navlist.item icon="calendar-days" :href="route('events.index')" :current="request()->routeIs('events.*')">Events &amp; tickets</flux:navlist.item>
            <flux:navlist.item icon="users" :href="route('users.index')" :current="request()->routeIs('users.*')">Team</flux:navlist.item>
            <flux:navlist.item icon="chat-bubble-left-right" :href="route('sms.management.index')" :current="request()->routeIs('sms.management.*')">SMS invitations</flux:navlist.item>
            <flux:navlist.item icon="identification" :href="route('card-templates.index')" :current="request()->routeIs('card-templates.*')">Card templates</flux:navlist.item>
            <flux:navlist.item icon="chart-bar" :href="route('reports.index')" :current="request()->routeIs('reports.*')">Reports</flux:navlist.item>
        @else
            <flux:navlist.item icon="squares-2x2" :href="route('scanner.dashboard')" :current="request()->routeIs('scanner.dashboard')">Overview</flux:navlist.item>
        @endif
    </flux:navlist>
    <p class="eyebrow px-3 mt-6 mb-1">At the door</p>
    <flux:navlist variant="outline">
        <flux:navlist.item icon="calendar" :href="route('scanner.events.index')" :current="request()->routeIs('scanner.events.*')">{{ $isAdmin ? 'Select event' : 'My events' }}</flux:navlist.item>
        <flux:navlist.item icon="qr-code" :href="route('scanner.scan.index')" :current="request()->routeIs('scanner.scan.*', 'scanner.validate.*')">Scan tickets</flux:navlist.item>
        <flux:navlist.item icon="clock" :href="route('scanner.history')" :current="request()->routeIs('scanner.history', 'scanner.history.*')">Admission history</flux:navlist.item>
        <flux:navlist.item icon="user-group" :href="route('scanner.attendees.index')" :current="request()->routeIs('scanner.attendees.*')">Attendees</flux:navlist.item>
    </flux:navlist>
    <flux:spacer />
    <div class="mt-8 border-t border-zinc-200 dark:border-zinc-700 pt-4">
        <flux:navlist variant="outline">
            @if($isAdmin)
                <flux:navlist.item icon="cog-6-tooth" :href="route('settings.index')" :current="request()->routeIs('settings.*')">Settings</flux:navlist.item>
            @endif
            <flux:navlist.item icon="user-circle" :href="route('profile.edit')" :current="request()->routeIs('profile.*')">My profile</flux:navlist.item>
        </flux:navlist>
        <form action="{{ route('logout') }}" method="POST" class="mt-2">
            @csrf
            <flux:button type="submit" variant="subtle" icon="arrow-right-start-on-rectangle" class="w-full justify-start!">Sign out</flux:button>
        </form>
    </div>
</flux:sidebar>
