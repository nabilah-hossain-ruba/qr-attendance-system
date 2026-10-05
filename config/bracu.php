<?php

return [

    /*
    |--------------------------------------------------------------------------
    | BRACU Section Schedule Feed
    |--------------------------------------------------------------------------
    |
    | JSON list of course sections (course, section, faculty initials, room,
    | class days/times) as published by BRACU Connect. The default is the
    | community-maintained public mirror of Connect's advising data; it only
    | contains the semester currently open for advising/registration.
    |
    */

    'schedule_feed_url' => env('BRACU_SCHEDULE_FEED_URL', 'https://usis-cdn.eniamza.com/connect.json'),

    'schedule_feed_timeout' => (int) env('BRACU_SCHEDULE_FEED_TIMEOUT', 120),

    /*
    |--------------------------------------------------------------------------
    | Timezone
    |--------------------------------------------------------------------------
    |
    | Class times in the feed are Dhaka local time. Used to work out which
    | classes are on "today" regardless of the app's storage timezone.
    |
    */

    'timezone' => env('BRACU_TIMEZONE', 'Asia/Dhaka'),

    /*
    |--------------------------------------------------------------------------
    | Semester Names
    |--------------------------------------------------------------------------
    |
    | Last digit of Connect's semesterSessionId (e.g. 20263 => 2026, term 3).
    |
    */

    'semester_terms' => [
        1 => 'Spring',
        2 => 'Summer',
        3 => 'Fall',
    ],

];
