<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\FacebookPage;
use App\Models\Task;
use App\Models\TaskType;
use App\Models\Team;
use App\Models\Tenant;
use App\Models\User;
use App\Models\YoutubeChannel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create Global Super Admin
        User::create([
            'tenant_id' => null,
            'name' => 'Super Admin',
            'email' => 'superadmin@tracker.com',
            'password' => Hash::make('password'),
            'role' => 'super_admin',
        ]);

        // 2. Create Tenant: DBC News
        $dbc = Tenant::create([
            'name' => 'DBC News',
            'slug' => 'dbc-news',
        ]);

        // Seed Default Designations for DBC
        $defaultDesignations = [
            'Reporter' => '#e0f2fe',
            'Script Writer' => '#fef3c7',
            'Uploader' => '#e0e7ff',
            'Voice Artist' => '#f3e8ff',
            'Correspondent' => '#dcfce7',
            'Content Reviewer' => '#ffedd5',
            'Video Editor' => '#ccfbf1',
            'Graphic Designer' => '#fce7f3',
            'Sound Engineer' => '#f1f5f9',
            'Researcher' => '#fae8ff',
            'Content Strategist' => '#e0f2fe',
            'SEO Specialist' => '#fef2f2'
        ];

        $dbcDesignations = [];
        foreach ($defaultDesignations as $name => $color) {
            $dbcDesignations[$name] = \App\Models\Designation::create([
                'tenant_id' => $dbc->id,
                'name' => $name,
                'color' => $color,
            ]);
        }

        // DBC News Staff/Users
        $dbcAdmin = User::create([
            'tenant_id' => $dbc->id,
            'name' => 'DBC Admin',
            'email' => 'dbcadmin@tracker.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        $dbcLeader = User::create([
            'tenant_id' => $dbc->id,
            'name' => 'DBC Leader',
            'email' => 'dbcleader@tracker.com',
            'password' => Hash::make('password'),
            'role' => 'team_leader',
        ]);

        $mHussain = User::create([
            'tenant_id' => $dbc->id,
            'name' => 'M. Hussain',
            'email' => 'm.hussain@tracker.com',
            'password' => Hash::make('password'),
            'role' => 'staff',
            'designation_id' => $dbcDesignations['Script Writer']->id,
        ]);

        $mMehadi = User::create([
            'tenant_id' => $dbc->id,
            'name' => 'Md. Mehadi Hasan',
            'email' => 'mehadi@tracker.com',
            'password' => Hash::make('password'),
            'role' => 'staff',
            'designation_id' => $dbcDesignations['Voice Artist']->id,
        ]);

        $mizan = User::create([
            'tenant_id' => $dbc->id,
            'name' => 'Mizan',
            'email' => 'mizan@tracker.com',
            'password' => Hash::make('password'),
            'role' => 'staff',
            'designation_id' => $dbcDesignations['Video Editor']->id,
        ]);

        $roney = User::create([
            'tenant_id' => $dbc->id,
            'name' => 'Roney',
            'email' => 'roney@tracker.com',
            'password' => Hash::make('password'),
            'role' => 'staff',
            'designation_id' => $dbcDesignations['Graphic Designer']->id,
        ]);

        $riad = User::create([
            'tenant_id' => $dbc->id,
            'name' => 'Riad Hossain',
            'email' => 'riad@tracker.com',
            'password' => Hash::make('password'),
            'role' => 'staff',
            'designation_id' => $dbcDesignations['Reporter']->id,
        ]);

        // DBC News Teams
        $webTeam = Team::create(['tenant_id' => $dbc->id, 'name' => 'Website Team']);
        $socialTeam = Team::create(['tenant_id' => $dbc->id, 'name' => 'Youtube & facebook Team']);

        // DBC News Task Types (from checkbox list)
        $types = ['PR', 'Reels', 'NWS', 'PKG', 'OOV', 'LIVE', 'PGM', 'Clip', 'UNCUT', 'SPL', 'SOT'];
        $typeModels = [];
        $colors = [
            'PR' => '#e2e8f0', 'Reels' => '#fbcfe8', 'NWS' => '#fef3c7', 'PKG' => '#dbeafe', 
            'OOV' => '#e0f2fe', 'LIVE' => '#fee2e2', 'PGM' => '#f3e8ff', 'Clip' => '#ccfbf1', 
            'UNCUT' => '#f5f5f4', 'SPL' => '#ffedd5', 'SOT' => '#ecfccb'
        ];
        foreach ($types as $t) {
            $typeModels[$t] = TaskType::create([
                'tenant_id' => $dbc->id,
                'name' => $t,
                'color' => $colors[$t] ?? '#3490dc'
            ]);
        }

        // DBC News Facebook Pages
        $fbPages = ['dbcnews.tv', 'ডিবিসি নিউজ', 'DBC News Daily', 'DBC News Live', 'DBC News Plus', 'DBC News Int.', 'Rajkahon', 'DBC News Sports', 'DBC News Entertainment'];
        $fbPageModels = [];
        foreach ($fbPages as $p) {
            $fbPageModels[$p] = FacebookPage::create(['tenant_id' => $dbc->id, 'name' => $p]);
        }

        // DBC News YouTube Channels
        $ytChannels = ['DBC NEWS SPORTS', 'DBC NEWS', 'DBC News Daily_YT', 'DBC NEWS Live'];
        $ytChannelModels = [];
        foreach ($ytChannels as $c) {
            $ytChannelModels[$c] = YoutubeChannel::create(['tenant_id' => $dbc->id, 'name' => $c]);
        }

        // DBC News Areas / News Sources
        $areas = [
            'Dhaka Office', 'Mizan - Reporter', 'Tali Khata (Full Program)', 
            'Digital Media Special - International', 'Full News', 
            'International Desk - Bangla', 'National Desk - Bangla'
        ];
        $areaModels = [];
        foreach ($areas as $a) {
            $areaModels[$a] = Area::create(['tenant_id' => $dbc->id, 'name' => $a]);
        }

        // DBC News Tasks
        // Task 1: Complete Links
        $t1 = Task::create([
            'tenant_id' => $dbc->id,
            'title' => 'গ্রেপ্তার হওয়া ১০ সেনা কর্মকর্তার বিরুদ্ধে অভিযোগ গঠনের শুনানি | Bangladesh Army | DBC NEWS',
            'task_type_id' => $typeModels['LIVE']->id,
            'facebook_url' => 'https://facebook.com/dbcnews.tv/videos/12345',
            'facebook_page_id' => $fbPageModels['dbcnews.tv']->id,
            'youtube_url' => 'https://youtube.com/watch?v=12345',
            'youtube_channel_id' => $ytChannelModels['DBC NEWS']->id,
            'on_air_time' => '03:00 PM',
            'on_air_date' => date('Y-m-d'),
            'area_id' => $areaModels['Mizan - Reporter']->id,
            'team_id' => $socialTeam->id,
            'tags' => 'Nagad',
            'remarks' => 'Urgent live broadcast completed.',
            'entry_by' => $roney->id,
        ]);

        \App\Models\TaskAssignment::create([
            'task_id' => $t1->id,
            'designation_id' => $dbcDesignations['Script Writer']->id,
            'user_id' => $riad->id,
        ]);
        \App\Models\TaskAssignment::create([
            'task_id' => $t1->id,
            'designation_id' => $dbcDesignations['Voice Artist']->id,
            'user_id' => $riad->id,
        ]);
        \App\Models\TaskAssignment::create([
            'task_id' => $t1->id,
            'designation_id' => $dbcDesignations['Video Editor']->id,
            'user_id' => $roney->id,
        ]);

        // Task 2: Multi types, complete links
        Task::create([
            'tenant_id' => $dbc->id,
            'title' => 'জাতীয় নির্বাচন আর গণভোট হবে শান্তিপূর্ণ: প্রধান উপদেষ্টা | Dr Muhammad Yunus | Chief Adviser | DBC NEWS',
            'task_type_id' => $typeModels['OOV']->id,
            'facebook_url' => 'https://facebook.com/dbcnews.tv/videos/67890',
            'facebook_page_id' => $fbPageModels['DBC News Daily']->id,
            'youtube_url' => 'https://youtube.com/watch?v=67890',
            'youtube_channel_id' => $ytChannelModels['DBC News Daily_YT']->id,
            'on_air_time' => '03:00 PM',
            'on_air_date' => date('Y-m-d'),
            'area_id' => $areaModels['Dhaka Office']->id,
            'team_id' => $socialTeam->id,
            'tags' => 'Nagad',
            'remarks' => 'On air statement.',
            'entry_by' => $mHussain->id,
        ]);

        // Task 3: Missing FB Link (test case for alert)
        Task::create([
            'tenant_id' => $dbc->id,
            'title' => 'সয়াবিন তেলের দাম বৃদ্ধির কোনো যৌক্তিকতা নেই: বাণিজ্য উপদেষ্টা | Soybean Oil | Price Hike | DBC NEWS',
            'task_type_id' => $typeModels['OOV']->id,
            'facebook_url' => null,
            'facebook_page_id' => null,
            'youtube_url' => 'https://youtube.com/watch?v=99999',
            'youtube_channel_id' => $ytChannelModels['DBC NEWS']->id,
            'on_air_time' => '02:00 PM',
            'on_air_date' => date('Y-m-d'),
            'area_id' => $areaModels['Dhaka Office']->id,
            'team_id' => $socialTeam->id,
            'tags' => 'Nagad',
            'remarks' => 'Facebook video pending edit.',
            'entry_by' => $mMehadi->id,
        ]);


        // 3. Create Tenant: Independent TV (to test Tenant Isolation)
        $itv = Tenant::create([
            'name' => 'Independent TV',
            'slug' => 'independent-tv',
        ]);

        $itvDesignations = [];
        foreach ($defaultDesignations as $name => $color) {
            $itvDesignations[$name] = \App\Models\Designation::create([
                'tenant_id' => $itv->id,
                'name' => $name,
                'color' => $color,
            ]);
        }

        $itvAdmin = User::create([
            'tenant_id' => $itv->id,
            'name' => 'ITV Admin',
            'email' => 'itvadmin@tracker.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        $itvStaff = User::create([
            'tenant_id' => $itv->id,
            'name' => 'ITV Staff',
            'email' => 'itvstaff@tracker.com',
            'password' => Hash::make('password'),
            'role' => 'staff',
            'designation_id' => $itvDesignations['Reporter']->id,
        ]);

        $itvTeam = Team::create(['tenant_id' => $itv->id, 'name' => 'ITV News Room']);

        $itvTypePkg = TaskType::create(['tenant_id' => $itv->id, 'name' => 'PKG', 'color' => '#dbeafe']);
        $itvTypeLive = TaskType::create(['tenant_id' => $itv->id, 'name' => 'LIVE', 'color' => '#fee2e2']);

        $itvFb = FacebookPage::create(['tenant_id' => $itv->id, 'name' => 'Independent Television']);
        $itvYt = YoutubeChannel::create(['tenant_id' => $itv->id, 'name' => 'Independent Television']);
        $itvArea = Area::create(['tenant_id' => $itv->id, 'name' => 'Chittagong Bureau']);

        // ITV Task (Must not show up in DBC News search)
        Task::create([
            'tenant_id' => $itv->id,
            'title' => 'চট্টগ্রাম বন্দরে নতুন জেটি উদ্বোধন | Independent TV',
            'task_type_id' => $itvTypePkg->id,
            'facebook_url' => 'https://facebook.com/independent/123',
            'facebook_page_id' => $itvFb->id,
            'youtube_url' => 'https://youtube.com/independent/123',
            'youtube_channel_id' => $itvYt->id,
            'on_air_time' => '10:00 AM',
            'on_air_date' => date('Y-m-d'),
            'area_id' => $itvArea->id,
            'team_id' => $itvTeam->id,
            'entry_by' => $itvStaff->id,
        ]);
    }
}
