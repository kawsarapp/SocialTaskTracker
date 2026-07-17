<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add is_task_column to designations table
        Schema::table('designations', function (Blueprint $table) {
            $table->boolean('is_task_column')->default(false);
        });

        // 2. Set default designations as task columns
        $defaultColumns = ['Script Writer', 'Voice Artist', 'Video Editor', 'Graphic Designer', 'Uploader'];
        DB::table('designations')
            ->whereIn('name', $defaultColumns)
            ->update(['is_task_column' => true]);

        // 3. Create task_assignments table
        Schema::create('task_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->onDelete('cascade');
            $table->foreignId('designation_id')->constrained('designations')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });

        // 4. Migrate existing task roles data
        $tasks = DB::table('tasks')->get();
        foreach ($tasks as $task) {
            // Find tenant's designations
            $designations = DB::table('designations')
                ->where('tenant_id', $task->tenant_id)
                ->get()
                ->keyBy('name');

            // Map script writer
            if ($task->script_by && isset($designations['Script Writer'])) {
                DB::table('task_assignments')->insert([
                    'task_id' => $task->id,
                    'designation_id' => $designations['Script Writer']->id,
                    'user_id' => $task->script_by,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Map voice artist
            if ($task->voice_by && isset($designations['Voice Artist'])) {
                DB::table('task_assignments')->insert([
                    'task_id' => $task->id,
                    'designation_id' => $designations['Voice Artist']->id,
                    'user_id' => $task->voice_by,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Map video editor
            if ($task->video_edit_by && isset($designations['Video Editor'])) {
                DB::table('task_assignments')->insert([
                    'task_id' => $task->id,
                    'designation_id' => $designations['Video Editor']->id,
                    'user_id' => $task->video_edit_by,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // 5. Drop hardcoded columns from tasks table
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['script_by']);
            $table->dropForeign(['voice_by']);
            $table->dropForeign(['video_edit_by']);

            $table->dropColumn(['script_by', 'voice_by', 'video_edit_by']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add back hardcoded columns
        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('script_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('voice_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('video_edit_by')->nullable()->constrained('users')->onDelete('set null');
        });

        // Restore data from task_assignments
        $assignments = DB::table('task_assignments')
            ->join('designations', 'task_assignments.designation_id', '=', 'designations.id')
            ->select('task_assignments.*', 'designations.name as designation_name')
            ->get();

        foreach ($assignments as $a) {
            if ($a->designation_name === 'Script Writer') {
                DB::table('tasks')->where('id', $a->task_id)->update(['script_by' => $a->user_id]);
            } elseif ($a->designation_name === 'Voice Artist') {
                DB::table('tasks')->where('id', $a->task_id)->update(['voice_by' => $a->user_id]);
            } elseif ($a->designation_name === 'Video Editor') {
                DB::table('tasks')->where('id', $a->task_id)->update(['video_edit_by' => $a->user_id]);
            }
        }

        Schema::dropIfExists('task_assignments');

        Schema::table('designations', function (Blueprint $table) {
            $table->dropColumn('is_task_column');
        });
    }
};
