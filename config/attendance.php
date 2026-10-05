<?php

return [

    /*
    |--------------------------------------------------------------------------
    | QR Code Validity
    |--------------------------------------------------------------------------
    |
    | Number of minutes a generated session QR code can be scanned.
    |
    */

    'qr_valid_minutes' => (int) env('ATTENDANCE_QR_VALID_MINUTES', 10),

    /*
    |--------------------------------------------------------------------------
    | Classroom Location Check
    |--------------------------------------------------------------------------
    |
    | The lecturer's location is captured when the QR code is generated.
    | Students must scan within `radius_meters` of that point. Readings whose
    | reported accuracy is worse than `max_accuracy_meters` are rejected so a
    | vague location cannot pass the radius check.
    |
    */

    'radius_meters' => (int) env('ATTENDANCE_RADIUS_METERS', 100),

    'max_accuracy_meters' => (int) env('ATTENDANCE_MAX_ACCURACY_METERS', 150),

    /*
    |--------------------------------------------------------------------------
    | Device Binding
    |--------------------------------------------------------------------------
    |
    | Name of the long-lived (encrypted) cookie that identifies a student's
    | device. Each student is bound to one device and each device to one
    | student. An admin can reset the binding from the user edit page.
    |
    */

    'device_cookie' => 'attendance_device',

    'device_cookie_minutes' => 60 * 24 * 365 * 5,

];
