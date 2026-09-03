@php
    $user = auth()->user();
    $name = $user?->name ?? 'G S Kibria';
    $words = explode(' ', trim($name));
    $initials = '';
    foreach ($words as $w) {
        if (!empty($w)) {
            $initials .= strtoupper($w[0]);
        }
    }
    $initials = substr($initials, 0, 3) ?: 'GSK';
    $roleName = $user?->roles?->first()?->name ? ucwords(str_replace(['_', '-'], ' ', $user->roles->first()->name)) : 'Authorizer';
    $org = $user?->getRawOriginal('organization') ?: 'Janata Bank PLC.';
@endphp
<div class="jb-sidebar-profile-card">
    <div class="jb-profile-row">
        <div class="jb-profile-avatar">
            <span>{{ $initials }}</span>
        </div>
        <div class="jb-profile-info">
            <span class="jb-profile-name" title="{{ $name }}">{{ $name }}</span>
            <span class="jb-profile-role" title="{{ $roleName }} · {{ $org }}">{{ $roleName }} · {{ $org }}</span>
        </div>
    </div>
    <div class="jb-profile-cbs-status">
        <span class="jb-cbs-dot"></span>
        <span class="jb-cbs-text">Oracle CBS: Connected</span>
    </div>
</div>