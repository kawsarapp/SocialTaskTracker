<x-mail::message>
# Daily Work Performance Summary

Here is the daily performance dashboard for **{{ $tenantName }}** on **{{ $date }}**.

---

## 1. Content Production Breakdown
The table below shows the total count of content items created and published today, grouped by task type:

<x-mail::table>
| Task Type | Total Count |
| :--- | :---: |
@forelse($stats as $type => $count)
| **{{ $type }}** | **{{ $count }}** |
@empty
| *No tasks logged today* | 0 |
@endforelse
</x-mail::table>

---

## 1.5 Work Shift Production Breakdown
The table below shows the total count of content items created today, grouped by work shifts:

<x-mail::table>
| Shift Name | Total Tasks |
| :--- | :---: |
@forelse($shiftStats as $shift => $count)
| **{{ $shift }}** | **{{ $count }}** |
@empty
| *No shifts logged today* | 0 |
@endforelse
</x-mail::table>

---

## 2. Staff Performance Table
Below is the breakdown of scripts written, voiceovers recorded, and videos edited by your staff today:

<x-mail::table>
| Staff Name | Scripts | Voiceovers | Video Edits |
| :--- | :---: | :---: | :---: |
@forelse($staffStats as $name => $roles)
| **{{ $name }}** | {{ $roles['scripts'] }} | {{ $roles['voices'] }} | {{ $roles['edits'] }} |
@empty
| *No staff activity logged today* | 0 | 0 | 0 |
@endforelse
</x-mail::table>

---

## 2.5 🌴 Staff On Leave Today
The following staff members were marked on leave today:

@forelse($leaves as $leaveName)
* **{{ $leaveName }}**
@empty
* *No staff members were on leave today.*
@endforelse

---

## 3. ⚠️ Missing / Pending Publishing Links Alert
The following tasks are on-air but staff have not yet entered the required Facebook or YouTube links in the system. Please ensure these are filled out:

@forelse($pendingLinks as $task)
<x-mail::panel>
🔴 **Task ID: #{{ $task->id }} - {{ $task->title }}** ({{ $task->taskType?->name ?? 'General' }})  
- **On Air Date & Time**: {{ $task->on_air_date?->format('d M, Y') ?? 'N/A' }} {{ $task->on_air_time ? 'at ' . $task->on_air_time : '' }}  
- **Entry By**: {{ $task->entryUser?->name ?? 'System' }}  
@if($task->relationLoaded('assignments') && $task->assignments->isNotEmpty())
- **Assigned Staff**: {{ $task->assignments->map(fn($a) => ($a->designation?->name ?? 'Role') . ': ' . ($a->user?->name ?? 'Unassigned'))->implode(', ') }}  
@endif
- **Missing Links**: 
  @if(!$task->facebook_url && !$task->youtube_url)
    Facebook & YouTube Links
  @elseif(!$task->facebook_url)
    Facebook Link
  @else
    YouTube Link
  @endif
</x-mail::panel>
@empty
<x-mail::panel>
✅ **All good!** No tasks have missing links for today's entries.
</x-mail::panel>
@endforelse

Thanks,<br>
{{ config('app.name') }} Automated Reporter System
</x-mail::message>
