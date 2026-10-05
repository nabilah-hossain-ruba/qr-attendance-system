<!-- resources/views/attendances/index.blade.php -->

<!DOCTYPE html>
<html>
<head>
    <title>Attendances</title>
</head>
<body>
    <h1>All Attendances</h1>
    <a href="{{ url('/attendances/create') }}">Add Attendance</a>

    <table border="1" cellpadding="10" cellspacing="0">
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Date</th>
        </tr>
        @foreach($attendances as $attendance)
        <tr>
            <td>{{ $attendance->id }}</td>
            <td>{{ $attendance->name }}</td>
            <td>{{ $attendance->created_at->format('Y-m-d') }}</td>
        </tr>
        @endforeach
    </table>
</body>
</html>
