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

## 3. ⚠️ Missing / Pending Publishing Links Alert
The following tasks are on-air but staff have not yet entered the required Facebook or YouTube links in the system. Please ensure these are filled out:

@forelse($pendingLinks as $task)
<x-mail::panel>
🔴 **Task ID: #{{ $task->id }} - {{ $task->title }}**  
- **On Air Time**: {{ $task->on_air_time }}  
- **Entry By**: {{ $task->entryUser->name }}  
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
