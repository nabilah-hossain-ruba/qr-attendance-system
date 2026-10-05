<?php

namespace App\Services;

use App\Models\Course;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class BracuScheduleSync
{
    /**
     * Faculty initial (upper case) => lecturer user id.
     *
     * @var array<string, int>
     */
    private $lecturerIds = [];

    /**
     * Download the BRACU section feed and create/update courses with their
     * schedule, room and lecturer.
     *
     * @return array
     */
    public function sync()
    {
        $sections = $this->fetchSections();

        $this->lecturerIds = User::where('role', 'lecturer')
            ->whereNotNull('faculty_initial')
            ->pluck('id', 'faculty_initial')
            ->mapWithKeys(fn ($id, $initial) => [strtoupper($initial) => $id])
            ->all();

        $stats = ['created' => 0, 'updated' => 0, 'with_lecturer' => 0, 'skipped' => 0];

        DB::transaction(function () use ($sections, &$stats) {
            foreach ($sections as $section) {
                foreach ($this->toCourseRows($section) as $row) {
                    if (!$row) {
                        $stats['skipped']++;
                        continue;
                    }

                    $result = $this->saveCourse($row);
                    $stats[$result['created'] ? 'created' : 'updated']++;

                    if ($result['course']->lecturer_id) {
                        $stats['with_lecturer']++;
                    }
                }
            }
        });

        Log::info('BRACU schedule sync finished', $stats);

        return $stats;
    }

    /**
     * Assign all synced courses taught by the given initial to the lecturer.
     * Called when an admin sets or changes a lecturer's faculty initial.
     *
     * @param  \App\Models\User  $lecturer
     * @return int number of courses assigned
     */
    public static function assignCoursesToLecturer(User $lecturer)
    {
        // Unassign courses the feed gives to a different initial (e.g. the lecturer's initial was changed).
        // Courses still marked "TBA" keep their manual assignment.
        Course::where('lecturer_id', $lecturer->id)
            ->whereNotNull('faculty_initials')
            ->get()
            ->filter(function ($course) use ($lecturer) {
                $initials = self::splitInitials($course->faculty_initials);

                return $initials && !in_array(strtoupper((string) $lecturer->faculty_initial), $initials);
            })
            ->each(fn ($course) => $course->update(['lecturer_id' => null]));

        if (!$lecturer->faculty_initial) {
            return 0;
        }

        $initial = strtoupper($lecturer->faculty_initial);
        $assigned = 0;

        Course::whereNotNull('faculty_initials')
            ->where('faculty_initials', 'like', '%' . $initial . '%')
            ->get()
            ->filter(fn ($course) => in_array($initial, self::splitInitials($course->faculty_initials)))
            ->each(function ($course) use ($lecturer, &$assigned) {
                $course->update(['lecturer_id' => $lecturer->id]);
                $assigned++;
            });

        return $assigned;
    }

    /**
     * @return array
     */
    private function fetchSections()
    {
        $response = Http::timeout(config('bracu.schedule_feed_timeout'))
            ->acceptJson()
            ->get(config('bracu.schedule_feed_url'));

        if (!$response->successful()) {
            throw new RuntimeException('Could not download BRACU schedule feed (HTTP ' . $response->status() . ').');
        }

        $sections = $response->json();

        if (!is_array($sections) || !array_is_list($sections)) {
            throw new RuntimeException('BRACU schedule feed returned an unexpected format.');
        }

        return $sections;
    }

    /**
     * A feed entry is the theory/main section, optionally with an attached lab section.
     *
     * @param  array  $section
     * @return array<int, array|null>
     */
    private function toCourseRows(array $section)
    {
        if (empty($section['sectionId']) || empty($section['courseCode']) || empty($section['sectionName'])) {
            return [null];
        }

        [$semester, $year] = $this->semesterFor($section);
        $schedule = $section['sectionSchedule'] ?? [];

        $rows = [[
            'bracu_section_id' => $section['sectionId'],
            'course_code' => $section['courseCode'],
            'course_name' => $this->titleCase($section['courseName'] ?? $section['courseCode']),
            'course_type' => $section['courseType'] ?? null,
            'section' => $section['sectionName'],
            'semester' => $semester,
            'year' => $year,
            'faculty_initials' => $section['faculties'] ?? null,
            'room' => $section['roomName'] ?? $section['roomNumber'] ?? null,
            'class_schedules' => $schedule['classSchedules'] ?? [],
            'class_start_date' => $schedule['classStartDate'] ?? null,
            'class_end_date' => $schedule['classEndDate'] ?? null,
        ]];

        if (!empty($section['labSectionId']) && !empty($section['labCourseCode'])) {
            $rows[] = [
                'bracu_section_id' => $section['labSectionId'],
                'course_code' => $section['labCourseCode'],
                'course_name' => $this->titleCase($section['labName'] ?? $section['labCourseCode']),
                'course_type' => 'LAB',
                'section' => $section['sectionName'],
                'semester' => $semester,
                'year' => $year,
                'faculty_initials' => $section['labFaculties'] ?? null,
                'room' => $section['labRoomName'] ?? null,
                'class_schedules' => $section['labSchedules'] ?? [],
                'class_start_date' => $schedule['classStartDate'] ?? null,
                'class_end_date' => $schedule['classEndDate'] ?? null,
            ];
        }

        return $rows;
    }

    /**
     * @param  array  $row
     * @return array{course: \App\Models\Course, created: bool}
     */
    private function saveCourse(array $row)
    {
        $course = Course::where('bracu_section_id', $row['bracu_section_id'])->first()
            ?? Course::where('course_code', $row['course_code'])
                ->where('section', $row['section'])
                ->where('semester', $row['semester'])
                ->where('year', $row['year'])
                ->first();

        $created = !$course;
        $course = $course ?? new Course();

        $course->fill($row);
        $course->schedule = $this->scheduleText($row['class_schedules']);
        $course->synced_at = now();

        // The feed decides the lecturer once it names a real faculty.
        // While it says "TBA", keep whatever lecturer is already assigned.
        $initials = self::splitInitials($row['faculty_initials']);

        if ($initials) {
            $course->lecturer_id = $this->matchLecturer($initials);
        }

        $course->save();

        return ['course' => $course, 'created' => $created];
    }

    /**
     * @param  array<int, string>  $initials
     * @return int|null
     */
    private function matchLecturer(array $initials)
    {
        foreach ($initials as $initial) {
            if (isset($this->lecturerIds[$initial])) {
                return $this->lecturerIds[$initial];
            }
        }

        return null;
    }

    /**
     * "CMR,DFD" => ['CMR', 'DFD']; "TBA" / empty => [].
     *
     * @param  string|null  $faculties
     * @return array<int, string>
     */
    private static function splitInitials($faculties)
    {
        return collect(explode(',', (string) $faculties))
            ->map(fn ($initial) => strtoupper(trim($initial)))
            ->filter(fn ($initial) => $initial !== '' && $initial !== 'TBA')
            ->values()
            ->all();
    }

    /**
     * semesterSessionId 20263 => ['Fall', 2026].
     *
     * @param  array  $section
     * @return array{0: string, 1: int}
     */
    private function semesterFor(array $section)
    {
        $sessionId = (string) ($section['semesterSessionId'] ?? '');

        if (preg_match('/^(\d{4})(\d)$/', $sessionId, $matches)) {
            $term = config('bracu.semester_terms')[(int) $matches[2]] ?? 'Term ' . $matches[2];
            return [$term, (int) $matches[1]];
        }

        $start = $section['sectionSchedule']['classStartDate'] ?? null;
        $year = $start ? Carbon::parse($start)->year : now()->year;

        return ['Unknown', $year];
    }

    /**
     * [{day: MONDAY, startTime: 11:00:00, endTime: 12:20:00}, ...] => "Mon 11:00 AM-12:20 PM, Wed 11:00 AM-12:20 PM"
     *
     * @param  array  $classSchedules
     * @return string|null
     */
    private function scheduleText(array $classSchedules)
    {
        $parts = collect($classSchedules)
            ->filter(fn ($slot) => !empty($slot['day']) && !empty($slot['startTime']) && !empty($slot['endTime']))
            ->map(fn ($slot) => ucfirst(strtolower(substr($slot['day'], 0, 3))) . ' '
                . Carbon::parse($slot['startTime'])->format('g:i A') . '-'
                . Carbon::parse($slot['endTime'])->format('g:i A'));

        return $parts->isEmpty() ? null : $parts->implode(', ');
    }

    /**
     * "INTRODUCTION TO COMPUTER SCIENCE" => "Introduction To Computer Science"
     *
     * @param  string  $name
     * @return string
     */
    private function titleCase($name)
    {
        return ucwords(strtolower(trim($name)));
    }
}
